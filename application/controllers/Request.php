<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Request extends CI_Controller
{
    private const SEEN_SESSION_KEY = 'request_notifications_seen_at';

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->helper('url');
        $this->load->library('session');

        if ($this->session->userdata('logged_in') !== TRUE) {
            redirect('login');
        }

        if ($this->session->userdata('level') === 'Student') {
            show_error('You are not allowed to view staff notifications.', 403, 'Access Denied');
        }
    }

    public function index()
    {
        $seenAt = (int)$this->session->userdata(self::SEEN_SESSION_KEY);
        $requests = $this->pending_requests(250);

        foreach ($requests as &$request) {
            $request['unread'] = $seenAt === 0 || $request['timestamp'] > $seenAt;
        }
        unset($request);

        $this->session->set_userdata(self::SEEN_SESSION_KEY, time());
        $this->load->view('request_notifications', [
            'requests' => $requests,
        ]);
    }

    public function ajax_pending_count()
    {
        $seenAt = (int)$this->session->userdata(self::SEEN_SESSION_KEY);
        $count = 0;

        foreach ($this->pending_requests(1000) as $request) {
            if ($seenAt === 0 || $request['timestamp'] > $seenAt) {
                $count++;
            }
        }

        $this->json(['count' => $count]);
    }

    public function ajax_pending_list()
    {
        $limit = max(1, min(20, (int)$this->input->get('limit')));
        $seenAt = (int)$this->session->userdata(self::SEEN_SESSION_KEY);
        $requests = $this->pending_requests($limit);

        foreach ($requests as &$request) {
            $request['unread'] = $seenAt === 0 || $request['timestamp'] > $seenAt;
            unset($request['timestamp'], $request['request_date_display']);
        }
        unset($request);

        $this->json(['data' => $requests]);
    }

    public function ajax_mark_seen()
    {
        $this->session->set_userdata(self::SEEN_SESSION_KEY, time());
        $this->json(['success' => true]);
    }

    private function pending_requests($limit)
    {
        if ($this->db->table_exists('document_requests')) {
            return $this->modern_pending_requests($limit);
        }

        if (!$this->db->table_exists('stude_request')) {
            return [];
        }

        $this->db->select(
            "'legacy' AS source,
             sr.trackingNo AS request_id,
             sr.docName AS document_type,
             sr.purpose,
             sr.reqStat AS status,
             sr.dateReq AS requested_date,
             sr.timeReq AS requested_time,
             sr.StudentNumber,
             TRIM(CONCAT(COALESCE(sp.FirstName, ''), ' ', COALESCE(sp.LastName, ''))) AS student",
            false
        );
        $this->db->from('stude_request sr');
        $this->db->join('studeprofile sp', 'sp.StudentNumber = sr.StudentNumber', 'left');
        $this->db->where("LOWER(TRIM(sr.reqStat)) IN ('open', 'pending')", null, false);
        $this->db->order_by('sr.dateReq', 'DESC');
        $this->db->order_by('sr.trackingNo', 'DESC');
        $this->db->limit((int)$limit);

        return $this->normalize_requests($this->db->get()->result());
    }

    private function modern_pending_requests($limit)
    {
        $requiredFields = ['id', 'document_type', 'status', 'request_date', 'StudentNumber'];
        foreach ($requiredFields as $field) {
            if (!$this->db->field_exists($field, 'document_requests')) {
                return [];
            }
        }

        $purposeSelect = $this->db->field_exists('purpose', 'document_requests')
            ? 'dr.purpose'
            : "''";

        $this->db->select(
            "'modern' AS source,
             dr.id AS request_id,
             dr.document_type,
             {$purposeSelect} AS purpose,
             dr.status,
             DATE(dr.request_date) AS requested_date,
             TIME(dr.request_date) AS requested_time,
             dr.StudentNumber,
             TRIM(CONCAT(COALESCE(sp.FirstName, ''), ' ', COALESCE(sp.LastName, ''))) AS student",
            false
        );
        $this->db->from('document_requests dr');
        $this->db->join('studeprofile sp', 'sp.StudentNumber = dr.StudentNumber', 'left');
        $this->db->where("LOWER(TRIM(dr.status)) IN ('open', 'pending')", null, false);
        $this->db->order_by('dr.request_date', 'DESC');
        $this->db->order_by('dr.id', 'DESC');
        $this->db->limit((int)$limit);

        return $this->normalize_requests($this->db->get()->result());
    }

    private function normalize_requests($rows)
    {
        $requests = [];

        foreach ($rows as $row) {
            $dateValue = trim((string)$row->requested_date . ' ' . (string)$row->requested_time);
            $timestamp = strtotime($dateValue) ?: strtotime((string)$row->requested_date) ?: 0;
            $student = trim((string)$row->student);
            $requestId = (string)$row->request_id;
            $isLegacy = $row->source === 'legacy';

            $requests[] = [
                'id'                   => $requestId,
                'document_type'        => (string)$row->document_type,
                'purpose'              => (string)$row->purpose,
                'status'               => (string)$row->status,
                'student'              => $student !== '' ? $student : (string)$row->StudentNumber,
                'student_number'       => (string)$row->StudentNumber,
                'request_date'         => $timestamp > 0 ? date('Y-m-d H:i:s', $timestamp) : (string)$row->requested_date,
                'request_date_display' => $timestamp > 0 ? date('M j, Y \a\t g:i A', $timestamp) : (string)$row->requested_date,
                'timestamp'            => $timestamp,
                'url'                  => $isLegacy
                    ? site_url('Page/studentRequestStat') . '?' . http_build_query(['trackingNo' => $requestId])
                    : site_url('request') . '#request-' . rawurlencode($requestId),
            ];
        }

        return $requests;
    }

    private function json(array $payload)
    {
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($payload));
    }
}
