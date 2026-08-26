<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * EmailTest — SMTP diagnostics for application/config/email.php.
 *
 * Two paths, both reachable from the form:
 *   Queue (default)  push into fbmso_email_queue and let the EmailQueue cron
 *                    deliver it — this is the exact path every real portal
 *                    email now takes, so it is what you want to verify.
 *   Direct SMTP      talk to the mail server inline and print the full SMTP
 *                    conversation. Use this to diagnose credentials, because
 *                    the queue swallows the transcript into last_error.
 */
class EmailTest extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->helper('url');
		$this->load->config('email');
		$this->load->library('email');

		// Staff only: this page sends from the school mailbox, so it must never
		// be reachable by a student account (spam vector + burns the SMTP
		// limit). AuthGuard already requires a session to get here.
		$username = trim((string) $this->session->userdata('username'));
		$level    = trim((string) $this->session->userdata('level'));
		if ($username === '' || $level === '' || strcasecmp($level, 'Student') === 0) {
			show_error('Forbidden', 403);
		}
	}

	public function index()
	{
		$config = $this->getEmailConfig();
		$form   = $this->getFormData();

		$data = [
			'action_url' => site_url('email-test'),
			'queue_url'  => site_url('EmailQueue/key'),
			'config'     => $config,
			'form'       => $form,
			'result'     => null,
			'counts'     => $this->queueCounts(),
		];

		if (strtoupper((string) $this->input->server('REQUEST_METHOD')) === 'POST') {
			$data['result'] = $form['via_queue']
				? $this->queueTestEmail($form)
				: $this->sendTestEmail($config, $form);
			$data['counts'] = $this->queueCounts();
		}

		$this->load->view('email_test', $data);
	}

	protected function queueCounts()
	{
		$counts = ['pending' => 0, 'sent' => 0, 'failed' => 0];

		if (!fbmso_mailqueue_ensure_table($this)) {
			return $counts;
		}

		$rows = $this->db->select('status, COUNT(*) AS c')
			->from('fbmso_email_queue')
			->group_by('status')
			->get()->result();

		foreach ($rows as $r) {
			$counts[(string) $r->status] = (int) $r->c;
		}

		return $counts;
	}

	protected function getEmailConfig()
	{
		return [
			'protocol'     => (string) $this->config->item('protocol'),
			'smtp_host'    => (string) $this->config->item('smtp_host'),
			'smtp_user'    => (string) $this->config->item('smtp_user'),
			'smtp_pass'    => (string) $this->config->item('smtp_pass'),
			'smtp_port'    => (string) $this->config->item('smtp_port'),
			'smtp_crypto'  => (string) $this->config->item('smtp_crypto'),
			'smtp_timeout' => (string) $this->config->item('smtp_timeout'),
			'mailtype'     => (string) $this->config->item('mailtype'),
			'charset'      => (string) $this->config->item('charset'),
		];
	}

	protected function getFormData()
	{
		$subject = trim((string) $this->input->post('subject', true));
		$subject = str_replace(["\r", "\n"], '', $subject);

		$message = trim((string) $this->input->post('message', true));
		if ($message === '') {
			$message = "This is a test email from the FBMSO Attendance Portal.\n\nSent at: " . date('Y-m-d H:i:s');
		}

		$isPost = strtoupper((string) $this->input->server('REQUEST_METHOD')) === 'POST';

		return [
			'to_email'  => trim((string) $this->input->post('to_email', true)),
			'subject'   => $subject !== '' ? $subject : 'FBMSO SMTP Test',
			'message'   => $message,
			// Default to the queue (the production path); direct SMTP is opt-in.
			'via_queue' => $isPost ? ((string) $this->input->post('via_queue') === '1') : true,
		];
	}

	protected function queueTestEmail(array $form)
	{
		if (!filter_var($form['to_email'], FILTER_VALIDATE_EMAIL)) {
			return [
				'success' => false,
				'message' => 'Enter a valid recipient email address.',
				'debug'   => null,
			];
		}

		$body = '<p>' . nl2br(html_escape($form['message'])) . '</p>'
			. '<hr>'
			. '<p><strong>Path:</strong> fbmso_email_queue table &rarr; EmailQueue cron (throttled sender)</p>'
			. '<p><strong>Queued at:</strong> ' . date('Y-m-d H:i:s') . '</p>';

		if (!fbmso_mailqueue_push($this, $form['to_email'], $form['subject'], $body, 'FBMSO Email Test')) {
			return [
				'success' => false,
				'message' => 'Failed to insert into fbmso_email_queue. Check database connectivity.',
				'debug'   => null,
			];
		}

		return [
			'success' => true,
			'message' => 'Queued. The cron delivers it within ~2 minutes — watch the counters above (pending should drop, sent should rise), then check the inbox and spam folder.',
			'debug'   => null,
		];
	}

	protected function sendTestEmail(array $config, array $form)
	{
		if (!filter_var($form['to_email'], FILTER_VALIDATE_EMAIL)) {
			return [
				'success' => false,
				'message' => 'Enter a valid recipient email address.',
				'debug'   => null,
			];
		}

		if ($config['smtp_user'] === '' || $config['smtp_host'] === '' || $config['smtp_port'] === '') {
			return [
				'success' => false,
				'message' => 'SMTP configuration is incomplete in application/config/email.php.',
				'debug'   => null,
			];
		}

		$mailConfig = [
			'protocol'     => $config['protocol'] !== '' ? $config['protocol'] : 'smtp',
			'smtp_host'    => $config['smtp_host'],
			'smtp_user'    => $config['smtp_user'],
			'smtp_pass'    => $config['smtp_pass'],
			'smtp_port'    => $config['smtp_port'],
			'smtp_crypto'  => $config['smtp_crypto'],
			'smtp_timeout' => $config['smtp_timeout'] !== '' ? (int) $config['smtp_timeout'] : 10,
			'mailtype'     => $config['mailtype'] !== '' ? $config['mailtype'] : 'html',
			'charset'      => $config['charset'] !== '' ? $config['charset'] : 'utf-8',
			'newline'      => "\r\n",
			'crlf'         => "\r\n",
			'wordwrap'     => true,
		];

		$messageBody = '<p>' . nl2br(html_escape($form['message'])) . '</p>'
			. '<hr>'
			. '<p><strong>SMTP User:</strong> ' . html_escape($config['smtp_user']) . '</p>'
			. '<p><strong>SMTP Host:</strong> ' . html_escape($config['smtp_host']) . ':' . html_escape($config['smtp_port']) . '</p>';

		$this->email->clear(true);
		$this->email->initialize($mailConfig);
		$this->email->set_newline("\r\n");
		$this->email->set_crlf("\r\n");

		if (method_exists($this->email, 'set_mailtype')) {
			$this->email->set_mailtype('html');
		}

		$this->email->from($config['smtp_user'], 'FBMSO Email Test');
		$this->email->to($form['to_email']);
		$this->email->subject($form['subject']);
		$this->email->message($messageBody);

		$sent = (bool) $this->email->send(false);

		return [
			'success' => $sent,
			'message' => $sent
				? 'Test email sent successfully over direct SMTP.'
				: 'Email send failed. Review the SMTP conversation below.',
			// Empty $include => the SMTP conversation only, no header/body dump.
			'debug'   => trim((string) strip_tags($this->email->print_debugger([]))),
		];
	}
}
