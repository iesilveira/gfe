<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class GFE_Loader {

    public static function init() {
        require_once GFE_PATH . 'includes/class-gfe-editor.php';
        require_once GFE_PATH . 'includes/admin/class-gfe-admin-page.php';
        require_once GFE_PATH . 'includes/updater/class-gfe-updater.php';

        GFE_Editor::init();
        GFE_Admin_Page::init();
        GFE_Updater::init();
    }
}
