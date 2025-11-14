<?php
/**
 * Plugin Name: Gestor de Funções para Editores - SES/MG
 * Description: Restringe e personaliza funções para usuários com a role Editor.
 * Version: 2.2
 * Author: Ismael Eloi da Silveira Silva
 * Text Domain: gfe
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Segurança
}

define( 'GFE_PATH', plugin_dir_path( __FILE__ ) );
define( 'GFE_URL', plugin_dir_url( __FILE__ ) );

define( 'GFE_VERSION', '2.2' );

equire_once GFE_PATH . 'includes/class-gfe-loader.php';

add_action( 'plugins_loaded', [ 'GFE_Loader', 'init' ] );
