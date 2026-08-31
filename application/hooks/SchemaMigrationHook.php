<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Applies pending schema migrations on the first request after a deploy, so
 * production needs no manual phpMyAdmin step.
 *
 * Runs before AuthGuard in the post_controller_constructor chain: the schema
 * must be current before any controller (login included) touches the tables.
 * Once applied, a marker file makes this an is_file() call per request.
 */
class SchemaMigrationHook
{
    public function migrate()
    {
        $CI = &get_instance();

        // The database is autoloaded, but stay defensive: a request that
        // somehow lacks it must not fatal here.
        if (!isset($CI->db)) {
            return;
        }

        $CI->load->library('schema_migrator');
        $CI->schema_migrator->run();
    }
}
