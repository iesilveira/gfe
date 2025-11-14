<?php

class Saude_MG_Permission_Manager_Admin {

    private $plugin_name;
    private $version;

    public function __construct( $plugin_name, $version ) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    public function enqueue_styles() {
        wp_enqueue_style( $this->plugin_name, SMPM_PLUGIN_URL . 'admin/css/saude-mg-permission-manager-admin.css', array(), $this->version, 'all' );
    }

    public function enqueue_scripts() {
        wp_enqueue_script( $this->plugin_name, SMPM_PLUGIN_URL . 'admin/js/saude-mg-permission-manager-admin.js', array( 'jquery' ), $this->version, false );
    }

    public function smpm_add_permission_field( $user ) {
        if ( in_array( 'editor', (array) $user->roles ) ) {
            ?>
            <script type="text/javascript">
                jQuery(document).ready(function($) {
                    var permissionSection = $("#smpm-permission-section");
                    $("#your-profile h2:first").after(permissionSection);
                });
            </script>
            <div id="smpm-permission-section">
                <h3><?php _e( 'Gerenciamento de Permissões SES/MG', 'saude-mg-permission-manager' ); ?></h3>
                <table class="form-table">
                    <tr>
                        <th><label for="smpm_manage_permissions"><?php _e( 'Permissões do Editor', 'saude-mg-permission-manager' ); ?></label></th>
                        <td>
                            <button type="button" id="smpm_manage_permissions_button" class="button button-primary">
                                <?php _e( 'Gerenciar permissões', 'saude-mg-permission-manager' ); ?>
                            </button>
                            <p class="description">
                                <?php _e( 'Clique para gerenciar as permissões de páginas, categorias e plugins para este usuário.', 'saude-mg-permission-manager' ); ?>
                            </p>
                        </td>
                    </tr>
                </table>
            </div>
            <?php
            wp_nonce_field( 'smpm_save_permissions', 'smpm_permissions_nonce' );
            $this->render_permission_modal( $user );
        }
    }

    public function smpm_save_permission_field( $user_id ) {
        if ( ! current_user_can( 'edit_user', $user_id ) ) {
            return $user_id;
        }

        if ( ! isset( $_POST['smpm_permissions_nonce'] ) || ! wp_verify_nonce( $_POST['smpm_permissions_nonce'], 'smpm_save_permissions' ) ) {
            return $user_id;
        }

        // Registra as alterações antes de salvar
        $this->smpm_log_permission_changes( $user_id );

        // Salvar permissões de páginas
        $allowed_pages = isset( $_POST['smpm_allowed_pages'] ) ? array_map( 'absint', $_POST['smpm_allowed_pages'] ) : array();
        update_user_meta( $user_id, 'smpm_allowed_pages', $allowed_pages );

        // Salvar permissões de categorias
        $allowed_categories = isset( $_POST['smpm_allowed_categories'] ) ? array_map( 'absint', $_POST['smpm_allowed_categories'] ) : array();
        update_user_meta( $user_id, 'smpm_allowed_categories', $allowed_categories );
    }

    private function render_permission_modal( $user ) {
        $allowed_pages = get_user_meta( $user->ID, 'smpm_allowed_pages', true );
        $allowed_categories = get_user_meta( $user->ID, 'smpm_allowed_categories', true );

        if ( ! is_array( $allowed_pages ) ) $allowed_pages = array();
        if ( ! is_array( $allowed_categories ) ) $allowed_categories = array();

        // Get all pages
        $pages = get_posts( array(
            'post_type'      => 'page',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
            'post_status'    => 'publish',
            'suppress_filters' => false // Important for WPML compatibility
        ) );

        // Get all categories
        $categories = get_categories( array(
            'orderby'    => 'name',
            'order'      => 'ASC',
            'hide_empty' => 0
        ) );

        ?>
        <div id="smpm-permission-modal" class="smpm-modal" style="display: none;">
            <div class="smpm-modal-content">
                <span class="smpm-close-button">&times;</span>
                <h2><?php _e( 'Gestor de Funções - Saúde MG', 'saude-mg-permission-manager' ); ?></h2>
                <p>Usuário: <?php echo esc_html( $user->display_name ); ?></p>

                <div id="smpm-permission-form">
                    <!-- Páginas -->
                    <div class="smpm-section">
                        <h3>[Páginas] ▾</h3>
                        <p><label>🔍 Buscar páginas: <input type="text" class="smpm-search" data-target="smpm-pages-list"></label></p>
                        <button type="button" class="smpm-select-all" data-target="smpm-pages-list">Marcar todos</button>
                        <button type="button" class="smpm-deselect-all" data-target="smpm-pages-list">Desmarcar todos</button>
                        <ul id="smpm-pages-list" class="smpm-list">
                            <?php foreach ( $pages as $page ) : ?>
                                <li>
                                    <label>
                                        <input type="checkbox" name="smpm_allowed_pages[]" value="<?php echo esc_attr( $page->ID ); ?>" <?php checked( in_array( $page->ID, $allowed_pages ), true ); ?>>
                                        <?php echo esc_html( $page->post_title ); ?>
                                    </label>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                    <!-- Categorias de Posts -->
                    <div class="smpm-section">
                        <h3>[Posts] ▾</h3>
                        <p><label>🔍 Buscar categorias: <input type="text" class="smpm-search" data-target="smpm-categories-list"></label></p>
                        <button type="button" class="smpm-select-all" data-target="smpm-categories-list">Marcar todos</button>
                        <button type="button" class="smpm-deselect-all" data-target="smpm-categories-list">Desmarcar todos</button>
                        <ul id="smpm-categories-list" class="smpm-list">
                            <?php foreach ( $categories as $category ) : ?>
                                <li>
                                    <label>
                                        <input type="checkbox" name="smpm_allowed_categories[]" value="<?php echo esc_attr( $category->term_id ); ?>" <?php checked( in_array( $category->term_id, $allowed_categories ), true ); ?>>
                                        <?php echo esc_html( $category->name ); ?>
                                    </label>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                    <button type="button" class="button button-primary" id="smpm-save-permissions"><?php _e( 'Salvar Permissões', 'saude-mg-permission-manager' ); ?></button>
                </div>
            </div>
        </div>
        <?php
    }

