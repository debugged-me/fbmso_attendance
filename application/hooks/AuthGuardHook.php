<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Runs the shared auth gate on every request.
 *
 * Registered as post_controller_constructor so the router and the loaded
 * controller are both available: at this point we know exactly which
 * controller/method is about to run, and can stop it before it does.
 *
 * Named *Hook to stay clear of the Authguard library — PHP class names are
 * case-insensitive, so `class AuthGuard` here would shadow the library and
 * CI would hand controllers this object instead.
 */
class AuthGuardHook
{
    public function guard()
    {
        $CI = &get_instance();

        $CI->load->library('authguard');
        $CI->authguard->enforce();
    }
}
