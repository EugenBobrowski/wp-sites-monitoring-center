<?php
/**
 * Created by PhpStorm.
 * User: eugen
 * Date: 17.11.16
 * Time: 19:10
 */

namespace Plugin_Update_Monitor;

require_once( path_join(plugin_dir_path(__FILE__), 'class-wp-list-table.php') );

class My_List_Table extends WP_List_Table {

    public $sites_data = array();
    public $plugins = array(
//        'simple-review-post' => array(
//            'name' => 'Simple Review Post'
//        ),
//        'amazon-affiliate' => array(
//            'name' => 'Amazon Affiliate',
//            'version_callback' => array('$this', 'check_version'),
//            'version_callbeck_args' => array(
//                'matadata_'
//            ),
//        ),
        'item' => array(
            'name' => 'item',
        ),
        'robots_txt' => array(
            'name' => 'Robots.txt info',
        ),

    );

    public function __construct( $args = array() ) {
        parent::__construct( array(
            'singular' => 'site',
            'plural'   => 'sites',
            'screen'   => isset( $args['screen'] ) ? $args['screen'] : null,
        ) );

        $this->is_site_users = 'site-users-network' === $this->screen->id;

        if ( $this->is_site_users )
            $this->site_id = isset( $_REQUEST['id'] ) ? intval( $_REQUEST['id'] ) : 0;
    }

    function get_columns(){
        $columns = array(
            'cb'       => '<input type="checkbox" />',
            'site' => 'Site',
        );
        foreach ($this->plugins as $plugin_slug=>$plugin_data) {
            $columns[$plugin_slug] = $plugin_data['name'];
        }

        return $columns;
    }

    protected function get_sortable_columns() {
        $c = array(
            'site' => 'site',
        );

        return $c;
    }

    function prepare_items() {
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = array();
        $this->_column_headers = array($columns, $hidden, $sortable);
        $this->sites_data = apply_filters('pum_sites', $this->sites_data);
        foreach ($this->sites_data as $site=>$data) {
            $this->items[$site] = array_merge(array(
                'site' => $site,
            ), $data);
            $this->items[$site]['plugins'] = array();
            foreach ($this->plugins as $plugin_slug => $plugin) {
                $this->items[$site]['plugins'][$plugin_slug] = '$plugin_slug';

            }
        }
    }

    public function column_cb( $site ) {
         ?>
            <label class="screen-reader-text" for="cb-select-<?php $site['site']; ?>"><?php
                printf( __( 'Select %s' ), _draft_or_post_title() );
                ?></label>
            <input id="cb-select-<?php the_ID(); ?>" type="checkbox" name="post[]" value="<?php the_ID(); ?>" />
            <div class="locked-indicator"></div>
        <?php
    }

    function column_default( $item, $column_name ) {
        switch( $column_name ) {
            case 'site':
//            case 'amazon-affiliate':
            case 'wp_pasword':
                return $item[ $column_name ];

            default:
//                return $item[ $column_name ];
//                return $column_name;

                return print_r( $item, true ) ; //Show the whole array for troubleshooting purposes
        }
    }

    function column_site ( $item ) {
        return $item['site'];
    }
    function column_robots_txt ( $item ) {
        $all = get_transient('sites_robots_txt_cache');
        if (!$all || empty($all[$item['site']])) {
            $robots_txt = file_get_contents('http://' . trim($item['site'], '/') . '/robots.txt');
            if (!is_array($all)) $all = array();
            $all[$item['site']] = $robots_txt;
            set_transient('sites_robots_txt_cache', $all, 240);
        } else {
            $robots_txt = $all[$item['site']];
        }



        if (strpos($robots_txt, 'User-agent: Mozilla/5.0 (compatible;contxbot/1.0)') !== false) $r = 'Amazon crawler';
        else $r = var_export(strpos($robots_txt, 'User-agent: Mozilla/5.0 (compatible;contxbot/1.0)'), true) . '<hr />' . $robots_txt;
        return $r;
    }

}