    public function smpm_restrict_editor_access() {
        $current_user = wp_get_current_user();
        
        // Só aplica restrições para usuários com função de Editor
        if ( ! in_array( 'editor', (array) $current_user->roles ) ) {
            return;
        }

        global $pagenow;

        // Restrições para páginas
        if ( $pagenow == 'post.php' || $pagenow == 'edit.php' ) {
            $post_type = isset( $_GET['post_type'] ) ? $_GET['post_type'] : 'post';
            
            if ( $post_type == 'page' ) {
                $this->smpm_restrict_page_access( $current_user->ID );
            } elseif ( $post_type == 'post' ) {
                $this->smpm_restrict_post_access( $current_user->ID );
            }
        }
    }

    private function smpm_restrict_page_access( $user_id ) {
        $allowed_pages = get_user_meta( $user_id, 'smpm_allowed_pages', true );
        
        if ( ! is_array( $allowed_pages ) ) {
            $allowed_pages = array();
        }

        if ( isset( $_GET['post'] ) ) {
            $post_id = absint( $_GET['post'] );
            $post = get_post( $post_id );
            
            if ( $post && $post->post_type == 'page' && ! in_array( $post_id, $allowed_pages ) ) {
                wp_die( __( 'Você não tem permissão para acessar esta página.', 'saude-mg-permission-manager' ) );
            }
        }
    }

    private function smpm_restrict_post_access( $user_id ) {
        $allowed_categories = get_user_meta( $user_id, 'smpm_allowed_categories', true );
        
        if ( ! is_array( $allowed_categories ) ) {
            $allowed_categories = array();
        }

        if ( isset( $_GET['post'] ) ) {
            $post_id = absint( $_GET['post'] );
            $post_categories = wp_get_post_categories( $post_id );
            
            $has_allowed_category = false;
            foreach ( $post_categories as $cat_id ) {
                if ( in_array( $cat_id, $allowed_categories ) ) {
                    $has_allowed_category = true;
                    break;
                }
            }
            
            if ( ! $has_allowed_category && ! empty( $allowed_categories ) ) {
                wp_die( __( 'Você não tem permissão para acessar este post.', 'saude-mg-permission-manager' ) );
            }
        }
    }


