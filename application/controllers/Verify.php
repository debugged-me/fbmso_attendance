<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Public receipt verification — the QR code printed on official receipts
 * lands here. Anyone holding the physical receipt can confirm it is genuine;
 * the HMAC signature in the URL keeps receipts unenumerable, so this page
 * never confirms or denies an O.R. the caller doesn't already possess.
 */
class Verify extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->database();
		$this->load->helper(['url', 'receipt_verify']);
	}

	public function receipt($or = '', $sig = '')
	{
		$this->output->set_header('Cache-Control: no-cache, no-store, must-revalidate');
		$this->output->set_header('Pragma: no-cache');
		$this->output->set_header('Expires: 0');

		$or  = trim((string)$or);
		$sig = strtolower(trim((string)$sig));

		$state   = 'invalid';
		$lines   = [];
		$payment = null;
		$total   = 0.0;

		if ($or !== '' && preg_match('/^[a-f0-9]{16}$/', $sig)) {
			$rows = $this->db->select("p.*,
					COALESCE(NULLIF(sp.LastName,''), su.LastName, '') AS LastName,
					COALESCE(NULLIF(sp.FirstName,''), su.FirstName, '') AS FirstName,
					COALESCE(NULLIF(sp.MiddleName,''), su.MiddleName, '') AS MiddleName", false)
				->from('paymentsaccounts p')
				->join('studeprofile sp', 'sp.StudentNumber = p.StudentNumber', 'left')
				->join('studentsignup su', 'su.StudentNumber = p.StudentNumber', 'left')
				->where('p.ORNumber', $or)
				->order_by('p.ID', 'ASC')
				->get()
				->result();

			if (empty($rows)) {
				$state = 'not_found';
			} else {
				// An O.R. belongs to one student; the signature binds to that
				// pair. Check each distinct student number defensively.
				$matchedSn = null;
				foreach ($rows as $row) {
					$sn = (string)$row->StudentNumber;
					if (hash_equals(receipt_verify_signature($or, $sn), $sig)) {
						$matchedSn = $sn;
						break;
					}
				}

				if ($matchedSn === null) {
					$state = 'invalid';
				} else {
					foreach ($rows as $row) {
						if ((string)$row->StudentNumber !== $matchedSn) {
							continue;
						}
						if ($payment === null) {
							$payment = $row;
						}
						if (strcasecmp((string)$row->ORStatus, 'Valid') === 0) {
							$lines[] = $row;
							$total  += (float)$row->Amount;
						}
					}
					$state = empty($lines) ? 'voided' : 'verified';
				}
			}
		}

		$settings = $this->db->select('SchoolName, SchoolAddress, telNo')
			->from('o_srms_settings')
			->limit(1)
			->get()
			->row();

		$this->load->view('verify_receipt', [
			'state'    => $state,
			'or'       => $or,
			'payment'  => $payment,
			'lines'    => $lines,
			'total'    => $total,
			'settings' => $settings,
		]);
	}
}
