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
                                <?php _e( 'Clique para gerenciar as permissões de páginas e categorias para este usuário.', 'saude-mg-permission-manager' ); ?>
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

        $this->smpm_log_permission_changes( $user_id );

        $allowed_pages = isset( $_POST['smpm_allowed_pages'] ) ? array_map( 'absint', $_POST['smpm_allowed_pages'] ) : array();
        update_user_meta( $user_id, 'smpm_allowed_pages', $allowed_pages );

        $allowed_categories = isset( $_POST['smpm_allowed_categories'] ) ? array_map( 'absint', $_POST['smpm_allowed_categories'] ) : array();
        update_user_meta( $user_id, 'smpm_allowed_categories', $allowed_categories );
    }

    private function render_permission_modal( $user ) {
        $allowed_pages = get_user_meta( $user->ID, 'smpm_allowed_pages', true );
        $allowed_categories = get_user_meta( $user->ID, 'smpm_allowed_categories', true );

        if ( ! is_array( $allowed_pages ) ) $allowed_pages = array();
        if ( ! is_array( $allowed_categories ) ) $allowed_categories = array();

        $pages = get_posts( array(
            'post_type'      => 'page',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
            'post_status'    => 'publish',
            'suppress_filters' => false
        ) );

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
        if ( ! in_array( 'editor', (array) $current_user->roles ) ) {
            return;
        }

        global $pagenow;
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
        if ( ! is_array( $allowed_pages ) ) $allowed_pages = array();

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
        if ( ! is_array( $allowed_categories ) ) $allowed_categories = array();

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
        if ( ! in_array( 'editor', (array) $current_user->roles ) ) {
            return $allcaps;
        }

        if ( isset( $args[0] ) && ( $args[0] == 'edit_page' || $args[0] == 'delete_page' ) && isset( $args[2] ) ) {
            $post_id = $args[2];
            $allowed_pages = get_user_meta( $current_user->ID, 'smpm_allowed_pages', true );
            if ( ! is_array( $allowed_pages ) || ! in_array( $post_id, $allowed_pages ) ) {
                $allcaps['edit_page'] = false;
                $allcaps['delete_page'] = false;
            }
        }

        if ( isset( $args[0] ) && ( $args[0] == 'edit_post' || $args[0] == 'delete_post' ) && isset( $args[2] ) ) {
            $post_id = $args[2];
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
                $allcaps['edit_post'] = false;
                $allcaps['delete_post'] = false;
            }
        }

        return $allcaps;
    }

    public function smpm_remove_admin_menus() {
        $current_user = wp_get_current_user();
        if ( ! in_array( 'editor', (array) $current_user->roles ) ) {
            return;
        }

        remove_menu_page( 'themes.php' );
        remove_menu_page( 'profile.php' );
        remove_menu_page( 'tools.php' );
        remove_submenu_page( 'edit.php', 'edit-tags.php?taxonomy=category' );
        remove_submenu_page( 'edit.php', 'edit-tags.php?taxonomy=post_tag' );

        echo '<style>
        #menu-appearance, #toplevel_page_themes, #menu-posts .wp-submenu li a[href="edit-tags.php?taxonomy=category"], #menu-posts .wp-submenu li a[href="edit-tags.php?taxonomy=post_tag"], #collapse-menu {
            display: none !important; 
        }
        </style>';

        $allowed_pages = get_user_meta( $current_user->ID, 'smpm_allowed_pages', true );
        $allowed_categories = get_user_meta( $current_user->ID, 'smpm_allowed_categories', true );
        if ( ! is_array( $allowed_pages ) ) $allowed_pages = array();
        if ( ! is_array( $allowed_categories ) ) $allowed_categories = array();

        if ( empty( $allowed_pages ) ) {
            remove_menu_page( 'edit.php?post_type=page' );
        }
        if ( empty( $allowed_categories ) ) {
            remove_menu_page( 'edit.php' );
            echo '<style>#menu-posts { display: none !important; }</style>';
        }
        if ( empty( $allowed_pages ) && empty( $allowed_categories ) ) {
            remove_menu_page( 'upload.php' );
        }
    }

    public function smpm_filter_posts_query( $query ) {
        global $pagenow;
        $current_user = wp_get_current_user();
        if ( ! in_array( 'editor', (array) $current_user->roles ) || ! is_admin() || ! $query->is_main_query() ) {
            return $query;
        }

        if ( $pagenow == 'edit.php' && ( ! isset( $_GET['post_type'] ) || $_GET['post_type'] == 'post' ) ) {
            $allowed_categories = get_user_meta( $current_user->ID, 'smpm_allowed_categories', true );
            if ( is_array( $allowed_categories ) && ! empty( $allowed_categories ) ) {
                $query->set( 'category__in', $allowed_categories );
            } else {
                $query->set( 'post__in', array( 0 ) );
            }
        }

        if ( $pagenow == 'edit.php' && isset( $_GET['post_type'] ) && $_GET['post_type'] == 'page' ) {
            $allowed_pages = get_user_meta( $current_user->ID, 'smpm_allowed_pages', true );
            if ( is_array( $allowed_pages ) && ! empty( $allowed_pages ) ) {
                $query->set( 'post__in', $allowed_pages );
            } else {
                $query->set( 'post__in', array( 0 ) );
            }
        }
        return $query;
    }

    public function smpm_filter_media_query( $query ) {
        $current_user = wp_get_current_user();
        if ( in_array( 'editor', (array) $current_user->roles ) ) {
            $query['author'] = $current_user->ID;
        }
        return $query;
    }

    public function smpm_hide_admin_bar_items() {
        $current_user = wp_get_current_user();
        if ( ! in_array( 'editor', (array) $current_user->roles ) ) {
            return;
        }
        global $wp_admin_bar;
        $wp_admin_bar->remove_node( 'new-content' );
        $wp_admin_bar->remove_node( 'my-account' );
        $wp_admin_bar->remove_node( 'edit-profile' );
        
        echo '<style>
        #wp-admin-bar-new-content, #wp-admin-bar-my-account, #wp-admin-bar-edit-profile, #wp-admin-bar-spectra-ai-assistant, a[href*="action=delcachepage"] {
            display: none !important;
        }
        </style>';
    }

    public function smpm_remove_dashboard_widgets() {
        $current_user = wp_get_current_user();
        if ( ! in_array( 'editor', (array) $current_user->roles ) ) {
            return;
        }
        remove_action( 'welcome_panel', 'wp_welcome_panel' );
        global $wp_meta_boxes;
        unset( $wp_meta_boxes['dashboard'] );
    }

    public function smpm_hide_screen_options() {
        $current_user = wp_get_current_user();
        if ( in_array( 'editor', (array) $current_user->roles ) ) {
            echo '<style>#screen-options-link-wrap { display: none !important; }</style>';
        }
    }

    public function smpm_custom_dashboard_content() {
        global $pagenow;
        if ( $pagenow != 'index.php' ) return;
        
        $current_user = wp_get_current_user();
        if ( ! in_array( 'editor', (array) $current_user->roles ) ) {
            return;
        }

        $allowed_pages = get_user_meta( $current_user->ID, 'smpm_allowed_pages', true );
        $allowed_categories = get_user_meta( $current_user->ID, 'smpm_allowed_categories', true );
        $first_name = $current_user->user_firstname ? $current_user->user_firstname : $current_user->display_name;

        echo '<div id="smpm-custom-dashboard" style="margin-top: 20px; background: #fff; padding: 20px; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04);">';
        if ( ( ! is_array( $allowed_pages ) || empty( $allowed_pages ) ) && ( ! is_array( $allowed_categories ) || empty( $allowed_categories ) ) ) {
            echo '<h1>Bem vindo, ' . esc_html( $first_name ) . '!</h1>';
            echo '<h3>Este é o seu primeiro acesso?</h3>';
            echo '<p>Entre em contato com o Núcleo de Canais Digitais da ASCOM para a liberação de conteúdo e permissões.</p>';
        } else {
            echo '<h1>Bem-vindo(a) à Área de Administração do Portal da Saúde MG</h1>';
            echo '<p>Olá, ' . esc_html( $first_name ) . '!👋</p>';
            echo '<p>Seja bem-vindo(a) à área administrativa do Portal da Saúde de Minas Gerais. O seu perfil foi autorizado pela Assessoria de Comunicação (ASCOM) para gerenciar conteúdos de páginas e/ou posts específicos relacionados à sua área técnica.</p>';
            echo '<p>Lembre-se de que todas as alterações realizadas aqui são refletidas diretamente no portal, por isso, revise com atenção cada publicação antes de atualizar.</p>';
            echo '<p>Em caso de dúvidas sobre a utilização da ferramenta ou para solicitar suporte, entre em contato com a equipe da ASCOM pelo e-mail <strong>sesdigitalmg@gmail.com</strong>.</p>';
            echo '<p>Conte com a gente para garantir que as informações publicadas estejam sempre atualizadas, claras e de qualidade.</p>';
            echo '<p>Obrigado por contribuir com a comunicação da Saúde MG!</p>';
        }
        echo '</div>';
        echo '<script>jQuery(document).ready(function($) { $("#wpbody-content .wrap").hide(); $("#smpm-custom-dashboard").prependTo("#wpbody-content"); });</script>';
    }

    public function smpm_block_direct_access() {
        $current_user = wp_get_current_user();
        if ( ! in_array( 'editor', (array) $current_user->roles ) ) {
            return;
        }
        global $pagenow;
        if ( $pagenow == 'themes.php' || $pagenow == 'profile.php' || $pagenow == 'tools.php' || 
             ( $pagenow == 'edit-tags.php' && isset($_GET['taxonomy']) && ($_GET['taxonomy'] == 'category' || $_GET['taxonomy'] == 'post_tag') ) ) {
            wp_die( __( 'Você não tem permissão para acessar esta página.', 'saude-mg-permission-manager' ) );
        }
    }

    public function smpm_add_admin_log_page() {
        add_management_page( 'Log de Permissões', 'Log de Permissões', 'manage_options', 'smpm-log', array( $this, 'render_log_page' ) );
    }

    public function render_log_page() {
        $log = get_option( 'smpm_permission_log', array() );
        ?>
        <div class="wrap">
            <h1>Log de Alterações de Permissões</h1>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Data/Hora</th>
                        <th>Administrador</th>
                        <th>Usuário</th>
                        <th>Alterações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( array_reverse( $log ) as $entry ) : 
                        $admin = get_userdata( $entry['admin'] );
                        $user = get_userdata( $entry['user'] );
                    ?>
                        <tr>
                            <td><?php echo esc_html( $entry['date'] ); ?></td>
                            <td><?php echo $admin ? esc_html( $admin->display_name ) : 'Desconhecido'; ?></td>
                            <td><?php echo $user ? esc_html( $user->display_name ) : 'Desconhecido'; ?></td>
                            <td>
                                <strong>Páginas:</strong> <?php echo count( $entry['changes']['pages']['new'] ); ?> permitidas<br>
                                <strong>Categorias:</strong> <?php echo count( $entry['changes']['categories']['new'] ); ?> permitidas
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function smpm_filter_category_dropdown( $dropdown_args ) {
        $current_user = wp_get_current_user();
        if ( ! in_array( 'editor', (array) $current_user->roles ) ) {
            return $dropdown_args;
        }
        $allowed_categories = get_user_meta( $current_user->ID, 'smpm_allowed_categories', true );
        if ( is_array( $allowed_categories ) && ! empty( $allowed_categories ) ) {
            $dropdown_args['include'] = $allowed_categories;
        } else {
            $dropdown_args['include'] = array( 0 );
        }
        return $dropdown_args;
    }

    public function smpm_auto_allow_new_pages( $post_id, $post ) {
        if ( $post->post_type == 'page' ) {
            $current_user = wp_get_current_user();
            if ( in_array( 'editor', (array) $current_user->roles ) ) {
                $allowed_pages = get_user_meta( $current_user->ID, 'smpm_allowed_pages', true );
                if ( ! is_array( $allowed_pages ) ) $allowed_pages = array();
                if ( ! in_array( $post_id, $allowed_pages ) ) {
                    $allowed_pages[] = $post_id;
                    update_user_meta( $current_user->ID, 'smpm_allowed_pages', $allowed_pages );
                }
            }
        }
    }

    public function smpm_fix_new_post_capabilities( $allcaps, $cap, $args ) {
        $current_user = wp_get_current_user();
        if ( ! in_array( 'editor', (array) $current_user->roles ) ) {
            return $allcaps;
        }
        if ( in_array( 'edit_posts', $cap ) || in_array( 'publish_posts', $cap ) ) {
            $allowed_categories = get_user_meta( $current_user->ID, 'smpm_allowed_categories', true );
            if ( is_array( $allowed_categories ) && ! empty( $allowed_categories ) ) {
                $allcaps['edit_posts'] = true;
                $allcaps['publish_posts'] = true;
            }
        }
        return $allcaps;
    }

    public function smpm_remove_shariff_meta_box() {
        remove_meta_box( 'shariff_metabox', 'post', 'side' );
        remove_meta_box( 'shariff_metabox', 'page', 'side' );
    }

    private function smpm_log_permission_changes( $user_id ) {
        $admin_id = get_current_user_id();
        $old_pages = get_user_meta( $user_id, 'smpm_allowed_pages', true );
        $old_categories = get_user_meta( $user_id, 'smpm_allowed_categories', true );
        
        $new_pages = isset( $_POST['smpm_allowed_pages'] ) ? array_map( 'absint', $_POST['smpm_allowed_pages'] ) : array();
        $new_categories = isset( $_POST['smpm_allowed_categories'] ) ? array_map( 'absint', $_POST['smpm_allowed_categories'] ) : array();

        $log = get_option( 'smpm_permission_log', array() );
        $log[] = array(
            'date' => current_time( 'mysql' ),
            'admin' => $admin_id,
            'user' => $user_id,
            'changes' => array(
                'pages' => array( 'old' => $old_pages, 'new' => $new_pages ),
                'categories' => array( 'old' => $old_categories, 'new' => $new_categories )
            )
        );
        update_option( 'smpm_permission_log', array_slice( $log, -100 ) );
    }
    
    public function smpm_restrict_posts_list() {
        // Implementação vazia para evitar erro se chamado
    }
    
    public function smpm_restrict_pages_list() {
        // Implementação vazia para evitar erro se chamado
    }
    
    public function smpm_filter_categories_list( $terms, $taxonomies, $args ) {
        if ( ! is_admin() || ! in_array( 'category', $taxonomies ) ) return $terms;
        $current_user = wp_get_current_user();
        if ( ! in_array( 'editor', (array) $current_user->roles ) ) return $terms;
        
        $allowed_categories = get_user_meta( $current_user->ID, 'smpm_allowed_categories', true );
        if ( ! is_array( $allowed_categories ) || empty( $allowed_categories ) ) return array();
        
        $filtered_terms = array();
        foreach ( $terms as $term ) {
            if ( in_array( $term->term_id, $allowed_categories ) ) {
                $filtered_terms[] = $term;
            }
        }
        return $filtered_terms;
    }
    
    public function smpm_restrict_direct_url_access() {
        // Já implementado em smpm_block_direct_access
    }
    
    public function smpm_filter_post_categories( $args, $taxonomies ) {
        if ( ! is_admin() || ! in_array( 'category', $taxonomies ) ) return $args;
        $current_user = wp_get_current_user();
        if ( ! in_array( 'editor', (array) $current_user->roles ) ) return $args;
        
        $allowed_categories = get_user_meta( $current_user->ID, 'smpm_allowed_categories', true );
        if ( is_array( $allowed_categories ) && ! empty( $allowed_categories ) ) {
            $args['include'] = $allowed_categories;
        } else {
            $args['include'] = array( 0 );
        }
        return $args;
    }
}
