<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * EmailQueue — throttled background sender for the email_queue table.
 *
 * Web requests only ever INSERT into email_queue (see fbmso_email_helper);
 * this controller is the single process that talks to SMTP, run by cron:
 *
 *   URL cron :  *\/2 * * * *  curl -s "https://<domain>/EmailQueue/process?key=<token>"
 *   CLI cron :  *\/2 * * * *  php /path/to/fbmso_attendance/index.php EmailQueue process
 *
 * Visit /EmailQueue/key while logged in (any non-student staff account) for
 * queue counts; add ?show_cron=1 as Admin / IT / Super Admin to reveal the
 * exact cron URL + token.
 */
class EmailQueue extends CI_Controller
{
	public function process()
	{
		if (!$this->input->is_cli_request()) {
			$key = (string) $this->input->get('key', true);
			if ($key === '' || !hash_equals(fbmso_mailqueue_token($this), $key)) {
				show_error('Forbidden', 403);
				return;
			}
		}

		ignore_user_abort(true);
		@set_time_limit(300);

		// 5 emails / run, 2s apart; cron every 2 min => up to ~150/hour.
		$summary = fbmso_mailqueue_process($this, 5, 2);

		if ($this->input->is_cli_request()) {
			echo json_encode($summary), PHP_EOL;
			return;
		}

		$this->output
			->set_content_type('application/json')
			->set_output(json_encode($summary));
	}

	public function key()
	{
		$username = trim((string) $this->session->userdata('username'));
		$level    = trim((string) $this->session->userdata('level'));
		if ($username === '' || $level === '' || strcasecmp($level, 'Student') === 0) {
			show_error('Forbidden', 403);
			return;
		}

		fbmso_mailqueue_ensure_table($this);

		$counts = ['pending' => 0, 'sent' => 0, 'failed' => 0];
		$rows = $this->db->select('status, COUNT(*) AS c')->from('fbmso_email_queue')->group_by('status')->get()->result();
		foreach ($rows as $r) {
			$counts[(string) $r->status] = (int) $r->c;
		}

		// Token/cron command intentionally hidden unless explicitly requested
		// by an account that would be setting the cron job up.
		$showCron = '';
		$isAdmin  = in_array($level, ['Admin', 'IT', 'Super Admin'], true);
		if ((string) $this->input->get('show_cron') === '1' && $isAdmin) {
			$showCron = "\nCron (every 2 minutes):\n\n"
				. "  */2 * * * * curl -s \"" . site_url('EmailQueue/process') . '?key=' . fbmso_mailqueue_token($this) . "\" > /dev/null 2>&1\n";
		}

		// Retry link: only show to admins, and only when there is something
		// actually stuck to retry. Clicking it flips failed rows back to
		// pending so the next cron tick picks them up — no DB shell needed.
		$retryLink = '';
		if ($isAdmin && $counts['failed'] > 0) {
			$retryLink = "\nRetry: " . site_url('EmailQueue/retry')
				. "   (re-queues " . $counts['failed'] . " failed message" . ($counts['failed'] === 1 ? '' : 's') . ")\n";
		}

		$this->output->set_content_type('text/plain')->set_output(
			"FBMSO Email Queue\n"
			. "=================\n"
			. "Queue: pending=" . $counts['pending'] . " sent=" . $counts['sent'] . " failed=" . $counts['failed'] . "\n"
			. $this->_mailqueue_suspended_note()
			. $this->_mailqueue_recent_errors()
			. $retryLink
			. $showCron
		);
	}

	/**
	 * Clears the cooldown flag so the next cron run tries again immediately.
	 * Useful after fixing the SMTP settings that caused it — otherwise you wait
	 * out the full window for no reason.
	 */
	public function resume()
	{
		$level = trim((string) $this->session->userdata('level'));
		if (!in_array($level, ['Admin', 'IT', 'Super Admin'], true)) {
			show_error('Forbidden', 403);
			return;
		}

		$was = fbmso_mailqueue_suspended();
		@unlink(fbmso_mailqueue_suspend_file());

		$this->output->set_content_type('text/plain')->set_output(
			($was ? "Cooldown cleared. The next cron run will retry.\n" : "Sender was not in cooldown.\n")
			. "\n" . site_url('EmailQueue/key') . "\n"
		);
	}

	/**
	 * Re-queue failed messages back to pending so the next cron run retries
	 * them. Without this the only way to revive a stuck row is raw SQL, since
	 * fbmso_mailqueue_process() skips rows whose attempts >= maxAttempts.
	 *
	 * Optional ?id=N retries a single row; otherwise every failed row is
	 * reset. Admin / IT / Super Admin only.
	 */
	public function retry()
	{
		$level = trim((string) $this->session->userdata('level'));
		if (!in_array($level, ['Admin', 'IT', 'Super Admin'], true)) {
			show_error('Forbidden', 403);
			return;
		}

		fbmso_mailqueue_ensure_table($this);

		// Optional single-row retry — handy when one address is bad and you
		// want to fix just that row without reviving every failure.
		$id = (int) $this->input->get('id', true);

		$this->db->where('status', 'failed');
		if ($id > 0) {
			$this->db->where('id', $id);
		}
		$this->db->update('fbmso_email_queue', [
			'status'     => 'pending',
			'attempts'   => 0,
			'last_error' => '',
		]);

		$affected = $this->db->affected_rows();

		$this->output->set_content_type('text/plain')->set_output(
			"Re-queued " . $affected . " message" . ($affected === 1 ? '' : 's')
			. " — the next cron tick (within ~2 min) will retry.\n"
			. "\n" . site_url('EmailQueue/key') . "\n"
		);
	}

	private function _mailqueue_suspended_note()
	{
		if (!fbmso_mailqueue_suspended()) {
			return "Sender: active\n";
		}
		$until = (int) @file_get_contents(fbmso_mailqueue_suspend_file());
		return "Sender: COOLDOWN until " . date('Y-m-d H:i:s', $until)
			. " (a send failed with a transient/rate-limit error - see below)\n";
	}

	/**
	 * The last error recorded against each stuck message. Without this the only
	 * way to find out why the queue stalled is a direct database query.
	 */
	private function _mailqueue_recent_errors()
	{
		$rows = $this->db->select('id, to_email, status, attempts, last_error')
			->from('fbmso_email_queue')
			->where_in('status', ['pending', 'failed'])
			->where('last_error !=', '')
			->order_by('id', 'DESC')
			->limit(5)
			->get()->result();

		if (!$rows) {
			return '';
		}

		$out = "\nRecent errors\n-------------\n";
		foreach ($rows as $r) {
			$out .= '#' . (int) $r->id . ' [' . $r->status . ', attempt ' . (int) $r->attempts . '] '
				. $r->to_email . "\n    " . $r->last_error . "\n";
		}

		return $out;
	}
}
