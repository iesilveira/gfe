<?php
/**
 * Plugin Name: Gestor de Funções para Editores - SES/MG
 * Description: Restringe e personaliza o acesso de usuários com função de Editor no WordPress.
 * Version: 1.0.0
 * Author: Ismael Elói (ASCOM)
 * Author URI: https://www.saude.mg.gov.br
 * License: GPL2
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

define( 'SMPM_VERSION', '1.0.0' );
define( 'SMPM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SMPM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once SMPM_PLUGIN_DIR . 'includes/class-saude-mg-permission-manager.php';

function run_saude_mg_permission_manager() {
    $plugin = new Saude_MG_Permission_Manager();
    $plugin->run();
}
run_saude_mg_permission_manager();

register_deactivation_hook( __FILE__, 'saude_mg_permission_manager_deactivate' );
function saude_mg_permission_manager_deactivate() {
    // Limpeza de dados ou opções do plugin, se necessário.
}

register_uninstall_hook( __FILE__, 'saude_mg_permission_manager_uninstall' );
function saude_mg_permission_manager_uninstall() {
    // Limpeza completa de dados e opções do plugin.
    // Ex: delete_option( 'smpm_permissions' );
}


