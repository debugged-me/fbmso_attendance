<?php
defined('BASEPATH') or exit('No direct script access allowed');

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

		$isAdmin = in_array($level, ['Admin', 'IT', 'Super Admin'], true);
		$suspended = fbmso_mailqueue_suspended();
		$flash = (string) $this->input->get('msg', true);
		$showCron = '';
		if ((string) $this->input->get('show_cron') === '1' && $isAdmin) {
			$showCron = "*/2 * * * * curl -s \""
				. site_url('EmailQueue/process') . '?key=' . fbmso_mailqueue_token($this)
				. "\" > /dev/null 2>&1";
		}

		$this->output->set_content_type('text/html')->set_output(
			$this->_render_page($counts, $level, $isAdmin, $suspended, $flash, $showCron)
		);
	}
	public function resume()
	{
		$level = trim((string) $this->session->userdata('level'));
		if (!in_array($level, ['Admin', 'IT', 'Super Admin'], true)) {
			show_error('Forbidden', 403);
			return;
		}

		$was = fbmso_mailqueue_suspended();
		@unlink(fbmso_mailqueue_suspend_file());

		$msg = $was ? 'cooldown_cleared' : 'not_suspended';
		redirect('EmailQueue/key?msg=' . $msg);
	}

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

		$msg = $affected > 0
			? 'retried_' . $affected
			: 'retried_none';

		redirect('EmailQueue/key?msg=' . $msg);
	}


	private function _render_page(array $counts, $level, $isAdmin, $suspended, $flash, $showCron)
	{
		$esc = function ($s) {
			return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
		};

		// ---- Flash banner -------------------------------------------------
		$flashHtml = '';
		if ($flash !== '') {
			$flashMap = [
				'cooldown_cleared' => ['ok',   'Cooldown cleared. The next cron run will retry.'],
				'not_suspended'    => ['info', 'Sender was not in cooldown.'],
			];
			if (preg_match('/^retried_(\d+)$/', $flash, $m)) {
				$n = (int) $m[1];
				$flashMap[$flash] = ['ok', 'Re-queued ' . $n . ' message' . ($n === 1 ? '' : 's')
					. ' — the next cron tick (within ~2 min) will retry.'];
			} elseif ($flash === 'retried_none') {
				$flashMap[$flash] = ['info', 'No failed messages to re-queue.'];
			}

			if (isset($flashMap[$flash])) {
				list($kind, $text) = $flashMap[$flash];
				$flashHtml = '<div class="flash ' . $kind . '">' . $esc($text) . '</div>';
			}
		}

		// ---- Stat cards ---------------------------------------------------
		$cards = '';
		foreach (['pending' => 'Pending', 'sent' => 'Sent', 'failed' => 'Failed'] as $key => $label) {
			$cards .= '<div class="card ' . $key . '">'
				. '<div class="card-num">' . (int) $counts[$key] . '</div>'
				. '<div class="card-label">' . $esc($label) . '</div>'
				. '</div>';
		}

		// ---- Sender status ------------------------------------------------
		if ($suspended) {
			$until = (int) @file_get_contents(fbmso_mailqueue_suspend_file());
			$senderHtml = '<span class="status cooldown">COOLDOWN until '
				. $esc(date('Y-m-d H:i:s', $until))
				. '</span><p class="muted">A send failed with a transient/rate-limit error. The sender is paused.</p>';
		} else {
			$senderHtml = '<span class="status active">Active</span>';
		}

		// ---- Action buttons (admin only) ---------------------------------
		$actionsHtml = '';
		if ($isAdmin) {
			if ($suspended) {
				$actionsHtml .= '<a class="btn btn-warm" href="' . site_url('EmailQueue/resume') . '">'
					. 'Clear Cooldown</a>';
			}
			if ($counts['failed'] > 0) {
				$label = 'Retry Failed (' . (int) $counts['failed'] . ')';
				$actionsHtml .= '<a class="btn btn-primary" href="' . site_url('EmailQueue/retry') . '">'
					. $esc($label) . '</a>';
			}
		}
		$actionsHtml = $actionsHtml !== ''
			? '<div class="actions">' . $actionsHtml . '</div>'
			: '';

		// ---- Recent errors ------------------------------------------------
		$errorsHtml = $this->_render_recent_errors($esc);

		// ---- Cron block (admin + ?show_cron=1) ---------------------------
		$cronHtml = '';
		if ($showCron !== '') {
			$cronHtml = '<div class="section">'
				. '<h2>Cron <span class="muted">(every 2 minutes)</span></h2>'
				. '<pre>' . $esc($showCron) . '</pre>'
				. '</div>';
		}

		// ---- Admin hint ---------------------------------------------------
		$adminHint = $isAdmin
			? ''
			: '';

		return '<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>FBMSO Email Queue</title>
<style>
  :root {
    --bg:#f4f6f9; --card:#fff; --border:#e2e8f0; --text:#1a202c;
    --muted:#718096; --blue:#3b82f6; --blue-d:#2563eb;
    --green:#16a34a; --green-bg:#dcfce7;
    --red:#dc2626;  --red-bg:#fee2e2;
    --amber:#d97706; --amber-bg:#fef3c7;
    --info-bg:#e0f2fe;
  }
  *{box-sizing:border-box;margin:0;padding:0}
  body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;
       background:var(--bg);color:var(--text);line-height:1.6;padding:20px}
  .wrap{max-width:760px;margin:0 auto}
  h1{font-size:1.5rem;margin-bottom:4px}
  h2{font-size:1.1rem;margin-bottom:10px}
  .muted{color:var(--muted);font-weight:400;font-size:.9rem}
  code{background:var(--border);padding:1px 5px;border-radius:4px;font-size:.85rem}

  /* flash */
  .flash{padding:12px 16px;border-radius:8px;margin-bottom:18px;font-size:.95rem}
  .flash.ok{background:var(--green-bg);color:var(--green);border:1px solid #bbf7d0}
  .flash.info{background:var(--info-bg);color:#0369a1;border:1px solid #bae6fd}

  /* stat cards */
  .cards{display:flex;gap:14px;margin:18px 0;flex-wrap:wrap}
  .card{flex:1;min-width:120px;background:var(--card);border:1px solid var(--border);
        border-radius:10px;padding:18px;text-align:center}
  .card-num{font-size:2rem;font-weight:700;line-height:1}
  .card-label{text-transform:uppercase;font-size:.75rem;letter-spacing:.05em;
              color:var(--muted);margin-top:6px}
  .card.pending .card-num{color:var(--blue)}
  .card.sent    .card-num{color:var(--green)}
  .card.failed  .card-num{color:var(--red)}

  /* status pill */
  .status{display:inline-block;padding:3px 12px;border-radius:999px;font-size:.85rem;font-weight:600}
  .status.active{background:var(--green-bg);color:var(--green)}
  .status.cooldown{background:var(--amber-bg);color:var(--amber)}

  /* buttons */
  .actions{display:flex;gap:10px;margin:18px 0;flex-wrap:wrap}
  .btn{display:inline-block;padding:10px 20px;border-radius:8px;text-decoration:none;
       font-size:.9rem;font-weight:600;cursor:pointer;transition:opacity .15s}
  .btn:hover{opacity:.85}
  .btn-primary{background:var(--blue);color:#fff}
  .btn-warm{background:var(--amber);color:#fff}

  /* sections */
  .section{background:var(--card);border:1px solid var(--border);border-radius:10px;
           padding:18px;margin-bottom:18px}
  .section pre{background:#1e293b;color:#e2e8f0;padding:14px;border-radius:8px;
               overflow-x:auto;font-size:.85rem;white-space:pre-wrap;word-break:break-all}

  /* error table */
  table{width:100%;border-collapse:collapse;font-size:.9rem}
  th,td{text-align:left;padding:8px 10px;border-bottom:1px solid var(--border);vertical-align:top}
  th{font-size:.75rem;text-transform:uppercase;letter-spacing:.05em;color:var(--muted)}
  td .id{font-weight:700;color:var(--muted)}
  td .badge{display:inline-block;padding:1px 8px;border-radius:999px;font-size:.75rem;font-weight:600}
  td .badge.failed{background:var(--red-bg);color:var(--red)}
  td .badge.pending{background:#dbeafe;color:var(--blue-d)}
  td .err{color:#475569;font-family:monospace;font-size:.8rem;word-break:break-word;
          max-width:520px;display:block;margin-top:2px}
  .retry-one{font-size:.8rem;color:var(--blue);text-decoration:none;margin-left:8px}
  .retry-one:hover{text-decoration:underline}
</style>
</head>
<body>
<div class="wrap">
  <h1>Email Queue</h1>

  ' . $flashHtml . '

  <div class="cards">' . $cards . '</div>

  <div class="section">
    <h2>Sender</h2>
    ' . $senderHtml . '
  </div>

  ' . $actionsHtml . '

  ' . $errorsHtml . '

  ' . $cronHtml . '

  ' . $adminHint . '
</div>
</body>
</html>';
	}

	
	private function _render_recent_errors($esc)
	{
		$rows = $this->db->select('id, to_email, status, attempts, last_error')
			->from('fbmso_email_queue')
			->where_in('status', ['pending', 'failed'])
			->where('last_error !=', '')
			->order_by('id', 'DESC')
			->limit(10)
			->get()->result();

		if (!$rows) {
			return '<div class="section"><h2>Recent Errors</h2><p class="muted">No errors recorded.</p></div>';
		}

		$level = trim((string) $this->session->userdata('level'));
		$isAdmin = in_array($level, ['Admin', 'IT', 'Super Admin'], true);

		$body = '';
		foreach ($rows as $r) {
			$retryLink = '';
			if ($isAdmin && $r->status === 'failed') {
				$retryLink = ' <a class="retry-one" href="'
					. site_url('EmailQueue/retry?id=' . (int) $r->id)
					. '">retry this</a>';
			}

			$body .= '<tr>'
				. '<td><span class="id">#' . (int) $r->id . '</span></td>'
				. '<td>' . $esc($r->to_email) . $retryLink . '</td>'
				. '<td><span class="badge ' . $esc($r->status) . '">' . $esc($r->status) . '</span>'
				. ' <span class="muted">attempt ' . (int) $r->attempts . '</span></td>'
				. '<td><span class="err">' . $esc($r->last_error) . '</span></td>'
				. '</tr>';
		}

		return '<div class="section">
			<h2>Recent Errors</h2>
			<table>
				<thead><tr><th>#</th><th>To</th><th>Status</th><th>Last Error</th></tr></thead>
				<tbody>' . $body . '</tbody>
			</table>
		</div>';
	}
}
