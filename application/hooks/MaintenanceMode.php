<?php
defined('BASEPATH') or exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| Maintenance mode gate (second layer)
|--------------------------------------------------------------------------
|
| The real gate lives in index.php, before the CI bootstrap loads. This
| hook repeats the check inside CI for any future alternate entry point
| that forgets it. Both render application/views/maintenance.php.
|
*/
class MaintenanceMode
{
    public function check_maintenance()
    {
        include(APPPATH . 'config/config.php');

        if ( ! empty($config['maintenance_mode']))
        {
            include(APPPATH . 'views/maintenance.php');
            exit;
        }
    }
}
