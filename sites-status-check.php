<?php

namespace Plugin_Update_Monitor;

class Site_Status_Check
{

    protected static $instance;



    private function __construct()
    {
        if (!wp_next_scheduled('sites_status_check')) {
            wp_schedule_event(time(), 'hourly', 'sites_status_check');
        }
        add_action('sites_status_check', array($this, 'cron'));

    }

    public function cron()
    {
        $sites = get_posts(array(
                'numberposts' => -1,
                'post_type' => 'sites_controlled',
            )
        );

        $time_out = count($sites) * 10;

        set_time_limit($time_out);
        ini_set("default_socket_timeout", $time_out . "");

        foreach ($sites as $site) {
            $this->check_status($site);
        }

        file_put_contents(plugin_dir_path(__FILE__) . 'sites.txt', date('l jS \of F Y h:i:s A') . PHP_EOL, FILE_APPEND);
    }

    public function check_status($site)
    {
        $site = get_post($site);
        $status = array();

        $status = get_post_meta($site->ID, 'status', true);

        if (!is_array($status)) $status = array('connect' => false);

        $status['is_online'] = $this->check_is_online($site->post_title);
        $status['last_check'] = time();

        update_post_meta($site->ID, 'status', $status);
        update_post_meta($site->ID, 'status_online', $status['is_online']);
        update_post_meta($site->ID, 'status_last_check', $status['last_check']);

        return $status;

    }

    public function check_is_online($url)
    {
        $agent = "Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_USERAGENT, $agent);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_VERBOSE, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSLVERSION, 3);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        $page = curl_exec($ch);
        //echo curl_error($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $httpcode;
//        if ($httpcode == 200) return true;
//        if ($httpcode >= 200 && $httpcode < 300) return true;
//        else return false;
    }

    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}