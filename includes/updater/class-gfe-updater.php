<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class GFE_Updater {

    private static $repo = 'iesilveira/gfe';
    private static $api_url = 'https://api.github.com/repos/iesilveira/gfe/releases/latest';

    public static function init() {
        add_filter( 'site_transient_update_plugins', [ __CLASS__, 'check_for_update' ] );
        add_filter( 'plugins_api', [ __CLASS__, 'plugins_api_handler' ], 10, 3 );
    }

    public static function check_for_update( $transient ) {
        if ( empty( $transient->checked ) ) {
            return $transient;
        }

        $response = wp_remote_get( self::$api_url, [ 'headers' => [ 'User-Agent' => 'WordPress' ] ] );
        if ( is_wp_error( $response ) ) {
            return $transient;
        }

        $data = json_decode( wp_remote_retrieve_body( $response ) );
        if ( ! isset( $data->tag_name ) ) {
            return $transient;
        }

        $latest_version = ltrim( $data->tag_name, 'v' );
        if ( version_compare( $latest_version, GFE_VERSION, '>' ) ) {
            $plugin_file = 'gestor-funcoes-editores/gestor-funcoes-editores.php';
            $transient->response[ $plugin_file ] = (object) [
                'slug' => 'gestor-funcoes-editores',
                'new_version' => $latest_version,
                'url' => 'https://github.com/' . self::$repo,
                'package' => $data->zipball_url
            ];
        }

        return $transient;
    }

    public static function plugins_api_handler( $res, $action, $args ) {
        if ( 'plugin_information' !== $action || 'gestor-funcoes-editores' !== $args->slug ) {
            return $res;
        }

        $response = wp_remote_get( self::$api_url, [ 'headers' => [ 'User-Agent' => 'WordPress' ] ] );
        if ( is_wp_error( $response ) ) {
            return $res;
        }

        $data = json_decode( wp_remote_retrieve_body( $response ) );
        if ( ! isset( $data->tag_name ) ) {
            return $res;
        }

        $res = (object) [
            'name' => 'Gestor de Funções para Editores',
            'slug' => 'gestor-funcoes-editores',
            'version' => ltrim( $data->tag_name, 'v' ),
            'author' => '<a href="https://github.com/iesilveira">Ismael Eloi</a>',
            'homepage' => 'https://github.com/' . self::$repo,
            'download_link' => $data->zipball_url,
            'sections' => [
                'description' => 'Plugin para gerenciar funções de editores com atualização automática via GitHub.'
            ]
        ];

        return $res;
    }
}
