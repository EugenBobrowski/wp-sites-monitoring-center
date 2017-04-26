<?php

/*
Plugin Name: Issue Tracker
Plugin URI: http://wordpress.org/plugins/fields-for-all/
Description: This plugin require fields-for-all.
Author: Eugen Bobrowski
Version: 1.0
Author URI: http://atf.li
*/


namespace Plugin_Update_Monitor;


class Monitor_Page
{
    protected static $instance;


    private function __construct()
    {
        add_filter('pum_sites', array($this, 'pum_sites'));
        add_action('init', array($this, 'sites_post_type'));
        add_filter('manage_sites_controlled_posts_columns', array($this, 'set_custom_edit_sites_columns'));
        add_action('manage_sites_controlled_posts_custom_column', array($this, 'sites_columns'), 10, 2);
        add_filter('post_row_actions', array($this, 'site_row_actions'), 10, 2);
        add_action('load-edit.php', array($this, 'assets'));
        add_action('wp_ajax_refresh_status', array($this, 'refresh_status'));
        add_action('restrict_manage_posts', array($this, 'rc_custom_filter_field'), 99);
        add_filter('request', array($this, 'request_query_rc'), 99);
        $this->access_metabox();
    }

    public function assets()
    {
        if (filter_input(INPUT_GET, 'post_type') != 'sites_controlled') return;
        wp_enqueue_script('sites-monitor', plugin_dir_url(__FILE__) . 'js/script.js');
        wp_localize_script('sites-monitor', 'sites_monitor', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            '_wpnonce' => wp_create_nonce('sites_monitor_refresh_status')

        ));
        wp_enqueue_style('sites-monitor', plugin_dir_url(__FILE__) . 'css/style.css');
    }

    public function pum_sites($sites)
    {
        include 'sites.php';

        return $sites;
    }

    public function sites_post_type()
    {
        register_post_type('sites_controlled', array(
            'public' => true,
            'label' => __('Sites', 'textdomain'),
            'labels' => array(
                'name' => _x('Sites', 'Post type general name', 'textdomain'),
                'singular_name' => _x('Site', 'Post type singular name', 'textdomain'),
                'menu_name' => _x('Sites Monitor', 'Admin Menu text', 'textdomain'),
                'name_admin_bar' => _x('Site', 'Add New on Toolbar', 'textdomain'),
                /*'add_new'               => __( 'Add New', 'textdomain' ),
                'add_new_item'          => __( 'Add New Book', 'textdomain' ),
                'new_item'              => __( 'New Book', 'textdomain' ),
                'edit_item'             => __( 'Edit Book', 'textdomain' ),
                'view_item'             => __( 'View Book', 'textdomain' ),
                'all_items'             => __( 'All Books', 'textdomain' ),
                'search_items'          => __( 'Search Books', 'textdomain' ),
                'parent_item_colon'     => __( 'Parent Books:', 'textdomain' ),
                'not_found'             => __( 'No books found.', 'textdomain' ),
                'not_found_in_trash'    => __( 'No books found in Trash.', 'textdomain' ),
                'featured_image'        => _x( 'Book Cover Image', 'Overrides the “Featured Image” phrase for this post type. Added in 4.3', 'textdomain' ),
                'set_featured_image'    => _x( 'Set cover image', 'Overrides the “Set featured image” phrase for this post type. Added in 4.3', 'textdomain' ),
                'remove_featured_image' => _x( 'Remove cover image', 'Overrides the “Remove featured image” phrase for this post type. Added in 4.3', 'textdomain' ),
                'use_featured_image'    => _x( 'Use as cover image', 'Overrides the “Use as featured image” phrase for this post type. Added in 4.3', 'textdomain' ),
                'archives'              => _x( 'Book archives', 'The post type archive label used in nav menus. Default “Post Archives”. Added in 4.4', 'textdomain' ),
                'insert_into_item'      => _x( 'Insert into book', 'Overrides the “Insert into post”/”Insert into page” phrase (used when inserting media into a post). Added in 4.4', 'textdomain' ),
                'uploaded_to_this_item' => _x( 'Uploaded to this book', 'Overrides the “Uploaded to this post”/”Uploaded to this page” phrase (used when viewing media attached to a post). Added in 4.4', 'textdomain' ),
                'filter_items_list'     => _x( 'Filter books list', 'Screen reader text for the filter links heading on the post type listing screen. Default “Filter posts list”/”Filter pages list”. Added in 4.4', 'textdomain' ),
                'items_list_navigation' => _x( 'Books list navigation', 'Screen reader text for the pagination heading on the post type listing screen. Default “Posts list navigation”/”Pages list navigation”. Added in 4.4', 'textdomain' ),
                'items_list'            => _x( 'Books list', 'Screen reader text for the items list heading on the post type listing screen. Default “Posts list”/”Pages list”. Added in 4.4', 'textdomain' ),*/
            ),
            'supports' => array('title', 'comments'),
            'menu_icon' => 'dashicons-visibility',
            'menu_position' => 10

        ));

        register_taxonomy('sites_tags', 'sites_controlled', array(
            'label' => __('Sites Tags'),
            'rewrite' => array('slug' => 'site_tags'),
            'hierarchical' => true,
            'show_admin_column' => true,
        ));
        register_taxonomy('sites_owner', 'sites_controlled', array(
            'label' => __('Sites Owner'),
            'rewrite' => array('slug' => 'site_owner'),
            'hierarchical' => false,
            'show_admin_column' => true,
        ));


    }


    public function access_metabox()
    {
        add_filter('site_accesses_atf_metabox_fields', array($this, 'fix_descriptions'), 10, 3);

        if (!class_exists('Atf_Metabox')) return;

        new \Atf_Metabox('site_accesses', __('Accesses'), 'sites_controlled', array(
            'wp_user' => array(
                'title' => __('WP Admin'),
                'single' => true,
                'desc' => '<a href="http://{post_title}/wp-admin/">Dashboard</a>',
            ),
            'wp_pasword' => array(
                'title' => __('WP Admin Password'),
                'single' => true,
            ),
            'ip' => array(
                'title' => __('Server IP'),
                'single' => true,
                'desc' => 'ssh root@{value} -p22053',
            ),
            'root_pass' => array(
                'title' => __('Root Password'),
                'single' => true,
            ),
            'mysql_user' => array(
                'title' => __('MySQL User'),
                'single' => true,
            ),
            'mysql_pass' => array(
                'title' => __('MySQL Password'),
                'single' => true,
            ),
        ));
    }

    public function fix_descriptions($fields, $data, $post)
    {
        foreach ($fields as $key => $field) {
            if (empty($field['desc'])) continue;

            $fields[$key]['desc'] = str_replace(array('{post_title}', '{value}'), array($post->post_title, $data[$key]), $field['desc']);
        }
        return $fields;
    }

    function set_custom_edit_sites_columns($columns)
    {
        $first = array_slice($columns, 0, 1, true);
        $last = array_slice($columns, 1, null, true);
        $columns = array_merge($first, array('status' => ''), $last);
        unset($columns['date']);
        return $columns;
    }

    function site_row_actions($actions, $post)
    {
        //check for your post type
        if ($post->post_type == "sites_controlled") {
            /*do you stuff here
            you can unset to remove actions
            and to add actions ex:
            $actions['in_google'] = '<a href="http://www.google.com/?q='.get_permalink($post->ID).'">check if indexed</a>';
            */
            $actions = array_merge(array(
                'visit' => '<a href="http://' . $post->post_title . '">' . __('Visit') . '</a>',
            ),
                $actions
            );
            unset($actions['trash']);
            unset($actions['view']);
        }
        return $actions;
    }

    function sites_columns($column, $post_id)
    {

        switch ($column) {

            case 'status' :
                $this->column_status($post_id);
                break;
            case 'publisher' :
                echo get_post_meta($post_id, 'publisher', true);
                break;

        }
    }

    public function column_status($post_id)
    {
        $status = get_post_meta($post_id, 'status', true);

        if (!is_array($status)) {
            echo '<a href="#" class="status-icon" data-need-check="1"></a>';
            return;
        }

        $class = $this->get_icon_class($status);

        echo '<a href="#" class="' . $class . '" ' .
            'data-site-id="' . $post_id . '" ' .
            'data-online="' . $status['is_online'] . '" ' .
            'data-last-check="' . $status['last_check'] . '" ' .
            'data-now="' . date_i18n('Y-m-d H:i:s') . '" ' .
            'data-last-check-date="' . date_i18n('Y-m-d H:i:s', $status['last_check']) . '" ></a>';
    }

    public function get_icon_class($status)
    {
        $class = 'status-icon';

        if ($status['is_online'] == 200) {
            $class .= ' left-green ';
        } elseif ($status['is_online'] == 301) {
            $class .= ' left-yellow ';
        } else {
            $class .= ' left-red ';
        }

        if ($status['connect']) {
            $class .= ' right-green ';
        } else {
            $class .= ' right-red ';
        }

        return $class;
    }

    public function refresh_status()
    {
        check_admin_referer('sites_monitor_refresh_status');

        $site = absint($_POST['site_id']);

        $check = Site_Status_Check::get_instance();

        $status = $check->check_status($site);

        $status['class'] = $this->get_icon_class($status);

        wp_send_json($status);

        exit;
    }

    function rc_custom_filter_field()
    {
        global $typenow;

        if (in_array($typenow, array('sites_controlled'))) {
            $status = isset($_REQUEST['online_status']) ? $_REQUEST['online_status'] : '';
            ?>
            <select name="online_status">
                <option value="" <?php selected($status, '') ?>>All posts</option>
                <option value="offline" <?php selected($status, 200) ?>>Offline</option>
                <option value="200" <?php selected($status, 200) ?>>Online</option>
                <option value="301" <?php selected($status, 301) ?>>301</option>
            </select>
            <?php
        }
    }

    function request_query_rc($vars)
    {
        global $typenow;

        if (is_admin() && in_array($typenow, array('sites_controlled')) && !empty($_GET['online_status'])) {
            $status = absint($_GET['online_status']);
            $custom_args = array(
                'meta_query' => array(
                    array(
                        'key' => 'status_online',
                        'value' => $status,
                        'compare' => 'IN',
                    ),
                ),
            );

            if ($status == 'offline') {
                $custom_args = array(
                    'meta_query' => array(
                        array(
                            'key' => 'status_online',
                            'value' => array(200, 301),
                            'compare' => 'NOT IN',
                        ),
                    ),
                );
            }

            $vars = array_merge($vars, $custom_args);
        }

        return $vars;
    }

    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}

Monitor_Page::get_instance();