    public function smpm_filter_user_capabilities( $allcaps, $caps, $args ) {
        $current_user = wp_get_current_user();
        
        // Só aplica filtros para usuários com função de Editor
        if ( ! in_array( 'editor', (array) $current_user->roles ) ) {
            return $allcaps;
        }

        // Filtrar capacidades relacionadas a páginas
        if ( isset( $args[0] ) && ( $args[0] == 'edit_page' || $args[0] == 'delete_page' ) && isset( $args[2] ) ) {
            $post_id = $args[2];
            $allowed_pages = get_user_meta( $current_user->ID, 'smpm_allowed_pages', true );
            
            // Por padrão, bloqueia acesso a todas as páginas
            if ( ! is_array( $allowed_pages ) || ! in_array( $post_id, $allowed_pages ) ) {
                $allcaps['edit_pages'] = false;
                $allcaps['delete_pages'] = false;
                $allcaps['edit_page'] = false;
                $allcaps['delete_page'] = false;
            }
        }

        // Filtrar capacidades relacionadas a posts
        if ( isset( $args[0] ) && ( $args[0] == 'edit_post' || $args[0] == 'delete_post' ) && isset( $args[2] ) ) {
            $post_id = $args[2];
            $post_categories = wp_get_post_categories( $post_id );
            $allowed_categories = get_user_meta( $current_user->ID, 'smpm_allowed_categories', true );
            
            // Por padrão, bloqueia acesso a todos os posts
            $has_allowed_category = false;
            if ( is_array( $allowed_categories ) && ! empty( $allowed_categories ) ) {
                foreach ( $post_categories as $cat_id ) {
                    if ( in_array( $cat_id, $allowed_categories ) ) {
                        $has_allowed_category = true;
                        break;
                    }
                }
            }
            
            if ( ! $has_allowed_category ) {
                $allcaps['edit_posts'] = false;
                $allcaps['delete_posts'] = false;
                $allcaps['edit_post'] = false;
                $allcaps['delete_post'] = false;
            }
        }

        // Bloquear acesso geral a páginas se não tem nenhuma permitida
        $allowed_pages = get_user_meta( $current_user->ID, 'smpm_allowed_pages', true );
        if ( ! is_array( $allowed_pages ) || empty( $allowed_pages ) ) {
            $allcaps['edit_pages'] = false;
            $allcaps['delete_pages'] = false;
        }

        // Bloquear acesso geral a posts se não tem nenhuma categoria permitida
        $allowed_categories = get_user_meta( $current_user->ID, 'smpm_allowed_categories', true );
        if ( ! is_array( $allowed_categories ) || empty( $allowed_categories ) ) {
            $allcaps['edit_posts'] = false;
            $allcaps['delete_posts'] = false;
        }

        return $allcaps;
    }

