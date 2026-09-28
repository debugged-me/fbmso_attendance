<?php
defined('BASEPATH') or exit('No direct script access allowed');

if (!function_exists('fbmso_dashboard_route')) {
    /**
     * Where each account level lands: after signing in (Login::auth) and
     * after changing its password (Page::update_password). One list, so the
     * two can never send a role to different places.
     *
     * Returns NULL for a level that has no dashboard.
     */
    function fbmso_dashboard_route($level)
    {
        $routes = array(
            'Admin'              => 'page/admin',
            'School Admin'       => 'page/school_admin',
            'Registrar'          => 'page/registrar',
            'Head Registrar'     => 'page/registrar',
            'Super Admin'        => 'page/superAdmin',
            'Property Custodian' => 'page/p_custodian',
            'HR Admin'           => 'page/hr',
            'Academic Officer'   => 'page/a_officer',
            'Student'            => 'page/student',
            'Stude Applicant'    => 'page/student',
            'Cashier'            => 'Page/accounting',
            'Auditor'            => 'Page/accounting',
            'Committee'          => 'Page/committee',
            'Instructor'         => 'page/Instructor',
            'Encoder'            => 'page/encoder',
            'Human Resource'     => 'page/hr',
            'Guidance'           => 'page/guidance',
            'School Nurse'       => 'page/medical',
            'IT'                 => 'page/IT',
            'Librarian'          => 'page/library',
            'Principal'          => 'page/s_principal',
        );

        $level = (string)$level;

        return isset($routes[$level]) ? $routes[$level] : null;
    }
}
