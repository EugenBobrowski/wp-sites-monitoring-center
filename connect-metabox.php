<?php

/**
 * Created by PhpStorm.
 * User: eugen
 * Date: 03.03.17
 * Time: 18:22
 */

namespace Plugin_Update_Monitor;

class Monitoring_Connect_Metabox
{

    protected static $instance;
    private $sites_info;

    private function __construct()
    {
        add_action('load-post.php', array($this, 'init'));
        add_action('wp_ajax_connetct_metabox', array($this, 'ajax'));
    }

    public function init()
    {
        add_action('add_meta_boxes', array($this, 'add_metabox'));
        add_action('admin_enqueue_scripts', array($this, 'assets'));
    }

    public function assets()
    {
        wp_enqueue_script('connect-metabox', URL . 'js/connect-metabox.js');
        wp_localize_script('connect-metabox', 'connect_metabox', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('connect_motherfucker')
        ));
    }

    public function add_metabox()
    {
        add_meta_box(
            'metabox_id',
            __('Monitoring Connect', 'textdomain'),
            array($this, 'metabox'),
            'sites_controlled',
            'side',
            'low'
        );
    }

    public function metabox($post)
    {
        $connect = $this->get_sites_info($post);

        if (!$connect['connected']) {
            $this->metabox_connecting_form($connect);
            return;
        }

        $this->metabox_connected_content($post);

    }

    private function metabox_connected_content($post) {
        $post = get_post($post);
        $connect = $this->get_sites_info($post);
        $this->metabox_plugins_list($connect);
    }

    private function get_sites_info($post)
    {
        $connect = get_post_meta($post->ID, 'connect', true);
        $connect = wp_parse_args($connect, array(
            'connected' => false,
            'connecting_json' => '',
            'plugins' => array(),
            'plugins_active' => '',
            'last_info' => 0
        ));

        $status = get_post_meta($post->ID, 'status', true);

        if (!$connect['connected']) {
            $status['connect'] = false;
            return $connect;
        }

        $status['connect'] = true;
        update_post_meta($post->ID, 'status', $status);

        if ($connect['last_info'] < (time() - 60)) {
            $site_info = $this->make_connect(json_decode($connect['connecting_json'], true), 'site_info');
            if (!is_array($site_info)) {
                $connect['connected'] = false;
            } else {
                $connect = array_merge($connect, $site_info);
                $connect['last_info'] = time();
            }

            echo 'updated';
            update_post_meta($post->ID, 'connect', $connect);
        }

        return $connect;
    }

    private function metabox_plugins_list($sites_info)
    {
        /*
        'TextDomain' => 'amazon.affiliate',
        'DomainPath' => '',
        'Network' => false,
        'AuthorName' => 'Marakas',
        */
        ?>
        <table id="curent_site_plugins" class="wp-list-table widefat plugins">
            <thead>
            <tr>
                <td id="cb" class="manage-column column-cb check-column"><label class="screen-reader-text"
                                                                                for="cb-select-all-1">Select All</label><input
                            id="cb-select-all-1" type="checkbox"></td>
                <th scope="col" id="name" class="manage-column column-name column-primary">Plugin</th>
                <th scope="col" id="description" class="manage-column column-description">Description</th>
            </tr>
            </thead>

            <tbody id="the-list">
            <?php
            foreach ($sites_info['plugins'] as $plugin_slug => $plugin) {
                $active = in_array($plugin_slug, $sites_info['active_plugins']);
                ?>
                <tr class="<?php echo ($active) ? 'active' : 'inactive' ?>"
                    data-slug="<?php echo plugin_basename($plugin_slug); ?>"
                    data-plugin="<?php echo $plugin_slug; ?>">
                    <th scope="row" class="check-column">
                        <label class="screen-reader-text" for="checkbox_4ad876a7bfadae26522bad3a2676b0dd">Select Fields
                            For All</label>
                        <input type="checkbox" name="checked[]" value="fields-for-all/fields-for-all.php"
                               id="checkbox_4ad876a7bfadae26522bad3a2676b0dd"></th>
                    <td class="plugin-title column-primary">
                        <strong><?php echo $plugin['Name']; ?></strong>
                        <div class="row-actions visible">
                            <?php //if ($active) { ?>
                                <span class="deactivate" <?php echo ($active) ? '' : 'style="display:none;"'; ?>>
                                <a href="plugins.php?action=deactivate&amp;plugin=fields-for-all%2Ffields-for-all.php&amp;plugin_status=all&amp;paged=1&amp;s&amp;_wpnonce=d23338243b"
                                   class="deactivate-remote-plugin"
                                   data-action="plugin_deactivate"
                                   aria-label="Deactivate Fields For All">Deactivate</a> |
                                </span>
                            <?php //} else { ?>
                                <span class="activate"  <?php echo ($active) ? 'style="display:none;"' : ''; ?>>
                                    <a href="plugins.php?action=activate&amp;plugin=plugin-update-monitor%2Fsites-post-type.php&amp;plugin_status=all&amp;paged=1&amp;s&amp;_wpnonce=4b60b70c4c"
                                       class="edit activate-remote-plugin"
                                       data-action="plugin_activate"
                                       aria-label="Activate Issue Tracker">Activate</a> |
                                </span>
                            <?php //} ?>


                            <!--<span class="edit">
                                    <a href="plugin-editor.php?file=plugin-update-monitor/sites-post-type.php"
                                       class="edit" aria-label="Edit Issue Tracker">Edit</a> | </span>
                            <span class="delete">
                                    <a href="plugins.php?action=delete-selected&amp;checked%5B0%5D=plugin-update-monitor%2Fsites-post-type.php&amp;plugin_status=all&amp;paged=1&amp;s&amp;_wpnonce=5e05ce2d4f"
                                       class="delete" aria-label="Delete Issue Tracker">Delete</a></span>-->

                        </div>
                        <button type="button" class="toggle-row"><span
                                    class="screen-reader-text">Show more details</span></button>
                    </td>
                    <td class="column-description desc">
                        <div class="plugin-description"><?php echo wpautop($plugin['Description']); ?></div>
                        <div class="active second plugin-version-author-uri">
                            <?php
                            $vau = array(
                                'version' => sprintf(__('Version %s'), $plugin['Version']),
                                'author' => $plugin['Author'],
                            );

                            if (!empty($plugin['AuthorURI']))
                                $vau['author'] = sprintf('<a href="%s">%s</a>',
                                    $plugin['AuthorURI'], $plugin['Author']);

                            if (!empty($plugin['PluginURI']))
                                $vau['site'] = sprintf('<a href="%s">Visit plugin site</a>',
                                    $plugin['PluginURI']);

                            echo join(' | ', $vau);

                            ?>
                        </div>
                    </td>
                </tr>
                <?php
            }


            ?>
            </tbody>

            <tfoot>
            <tr>
                <td class="manage-column column-cb check-column"><label class="screen-reader-text"
                                                                        for="cb-select-all-2">Select All</label><input
                            id="cb-select-all-2" type="checkbox"></td>
                <th scope="col" class="manage-column column-name column-primary">Plugin</th>
                <th scope="col" class="manage-column column-description">Description</th>
            </tr>
            </tfoot>

        </table>

        <?php
    }

    private function metabox_connecting_form($connect)
    {
        ?>
        <div class="atf-fields">
        <div class="connecting-field">
            <?php
            \AtfHtmlHelper::textarea(array(
                'id' => 'connection_json',
                'name' => 'connect',
                'rows' => '3',
                'value' => $connect['connecting_json'],
            )); ?>


            <button id="connect_wp_site" class="button button-primary">Connect</button>

            <span class="spinner"
                  style="vertical-align: top; float: none;"></span>
        </div>
        </div>

        <?php
    }

    public function ajax()
    {
        check_admin_referer('connect_motherfucker');

        if (empty($_POST['todo'])) return;

        $post_id = absint($_POST['post_id']);
        $action = trim($_POST['todo']);

        if ($action == 'connect') {
            $json = trim($_POST['json']);
            $args = stripcslashes($json);
            $args = json_decode($args, true);

            $response = $this->make_connect($args, 'connect');

            if ($response['connected']) {
                $response['connecting_json'] = $json;
                update_post_meta($post_id, 'connect', $response);

                ob_start();

                $this->metabox_connected_content($post_id);

                wp_send_json(array('success' => true, 'html' => ob_get_clean()));

            }
            exit;
        }

        $connect = get_post_meta($post_id, 'connect', true);


        if (in_array($action, array('plugin_activate', 'plugin_deactivate'))) {
            $plugin = trim($_POST['plugin']);

            $response = $this->make_connect(json_decode($connect['connecting_json'], true), $action, array(
                'plugin' => $plugin
            ));

            wp_send_json($response);

            var_dump( json_decode($connect['connecting_json'], true) , $response) ;
            exit;
        }

        echo 'Nothing to do!';

        exit;
    }

    public function make_connect($json, $action, $_post = array())
    {
        $agent = "Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $json['path']);
        curl_setopt($ch, CURLOPT_REFERER, $json['path']);
        curl_setopt($ch, CURLOPT_USERAGENT, $agent);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_VERBOSE, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSLVERSION, 3);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, array_merge(array(
            'pass' => $json['pass'],
            'action' => $action
        ), $_post));
        $page = curl_exec($ch);

//        var_dump($page);

        //echo curl_error($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if ($httpcode == 200) return json_decode($page, true);

        return $httpcode;

    }


    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}

Monitoring_Connect_Metabox::get_instance();