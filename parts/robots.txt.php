<?php
/**
 * Created by PhpStorm.
 * User: eugen
 * Date: 24.02.17
 * Time: 11:02
 */

namespace Plugin_Update_Monitor;


class Robots_txt_Monitor_Page
{
    protected static $instance;


    private function __construct()
    {
        add_action('admin_menu', array($this, 'define_page'));
        add_filter('pum_sites', array($this, 'pum_sites'));
    }

    public function pum_sites($sites) {
        include 'sites.php';

        return $sites;
    }

    public function define_page () {
        $parent = add_menu_page( 'Plugin Update Monitor',
            'Plugin Monitor',
            'manage_options',
            'myplugin/myplugin-admin.php',
            array($this, 'display_monitor'),
            'dashicons-visibility', 10 );

        do_action('pum_submenu', $parent);
    }

    public function display_monitor() {

        include_once 'class-wp-sites-list-table.php';
        $myListTable = new My_List_Table();
        ?>
        <div class="wrap">
            <h1>
                <?php echo get_admin_page_title(); ?>
            </h1>
            <?php



            $myListTable->prepare_items();
            $myListTable->display();


            ?>

        </div>


        <?php
    }

    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}

Robots_txt_Monitor_Page::get_instance();