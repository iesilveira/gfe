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

        if (
            ! in_array(
                'editor',
                (array) $current_user->roles,
                true
            )
        ) {
            return;
        }

        global $pagenow;

        if (
            ! in_array(
                $pagenow,
                array(
                    'post.php',
                    'edit.php',
                ),
                true
            )
        ) {
            return;
        }

        $post_id = 0;

        if ( isset( $_GET['post'] ) ) {
            $post_id = absint(
                wp_unslash( $_GET['post'] )
            );
        } elseif ( isset( $_POST['post_ID'] ) ) {
            $post_id = absint(
                wp_unslash( $_POST['post_ID'] )
            );
        } elseif ( isset( $_POST['post_id'] ) ) {
            $post_id = absint(
                wp_unslash( $_POST['post_id'] )
            );
        }

        /*
         * Em post.php, o parâmetro post_type normalmente não
         * existe. O tipo correto precisa ser obtido pelo ID.
         * Isso também atende a edição pelo Elementor.
         */
        if ( $post_id > 0 ) {
            $post_type = get_post_type( $post_id );
        } else {
            $post_type = isset( $_REQUEST['post_type'] )
                ? sanitize_key(
                    wp_unslash(
                        $_REQUEST['post_type']
                    )
                )
                : 'post';
        }

        if ( 'page' === $post_type ) {
            $this->smpm_restrict_page_access(
                $current_user->ID,
                $post_id
            );

            return;
        }

        if ( 'post' === $post_type ) {
            $this->smpm_restrict_post_access(
                $current_user->ID,
                $post_id
            );
        }
    }

    private function smpm_restrict_page_access(
        $user_id,
        $post_id = 0
    ) {
        $post_id = absint( $post_id );

        if (
            $post_id <= 0
            && isset( $_REQUEST['post'] )
        ) {
            $post_id = absint(
                wp_unslash( $_REQUEST['post'] )
            );
        }

        /*
         * Na listagem de páginas não existe um conteúdo
         * individual a ser validado.
         */
        if ( $post_id <= 0 ) {
            return;
        }

        $post = get_post( $post_id );

        if (
            ! $post
            || 'page' !== $post->post_type
        ) {
            return;
        }

        $allowed_pages = get_user_meta(
            $user_id,
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

        if (
            ! in_array(
                $post_id,
                $allowed_pages,
                true
            )
        ) {
            wp_die(
                esc_html__(
                    'Você não tem permissão para acessar esta página.',
                    'saude-mg-permission-manager'
                ),
                esc_html__(
                    'Acesso negado',
                    'saude-mg-permission-manager'
                ),
                array(
                    'response'  => 403,
                    'back_link' => true,
                )
            );
        }
    }

    private function smpm_restrict_post_access(
        $user_id,
        $post_id = 0
    ) {
        $post_id = absint( $post_id );

        if (
            $post_id <= 0
            && isset( $_REQUEST['post'] )
        ) {
            $post_id = absint(
                wp_unslash( $_REQUEST['post'] )
            );
        }

        /*
         * Na listagem de posts não existe um conteúdo
         * individual a ser validado.
         */
        if ( $post_id <= 0 ) {
            return;
        }

        $post = get_post( $post_id );

        /*
         * Uma página nunca deve passar pela validação
         * de categorias de posts.
         */
        if (
            ! $post
            || 'post' !== $post->post_type
        ) {
            return;
        }

        $allowed_categories = get_user_meta(
            $user_id,
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

        $post_categories = array_values(
            array_unique(
                array_filter(
                    array_map(
                        'absint',
                        wp_get_post_categories(
                            $post_id
                        )
                    )
                )
            )
        );

        $permitted_categories = array_intersect(
            $post_categories,
            $allowed_categories
        );

        if ( empty( $permitted_categories ) ) {
            wp_die(
                esc_html__(
                    'Você não tem permissão para acessar este post.',
                    'saude-mg-permission-manager'
                ),
                esc_html__(
                    'Acesso negado',
                    'saude-mg-permission-manager'
                ),
                array(
                    'response'  => 403,
                    'back_link' => true,
                )
            );
        }
    }

    public function smpm_filter_user_capabilities(
        $allcaps,
        $caps,
        $args
    ) {
        if ( ! is_array( $allcaps ) ) {
            return $allcaps;
        }

        $current_user = wp_get_current_user();

        if (
            ! in_array(
                'editor',
                (array) $current_user->roles,
                true
            )
        ) {
            return $allcaps;
        }

        if (
            ! is_array( $args )
            || ! isset( $args[0] )
        ) {
            return $allcaps;
        }

        $requested_capability = (
            (string) $args[0]
        );

        $controlled_capabilities = array(
            'edit_post',
            'edit_page',
            'delete_post',
            'delete_page',
            'read_post',
            'read_page',
        );

        if (
            ! in_array(
                $requested_capability,
                $controlled_capabilities,
                true
            )
        ) {
            return $allcaps;
        }

        /*
         * O terceiro argumento de user_has_cap nem sempre
         * contém um ID numérico.
         *
         * Plugins e o editor de blocos podem fornecer um
         * WP_Post ou um objeto de contexto. Nunca devemos
         * enviar esses objetos diretamente para absint().
         */
        $post_id = 0;
        $context = $args[2] ?? null;

        if (
            is_int( $context )
            || is_string( $context )
            || is_float( $context )
        ) {
            if ( is_numeric( $context ) ) {
                $post_id = absint( $context );
            }
        } elseif ( $context instanceof WP_Post ) {
            $post_id = absint( $context->ID );
        } elseif (
            class_exists(
                'WP_Block_Editor_Context',
                false
            )
            && $context instanceof WP_Block_Editor_Context
            && isset( $context->post )
            && $context->post instanceof WP_Post
        ) {
            $post_id = absint(
                $context->post->ID
            );
        } elseif (
            is_object( $context )
            && isset( $context->ID )
            && is_numeric( $context->ID )
        ) {
            $post_id = absint( $context->ID );
        } elseif (
            is_array( $context )
            && isset( $context['ID'] )
            && is_numeric( $context['ID'] )
        ) {
            $post_id = absint(
                $context['ID']
            );
        }

        /*
         * Sem um post real, não existe uma permissão
         * individual que o GFE deva alterar.
         *
         * As capacidades gerais de criação são tratadas
         * por smpm_fix_new_post_capabilities().
         */
        if ( $post_id <= 0 ) {
            return $allcaps;
        }

        $post = get_post( $post_id );

        if ( ! $post instanceof WP_Post ) {
            return $allcaps;
        }

        $has_permission = false;

        if ( 'page' === $post->post_type ) {
            $allowed_pages = get_user_meta(
                $current_user->ID,
                'smpm_allowed_pages',
                true
            );

            $allowed_pages = is_array(
                $allowed_pages
            )
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

            $has_permission = in_array(
                $post_id,
                $allowed_pages,
                true
            );
        } elseif ( 'post' === $post->post_type ) {
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

            /*
             * Um novo post é criado inicialmente como
             * auto-draft e ainda não possui categorias.
             *
             * O Editor pode editar esse auto-draft quando:
             * - ele é o autor do conteúdo; e
             * - possui ao menos uma categoria permitida.
             *
             * A categoria efetiva continuará limitada pela
             * interface e pelas demais regras do GFE.
             */
            $is_own_new_post = (
                in_array(
                    $post->post_status,
                    array(
                        'auto-draft',
                        'draft',
                    ),
                    true
                )
                && absint( $post->post_author )
                    === absint( $current_user->ID )
                && empty(
                    wp_get_post_categories(
                        $post_id
                    )
                )
            );

            if (
                $is_own_new_post
                && ! empty( $allowed_categories )
            ) {
                $has_permission = true;
            } else {
                $post_categories = array_values(
                    array_unique(
                        array_filter(
                            array_map(
                                'absint',
                                wp_get_post_categories(
                                    $post_id
                                )
                            )
                        )
                    )
                );

                $has_permission = ! empty(
                    array_intersect(
                        $post_categories,
                        $allowed_categories
                    )
                );
            }
        } else {
            return $allcaps;
        }

        /*
         * $caps contém as capacidades primitivas calculadas
         * pelo WordPress para esta operação.
         */
        foreach (
            array_filter( (array) $caps )
            as $primitive_capability
        ) {
            if (
                ! is_string(
                    $primitive_capability
                )
            ) {
                continue;
            }

            $allcaps[
                $primitive_capability
            ] = $has_permission;
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

        $site_name = trim(
            wp_strip_all_tags(
                (string) get_bloginfo( 'name' )
            )
        );

        if ( '' === $site_name ) {
            $site_name = esc_html__(
                'Ver site',
                'saude-mg-permission-manager'
            );
        }

        /*
         * Remove os itens da barra, preservando apenas
         * os grupos estruturais necessários.
         */
        $nodes = $wp_admin_bar->get_nodes();

        if ( is_array( $nodes ) ) {
            foreach ( $nodes as $node ) {
                if ( ! isset( $node->id ) ) {
                    continue;
                }

                if (
                    in_array(
                        $node->id,
                        array(
                            'top-secondary',
                        ),
                        true
                    )
                ) {
                    continue;
                }

                $wp_admin_bar->remove_node(
                    $node->id
                );
            }
        }

        /*
         * Restaura o item wp-admin-bar-site-name.
         * O link abre a página pública do site.
         */
        $wp_admin_bar->add_node(
            array(
                'id'     => 'site-name',
                'parent' => false,
                'title'  => esc_html( $site_name ),
                'href'   => home_url( '/' ),
                'meta'   => array(
                    'class' => 'smpm-site-name',
                    'title' => esc_attr__(
                        'Visitar o site',
                        'saude-mg-permission-manager'
                    ),
                ),
            )
        );

        /*
         * Recria o item do usuário sem avatar e sem link
         * para edição do perfil.
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

            #wpadminbar
            .ab-top-menu
            > li:not(#wp-admin-bar-site-name) {
                display: none !important;
            }

            #wpadminbar
            #wp-admin-bar-site-name {
                display: block !important;
            }

            #wpadminbar
            #wp-admin-bar-site-name
            > .ab-item {
                display: block !important;
            }

            #wpadminbar
            .ab-top-secondary {
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
         * Os elementos são ocultados pelo CSS carregado
         * no cabeçalho administrativo.
         */
    }

    public function smpm_custom_dashboard_content() {
        global $pagenow;

        if ( 'index.php' !== $pagenow ) {
            return;
        }

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

        $has_pages = (
            is_array( $allowed_pages )
            && ! empty( $allowed_pages )
        );

        $has_categories = (
            is_array( $allowed_categories )
            && ! empty( $allowed_categories )
        );

        $first_name = trim(
            (string) $current_user->user_firstname
        );

        if ( '' === $first_name ) {
            $first_name = trim(
                (string) $current_user->display_name
            );
        }

        if ( '' === $first_name ) {
            $first_name = (
                (string) $current_user->user_login
            );
        }

        if ( ! $has_pages && ! $has_categories ) {
            $message = get_option(
                'smpm_first_access_message',
                $this->smpm_get_default_first_access_message()
            );
        } else {
            $message = get_option(
                'smpm_dashboard_message',
                $this->smpm_get_default_dashboard_message()
            );
        }

        $message = str_replace(
            '{nome}',
            esc_html( $first_name ),
            (string) $message
        );

        echo '<div id="smpm-custom-dashboard" '
            . 'style="margin-top:20px;'
            . 'background:#fff;'
            . 'padding:20px;'
            . 'border:1px solid #ccd0d4;'
            . 'box-shadow:0 1px 1px '
            . 'rgba(0,0,0,.04);">';

        echo wp_kses_post( $message );

        echo '</div>';

        echo '<script>
            jQuery(function ($) {
                $("#wpbody-content .wrap").hide();

                $("#smpm-custom-dashboard")
                    .prependTo("#wpbody-content");
            });
        </script>';
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

    public function smpm_fix_new_post_capabilities(
        $allcaps,
        $caps,
        $args
    ) {
        if ( ! is_array( $allcaps ) ) {
            return $allcaps;
        }

        $current_user = wp_get_current_user();

        if (
            ! in_array(
                'editor',
                (array) $current_user->roles,
                true
            )
        ) {
            return $allcaps;
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

        /*
         * Sem categorias permitidas, o Editor não deve
         * criar nem publicar novos posts.
         */
        if ( empty( $allowed_categories ) ) {
            return $allcaps;
        }

        $requested_capability = '';

        if (
            is_array( $args )
            && isset( $args[0] )
            && is_string( $args[0] )
        ) {
            $requested_capability = $args[0];
        }

        $primitive_capabilities = array_filter(
            (array) $caps,
            'is_string'
        );

        $is_post_creation_request = (
            in_array(
                'edit_posts',
                $primitive_capabilities,
                true
            )
            || in_array(
                'publish_posts',
                $primitive_capabilities,
                true
            )
            || in_array(
                $requested_capability,
                array(
                    'edit_posts',
                    'publish_posts',
                    'create_posts',
                ),
                true
            )
        );

        if ( ! $is_post_creation_request ) {
            return $allcaps;
        }

        $allcaps['edit_posts'] = true;
        $allcaps['publish_posts'] = true;
        $allcaps['create_posts'] = true;

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
        if ( ! is_array( $args ) ) {
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
            ! $current_user->exists()
            || ! in_array(
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

        /*
         * Se a consulta já possui uma lista include,
         * mantém somente a interseção entre essa lista
         * e as categorias autorizadas.
         */
        $existing_include = array();

        if (
            isset( $args['include'] )
            && '' !== $args['include']
        ) {
            $existing_include = array_values(
                array_unique(
                    array_filter(
                        array_map(
                            'absint',
                            (array) $args['include']
                        )
                    )
                )
            );
        }

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

        $args['include'] = (
            ! empty( $allowed_categories )
        )
            ? $allowed_categories
            : array( 0 );

        /*
         * Essas chaves devem continuar presentes para
         * evitar os avisos corrigidos na versão 17.13.1.
         */
        if ( ! isset( $args['exclude'] ) ) {
            $args['exclude'] = array();
        }

        if ( ! isset( $args['exclude_tree'] ) ) {
            $args['exclude_tree'] = array();
        }

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

    public function smpm_output_editor_menu_styles() {
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
         * Mantém apenas as restrições funcionais do menu.
         * Nenhuma logo, imagem, dimensão ou posicionamento
         * personalizado é aplicado à barra lateral.
         */
        echo '<style id="smpm-editor-menu-restrictions">
            #wpadminbar {
                z-index: 999 !important;
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

            @media screen and (max-width: 782px) {
                body.index-php #wpcontent {
                    padding-right: 10px !important;
                }
            }
        </style>';
    }

    public function smpm_restrict_visible_categories(
        $clauses,
        $taxonomies,
        $args
    ) {
        if ( ! is_array( $clauses ) ) {
            return $clauses;
        }

        $taxonomies = (array) $taxonomies;

        if (
            ! in_array(
                'category',
                $taxonomies,
                true
            )
        ) {
            return $clauses;
        }

        $current_user = wp_get_current_user();

        if (
            ! $current_user->exists()
            || ! in_array(
                'editor',
                (array) $current_user->roles,
                true
            )
        ) {
            return $clauses;
        }

        /*
         * A restrição precisa funcionar também nas
         * requisições REST e AJAX do editor de blocos.
         *
         * Por isso, não limitamos a execução apenas
         * ao retorno de is_admin().
         */
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

        if ( ! isset( $clauses['where'] ) ) {
            $clauses['where'] = '';
        }

        /*
         * Sem categorias permitidas, nenhuma categoria
         * deve ser retornada para o Editor.
         */
        if ( empty( $allowed_categories ) ) {
            $clauses['where'] .= ' AND 1 = 0';

            return $clauses;
        }

        /*
         * Os IDs são convertidos com absint antes da
         * montagem da cláusula, impedindo a inserção
         * de valores arbitrários na consulta.
         */
        $category_ids = implode(
            ',',
            array_map(
                'absint',
                $allowed_categories
            )
        );

        /*
         * WP_Term_Query utiliza o alias "tt" para a
         * tabela term_taxonomy.
         *
         * A restrição no SQL garante que dropdowns,
         * checklists, REST e AJAX recebam somente as
         * categorias atribuídas ao Editor.
         */
        $clauses['where'] .= sprintf(
            ' AND tt.term_id IN (%s)',
            $category_ids
        );

        return $clauses;
    }

    private function smpm_get_default_dashboard_message() {
        return '<h1>Bem-vindo(a) à Área de Administração '
            . 'do Portal da Saúde MG</h1>'
            . '<p>Olá, {nome}! 👋</p>'
            . '<p>Seja bem-vindo(a) à área administrativa '
            . 'do Portal da Saúde de Minas Gerais. O seu '
            . 'perfil foi autorizado pela Assessoria de '
            . 'Comunicação (ASCOM) para gerenciar conteúdos '
            . 'de páginas e/ou posts específicos relacionados '
            . 'à sua área técnica.</p>'
            . '<p>Lembre-se de que todas as alterações '
            . 'realizadas aqui são refletidas diretamente '
            . 'no portal, por isso, revise com atenção cada '
            . 'publicação antes de atualizar.</p>'
            . '<p>Em caso de dúvidas sobre a utilização da '
            . 'ferramenta ou para solicitar suporte, entre '
            . 'em contato com a equipe da ASCOM pelo e-mail '
            . '<strong>sesdigitalmg@gmail.com</strong>.</p>'
            . '<p>Conte com a gente para garantir que as '
            . 'informações publicadas estejam sempre '
            . 'atualizadas, claras e de qualidade.</p>'
            . '<p>Obrigado por contribuir com a comunicação '
            . 'da Saúde MG!</p>';
    }

    private function smpm_get_default_first_access_message() {
        return '<h1>Bem-vindo, {nome}!</h1>'
            . '<h3>Este é o seu primeiro acesso?</h3>'
            . '<p>Entre em contato com o Núcleo de Canais '
            . 'Digitais da ASCOM para a liberação de '
            . 'conteúdo e permissões.</p>';
    }

    public function smpm_sanitize_dashboard_message(
        $message
    ) {
        if ( ! is_string( $message ) ) {
            return '';
        }

        return wp_kses_post(
            wp_unslash( $message )
        );
    }

    public function smpm_register_settings() {
        register_setting(
            'smpm_gfe_settings',
            'smpm_dashboard_message',
            array(
                'type'              => 'string',
                'sanitize_callback' => array(
                    $this,
                    'smpm_sanitize_dashboard_message',
                ),
                'default'           => (
                    $this->smpm_get_default_dashboard_message()
                ),
            )
        );

        register_setting(
            'smpm_gfe_settings',
            'smpm_first_access_message',
            array(
                'type'              => 'string',
                'sanitize_callback' => array(
                    $this,
                    'smpm_sanitize_dashboard_message',
                ),
                'default'           => (
                    $this->smpm_get_default_first_access_message()
                ),
            )
        );
    }

    public function smpm_add_settings_page() {
        add_submenu_page(
            null,
            __(
                'Configurações do GFE',
                'saude-mg-permission-manager'
            ),
            __(
                'Configurações do GFE',
                'saude-mg-permission-manager'
            ),
            'manage_options',
            'smpm-gfe-settings',
            array(
                $this,
                'smpm_render_settings_page',
            )
        );
    }

    public function smpm_add_plugin_action_links(
        $links
    ) {
        if ( ! current_user_can( 'manage_options' ) ) {
            return $links;
        }

        $settings_link = sprintf(
            '<a href="%s">%s</a>',
            esc_url(
                admin_url(
                    'admin.php?page=smpm-gfe-settings'
                )
            ),
            esc_html__(
                'Configurações',
                'saude-mg-permission-manager'
            )
        );

        array_unshift(
            $links,
            $settings_link
        );

        return $links;
    }

    public function smpm_render_settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die(
                esc_html__(
                    'Você não tem permissão para acessar '
                    . 'esta página.',
                    'saude-mg-permission-manager'
                ),
                esc_html__(
                    'Acesso negado',
                    'saude-mg-permission-manager'
                ),
                array(
                    'response' => 403,
                )
            );
        }

        $dashboard_message = get_option(
            'smpm_dashboard_message',
            $this->smpm_get_default_dashboard_message()
        );

        $first_access_message = get_option(
            'smpm_first_access_message',
            $this->smpm_get_default_first_access_message()
        );

        ?>
        <div class="wrap">
            <h1>
                <?php esc_html_e(
                    'Configurações do GFE',
                    'saude-mg-permission-manager'
                ); ?>
            </h1>

            <p>
                <?php esc_html_e(
                    'Configure as mensagens exibidas aos '
                    . 'Editores na página Painel.',
                    'saude-mg-permission-manager'
                ); ?>
            </p>

            <div
                class="notice notice-info inline"
                style="margin:16px 0;"
            >
                <p>
                    <?php
                    echo wp_kses_post(
                        __(
                            'Use o marcador '
                            . '<code>{nome}</code> para '
                            . 'inserir automaticamente o nome '
                            . 'do Editor.',
                            'saude-mg-permission-manager'
                        )
                    );
                    ?>
                </p>
            </div>

            <form
                method="post"
                action="options.php"
            >
                <?php
                settings_fields(
                    'smpm_gfe_settings'
                );
                ?>

                <div
                    class="card"
                    style="
                        max-width:none;
                        margin-top:20px;
                        padding:20px;
                    "
                >
                    <h2>
                        <?php esc_html_e(
                            'Mensagem principal do Painel',
                            'saude-mg-permission-manager'
                        ); ?>
                    </h2>

                    <p>
                        <?php esc_html_e(
                            'Exibida quando o Editor possui '
                            . 'páginas ou categorias liberadas.',
                            'saude-mg-permission-manager'
                        ); ?>
                    </p>

                    <?php
                    wp_editor(
                        $dashboard_message,
                        'smpm_dashboard_message_editor',
                        array(
                            'textarea_name' => (
                                'smpm_dashboard_message'
                            ),
                            'textarea_rows' => 14,
                            'media_buttons' => false,
                            'teeny'         => false,
                            'quicktags'     => true,
                        )
                    );
                    ?>
                </div>

                <div
                    class="card"
                    style="
                        max-width:none;
                        margin-top:20px;
                        padding:20px;
                    "
                >
                    <h2>
                        <?php esc_html_e(
                            'Mensagem de primeiro acesso',
                            'saude-mg-permission-manager'
                        ); ?>
                    </h2>

                    <p>
                        <?php esc_html_e(
                            'Exibida quando o Editor ainda '
                            . 'não possui páginas nem '
                            . 'categorias liberadas.',
                            'saude-mg-permission-manager'
                        ); ?>
                    </p>

                    <?php
                    wp_editor(
                        $first_access_message,
                        'smpm_first_access_message_editor',
                        array(
                            'textarea_name' => (
                                'smpm_first_access_message'
                            ),
                            'textarea_rows' => 9,
                            'media_buttons' => false,
                            'teeny'         => false,
                            'quicktags'     => true,
                        )
                    );
                    ?>
                </div>

                <?php
                submit_button(
                    __(
                        'Salvar configurações',
                        'saude-mg-permission-manager'
                    )
                );
                ?>
            </form>
        </div>
        <?php
    }

}