    public function smpm_remove_admin_menus() {
        $current_user = wp_get_current_user();
        
        // Só aplica para usuários com função de Editor
        if ( ! in_array( 'editor', (array) $current_user->roles ) ) {
            return;
        }
        // Remove menus específicos sempre
        remove_menu_page( 'themes.php' );     // Modelos
        remove_menu_page( 'profile.php' );    // Perfil
        remove_menu_page( 'tools.php' );      // Ferramentas
        remove_submenu_page( 'edit.php
', 'edit-tags.php?taxonomy=category' ); // Categorias (dentro de Posts)
        remove_submenu_page( 'edit.php
', 'edit-tags.php?taxonomy=post_tag' ); // Tags (dentro de Posts)
        
        // Adiciona CSS para garantir que o menu Modelos seja ocultado
        echo 
'<style>
        #menu-appearance,
        #menu-appearance > a,
        #menu-appearance > .wp-submenu,
        #menu-appearance > .wp-submenu > li,
        #menu-appearance > .wp-submenu > li > a[href="themes.php"] { 
            display: none !important; 
        }
        </style>
';        // Verifica permissões do usuário
        $allowed_pages = get_user_meta( $current_user->ID, 'smpm_allowed_pages', true );
        $allowed_categories = get_user_meta( $current_user->ID, 'smpm_allowed_categories', true );
        
        if ( ! is_array( $allowed_pages ) ) $allowed_pages = array();
        if ( ! is_array( $allowed_categories ) ) $allowed_categories = array();
        
        // Remove menu "Páginas" se não tiver páginas permitidas
        if ( empty( $allowed_pages ) ) {
            remove_menu_page( 'edit.php?post_type=page' );
        }
        
        // Remove menu "Posts" se não tiver categorias permitidas
        if ( empty( $allowed_categories ) ) {
            remove_menu_page( 'edit.php' );
            // Remove também submenus relacionados a posts
            remove_submenu_page( 'edit.php', 'edit.php' );
            remove_submenu_page( 'edit.php', 'post-new.php' );
            remove_submenu_page( 'edit.php', 'edit-tags.php?taxonomy=category' );
            remove_submenu_page( 'edit.php', 'edit-tags.php?taxonomy=post_tag' );
            
            // Adiciona CSS para garantir que o menu Posts seja ocultado
            echo '<style>
            #menu-posts,
            #adminmenu #menu-posts,
            .wp-submenu li a[href="edit.php"],
            .wp-submenu li a[href*="edit.php"] {
                display: none !important;
            }
            </style>';
        }
        
        // Remove menu "Mídia" se não tiver nem páginas nem categorias permitidas
        if ( empty( $allowed_pages ) && empty( $allowed_categories ) ) {
            remove_menu_page( 'upload.php' );
        }
    }

    public function smpm_filter_posts_query( $query ) {
        global $pagenow;
        $current_user = wp_get_current_user();
        
        // Só aplica para usuários com função de Editor
        if ( ! in_array( 'editor', (array) $current_user->roles ) ) {
            return $query;
        }

        // Só aplica no admin e em consultas principais
        if ( ! is_admin() || ! $query->is_main_query() ) {
            return $query;
        }

        // Filtrar posts por categoria
        if ( $pagenow == 'edit.php' && ( ! isset( $_GET['post_type'] ) || $_GET['post_type'] == 'post' ) ) {
            $allowed_categories = get_user_meta( $current_user->ID, 'smpm_allowed_categories', true );
            
            if ( is_array( $allowed_categories ) && ! empty( $allowed_categories ) ) {
                $query->set( 'category__in', $allowed_categories );
            } else {
                // Se não tem categorias permitidas, não mostra nenhum post
                $query->set( 'post__in', array( 0 ) );
            }
        }

        // Filtrar páginas
        if ( $pagenow == 'edit.php' && isset( $_GET['post_type'] ) && $_GET['post_type'] == 'page' ) {
            $allowed_pages = get_user_meta( $current_user->ID, 'smpm_allowed_pages', true );
            
            if ( is_array( $allowed_pages ) && ! empty( $allowed_pages ) ) {
                $query->set( 'post__in', $allowed_pages );
            } else {
                // Se não tem páginas permitidas, não mostra nenhuma página
                $query->set( 'post__in', array( 0 ) );
            }
        }

        return $query;
    }

    public function smpm_restrict_posts_list() {
        // Removido o redirecionamento - permite que a página de posts seja exibida mesmo sem categorias permitidas
    }

    public function smpm_restrict_pages_list() {
        global $pagenow;
        $current_user = wp_get_current_user();
        
        // Só aplica para usuários com função de Editor
        if ( ! in_array( 'editor', (array) $current_user->roles ) ) {
            return;
        }

        // Verificar se está na página de listagem de páginas
        if ( $pagenow == 'edit.php' && isset( $_GET['post_type'] ) && $_GET['post_type'] == 'page' ) {
            $allowed_pages = get_user_meta( $current_user->ID, 'smpm_allowed_pages', true );
            
            // Se não tem páginas permitidas, redireciona para dashboard
            if ( ! is_array( $allowed_pages ) || empty( $allowed_pages ) ) {
                wp_redirect( admin_url() );
                exit;
            }
        }
    }
    
    public function smpm_filter_categories_list( $terms, $taxonomies, $args ) {
        $current_user = wp_get_current_user();
        
        // Só aplica para usuários com função de Editor
        if ( ! in_array( 'editor', (array) $current_user->roles ) ) {
            return $terms;
        }
        
        // Só filtra se for a taxonomia 'category'
        if ( ! in_array( 'category', $taxonomies ) ) {
            return $terms;
        }
        
        // Só aplica no admin
        if ( ! is_admin() ) {
            return $terms;
        }
        
        global $pagenow;
        
        // Só filtra na página de categorias
        if ( $pagenow !== 'edit-tags.php' || ! isset( $_GET['taxonomy'] ) || $_GET['taxonomy'] !== 'category' ) {
            return $terms;
        }
        
        $allowed_categories = get_user_meta( $current_user->ID, 'smpm_allowed_categories', true );
        
        if ( ! is_array( $allowed_categories ) || empty( $allowed_categories ) ) {
            return array(); // Se não tem categorias permitidas, não mostra nenhuma
        }
        
        // Filtra apenas as categorias permitidas
        $filtered_terms = array();
        foreach ( $terms as $term ) {
            if ( in_array( $term->term_id, $allowed_categories ) ) {
                $filtered_terms[] = $term;
            }
        }
        
        return $filtered_terms;
    }
    
    public function smpm_hide_admin_bar_items() {
        global $wp_admin_bar;
        $current_user = wp_get_current_user();
        
        // Só aplica para usuários com função de Editor
        if ( ! in_array( 'editor', (array) $current_user->roles ) ) {
            return;
        }
        
        // Remove itens da barra superior
        $wp_admin_bar->remove_node( 'user-info' );        // Informações do usuário
        $wp_admin_bar->remove_node( 'edit-profile' );     // Editar Perfil
        $wp_admin_bar->remove_node( 'new-content' );      // + Novo
        $wp_admin_bar->remove_node( 'new-post' );         // + Novo Post
        $wp_admin_bar->remove_node( 'new-media' );        // + Novo Mídia
        $wp_admin_bar->remove_node( 'new-page' );         // + Novo Página
        $wp_admin_bar->remove_node( 'new-user' );         // + Novo Usuário
        
        // Cache plugins
        $wp_admin_bar->remove_node( 'w3tc' );             // W3 Total Cache
        $wp_admin_bar->remove_node( 'w3tc-flush-all' );   // W3 Total Cache - Flush All
        $wp_admin_bar->remove_node( 'wp-super-cache' );   // WP Super Cache
        $wp_admin_bar->remove_node( 'autoptimize' );      // Autoptimize
        $wp_admin_bar->remove_node( 'litespeed-menu' );   // LiteSpeed Cache
        $wp_admin_bar->remove_node( 'wp-rocket' );        // WP Rocket
        $wp_admin_bar->remove_node( 'wp-fastest-cache' ); // WP Fastest Cache
        $wp_admin_bar->remove_node( 'cache-enabler' );    // Cache Enabler
        $wp_admin_bar->remove_node( 'comet-cache' );      // Comet Cache
        
        // Outros plugins comuns
        $wp_admin_bar->remove_node( 'updraft_admin_node' ); // UpdraftPlus
        $wp_admin_bar->remove_node( 'wpseo-menu' );         // Yoast SEO
        $wp_admin_bar->remove_node( 'rank-math' );          // Rank Math
        $wp_admin_bar->remove_node( 'spectra-ai' );         // Spectra AI
        $wp_admin_bar->remove_node( 'ai-assistant' );       // AI Assistant
        
        // Adiciona CSS para ocultar elementos específicos que podem não ser removidos via remove_node
        echo '<style>
        #wp-admin-bar-w3tc,
        #wp-admin-bar-w3tc-flush-all,
        #wp-admin-bar-wp-super-cache,
        #wp-admin-bar-autoptimize,
        #wp-admin-bar-litespeed-menu,
        #wp-admin-bar-wp-rocket,
        #wp-admin-bar-spectra-ai,
        #wp-admin-bar-ai-assistant,
        .ab-item[href*="delcachepage"],
        .ab-item[href*="action=delcachepage"],
        .ab-item[href*="index.php"],
        .ab-item[href="' . admin_url('index.php') . '"],
        #wp-admin-bar-collapse-menu,
        #collapse-menu,
        .folded #collapse-menu,
        #adminmenu .wp-submenu-head,
        #adminmenu .wp-menu-toggle { 
            display: none !important; 
        }
        
        /* Oculta botão recolher menu */
        #collapse-button,
        #collapse-menu,
        .folded #collapse-menu {
            display: none !important;
        }
        </style>';
    }
    
    public function smpm_remove_dashboard_widgets() {
        $current_user = wp_get_current_user();
        
        // Só aplica para usuários com função de Editor
        if ( ! in_array( 'editor', (array) $current_user->roles ) ) {
            return;
        }
        
        // Remove todos os widgets do painel - WordPress core
        remove_meta_box( 'dashboard_right_now', 'dashboard', 'normal' );
        remove_meta_box( 'dashboard_recent_comments', 'dashboard', 'normal' );
        remove_meta_box( 'dashboard_incoming_links', 'dashboard', 'normal' );
        remove_meta_box( 'dashboard_plugins', 'dashboard', 'normal' );
        remove_meta_box( 'dashboard_quick_press', 'dashboard', 'side' );
        remove_meta_box( 'dashboard_recent_drafts', 'dashboard', 'side' );
        remove_meta_box( 'dashboard_primary', 'dashboard', 'side' );
        remove_meta_box( 'dashboard_secondary', 'dashboard', 'side' );
        remove_meta_box( 'dashboard_activity', 'dashboard', 'normal' );
        remove_meta_box( 'dashboard_site_health', 'dashboard', 'normal' );
        remove_meta_box( 'welcome_panel', 'dashboard', 'normal' );
        
        // Remove widgets de plugins comuns
        remove_meta_box( 'wpseo-dashboard-overview', 'dashboard', 'normal' );  // Yoast SEO
        remove_meta_box( 'rank_math_pro_notice', 'dashboard', 'normal' );      // Rank Math
        remove_meta_box( 'rg_forms_dashboard', 'dashboard', 'normal' );        // Gravity Forms
        remove_meta_box( 'wpe_dify_news_feed', 'dashboard', 'normal' );        // WP Engine
        remove_meta_box( 'wordfence_activity_report_widget', 'dashboard', 'normal' ); // Wordfence
        remove_meta_box( 'jetpack_summary_widget', 'dashboard', 'normal' );    // Jetpack
        remove_meta_box( 'bbp-dashboard-right-now', 'dashboard', 'normal' );   // bbPress
        remove_meta_box( 'woocommerce_dashboard_status', 'dashboard', 'normal' ); // WooCommerce
        
        // Remove painel de boas-vindas
        remove_action( 'welcome_panel', 'wp_welcome_panel' );
    }
    
    public function smpm_hide_screen_options() {
        $current_user = wp_get_current_user();
        
        // Só aplica para usuários com função de Editor
        if ( ! in_array( 'editor', (array) $current_user->roles ) ) {
            return;
        }
        
        global $pagenow;
        
        // Oculta "Opções de tela" no painel
        if ( $pagenow == 'index.php' ) {
            echo '<style>#screen-options-link-wrap, #contextual-help-link-wrap { display: none !important; }</style>';
        }
    }
    
    public function smpm_restrict_direct_url_access() {
        $current_user = wp_get_current_user();
        
        // Só aplica para usuários com função de Editor
        if ( ! in_array( 'editor', (array) $current_user->roles ) ) {
            return;
        }
        
        global $pagenow;
        
        // Restringe acesso direto a URLs de edição de páginas
        if ( $pagenow == 'post.php' && isset( $_GET['post'] ) && isset( $_GET['action'] ) && $_GET['action'] == 'edit' ) {
            $post_id = absint( $_GET['post'] );
            $post = get_post( $post_id );
            
            if ( $post && $post->post_type == 'page' ) {
                $allowed_pages = get_user_meta( $current_user->ID, 'smpm_allowed_pages', true );
                
                if ( ! is_array( $allowed_pages ) || ! in_array( $post_id, $allowed_pages ) ) {
                    wp_die( __( 'Você não tem permissão para acessar esta página.', 'saude-mg-permission-manager' ) );
                }
            } elseif ( $post && $post->post_type == 'post' ) {
                $post_categories = wp_get_post_categories( $post_id );
                $allowed_categories = get_user_meta( $current_user->ID, 'smpm_allowed_categories', true );
                
                $has_allowed_category = false;
                if ( is_array( $allowed_categories ) && ! empty( $allowed_categories ) ) {
                    foreach ( $post_categories as $cat_id ) {
                        if ( in_array( $cat_id, $allowed_categories ) ) {
                            $has_allowed_category = true;
                            break;
                        }
                    }
                }
                
                if ( ! $has_allowed_category ) {
                    wp_die( __( 'Você não tem permissão para acessar este post.', 'saude-mg-permission-manager' ) );
                }
            }
        }
    }
    
    public function smpm_custom_dashboard_content() {
        $current_user = wp_get_current_user();
        
        // Só aplica para usuários com função de Editor
        if ( ! in_array( 'editor', (array) $current_user->roles ) ) {
            return;
        }
        
        $allowed_pages = get_user_meta( $current_user->ID, 'smpm_allowed_pages', true );
        $allowed_categories = get_user_meta( $current_user->ID, 'smpm_allowed_categories', true );
        
        if ( ! is_array( $allowed_pages ) ) $allowed_pages = array();
        if ( ! is_array( $allowed_categories ) ) $allowed_categories = array();
        
        global $pagenow;
        
        if ( $pagenow == 'index.php' ) {
            // Pega o primeiro nome do usuário
            $first_name = $current_user->first_name;
            if ( empty( $first_name ) ) {
                $first_name = $current_user->display_name;
            }
            
            // Se não tem nenhuma permissão, mostra mensagem de primeiro acesso
            if ( empty( $allowed_pages ) && empty( $allowed_categories ) ) {
                echo '<script>
                jQuery(document).ready(function($) {
                    $("#wpbody-content .wrap").html(`
                        <h1>Bem vindo, ' . esc_js( $first_name ) . '!</h1>
                        <h2>Este é o seu primeiro acesso?</h2>
                        <p>Entre em contato com o Núcleo de Canais Digitais da ASCOM para a liberação de conteúdo e permissões.</p>
                    `);
                });
                </script>';
            } else {
                // Se tem permissões, mostra mensagem de boas-vindas personalizada
                echo '<script>
                jQuery(document).ready(function($) {
                    $("#wpbody-content .wrap").html(`
                        <h1>Bem-vindo(a) à Área de Administração do Portal da Saúde MG</h1>
                        <p>Olá, ' . esc_js( $first_name ) . '! 👋</p>
                        <p>Seja bem-vindo(a) à área administrativa do Portal da Saúde de Minas Gerais.<br>
                        O seu perfil foi autorizado pela Assessoria de Comunicação (ASCOM) para gerenciar conteúdos de páginas e/ou posts específicos relacionados à sua área técnica.</p>
                        <p>Lembre-se de que todas as alterações realizadas aqui são refletidas diretamente no portal, por isso, revise com atenção cada publicação antes de atualizar.</p>
                        <p>Em caso de dúvidas sobre a utilização da ferramenta ou para solicitar suporte, entre em contato com a equipe da ASCOM pelo e-mail <strong>sesdigitalmg@gmail.com</strong>.</p>
                        <p>Conte com a gente para garantir que as informações publicadas estejam sempre atualizadas, claras e de qualidade.</p>
                        <p><strong>Obrigado por contribuir com a comunicação da Saúde MG!</strong></p>
                    `);
                });
                </script>';
            }
        }
    }
    
    public function smpm_block_direct_access() {
        $current_user = wp_get_current_user();
        
        // Só aplica para usuários com função de Editor
        if ( ! in_array( 'editor', (array) $current_user->roles ) ) {
            return;
        }
        
        global $pagenow;
        
        // Bloqueia acesso direto a URLs de perfil, temas, ferramentas, categorias e tags
        $blocked_pages = array( 
            'profile.php',
            'themes.php',
            'tools.php'
        );
        
        if ( in_array( $pagenow, $blocked_pages ) ) {
            wp_die( __( 'Você não tem permissão para acessar esta página.', 'saude-mg-permission-manager' ) );
        }
        
        // Bloqueia acesso a páginas de categorias e tags
        if ( $pagenow == 'edit-tags.php' && ( 
            ( isset( $_GET['taxonomy'] ) && $_GET['taxonomy'] == 'category' ) ||
            ( isset( $_GET['taxonomy'] ) && $_GET['taxonomy'] == 'post_tag' )
        ) ) {
            wp_die( __( 'Você não tem permissão para acessar esta página.', 'saude-mg-permission-manager' ) );
        }
        
        // Bloqueia acesso a URLs de cache
        if ( isset( $_GET['action'] ) && $_GET['action'] == 'delcachepage' ) {
            wp_die( __( 'Você não tem permissão para limpar o cache.', 'saude-mg-permission-manager' ) );
        }
        
        // Bloqueia acesso a upload.php se não tiver permissões
        if ( $pagenow == 'upload.php' ) {
            $allowed_pages = get_user_meta( $current_user->ID, 'smpm_allowed_pages', true );
            $allowed_categories = get_user_meta( $current_user->ID, 'smpm_allowed_categories', true );
            
            if ( ! is_array( $allowed_pages ) ) $allowed_pages = array();
            if ( ! is_array( $allowed_categories ) ) $allowed_categories = array();
            
            if ( empty( $allowed_pages ) && empty( $allowed_categories ) ) {
                wp_die( __( 'Você não tem permissão para acessar a biblioteca de mídia.', 'saude-mg-permission-manager' ) );
            }
        }
        
        // Bloqueia acesso direto a edit.php se não tiver categorias permitidas
        if ( $pagenow == 'edit.php' && ( ! isset( $_GET['post_type'] ) || $_GET['post_type'] == 'post' ) ) {
            $allowed_categories = get_user_meta( $current_user->ID, 'smpm_allowed_categories', true );
            
            if ( ! is_array( $allowed_categories ) || empty( $allowed_categories ) ) {
                wp_die( __( 'Você não tem permissão para acessar a área de posts.', 'saude-mg-permission-manager' ) );
            }
        }
    }
    
    public function smpm_log_permission_changes( $user_id ) {
        // Só registra se for um usuário Editor
        $user = get_user_by( 'id', $user_id );
        if ( ! $user || ! in_array( 'editor', (array) $user->roles ) ) {
            return;
        }
        
        $current_user = wp_get_current_user();
        
        // Só registra se quem está alterando é um administrador
        if ( ! in_array( 'administrator', (array) $current_user->roles ) ) {
            return;
        }
        
        $old_pages = get_user_meta( $user_id, 'smpm_allowed_pages', true );
        $old_categories = get_user_meta( $user_id, 'smpm_allowed_categories', true );
        
        $new_pages = isset( $_POST['smpm_allowed_pages'] ) ? array_map( 'absint', $_POST['smpm_allowed_pages'] ) : array();
        $new_categories = isset( $_POST['smpm_allowed_categories'] ) ? array_map( 'absint', $_POST['smpm_allowed_categories'] ) : array();
        
        if ( ! is_array( $old_pages ) ) $old_pages = array();
        if ( ! is_array( $old_categories ) ) $old_categories = array();
        
        // Verifica se houve mudanças
        $pages_changed = ( $old_pages != $new_pages );
        $categories_changed = ( $old_categories != $new_categories );
        
        if ( $pages_changed || $categories_changed ) {
            $log_entry = array(
                'timestamp' => current_time( 'mysql' ),
                'admin_user' => $current_user->display_name . ' (' . $current_user->user_login . ')',
                'target_user' => $user->display_name . ' (' . $user->user_login . ')',
                'changes' => array()
            );
            
            if ( $pages_changed ) {
                $log_entry['changes']['pages'] = array(
                    'old' => $old_pages,
                    'new' => $new_pages
                );
            }
            
            if ( $categories_changed ) {
                $log_entry['changes']['categories'] = array(
                    'old' => $old_categories,
                    'new' => $new_categories
                );
            }
            
            // Salva o log
            $logs = get_option( 'smpm_permission_logs', array() );
            $logs[] = $log_entry;
            
            // Mantém apenas os últimos 100 registros
            if ( count( $logs ) > 100 ) {
                $logs = array_slice( $logs, -100 );
            }
            
            update_option( 'smpm_permission_logs', $logs );
        }
    }
    
    public function smpm_add_admin_log_page() {
        $current_user = wp_get_current_user();
        
        // Só adiciona para administradores
        if ( ! in_array( 'administrator', (array) $current_user->roles ) ) {
            return;
        }
        
        add_management_page(
            'Log de Permissões',
            'Log de Permissões',
            'manage_options',
            'smpm-permission-logs',
            array( $this, 'smpm_display_log_page' )
        );
    }
    
    public function smpm_display_log_page() {
        $logs = get_option( 'smpm_permission_logs', array() );
        $logs = array_reverse( $logs ); // Mais recentes primeiro
        
        echo '<div class="wrap">';
        echo '<h1>Log de Alterações de Permissões</h1>';
        
        if ( empty( $logs ) ) {
            echo '<p>Nenhuma alteração de permissão registrada.</p>';
        } else {
            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead><tr>';
            echo '<th>Data/Hora</th>';
            echo '<th>Administrador</th>';
            echo '<th>Usuário Editado</th>';
            echo '<th>Alterações</th>';
            echo '</tr></thead>';
            echo '<tbody>';
            
            foreach ( $logs as $log ) {
                echo '<tr>';
                echo '<td>' . esc_html( $log['timestamp'] ) . '</td>';
                echo '<td>' . esc_html( $log['admin_user'] ) . '</td>';
                echo '<td>' . esc_html( $log['target_user'] ) . '</td>';
                echo '<td>';
                
                if ( isset( $log['changes']['pages'] ) ) {
                    $old_pages = $log['changes']['pages']['old'];
                    $new_pages = $log['changes']['pages']['new'];
                    echo '<strong>Páginas:</strong> ' . count( $old_pages ) . ' → ' . count( $new_pages ) . '<br>';
                }
                
                if ( isset( $log['changes']['categories'] ) ) {
                    $old_cats = $log['changes']['categories']['old'];
                    $new_cats = $log['changes']['categories']['new'];
                    echo '<strong>Categorias:</strong> ' . count( $old_cats ) . ' → ' . count( $new_cats );
                }
                
                echo '</td>';
                echo '</tr>';
            }
            
            echo '</tbody>';
            echo '</table>';
        }
        
        echo '</div>';
    }
    
    public function smpm_filter_post_categories( $args, $taxonomies ) {
        $current_user = wp_get_current_user();
        
        // Só aplica para usuários com função de Editor
        if ( ! in_array( 'editor', (array) $current_user->roles ) ) {
            return $args;
        }
        
        // Só aplica para taxonomia de categorias
        if ( ! in_array( 'category', $taxonomies ) ) {
            return $args;
        }
        
        $allowed_categories = get_user_meta( $current_user->ID, 'smpm_allowed_categories', true );
        
        if ( is_array( $allowed_categories ) && ! empty( $allowed_categories ) ) {
            $args['include'] = $allowed_categories;
        } else {
            // Se não tem categorias permitidas, não mostra nenhuma
            $args['include'] = array( 0 );
        }
        
        return $args;
    }
    
    public function smpm_filter_category_dropdown( $dropdown_args, $post ) {
        $current_user = wp_get_current_user();
        
        // Só aplica para usuários com função de Editor
        if ( ! in_array( 'editor', (array) $current_user->roles ) ) {
            return $dropdown_args;
        }
        
        // Verifica se o post existe antes de tentar acessar suas propriedades
        if ( ! $post ) {
            return $dropdown_args;
        }
        
        $allowed_categories = get_user_meta( $current_user->ID, 'smpm_allowed_categories', true );
        
        if ( is_array( $allowed_categories ) && ! empty( $allowed_categories ) ) {
            $dropdown_args['include'] = $allowed_categories;
        } else {
            // Se não tem categorias permitidas, não mostra nenhuma
            $dropdown_args['include'] = array( 0 );
        }
        
        return $dropdown_args;
    }
    
    public function smpm_fix_new_post_capabilities( $allcaps, $cap, $args ) {
        $current_user = wp_get_current_user();
        
        // Só aplica para usuários com função de Editor
        if ( ! in_array( 'editor', (array) $current_user->roles ) ) {
            return $allcaps;
        }
        
        // Permite criar novos posts se tiver pelo menos uma categoria permitida
        if ( in_array( 'edit_posts', $cap ) || in_array( 'publish_posts', $cap ) ) {
            $allowed_categories = get_user_meta( $current_user->ID, 'smpm_allowed_categories', true );
            
            if ( is_array( $allowed_categories ) && ! empty( $allowed_categories ) ) {
                $allcaps['edit_posts'] = true;
                $allcaps['publish_posts'] = true;
            }
        }
        
        return $allcaps;
    }
}