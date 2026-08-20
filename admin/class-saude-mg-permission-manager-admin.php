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

        if (
            ! in_array(
                'editor',
                (array) $current_user->roles,
                true
            )
        ) {
            return;
        }

        global $menu;

        $allowed_menu_slugs = array(
            'index.php',
            'edit.php',
            'upload.php',
            'edit.php?post_type=page',
        );

        if ( is_array( $menu ) ) {
            foreach ( $menu as $menu_item ) {
                if ( ! isset( $menu_item[2] ) ) {
                    continue;
                }

                $menu_slug = (string) $menu_item[2];

                if (
                    ! in_array(
                        $menu_slug,
                        $allowed_menu_slugs,
                        true
                    )
                ) {
                    remove_menu_page(
                        $menu_slug
                    );
                }
            }
        }

        remove_submenu_page(
            'edit.php',
            'edit-tags.php?taxonomy=category'
        );

        remove_submenu_page(
            'edit.php',
            'edit-tags.php?taxonomy=post_tag'
        );

        $allowed_pages = get_user_meta(
            $current_user->ID,
            'smpm_allowed_pages',
            true
        );

        $allowed_categories = get_user_meta(
            $current_user->ID,
            'smpm_allowed_categories',
            true
        );

        $allowed_pages = is_array( $allowed_pages )
            ? array_values(
                array_filter(
                    array_map(
                        'absint',
                        $allowed_pages
                    )
                )
            )
            : array();

        $allowed_categories = is_array(
            $allowed_categories
        )
            ? array_values(
                array_filter(
                    array_map(
                        'absint',
                        $allowed_categories
                    )
                )
            )
            : array();

        if ( empty( $allowed_pages ) ) {
            remove_menu_page(
                'edit.php?post_type=page'
            );
        }

        if ( empty( $allowed_categories ) ) {
            remove_menu_page(
                'edit.php'
            );
        }
    }

    public function smpm_filter_posts_query( $query ) {
        global $pagenow;

        $current_user = wp_get_current_user();

        if (
            ! is_admin()
            || 'edit.php' !== $pagenow
            || ! $query->is_main_query()
            || ! in_array(
                'editor',
                (array) $current_user->roles,
                true
            )
        ) {
            return $query;
        }

        $post_type = $query->get( 'post_type' );

        if ( empty( $post_type ) ) {
            $post_type = isset( $_GET['post_type'] )
                ? sanitize_key(
                    wp_unslash( $_GET['post_type'] )
                )
                : 'post';
        }

        if ( 'page' === $post_type ) {
            $allowed_pages = get_user_meta(
                $current_user->ID,
                'smpm_allowed_pages',
                true
            );

            $allowed_pages = is_array( $allowed_pages )
                ? array_values(
                    array_unique(
                        array_filter(
                            array_map(
                                'absint',
                                $allowed_pages
                            )
                        )
                    )
                )
                : array();

            $query->set(
                'post__in',
                ! empty( $allowed_pages )
                    ? $allowed_pages
                    : array( 0 )
            );

            return $query;
        }

        if ( 'post' !== $post_type ) {
            return $query;
        }

        /*
         * A categoria será aplicada em posts_clauses.
         * Aqui removemos os filtros nativos para evitar
         * condições SQL duplicadas ou conflitantes.
         */
        $query->set( 'cat', 0 );
        $query->set( 'category_name', '' );
        $query->set( 'category__in', array() );
        $query->set( 'category__and', array() );
        $query->set( 'category__not_in', array() );
        $query->set( 'tax_query', array() );

        $query->set(
            'smpm_restrict_categories',
            true
        );

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

        if (
            ! in_array(
                'editor',
                (array) $current_user->roles,
                true
            )
        ) {
            return;
        }

        global $wp_admin_bar;

        if ( ! is_object( $wp_admin_bar ) ) {
            return;
        }

        $display_name = trim(
            wp_strip_all_tags(
                (string) $current_user->display_name
            )
        );

        if ( '' === $display_name ) {
            $display_name = wp_strip_all_tags(
                (string) $current_user->user_login
            );
        }

        /*
         * Remove todos os itens nativos, inclusive o item
         * my-account que contém o avatar em seu HTML.
         */
        $nodes = $wp_admin_bar->get_nodes();

        if ( is_array( $nodes ) ) {
            foreach ( $nodes as $node ) {
                if (
                    isset( $node->id )
                    && 'top-secondary' !== $node->id
                ) {
                    $wp_admin_bar->remove_node(
                        $node->id
                    );
                }
            }
        }

        /*
         * Recria o item sem imagem, avatar ou link.
         */
        $wp_admin_bar->add_node(
            array(
                'id'     => 'my-account',
                'parent' => 'top-secondary',
                'title'  => sprintf(
                    '<span class="display-name">%s</span>',
                    esc_html( $display_name )
                ),
                'href'   => false,
                'meta'   => array(
                    'class'      => 'smpm-user-account',
                    'menu_title' => esc_attr(
                        $display_name
                    ),
                    'tabindex'   => 0,
                ),
            )
        );

        $wp_admin_bar->add_group(
            array(
                'parent' => 'my-account',
                'id'     => 'user-actions',
            )
        );

        $wp_admin_bar->add_node(
            array(
                'parent' => 'user-actions',
                'id'     => 'logout',
                'title'  => esc_html__(
                    'Sair',
                    'saude-mg-permission-manager'
                ),
                'href'   => wp_logout_url(),
            )
        );

        echo '<style>
            #wpadminbar {
                z-index: 999 !important;
            }

            #wpadminbar .ab-top-menu > li {
                display: none !important;
            }

            #wpadminbar .ab-top-secondary {
                display: block !important;
                float: right !important;
            }

            #wpadminbar
            .ab-top-secondary
            > li:not(#wp-admin-bar-my-account) {
                display: none !important;
            }

            #wpadminbar
            #wp-admin-bar-my-account {
                display: block !important;
                float: right !important;
            }

            #wpadminbar
            #wp-admin-bar-my-account
            > .ab-item {
                background: transparent !important;
                cursor: default;
                display: block !important;
                padding-left: 10px;
                padding-right: 10px;
            }

            #wpadminbar
            #wp-admin-bar-my-account
            > .ab-item::before,
            #wpadminbar
            #wp-admin-bar-my-account
            > .ab-item::after {
                background: none !important;
                content: none !important;
                display: none !important;
            }

            #wpadminbar
            #wp-admin-bar-my-account
            > .ab-item
            .display-name {
                display: inline !important;
                margin: 0 !important;
                padding: 0 !important;
            }

            #wpadminbar
            #wp-admin-bar-my-account
            img,
            #wpadminbar
            #wp-admin-bar-my-account
            .avatar,
            #wpadminbar
            #wp-admin-bar-my-account
            .ab-icon,
            #wpadminbar
            #wp-admin-bar-user-info,
            #wpadminbar
            #wp-admin-bar-edit-profile {
                display: none !important;
                height: 0 !important;
                margin: 0 !important;
                max-height: 0 !important;
                max-width: 0 !important;
                opacity: 0 !important;
                padding: 0 !important;
                visibility: hidden !important;
                width: 0 !important;
            }

            #wpadminbar
            #wp-admin-bar-my-account
            .ab-sub-wrapper {
                min-width: 100px;
            }

            #wpadminbar
            #wp-admin-bar-user-actions {
                padding: 0;
            }

            #wpadminbar
            #wp-admin-bar-logout
            > .ab-item {
                min-width: 80px;
                padding: 6px 12px;
                text-align: left;
            }
        </style>';

        /*
         * Remove por JavaScript qualquer avatar reinserido
         * posteriormente por outro plugin.
         */
        echo '<script>
            document.addEventListener(
                "DOMContentLoaded",
                function () {
                    function smpmRemoveUserAvatar() {
                        var account = document.querySelector(
                            "#wp-admin-bar-my-account"
                        );

                        if (!account) {
                            return;
                        }

                        account.querySelectorAll(
                            "img, .avatar, .ab-icon"
                        ).forEach(function (element) {
                            element.remove();
                        });

                        var accountLink = account.querySelector(
                            ":scope > .ab-item"
                        );

                        if (accountLink) {
                            accountLink.removeAttribute("href");
                        }
                    }

                    smpmRemoveUserAvatar();

                    if (document.body) {
                        new MutationObserver(
                            smpmRemoveUserAvatar
                        ).observe(
                            document.body,
                            {
                                childList: true,
                                subtree: true
                            }
                        );
                    }
                }
            );
        </script>';
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

        if (
            ! in_array(
                'editor',
                (array) $current_user->roles,
                true
            )
        ) {
            return;
        }

        /*
         * O CSS desta restrição agora é carregado junto
         * ao stylesheet nativo wp-admin.
         *
         * Este método permanece apenas para compatibilidade
         * com o hook registrado pelas versões anteriores.
         */
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
        if ( ! is_admin() ) {
            return $terms;
        }

        $taxonomies = (array) $taxonomies;

        if ( ! in_array( 'category', $taxonomies, true ) ) {
            return $terms;
        }

        $current_user = wp_get_current_user();

        if ( ! in_array( 'editor', (array) $current_user->roles, true ) ) {
            return $terms;
        }

        $allowed_categories = get_user_meta(
            $current_user->ID,
            'smpm_allowed_categories',
            true
        );

        $allowed_categories = is_array( $allowed_categories )
            ? array_values(
                array_filter(
                    array_map( 'absint', $allowed_categories )
                )
            )
            : array();

        if ( empty( $allowed_categories ) ) {
            return array();
        }

        if ( ! is_array( $terms ) ) {
            return $terms;
        }

        $filtered_terms = array();

        foreach ( $terms as $term ) {
            $term_id = 0;

            if ( $term instanceof WP_Term ) {
                $term_id = (int) $term->term_id;
            } elseif ( is_object( $term ) && isset( $term->term_id ) ) {
                $term_id = (int) $term->term_id;
            } elseif ( is_array( $term ) && isset( $term['term_id'] ) ) {
                $term_id = (int) $term['term_id'];
            } elseif ( is_numeric( $term ) ) {
                $term_id = (int) $term;
            }

            if (
                $term_id > 0
                && in_array( $term_id, $allowed_categories, true )
            ) {
                $filtered_terms[] = $term;
            }
        }

        return $filtered_terms;
    }

    
    public function smpm_restrict_direct_url_access() {
        // Já implementado em smpm_block_direct_access
    }
    
    public function smpm_filter_post_categories(
        $args,
        $taxonomies
    ) {
        if ( ! is_admin() || ! is_array( $args ) ) {
            return $args;
        }

        $taxonomies = (array) $taxonomies;

        if (
            ! in_array(
                'category',
                $taxonomies,
                true
            )
        ) {
            return $args;
        }

        $current_user = wp_get_current_user();

        if (
            ! in_array(
                'editor',
                (array) $current_user->roles,
                true
            )
        ) {
            return $args;
        }

        $allowed_categories = get_user_meta(
            $current_user->ID,
            'smpm_allowed_categories',
            true
        );

        $allowed_categories = is_array(
            $allowed_categories
        )
            ? array_values(
                array_unique(
                    array_filter(
                        array_map(
                            'absint',
                            $allowed_categories
                        )
                    )
                )
            )
            : array();

        $existing_include = isset(
            $args['include']
        )
            ? array_values(
                array_filter(
                    array_map(
                        'absint',
                        (array) $args['include']
                    )
                )
            )
            : array();

        if (
            ! empty( $existing_include )
            && ! empty( $allowed_categories )
        ) {
            $allowed_categories = array_values(
                array_intersect(
                    $allowed_categories,
                    $existing_include
                )
            );
        }

        $args['include'] = ! empty(
            $allowed_categories
        )
            ? $allowed_categories
            : array( 0 );

        unset( $args['exclude'] );
        unset( $args['exclude_tree'] );

        return $args;
    }

    public function smpm_remove_editor_help( $screen ) {
        $current_user = wp_get_current_user();

        if ( ! in_array( 'editor', (array) $current_user->roles, true ) ) {
            return;
        }

        if (
            is_object( $screen )
            && method_exists( $screen, 'remove_help_tabs' )
        ) {
            $screen->remove_help_tabs();
        }
    }

    public function smpm_filter_restricted_post_views(
        $views
    ) {
        $current_user = wp_get_current_user();

        if (
            ! is_admin()
            || ! is_array( $views )
            || ! in_array(
                'editor',
                (array) $current_user->roles,
                true
            )
        ) {
            return $views;
        }

        $post_type = (
            'views_edit-page' === current_filter()
        )
            ? 'page'
            : 'post';

        foreach ( $views as $view_key => $view_html ) {
            $normalized_view = sanitize_key(
                (string) $view_key
            );

            $count = $this->smpm_get_restricted_view_count(
                $post_type,
                $normalized_view,
                $current_user->ID
            );

            if ( null === $count ) {
                continue;
            }

            if (
                $count <= 0
                && 'all' !== $normalized_view
            ) {
                unset( $views[ $view_key ] );
                continue;
            }

            $formatted_count = number_format_i18n(
                $count
            );

            $replacement = sprintf(
                '<span class="count">(%s)</span>',
                esc_html( $formatted_count )
            );

            $updated_html = preg_replace(
                '/<span\s+class=(["\'])count\1>'
                . '\s*\(?\s*[^<]*?\s*\)?\s*'
                . '<\/span>/i',
                $replacement,
                $view_html,
                1
            );

            if (
                is_string( $updated_html )
                && $updated_html !== $view_html
            ) {
                $views[ $view_key ] = $updated_html;
                continue;
            }

            $fallback_html = preg_replace(
                '/\(\s*[\d\.,]+\s*\)/',
                '(' . esc_html( $formatted_count ) . ')',
                $view_html,
                1
            );

            if ( is_string( $fallback_html ) ) {
                $views[ $view_key ] = $fallback_html;
            }
        }

        return $views;
    }

    private function smpm_get_restricted_view_count(
        $post_type,
        $view_key,
        $user_id
    ) {
        $supported_views = array(
            'all',
            'mine',
            'publish',
            'future',
            'draft',
            'pending',
            'private',
            'trash',
            'sticky',
        );

        if (
            ! in_array(
                $view_key,
                $supported_views,
                true
            )
        ) {
            return null;
        }

        $query_args = array(
            'post_type'              => $post_type,
            'posts_per_page'         => 1,
            'paged'                  => 1,
            'fields'                 => 'ids',
            'no_found_rows'          => false,
            'ignore_sticky_posts'    => true,
            'orderby'                => 'ID',
            'order'                  => 'ASC',
            'suppress_filters'       => true,
            'cache_results'          => false,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
        );

        if ( 'page' === $post_type ) {
            $allowed_pages = get_user_meta(
                $user_id,
                'smpm_allowed_pages',
                true
            );

            $allowed_pages = is_array( $allowed_pages )
                ? array_values(
                    array_filter(
                        array_map(
                            'absint',
                            $allowed_pages
                        )
                    )
                )
                : array();

            if ( empty( $allowed_pages ) ) {
                return 0;
            }

            $query_args['post__in'] = $allowed_pages;
        } else {
            $allowed_categories = get_user_meta(
                $user_id,
                'smpm_allowed_categories',
                true
            );

            $allowed_categories = is_array(
                $allowed_categories
            )
                ? array_values(
                    array_filter(
                        array_map(
                            'absint',
                            $allowed_categories
                        )
                    )
                )
                : array();

            if ( empty( $allowed_categories ) ) {
                return 0;
            }

            $query_args['category__in'] = (
                $allowed_categories
            );
        }

        if (
            'all' === $view_key
            || 'mine' === $view_key
        ) {
            $statuses = get_post_stati(
                array(
                    'show_in_admin_all_list' => true,
                ),
                'names'
            );

            $query_args['post_status'] = ! empty(
                $statuses
            )
                ? array_values( $statuses )
                : array(
                    'publish',
                    'future',
                    'draft',
                    'pending',
                    'private',
                );

            if ( 'mine' === $view_key ) {
                $query_args['author'] = absint(
                    $user_id
                );
            }
        } elseif ( 'sticky' === $view_key ) {
            if ( 'post' !== $post_type ) {
                return 0;
            }

            $sticky_posts = array_values(
                array_filter(
                    array_map(
                        'absint',
                        (array) get_option(
                            'sticky_posts',
                            array()
                        )
                    )
                )
            );

            if ( empty( $sticky_posts ) ) {
                return 0;
            }

            $existing_post_in = isset(
                $query_args['post__in']
            )
                ? (array) $query_args['post__in']
                : array();

            if ( ! empty( $existing_post_in ) ) {
                $sticky_posts = array_values(
                    array_intersect(
                        $existing_post_in,
                        $sticky_posts
                    )
                );
            }

            if ( empty( $sticky_posts ) ) {
                return 0;
            }

            $query_args['post__in'] = $sticky_posts;
            $query_args['post_status'] = 'publish';
        } else {
            $query_args['post_status'] = $view_key;
        }

        $count_query = new WP_Query(
            $query_args
        );

        return absint(
            $count_query->found_posts
        );
    }

    public function smpm_remove_elementor_ai_from_media() {
        $current_user = wp_get_current_user();

        if (
            ! in_array(
                'editor',
                (array) $current_user->roles,
                true
            )
        ) {
            return;
        }

        ?>
        <style>
            [id*="elementor-ai"],
            [class*="elementor-ai"],
            [id*="e-ai-"],
            [class*="e-ai-"],
            [data-action*="elementor-ai"],
            [data-event*="elementor-ai"],
            [aria-label*="Elementor AI"],
            [title*="Elementor AI"],
            .e-ai-button,
            .e-ai-layout-button,
            .elementor-ai-button {
                display: none !important;
            }
        </style>

        <script>
            (function () {
                'use strict';

                var terms = [
                    'gerar com a ia do elementor',
                    'gerar com ia do elementor',
                    'generate with elementor ai',
                    'elementor ai'
                ];

                function normalize(value) {
                    return String(value || '')
                        .toLocaleLowerCase('pt-BR')
                        .replace(/\s+/g, ' ')
                        .trim();
                }

                function isAiElement(element) {
                    if (
                        !element
                        || element.nodeType !== 1
                    ) {
                        return false;
                    }

                    var attributes = [
                        element.id,
                        element.className,
                        element.getAttribute('aria-label'),
                        element.getAttribute('title'),
                        element.getAttribute('data-action'),
                        element.getAttribute('data-event')
                    ];

                    var attributesText = normalize(
                        attributes.join(' ')
                    );

                    if (
                        attributesText.indexOf(
                            'elementor-ai'
                        ) !== -1
                        || attributesText.indexOf(
                            'e-ai-'
                        ) !== -1
                    ) {
                        return true;
                    }

                    var text = normalize(
                        element.textContent
                    );

                    return terms.some(
                        function (term) {
                            return (
                                text === term
                                || text.indexOf(term) !== -1
                            );
                        }
                    );
                }

                function removeAiElements() {
                    var selector = [
                        'button',
                        'a',
                        '[role="button"]',
                        '.media-menu-item',
                        '.media-router a',
                        '.media-frame-menu a'
                    ].join(',');

                    document
                        .querySelectorAll(selector)
                        .forEach(function (element) {
                            if (!isAiElement(element)) {
                                return;
                            }

                            var parent = element.closest(
                                'li,'
                                + '.media-menu-item,'
                                + '.elementor-button,'
                                + '[class*="elementor-ai"],'
                                + '[class*="e-ai-"]'
                            );

                            (parent || element).remove();
                        });
                }

                removeAiElements();

                if (document.body) {
                    new MutationObserver(
                        removeAiElements
                    ).observe(
                        document.body,
                        {
                            childList: true,
                            subtree: true
                        }
                    );
                }
            }());
        </script>
        <?php
    }

    public function smpm_filter_posts_clauses(
        $clauses,
        $query
    ) {
        global $pagenow;
        global $wpdb;

        $current_user = wp_get_current_user();

        if (
            ! is_admin()
            || 'edit.php' !== $pagenow
            || ! is_array( $clauses )
            || ! $query->is_main_query()
            || ! $query->get(
                'smpm_restrict_categories'
            )
            || ! in_array(
                'editor',
                (array) $current_user->roles,
                true
            )
        ) {
            return $clauses;
        }

        $allowed_categories = get_user_meta(
            $current_user->ID,
            'smpm_allowed_categories',
            true
        );

        $allowed_categories = is_array(
            $allowed_categories
        )
            ? array_values(
                array_unique(
                    array_filter(
                        array_map(
                            'absint',
                            $allowed_categories
                        )
                    )
                )
            )
            : array();

        $selected_category = isset( $_GET['cat'] )
            ? absint(
                wp_unslash( $_GET['cat'] )
            )
            : 0;

        if ( empty( $allowed_categories ) ) {
            $effective_categories = array( 0 );
        } elseif ( $selected_category > 0 ) {
            $effective_categories = in_array(
                $selected_category,
                $allowed_categories,
                true
            )
                ? array( $selected_category )
                : array( 0 );
        } else {
            $effective_categories = (
                $allowed_categories
            );
        }

        $category_ids = implode(
            ',',
            array_map(
                'absint',
                $effective_categories
            )
        );

        if ( '' === $category_ids ) {
            $category_ids = '0';
        }

        /*
         * EXISTS evita duplicação de Posts que possuam
         * mais de uma categoria permitida.
         */
        $clauses['where'] .= "
            AND EXISTS (
                SELECT 1
                FROM {$wpdb->term_relationships}
                    AS smpm_tr
                INNER JOIN {$wpdb->term_taxonomy}
                    AS smpm_tt
                    ON smpm_tt.term_taxonomy_id =
                        smpm_tr.term_taxonomy_id
                WHERE
                    smpm_tr.object_id =
                        {$wpdb->posts}.ID
                    AND smpm_tt.taxonomy =
                        'category'
                    AND smpm_tt.term_id IN (
                        {$category_ids}
                    )
            )
        ";

        return $clauses;
    }

    public function smpm_enqueue_editor_critical_styles(
        $hook_suffix
    ) {
        $current_user = wp_get_current_user();

        if (
            ! in_array(
                'editor',
                (array) $current_user->roles,
                true
            )
        ) {
            return;
        }

        $site_icon_url = get_site_icon_url( 160 );

        $background_image = 'none';

        if ( ! empty( $site_icon_url ) ) {
            $background_image = sprintf(
                'url("%s")',
                esc_url_raw( $site_icon_url )
            );
        }

        /*
         * Garante que o handle nativo esteja na fila.
         * O CSS inline será impresso imediatamente depois
         * do stylesheet principal do wp-admin.
         */
        wp_enqueue_style( 'wp-admin' );

        $critical_css = '
            /*
             * Layout crítico do perfil Editor.
             * Este bloco é impresso junto ao CSS nativo,
             * antes da primeira renderização da página.
             */
            html.wp-toolbar {
                padding-top: 32px;
            }

            #wpadminbar {
                z-index: 999 !important;
            }

            #adminmenuback,
            #adminmenuwrap,
            #adminmenu {
                width: 160px;
            }

            #adminmenuwrap {
                box-sizing: border-box;
                padding-top: 1px;
            }

            #adminmenuwrap::before {
                background-image: '
                . $background_image .
                ';
                background-position: center;
                background-repeat: no-repeat;
                background-size: contain;
                box-sizing: border-box;
                content: "";
                display: block;
                height: 80px;
                margin: 18px auto;
                min-height: 80px;
                min-width: 80px;
                width: 80px;
            }

            #adminmenu {
                clear: both;
                margin-top: 0;
            }

            #wpcontent,
            #wpfooter {
                margin-left: 160px;
            }

            #adminmenu
            li.menu-top:not(#menu-dashboard):not(#menu-posts):not(#menu-media):not(#menu-pages),
            #collapse-menu,
            #menu-posts
            .wp-submenu
            a[href*="edit-tags.php"],
            #menu-posts
            .wp-submenu
            a[href*="post_tag"] {
                display: none !important;
            }

            #menu-dashboard,
            #menu-posts,
            #menu-media,
            #menu-pages {
                display: block;
            }

            body.sticky-menu #adminmenuwrap,
            .sticky-menu #adminmenuwrap {
                margin-top: -40px;
                position: fixed;
            }

            body.index-php #wpcontent {
                box-sizing: border-box;
                padding-right: 20px !important;
            }

            body.index-php #wpbody,
            body.index-php #wpbody-content,
            body.index-php .wrap {
                box-sizing: border-box;
                max-width: 100%;
            }

            body.index-php .wrap {
                margin-right: 0;
            }

            #screen-options-link-wrap,
            #contextual-help-link-wrap,
            #contextual-help-wrap {
                display: none !important;
            }

            @media screen and (max-width: 960px) {
                .auto-fold #adminmenuback,
                .auto-fold #adminmenuwrap,
                .auto-fold #adminmenu {
                    width: 160px;
                }

                .auto-fold #wpcontent,
                .auto-fold #wpfooter {
                    margin-left: 160px;
                }

                .auto-fold #adminmenuwrap::before {
                    height: 80px;
                    margin: 18px auto;
                    min-height: 80px;
                    min-width: 80px;
                    width: 80px;
                }
            }

            @media screen and (max-width: 782px) {
                html.wp-toolbar {
                    padding-top: 46px;
                }

                body.auto-fold #wpcontent,
                body.auto-fold #wpfooter {
                    margin-left: 0;
                }

                body.index-php #wpcontent {
                    padding-right: 10px !important;
                }

                body.sticky-menu #adminmenuwrap,
                .sticky-menu #adminmenuwrap {
                    margin-top: 0;
                }
            }
        ';

        wp_add_inline_style(
            'wp-admin',
            $critical_css
        );
    }

}
