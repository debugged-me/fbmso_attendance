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
        // QR tokens expire after 1 year — forces re-issue so stale tokens
        // don't remain valid indefinitely.
        $expires = date('Y-m-d H:i:s', strtotime('+1 year'));

        $this->db->insert('student_qr', [
            'student_number' => $student_number,
            'qr_token'       => $token,
            'status'         => 'active',
            'issued_at'      => $now,
            'expires_at'     => $expires,
        ]);

        return (object)[
            'student_number' => $student_number,
            'qr_token'       => $token,
            'token'          => $token,
            'status'         => 'active',
            'issued_at'      => $now,
            'expires_at'     => $expires,
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
        if ($row) {
            // Check expiry — if the token has expired, mark it revoked
            // and refuse to return it.
            if (!empty($row->expires_at) && strtotime($row->expires_at) < time()) {
                $this->db->where('id', $row->id)
                         ->update('student_qr', [
                             'status'     => 'revoked',
                             'revoked_at' => date('Y-m-d H:i:s'),
                         ]);
                return null;
            }
            $row->token = $row->qr_token;
        }
        return $row;
    }
}
