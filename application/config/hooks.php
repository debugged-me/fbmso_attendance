<?php
defined('BASEPATH') or exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| Hooks
| -------------------------------------------------------------------------
| This file lets you define "hooks" to extend CI without hacking the core
| files.  Please see the user guide for info:
|
|	https://codeigniter.com/user_guide/general/hooks.html
|
*/

$config['enable_hooks'] = TRUE;

$hook['pre_controller'] = array(
    'class'    => 'MaintenanceMode',
    'function' => 'check_maintenance',
    'filename' => 'MaintenanceMode.php',
    'filepath' => 'hooks',
);

/*
| Shared authentication gate. Runs after the controller is constructed, so
| the router knows which controller/method was requested and the guard can
| stop it before the method runs. See application/config/authguard.php.
*/
$hook['post_controller_constructor'] = array(
    'class'    => 'AuthGuardHook',
    'function' => 'guard',
    'filename' => 'AuthGuardHook.php',
    'filepath' => 'hooks',
);
