<?php
class Student_qr_model extends CI_Model {

    public function get_or_issue($student_number) {
        // Prefer an active token; only fall back to issuing a new one when
        // the student has no active QR at all.
        $row = $this->db->where('student_number', $student_number)
                        ->where('status', 'active')
                        ->order_by('issued_at', 'DESC')
                        ->get('student_qr')->row();
        if ($row) {
            $row->token = $row->qr_token;
            return $row;
        }
        $token = bin2hex(random_bytes(16));
        $now   = date('Y-m-d H:i:s');

        $this->db->insert('student_qr', [
            'student_number' => $student_number,
            'qr_token'       => $token,
            'status'         => 'active',
            'issued_at'      => $now,
        ]);

        return (object)[
            'student_number' => $student_number,
            'qr_token'       => $token,
            'token'          => $token,
            'status'         => 'active',
            'issued_at'      => $now,
        ];
    }

    // public function get_by_token($token) {
    //     $row = $this->db->where('qr_token', $token)
    //                     ->where('status', 'active')
    //                     ->get('student_qr')->row();
    //     if ($row) $row->token = $row->qr_token;
    //     return $row;
    // }
     public function get_active($student_number) {
        $row = $this->db->where('student_number', $student_number)
                        ->where('status', 'active')
                        ->get('student_qr')->row();
        if ($row) $row->token = $row->qr_token;
        return $row ?: null;
    }

    public function get_by_token($token) {
        $row = $this->db->where('qr_token', $token)
                        ->where('status', 'active')
                        ->get('student_qr')->row();
        if ($row) $row->token = $row->qr_token;
        return $row;
    }
}
