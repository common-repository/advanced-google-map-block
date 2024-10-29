<?php

declare(strict_types=1);

namespace xero\XeroGoogleMap;

defined('ABSPATH') || exit;


class XERO_GOOGLE_MAP_ADMIN {
    /**
     * XERO_GOOGLE_MAP_ADMIN constructor.
     */
    protected $icon_data_url;
    public function __construct() {
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
    }

    public function admin_enqueue_scripts($screen) {
        if ($screen !== 'toplevel_page_xero-google-map') {
            return;
        }
        wp_enqueue_style('wp-components');
        wp_enqueue_style('xero-blocks-admin', plugins_url('admin/style-xero-blocks.css', __DIR__), array(), XERO_GOOGLE_MAP_VERSION);
        wp_enqueue_script('xero-blocks-admin', plugins_url('admin/xero-blocks-admin.js', __DIR__), array('wp-i18n', 'wp-element', 'wp-components', 'wp-blocks', 'wp-editor', 'wp-data'), XERO_GOOGLE_MAP_VERSION, true);
        wp_localize_script('xero-blocks-admin', 'XeroGoogleMapAdminObj', [
            'version' => XERO_GOOGLE_MAP_VERSION,
            'nonce' => wp_create_nonce('wp_rest'),
            'restUrl' => rest_url('xero-blocks/v1'),
            'restNonce' => wp_create_nonce('wp_rest'),
            'restNonceHeader' => wp_create_nonce('wp_rest'),
            'assetsUrl' => XERO_GOOGLE_MAP_ADMIN_URL . 'assets/',
        ]);
    }



    // Path: includes/Admin.php (continued
    /**
     * Add admin menu.
     */
    public function admin_menu() {
        add_menu_page(
            esc_html__('XeroGoogleMap', 'advanced-google-map-block'),
            esc_html__('XeroGoogleMap', 'advanced-google-map-block'),
            'manage_options',
            'xero-google-map',
            array($this, 'display_admin_page'),
            'data:image/svg+xml;base64,' . base64_encode(file_get_contents(XERO_GOOGLE_MAP_DIR_PATH . 'assets/images/admin-icon.svg')),
            56.9
        );
    }

    /**
     * Render the admin page.
     */
    public function display_admin_page() {
?>
        <div class="xero-google-map-admin-page"></div>
<?php
    }
}

new XERO_GOOGLE_MAP_ADMIN();
