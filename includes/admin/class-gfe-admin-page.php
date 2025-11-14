<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class GFE_Admin_Page {

    public static function init() {
        add_action( 'admin_menu', [ __CLASS__, 'add_settings_page' ] );
    }

    public static function add_settings_page() {
        add_menu_page(
            __( 'Configurações GFE', 'gfe' ),
            __( 'GFE', 'gfe' ),
            'manage_options',
            'gfe-settings',
            [ __CLASS__, 'render_settings_page' ],
            'dashicons-admin-generic',
            81
        );
    }

    public static function render_settings_page() {
        echo '<div class="wrap"><h1>' . __( 'Configurações do Gestor de Funções para Editores', 'gfe' ) . '</h1>';
        echo '<p>' . __( 'Aqui você poderá configurar as opções do plugin.', 'gfe' ) . '</p>';
        echo '</div>';
    }
}
