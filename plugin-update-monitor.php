<?php

/*
Plugin Name: Plugin Update Monitor
Plugin URI: http://wordpress.org/plugins/fields-for-all/
Description: This plugin require fields-for-all.
Author: Eugen Bobrowski
Version: 1.0
Author URI: http://atf.li
*/

namespace Plugin_Update_Monitor;

define ('PATH', plugin_dir_path(__FILE__));
define ('URL', plugin_dir_url(__FILE__));

class Plugin_Update_Monitor
{
    protected static $instance;


    private function __construct()
    {
        $this->load_structure();
        $this->load_status_check();
        \register_activation_hook(__FILE__, array($this, 'activation'));

    }

    public function load_structure()
    {
        include_once plugin_dir_path(__FILE__) . 'sites-post-type.php';
        include_once plugin_dir_path(__FILE__) . 'connect-metabox.php';
    }

    public function load_status_check()
    {
        include_once plugin_dir_path(__FILE__) . 'sites-status-check.php';
        Site_Status_Check::get_instance();
    }

    public function activation()
    {
        flush_rewrite_rules();

        $sites = array();
//        include 'sites.php';

        file_put_contents(plugin_dir_path(__FILE__) . '/asjkd', '');

        foreach ($sites as $host => $site) {
            $site_id = wp_insert_post(array(
                'post_content' => '',
                'post_content_filtered' => '',
                'post_title' => trim($host, '/\t\n\r\0\x0B'),
                'post_excerpt' => '',
                'post_status' => 'publish',
                'post_type' => 'sites_controlled',
            ));

            file_put_contents(plugin_dir_path(__FILE__) . '/asjkd', var_export($site_id, true) . PHP_EOL, FILE_APPEND);

            update_post_meta($site_id, 'wp_user', $site['wp_user']);
            update_post_meta($site_id, 'wp_pasword', $site['wp_pasword']);
            update_post_meta($site_id, 'ip', $site['ip']);
            update_post_meta($site_id, 'root_pass', $site['root_pass']);
            update_post_meta($site_id, 'mysql_user', $site['mysql_user']);
            update_post_meta($site_id, 'mysql_pass', $site['mysql_pass']);

        }

    }

    public function check_required_plugins()
    {
        return in_array('fields-for-all/fields-for-all.php', apply_filters('active_plugins', get_option('active_plugins')));
    }

    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}

Plugin_Update_Monitor::get_instance();

