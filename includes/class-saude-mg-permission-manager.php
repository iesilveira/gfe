<?php

class Saude_MG_Permission_Manager {

    protected $loader;
    protected $plugin_name;
    protected $version;

    public function __construct() {
        $this->plugin_name = 'saude-mg-permission-manager';
        $this->version = SMPM_VERSION;

        $this->load_dependencies();
        $this->define_admin_hooks();
    }

    private function load_dependencies() {
        require_once SMPM_PLUGIN_DIR . 'includes/class-saude-mg-permission-manager-loader.php';
        require_once SMPM_PLUGIN_DIR . 'admin/class-saude-mg-permission-manager-admin.php';

        $this->loader = new Saude_MG_Permission_Manager_Loader();
    }

    private function define_admin_hooks() {
        $plugin_admin = new Saude_MG_Permission_Manager_Admin( $this->get_plugin_name(), $this->get_version() );

        $this->loader->add_action( 'show_user_profile', $plugin_admin, 'smpm_add_permission_field' );
        $this->loader->add_action( 'edit_user_profile', $plugin_admin, 'smpm_add_permission_field' );
        $this->loader->add_action( 'personal_options_update', $plugin_admin, 'smpm_save_permission_field' );
        $this->loader->add_action( 'edit_user_profile_update', $plugin_admin, 'smpm_save_permission_field' );
        $this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
        $this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
        
        // Permission control hooks
        $this->loader->add_action( 'admin_init', $plugin_admin, 'smpm_restrict_editor_access' );
        $this->loader->add_filter( 'user_has_cap', $plugin_admin, 'smpm_filter_user_capabilities', 10, 3 );
        $this->loader->add_action( 'admin_menu', $plugin_admin, 'smpm_remove_admin_menus', 9999 );
        $this->loader->add_action( 'load-edit.php', $plugin_admin, 'smpm_restrict_posts_list' );
        $this->loader->add_action( 'load-edit.php', $plugin_admin, 'smpm_restrict_pages_list' );
        
        // Hooks para filtrar categorias na página de categorias
        $this->loader->add_filter( 'get_terms', $plugin_admin, 'smpm_filter_categories_list', 10, 3 );
        
        // Hooks para ocultar elementos da barra superior
        
        // Hooks para limpar o painel
        $this->loader->add_action( 'wp_dashboard_setup', $plugin_admin, 'smpm_remove_dashboard_widgets' );
        $this->loader->add_action( 'admin_head', $plugin_admin, 'smpm_hide_screen_options' );
        $this->loader->add_action( 'admin_footer', $plugin_admin, 'smpm_custom_dashboard_content' );
        
        // Hook para restrição de acesso direto a URLs
        $this->loader->add_action( 'current_screen', $plugin_admin, 'smpm_restrict_direct_url_access' );
        $this->loader->add_action( 'admin_init', $plugin_admin, 'smpm_block_direct_access' );
        
        // Hook para página de log de administradores
        $this->loader->add_action( 'admin_menu', $plugin_admin, 'smpm_add_admin_log_page' );
        
        // Hooks para filtrar categorias na criação/edição de posts
// Hook para corrigir capacidades de criação de posts
        $this->loader->add_filter( 'user_has_cap', $plugin_admin, 'smpm_fix_new_post_capabilities', 10, 3 );

        // Interface restrita e contadores
        $this->loader->add_action( 'current_screen', $plugin_admin, 'smpm_remove_editor_help', 9999 );
        $this->loader->add_filter( 'views_edit-post', $plugin_admin, 'smpm_filter_restricted_post_views', 9999, 1 );
        $this->loader->add_filter( 'views_edit-page', $plugin_admin, 'smpm_filter_restricted_post_views', 9999, 1 );

        $this->loader->add_action( 'admin_footer', $plugin_admin, 'smpm_remove_elementor_ai_from_media', 9999 );
        $this->loader->add_action( 'wp_before_admin_bar_render', $plugin_admin, 'smpm_hide_admin_bar_items', 9999 );
        $this->loader->add_filter( 'pre_get_posts', $plugin_admin, 'smpm_filter_posts_query', 9999, 1 );
        $this->loader->add_filter( 'posts_clauses', $plugin_admin, 'smpm_filter_posts_clauses', 9999, 2 );
        $this->loader->add_filter( 'get_terms_args', $plugin_admin, 'smpm_filter_post_categories', 9999, 2 );

        $this->loader->add_action( 'admin_head', $plugin_admin, 'smpm_output_editor_menu_styles', 0 );

        $this->loader->add_filter( 'terms_clauses', $plugin_admin, 'smpm_restrict_visible_categories', 9999, 3 );
        $this->loader->add_action( 'admin_menu', $plugin_admin, 'smpm_add_settings_page', 9999 );
        $this->loader->add_action( 'admin_init', $plugin_admin, 'smpm_register_settings' );
        $this->loader->add_filter( 'plugin_action_links_' . plugin_basename( SMPM_PLUGIN_DIR . 'saude-mg-permission-manager.php' ), $plugin_admin, 'smpm_add_plugin_action_links', 10, 1 );
    }

    public function run() {
        $this->loader->run();
    }

    public function get_plugin_name() {
        return $this->plugin_name;
    }

    public function get_loader() {
        return $this->loader;
    }

    public function get_version() {
        return $this->version;
    }
}

