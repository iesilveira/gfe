<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class GFE_Editor {

    public static function init() {
        add_action( 'admin_menu', [ __CLASS__, 'remove_menus_for_editors' ], 999 );
        add_action( 'admin_init', [ __CLASS__, 'restrict_access' ] );
    }

    public static function remove_menus_for_editors() {
        if ( ! current_user_can( 'editor' ) ) {
            return;
        }

        remove_menu_page( 'edit.php?post_type=page' );
        remove_menu_page( 'plugins.php' );
        remove_menu_page( 'tools.php' );
    }

    public static function restrict_access() {
        if ( ! current_user_can( 'editor' ) ) {
            return;
        }

        global $pagenow;
        $restricted_pages = [ 'plugins.php', 'tools.php' ];

        if ( in_array( $pagenow, $restricted_pages ) ) {
            wp_redirect( admin_url() );
            exit;
        }
    }
}
