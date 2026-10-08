<?php
defined('BASEPATH') or exit('No direct script access allowed');

class MassAnnouncement extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->database();
		$this->load->helper('url');
		$this->load->library(['form_validation', 'session']);
		$this->load->model('SettingsModel');
		$this->load->model('MassAnnouncementModel');
		$this->config->load('mass_announcement_email', true);

		if ($this->session->userdata('logged_in') !== true) {
			redirect('login');
		}
	}

	public function index()
	{
		if (!$this->requireMassAnnouncementAccess(true)) {
			return;
		}

		$this->load->view('mass_announcement', $this->buildPageData());
	}

	public function sections()
	{
		if (!$this->requireMassAnnouncementAccess()) {
			return;
		}

		$term = $this->MassAnnouncementModel->getActiveTerm();
		$yearLevel = trim((string) $this->input->get('year_level', true));
		$sections = [];

		if ($term['sy'] !== '' && $term['semester'] !== '') {
			$sections = $this->MassAnnouncementModel->getSections($term['sy'], $term['semester'], $yearLevel);
		}

		$results = [];
		foreach ($sections as $section) {
			$results[] = [
				'id' => $section,
				'text' => $section,
			];
		}

		$this->output
			->set_content_type('application/json')
			->set_output(json_encode(['results' => $results]));
	}

	public function students()
	{
		if (!$this->requireMassAnnouncementAccess()) {
			return;
		}

		$term = $this->MassAnnouncementModel->getActiveTerm();
		$search = trim((string) $this->input->get('q', true));
		$yearLevel = trim((string) $this->input->get('year_level', true));
		$section = trim((string) $this->input->get('section', true));
		$results = [];

		if ($search !== '' && $term['sy'] !== '' && $term['semester'] !== '') {
			$students = $this->MassAnnouncementModel->searchStudents($term['sy'], $term['semester'], $search, $yearLevel, $section);
			foreach ($students as $student) {
				$results[] = [
					'id' => (string) ($student->StudentNumber ?? ''),
					'text' => $this->formatStudentOptionText($student),
				];
			}
		}

		$this->output
			->set_content_type('application/json')
			->set_output(json_encode(['results' => $results]));
	}

	public function send()
	{
		if (!$this->requireMassAnnouncementAccess(true)) {
			return;
		}

		if (strtoupper((string) $this->input->method()) !== 'POST') {
			redirect('mass-announcement');
			return;
		}

		$targetType = trim((string) $this->input->post('target_type', true));
		$yearLevel = trim((string) $this->input->post('year_level', true));
		$section = trim((string) $this->input->post('section', true));
		$studentNumber = trim((string) $this->input->post('student_number', true));
		$includeParents = $this->input->post('include_parents') ? 1 : 0;
		$subject = trim((string) $this->input->post('subject', true));
		$messageHtml = trim((string) $this->input->post('message', false));

		$oldInput = [
			'target_type' => $targetType,
			'year_level' => $yearLevel,
			'section' => $section,
			'student_number' => $studentNumber,
			'include_parents' => $includeParents,
			'subject' => $subject,
			'message' => $messageHtml,
		];

		$errors = [];
		if (!in_array($targetType, ['all', 'year', 'section', 'individual'], true)) {
			$errors[] = 'Choose a valid recipient filter.';
		}

		if ($targetType === 'year' && $yearLevel === '') {
			$errors[] = 'Year level is required when sending by year level.';
		}

		if ($targetType === 'section') {
			if ($yearLevel === '') {
				$errors[] = 'Year level is required when sending by section.';
			}
			if ($section === '') {
				$errors[] = 'Section is required when sending by section.';
			}
		}

		if ($targetType === 'individual' && $studentNumber === '') {
			$errors[] = 'Select a student when sending to an individual student.';
		}

		if ($subject === '') {
			$errors[] = 'Subject is required.';
		} elseif (strlen($subject) > 255) {
			$errors[] = 'Subject must not exceed 255 characters.';
		}

		if (trim(strip_tags($messageHtml)) === '') {
			$errors[] = 'Message is required.';
		}

		$term = $this->MassAnnouncementModel->getActiveTerm();
		if ($term['sy'] === '' || $term['semester'] === '') {
			$errors[] = 'Active school year and semester are not configured.';
		}

		$emailSettings = $this->resolveEmailSettings();
		if (!$this->isEmailSettingsReady($emailSettings)) {
			$errors[] = 'Mass email setup is incomplete. Update the Brevo/SMTP sender settings first.';
		}

		if (!empty($errors)) {
			$this->session->set_flashdata('mass_announcement_old', $oldInput);
			$this->session->set_flashdata('danger', implode('<br>', $errors));
			redirect('mass-announcement');
			return;
		}

		$recipients = $this->MassAnnouncementModel->getRecipients($term['sy'], $term['semester'], $oldInput);
		$emailRecipients = $this->extractEmailRecipients($recipients);
		$recipientCount = count($emailRecipients);

		if ($recipientCount === 0) {
			$this->session->set_flashdata('mass_announcement_old', $oldInput);
			$this->session->set_flashdata('danger', 'No recipient email addresses were found for the selected filter.');
			redirect('mass-announcement');
			return;
		}

		$sentCount = 0;
		$failedCount = 0;
		$transportErrors = [];
		$usedTransports = [];
		$fallbackWarnings = [];
		$batches = array_chunk($emailRecipients, 100);

		@set_time_limit(0);

		foreach ($batches as $batch) {
			$result = $this->sendAnnouncementBatch($emailSettings, $batch, $subject, $messageHtml);

			if (!empty($result['ok'])) {
				$sentCount += count($batch);
				$usedTransports[] = (string) ($result['transport'] ?? $emailSettings['transport']);
				if (!empty($result['fallback_used']) && !empty($result['message'])) {
					$fallbackWarnings[$result['transport']] = (string) $result['message'];
				}
				continue;
			}

			$failedCount += count($batch);
			if (!empty($result['message'])) {
				$transportErrors[] = (string) $result['message'];
			}
		}

		$postData = [
			'subject' => $subject,
			'message' => $messageHtml,
			'sy' => $term['sy'],
			'semester' => $term['semester'],
			'year_level' => $this->buildTargetLabel($targetType, $yearLevel, $section, $studentNumber),
			'include_parents' => $includeParents,
			'recipient_count' => $recipientCount,
			'sent_count' => $sentCount,
			'failed_count' => $failedCount,
			'transport' => $this->resolveLoggedTransport($usedTransports, (string) $emailSettings['transport']),
			'sender_email' => $emailSettings['sender_email'],
			'sender_name' => $emailSettings['sender_name'],
			'created_by' => (string) $this->session->userdata('username'),
		];

		$postId = $this->MassAnnouncementModel->saveAnnouncementPost($postData);
		if ($postId === false) {
			log_message('error', 'Mass announcement history save failed for subject: ' . $subject);
		}

		if ($sentCount > 0 && $failedCount === 0) {
			$this->writeAuditTrail('Sent mass announcement: ' . $subject);
		} elseif ($sentCount > 0) {
			$this->writeAuditTrail('Partially sent mass announcement: ' . $subject);
		} else {
			$this->writeAuditTrail('Attempted mass announcement: ' . $subject);
		}

		$warningMessages = [];
		if ($includeParents) {
			$warningMessages[] = 'Parent/guardian contacts were requested, but this project currently only has deliverable student email records.';
		}
		foreach ($fallbackWarnings as $fallbackWarning) {
			$warningMessages[] = $fallbackWarning;
		}

		if ($sentCount > 0 && $failedCount === 0) {
			$this->session->set_flashdata('success', 'Mass announcement sent to ' . $sentCount . ' email recipient(s).');
		} elseif ($sentCount > 0) {
			$message = 'Mass announcement partially sent. Successful: ' . $sentCount . ', failed: ' . $failedCount . '.';
			if (!empty($transportErrors)) {
				$message .= ' ' . html_escape($transportErrors[0]);
			}
			$warningMessages[] = $message;
		} else {
			$message = 'Mass announcement could not be sent.';
			if (!empty($transportErrors)) {
				$message .= ' ' . html_escape($transportErrors[0]);
			}
			$this->session->set_flashdata('danger', $message);
			$this->session->set_flashdata('mass_announcement_old', $oldInput);
		}

		if (!empty($warningMessages)) {
			$this->session->set_flashdata('warning', implode('<br>', $warningMessages));
		}

		redirect('mass-announcement');
	}

	public function settings()
	{
		if ($this->session->userdata('level') !== 'Super Admin') {
			$this->session->set_flashdata('danger', 'Only Super Admin can manage the mass email setup.');
			redirect('Settings/schoolInfo');
			return;
		}

		$returnTo = $this->sanitizeReturnTo($this->input->post('return_to', true));
		if (strtoupper((string) $this->input->method()) !== 'POST') {
			redirect($returnTo);
			return;
		}

		$postedTransport = trim((string) $this->input->post('transport', true));
		$postedSenderEmail = trim((string) $this->input->post('sender_email', true));
		$postedBrevoApiUrl = trim((string) $this->input->post('brevo_api_url', true));
		$postedBrevoApiKey = trim((string) $this->input->post('brevo_api_key', false));
		$postedSmtpHost = trim((string) $this->input->post('smtp_host', true));
		$postedSmtpPort = trim((string) $this->input->post('smtp_port', true));
		$postedSmtpCrypto = trim((string) $this->input->post('smtp_crypto', true));
		$postedSmtpUser = trim((string) $this->input->post('smtp_user', true));
		$postedSmtpPass = trim((string) $this->input->post('smtp_pass', false));

		$oldInput = [
			'transport' => $postedTransport,
			'sender_email' => $postedSenderEmail,
			'brevo_api_url' => $postedBrevoApiUrl,
			'brevo_api_key' => $postedBrevoApiKey,
			'smtp_host' => $postedSmtpHost,
			'smtp_port' => $postedSmtpPort,
			'smtp_crypto' => $postedSmtpCrypto,
			'smtp_user' => $postedSmtpUser,
			'smtp_pass' => $postedSmtpPass,
		];

		$errors = [];
		if (!in_array($postedTransport, ['brevo_api', 'smtp'], true)) {
			$errors[] = 'Choose a valid transport.';
		}

		if ($postedSenderEmail === '' || !filter_var($postedSenderEmail, FILTER_VALIDATE_EMAIL)) {
			$errors[] = 'Sender email must be a valid email address.';
		}

		if ($postedTransport === 'brevo_api') {
			if ($postedBrevoApiUrl === '' || !filter_var($postedBrevoApiUrl, FILTER_VALIDATE_URL)) {
				$errors[] = 'Brevo API URL must be a valid URL.';
			}

			if ($postedBrevoApiKey === '') {
				$errors[] = 'Brevo API key is required when Brevo API transport is selected.';
			}
		}

		if ($postedTransport === 'smtp') {
			if ($postedSmtpHost === '') {
				$errors[] = 'SMTP host is required when SMTP transport is selected.';
			}

			if ($postedSmtpPort === '' || filter_var($postedSmtpPort, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
				$errors[] = 'SMTP port must be a valid positive number.';
			}

			if (!in_array($postedSmtpCrypto, ['tls', 'ssl', ''], true)) {
				$errors[] = 'SMTP crypto must be tls, ssl, or none.';
			}

			if ($postedSmtpUser === '') {
				$errors[] = 'SMTP user is required when SMTP transport is selected.';
			}

			if ($postedSmtpPass === '') {
				$errors[] = 'SMTP password is required when SMTP transport is selected.';
			}
		}

		if (!empty($errors)) {
			$this->session->set_flashdata('mass_email_settings_old', $oldInput);
			$this->session->set_flashdata('open_panel', 'mass_email');
			$this->session->set_flashdata('danger', implode('<br>', $errors));
			redirect($returnTo);
			return;
		}

		$schoolSettings = $this->SettingsModel->get_settings();
		$existingSettings = $this->SettingsModel->getMassAnnouncementEmailSettings();
		$senderName = trim((string) ($schoolSettings->SchoolName ?? 'School Records Management System'));

		$saveData = [
			'transport' => $postedTransport,
			'sender_email' => $postedSenderEmail,
			'sender_name' => $senderName,
			'brevo_api_url' => $postedBrevoApiUrl !== '' ? $postedBrevoApiUrl : 'https://api.brevo.com/v3/smtp/email',
			'brevo_api_key' => $postedBrevoApiKey,
			'smtp_host' => $postedSmtpHost !== '' ? $postedSmtpHost : 'smtp-relay.brevo.com',
			'smtp_port' => (int) ($postedSmtpPort !== '' ? $postedSmtpPort : 587),
			'smtp_crypto' => $postedSmtpCrypto,
			'smtp_user' => $postedSmtpUser,
			'smtp_pass' => $postedSmtpPass,
			'is_active' => 1,
		];

		$saved = $this->SettingsModel->saveMassAnnouncementEmailSettings($saveData, (int) ($existingSettings->id ?? 0));
		if ($saved) {
			$this->session->set_flashdata('success', 'Mass email setup saved successfully.');
		} else {
			$this->session->set_flashdata('mass_email_settings_old', $oldInput);
			$this->session->set_flashdata('open_panel', 'mass_email');
			$this->session->set_flashdata('danger', 'Unable to save the mass email setup.');
		}

		redirect($returnTo);
	}

	private function requireMassAnnouncementAccess($redirectOnFailure = false)
	{
		$level = (string) $this->session->userdata('level');
		$allowed = in_array($level, ['Admin', 'Super Admin'], true);

		if ($allowed) {
			return true;
		}

		if ($redirectOnFailure) {
			$this->session->set_flashdata('danger', 'Only Admin and Super Admin can access the mass announcement page.');
			redirect('Announcement');
			return false;
		}

		show_error('Forbidden', 403);
		return false;
	}

	private function buildPageData()
	{
		$term = $this->MassAnnouncementModel->getActiveTerm();
		$oldInput = $this->session->flashdata('mass_announcement_old');
		$oldInput = is_array($oldInput) ? $oldInput : [];

		$targetType = trim((string) ($oldInput['target_type'] ?? 'all'));
		if (!in_array($targetType, ['all', 'year', 'section', 'individual'], true)) {
			$targetType = 'all';
		}

		$selectedYearLevel = trim((string) ($oldInput['year_level'] ?? ''));
		$selectedSection = trim((string) ($oldInput['section'] ?? ''));
		$selectedStudentNumber = trim((string) ($oldInput['student_number'] ?? ''));
		$selectedStudentOption = null;
		$sections = [];

		if ($term['sy'] !== '' && $term['semester'] !== '') {
			if ($selectedYearLevel !== '') {
				$sections = $this->MassAnnouncementModel->getSections($term['sy'], $term['semester'], $selectedYearLevel);
			}

			if ($selectedStudentNumber !== '') {
				$selectedStudentOption = $this->MassAnnouncementModel->getStudentByNumber($term['sy'], $term['semester'], $selectedStudentNumber);
			}
		}

		$emailSettings = $this->resolveEmailSettings();
		$emailReady = $this->isEmailSettingsReady($emailSettings);
		$emailStatusMessage = $emailReady
			? 'Using ' . strtoupper(str_replace('_', ' ', $emailSettings['transport'])) . ' from the stored mass email setup.'
			: 'Mass email setup is incomplete. Update the sender settings before sending.';
		if ((string) ($emailSettings['transport'] ?? '') === 'smtp' && $this->hasBrevoApiSettings($emailSettings)) {
			$emailStatusMessage .= ' Brevo API is also available as automatic fallback.';
		}

		return [
			'open_panel' => trim((string) $this->session->flashdata('mass_announcement_open_panel')),
			'target_type' => $targetType,
			'year_levels' => ($term['sy'] !== '' && $term['semester'] !== '')
				? $this->MassAnnouncementModel->getYearLevels($term['sy'], $term['semester'])
				: [],
			'selected_year_level' => $selectedYearLevel,
			'sections' => $sections,
			'selected_section' => $selectedSection,
			'include_parents' => !empty($oldInput['include_parents']),
			'selected_student_number' => $selectedStudentNumber,
			'selected_student_option' => $selectedStudentOption,
			'subject' => (string) ($oldInput['subject'] ?? ''),
			'message' => (string) ($oldInput['message'] ?? ''),
			'announcement_history' => $this->MassAnnouncementModel->getAnnouncementHistory(50),
			'current_sy' => $term['sy'],
			'current_semester' => $term['semester'],
			'current_term_student_count' => ($term['sy'] !== '' && $term['semester'] !== '')
				? $this->MassAnnouncementModel->countStudents($term['sy'], $term['semester'])
				: 0,
			'current_term_email_count' => ($term['sy'] !== '' && $term['semester'] !== '')
				? $this->MassAnnouncementModel->countStudentsWithEmail($term['sy'], $term['semester'])
				: 0,
			'email_settings' => $emailSettings,
			'email_ready' => $emailReady,
			'email_status_message' => $emailStatusMessage,
			'can_manage_mass_email' => ($this->session->userdata('level') === 'Super Admin'),
		];
	}

	private function resolveEmailSettings()
	{
		$section = 'mass_announcement_email';
		$configDefaults = (array) $this->config->item('mass_announcement_email', $section);
		$dbSettings = $this->SettingsModel->getMassAnnouncementEmailSettings();
		$dbSettings = $dbSettings ? (array) $dbSettings : [];

		$schoolSettings = $this->SettingsModel->get_settings();
		$senderNameFallback = trim((string) ($schoolSettings->SchoolName ?? 'School Records Management System'));

		$transport = trim((string) ($dbSettings['transport'] ?? $this->config->item('mass_announcement_transport', $section) ?? 'brevo_api'));
		if (!in_array($transport, ['brevo_api', 'smtp'], true)) {
			$transport = 'brevo_api';
		}

		return [
			'transport' => $transport,
			'brevo_api_url' => trim((string) ($dbSettings['brevo_api_url'] ?? $this->config->item('mass_announcement_brevo_url', $section) ?? 'https://api.brevo.com/v3/smtp/email')),
			'brevo_api_key' => trim((string) ($dbSettings['brevo_api_key'] ?? $this->config->item('mass_announcement_brevo_api_key', $section) ?? '')),
			'smtp_host' => trim((string) ($dbSettings['smtp_host'] ?? ($configDefaults['smtp_host'] ?? 'smtp-relay.brevo.com'))),
			'smtp_user' => trim((string) ($dbSettings['smtp_user'] ?? ($configDefaults['smtp_user'] ?? ''))),
			'smtp_pass' => trim((string) ($dbSettings['smtp_pass'] ?? ($configDefaults['smtp_pass'] ?? ''))),
			'smtp_port' => (int) ($dbSettings['smtp_port'] ?? ($configDefaults['smtp_port'] ?? 587)),
			'smtp_crypto' => trim((string) ($dbSettings['smtp_crypto'] ?? ($configDefaults['smtp_crypto'] ?? 'tls'))),
			'sender_email' => trim((string) ($dbSettings['sender_email'] ?? $this->config->item('mass_announcement_sender_email', $section) ?? '')),
			'sender_name' => trim((string) ($dbSettings['sender_name'] ?? $this->config->item('mass_announcement_sender_name', $section) ?? $senderNameFallback)),
		];
	}

	private function isEmailSettingsReady(array $settings)
	{
		return $this->hasTransportConfig((string) ($settings['transport'] ?? ''), $settings)
			|| $this->hasBrevoApiSettings($settings)
			|| $this->hasSmtpSettings($settings);
	}

	private function hasTransportConfig($transport, array $settings)
	{
		if ($transport === 'smtp') {
			return $this->hasSmtpSettings($settings);
		}

		if ($transport === 'brevo_api') {
			return $this->hasBrevoApiSettings($settings);
		}

		return false;
	}

	private function hasBrevoApiSettings(array $settings)
	{
		return (($settings['sender_email'] ?? '') !== '')
			&& filter_var((string) ($settings['sender_email'] ?? ''), FILTER_VALIDATE_EMAIL)
			&& (($settings['brevo_api_url'] ?? '') !== '')
			&& filter_var((string) ($settings['brevo_api_url'] ?? ''), FILTER_VALIDATE_URL)
			&& (($settings['brevo_api_key'] ?? '') !== '');
	}

	private function hasSmtpSettings(array $settings)
	{
		return (($settings['sender_email'] ?? '') !== '')
			&& filter_var((string) ($settings['sender_email'] ?? ''), FILTER_VALIDATE_EMAIL)
			&& (($settings['smtp_host'] ?? '') !== '')
			&& (($settings['smtp_user'] ?? '') !== '')
			&& (($settings['smtp_pass'] ?? '') !== '')
			&& ((int) ($settings['smtp_port'] ?? 0) > 0);
	}

	private function extractEmailRecipients(array $rows)
	{
		$recipients = [];
		$seen = [];

		foreach ($rows as $row) {
			$email = trim((string) ($row->email ?? ''));
			// Also drops typo/.local addresses: one refused BCC fails the whole
			// SMTP send, and each bounce counts against the host's hourly limit.
			if (fbmso_mailqueue_validate_recipient($email, false) !== null) {
				continue;
			}

			$key = strtolower($email);
			if (isset($seen[$key])) {
				continue;
			}

			$seen[$key] = true;
			$recipients[] = [
				'email' => $email,
				'name' => $this->formatRecipientName($row),
			];
		}

		return $recipients;
	}

	private function sendViaBrevoApi(array $settings, array $recipients, $subject, $messageHtml)
	{
		if (!function_exists('curl_init')) {
			return ['ok' => false, 'message' => 'cURL is not available on this PHP server.'];
		}

		$payload = [
			'sender' => [
				'email' => $settings['sender_email'],
				'name' => $settings['sender_name'],
			],
			'subject' => $subject,
			'htmlContent' => $messageHtml,
			'textContent' => $this->buildPlainTextMessage($messageHtml),
		];

		if (count($recipients) === 1) {
			$payload['to'] = [$recipients[0]];
		} else {
			$payload['to'] = [[
				'email' => $settings['sender_email'],
				'name' => $settings['sender_name'] !== '' ? $settings['sender_name'] : 'Mass Announcement',
			]];
			$payload['bcc'] = $recipients;
		}

		$body = json_encode($payload);
		if ($body === false) {
			return ['ok' => false, 'message' => 'Unable to encode the Brevo email payload.'];
		}

		$ch = curl_init($settings['brevo_api_url']);
		curl_setopt_array($ch, [
			CURLOPT_POST => true,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_HTTPHEADER => [
				'Accept: application/json',
				'Content-Type: application/json',
				'api-key: ' . $settings['brevo_api_key'],
			],
			CURLOPT_POSTFIELDS => $body,
			CURLOPT_CONNECTTIMEOUT => 10,
			CURLOPT_TIMEOUT => 30,
		]);

		$response = curl_exec($ch);
		$curlError = curl_error($ch);
		$statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		if ($response === false) {
			return ['ok' => false, 'message' => 'Brevo request failed: ' . $curlError];
		}

		if ($statusCode >= 200 && $statusCode < 300) {
			return ['ok' => true, 'message' => ''];
		}

		$responseText = trim(strip_tags((string) $response));
		if ($responseText !== '') {
			$responseText = substr($responseText, 0, 200);
		}

		return ['ok' => false, 'message' => 'Brevo API returned HTTP ' . $statusCode . ($responseText !== '' ? ': ' . $responseText : '.')];
	}

	private function sendViaSmtp(array $settings, array $recipients, $subject, $messageHtml)
	{
		$this->load->library('email');

		$config = [
			'protocol' => 'smtp',
			'smtp_host' => $settings['smtp_host'],
			'smtp_user' => $settings['smtp_user'],
			'smtp_pass' => $settings['smtp_pass'],
			'smtp_port' => (int) $settings['smtp_port'],
			'smtp_crypto' => $settings['smtp_crypto'],
			'smtp_timeout' => 20,
			'charset' => 'utf-8',
			'mailtype' => 'html',
			'newline' => "\r\n",
			'crlf' => "\r\n",
		];

		$this->email->clear(true);
		$this->email->initialize($config);
		if (method_exists($this->email, 'set_mailtype')) {
			$this->email->set_mailtype('html');
		}
		if (method_exists($this->email, 'set_newline')) {
			$this->email->set_newline("\r\n");
		}
		if (method_exists($this->email, 'set_crlf')) {
			$this->email->set_crlf("\r\n");
		}

		$this->email->from($settings['sender_email'], $settings['sender_name']);
		if (count($recipients) === 1) {
			$this->email->to($recipients[0]['email']);
		} else {
			$this->email->to($settings['sender_email']);
			$this->email->bcc(array_column($recipients, 'email'));
		}
		$this->email->subject($subject);
		$this->email->message($messageHtml);

		$sent = $this->email->send(false);
		if ($sent) {
			return ['ok' => true, 'message' => ''];
		}

		return [
			'ok' => false,
			'message' => trim(strip_tags($this->email->print_debugger(['headers']))),
		];
	}

	private function sendAnnouncementBatch(array $settings, array $recipients, $subject, $messageHtml)
	{
		$preferredTransport = ((string) ($settings['transport'] ?? '') === 'smtp') ? 'smtp' : 'brevo_api';
		$primaryResult = $this->sendWithTransport($preferredTransport, $settings, $recipients, $subject, $messageHtml);
		if (!empty($primaryResult['ok'])) {
			return [
				'ok' => true,
				'transport' => $preferredTransport,
				'fallback_used' => false,
				'message' => '',
			];
		}

		$fallbackTransport = ($preferredTransport === 'smtp') ? 'brevo_api' : 'smtp';
		if (!$this->hasTransportConfig($fallbackTransport, $settings)) {
			return [
				'ok' => false,
				'transport' => $preferredTransport,
				'fallback_used' => false,
				'message' => (string) ($primaryResult['message'] ?? ''),
			];
		}

		$fallbackResult = $this->sendWithTransport($fallbackTransport, $settings, $recipients, $subject, $messageHtml);
		if (!empty($fallbackResult['ok'])) {
			$preferredLabel = strtoupper(str_replace('_', ' ', $preferredTransport));
			$fallbackLabel = strtoupper(str_replace('_', ' ', $fallbackTransport));

			return [
				'ok' => true,
				'transport' => $fallbackTransport,
				'fallback_used' => true,
				'message' => $preferredLabel . ' failed, so this batch was sent through ' . $fallbackLabel . '. Update Mass Email Setup to use ' . $fallbackLabel . ' as the primary transport.',
			];
		}

		$message = trim((string) ($primaryResult['message'] ?? ''));
		$fallbackMessage = trim((string) ($fallbackResult['message'] ?? ''));
		if ($fallbackMessage !== '') {
			$message .= ($message !== '' ? ' Fallback also failed: ' : '') . $fallbackMessage;
		}

		return [
			'ok' => false,
			'transport' => $preferredTransport,
			'fallback_used' => false,
			'message' => $message,
		];
	}

	private function sendWithTransport($transport, array $settings, array $recipients, $subject, $messageHtml)
	{
		if ($transport === 'smtp') {
			return $this->sendViaSmtp($settings, $recipients, $subject, $messageHtml);
		}

		return $this->sendViaBrevoApi($settings, $recipients, $subject, $messageHtml);
	}

	private function resolveLoggedTransport(array $usedTransports, $defaultTransport)
	{
		$usedTransports = array_values(array_unique(array_filter($usedTransports)));
		if (count($usedTransports) === 1) {
			return $usedTransports[0];
		}

		if (count($usedTransports) > 1) {
			return 'mixed';
		}

		return $defaultTransport;
	}

	private function buildTargetLabel($targetType, $yearLevel, $section, $studentNumber)
	{
		if ($targetType === 'year') {
			return 'Year Level: ' . $yearLevel;
		}

		if ($targetType === 'section') {
			return 'Section: ' . $section;
		}

		if ($targetType === 'individual') {
			return 'Student: ' . $studentNumber;
		}

		return 'All Enrolled Students';
	}

	private function buildPlainTextMessage($messageHtml)
	{
		$text = html_entity_decode(strip_tags((string) $messageHtml), ENT_QUOTES, 'UTF-8');
		$text = preg_replace("/\R{3,}/", "\n\n", (string) $text);
		return trim((string) $text);
	}

	private function formatStudentOptionText($student)
	{
		$studentNumber = trim((string) ($student->StudentNumber ?? ''));
		$fullName = $this->formatRecipientName($student);
		$yearLevel = trim((string) ($student->YearLevel ?? ''));
		$section = trim((string) ($student->Section ?? ''));

		$parts = [];
		if ($studentNumber !== '') {
			$parts[] = $studentNumber;
		}
		if ($fullName !== '') {
			$parts[] = $fullName;
		}

		$meta = trim($yearLevel . ($yearLevel !== '' && $section !== '' ? ' - ' : '') . $section);
		if ($meta !== '') {
			$parts[] = $meta;
		}

		return implode(' - ', $parts);
	}

	private function formatRecipientName($row)
	{
		$lastName = trim((string) ($row->LastName ?? ''));
		$firstName = trim((string) ($row->FirstName ?? ''));
		$middleName = trim((string) ($row->MiddleName ?? ''));

		$name = trim($lastName . ', ' . $firstName, ', ');
		if ($middleName !== '') {
			$name .= ' ' . strtoupper(substr($middleName, 0, 1)) . '.';
		}

		return trim($name, ', ');
	}

	private function writeAuditTrail($description)
	{
		$username = trim((string) $this->session->userdata('username'));
		if ($username === '' || $description === '') {
			return;
		}

		$this->db->insert('atrail', [
			'atDesc' => $description,
			'atDate' => date('Y-m-d'),
			'atTime' => date('h:i:s A'),
			'atRes' => $username,
			'atSNo' => '',
		]);
	}

	private function sanitizeReturnTo($returnTo)
	{
		$returnTo = trim((string) $returnTo);
		if ($returnTo === '') {
			return 'Settings/schoolInfo?panel=mass_email';
		}

		$parts = parse_url($returnTo);
		if ($parts === false || !empty($parts['scheme']) || !empty($parts['host'])) {
			return 'Settings/schoolInfo?panel=mass_email';
		}

		$path = trim((string) ($parts['path'] ?? ''), '/');
		if ($path === '') {
			$path = 'Settings/schoolInfo';
		}

		$query = isset($parts['query']) && $parts['query'] !== '' ? '?' . $parts['query'] : '';
		return $path . $query;
	}
}
