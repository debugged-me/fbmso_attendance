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
		if ((string) $this->input->get('show_cron') === '1' && in_array($level, ['Admin', 'IT', 'Super Admin'], true)) {
			$showCron = "\nCron (every 2 minutes):\n\n"
				. "  */2 * * * * curl -s \"" . site_url('EmailQueue/process') . '?key=' . fbmso_mailqueue_token($this) . "\" > /dev/null 2>&1\n";
		}

		$this->output->set_content_type('text/plain')->set_output(
			"FBMSO Email Queue\n"
			. "=================\n"
			. "Queue: pending=" . $counts['pending'] . " sent=" . $counts['sent'] . " failed=" . $counts['failed'] . "\n"
			. $this->_mailqueue_suspended_note()
			. $showCron
		);
	}

	private function _mailqueue_suspended_note()
	{
		if (!fbmso_mailqueue_suspended()) {
			return "Sender: active\n";
		}
		$until = (int) @file_get_contents(fbmso_mailqueue_suspend_file());
		return "Sender: COOLDOWN until " . date('Y-m-d H:i:s', $until) . " (provider rate limit detected)\n";
	}
}
