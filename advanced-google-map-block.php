<?php

/**
 * Plugin Name:       Google Map Block
 * Description:       A simple Google Map Block for Gutenberg.
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Version:           1.2.0
 * Author:            wpxero
 * Author URI:        https://wpxero.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       advanced-google-map-block
 */



if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Main XeroGoogleMap Class
 *
 * @class XeroGoogleMap
 * @since 1.0.0
 */



class XeroGoogleMap {
    /**
     * The single class instance.
     *
     * @var $instance
     */
    private static $instance = null;

    /**
     * Path to the plugin directory
     *
     * @var $plugin_path
     */
    public $plugin_path;

    /**
     * URL to the plugin directory
     *
     * @var $plugin_url
     */
    public $plugin_url;

    /**
     * Plugin name
     *
     * @var $plugin_name
     */
    public $plugin_name;

    /**
     * Plugin version
     *
     * @var $plugin_version
     */
    public $plugin_version;

    /**
     * Plugin slug
     *
     * @var $plugin_slug
     */
    public $plugin_slug;

    /**
     * Plugin name sanitized
     *
     * @var $plugin_name_sanitized
     */
    public $plugin_name_sanitized;

    /**
     * XeroGoogleMap constructor.
     */
    public function __construct() {
        /* We do nothing here! */
    }

    /**
     * Get the single class instance.
     *
     * @return XeroGoogleMap
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
            self::$instance->define_constants(); // Define constants
            self::$instance->init_hooks(); // Initialize hooks
        }
        return self::$instance;
    }

    public function init_hooks() {
        $this->load_files();
        add_action('enqueue_block_assets', array($this, 'enqueue_editor_assets'));
        add_action('init', array($this, 'register_blocks'));
        add_filter('block_categories_all', array($this, 'add_custom_block_category'));


        if (!is_admin()) {
            add_action('wp_enqueue_scripts', array($this, 'enqueue_global_style'));
            add_action('wp_enqueue_scripts', array($this, 'enqueue_front_scripts'));
            add_action('enqueue_block_assets', array($this, 'enqueue_front_scripts'));
        }
    }
    /**
     * Define Constants
     */
    public static function define_constants() {
        define('XERO_GOOGLE_MAP_FILE', __FILE__);
        define('XERO_GOOGLE_MAP_SLUG', 'xero-google-map');
        define('XERO_GOOGLE_MAP_VERSION', '1.2.0');
        define('XERO_GOOGLE_MAP_DIR_PATH', plugin_dir_path(__FILE__));
        define('XERO_GOOGLE_MAP_ADMIN_URL', plugin_dir_url(__FILE__));
        define('XERO_GOOGLE_MAP_WP_VERSION', (float) get_bloginfo('version'));
        define('XERO_GOOGLE_MAP_PHP_VERSION', (float) phpversion());
    }

    public function load_files() {
        require_once XERO_GOOGLE_MAP_DIR_PATH . 'includes/CSSBuilder.php';
        require_once XERO_GOOGLE_MAP_DIR_PATH . 'includes/Property.php';
    }
    public function add_custom_block_category($categories) {
        $category = array(
            'slug' => 'xero',
            'title' => 'WPXERO',
        );
        return array_merge(array($category), $categories);
    }

    public function enqueue_editor_assets() {
        if (!is_admin()) {
            return;
        }
        wp_enqueue_style('style-wpxero', plugins_url('dist/style-wpxero.css', __FILE__));
        $asset_path = __DIR__ . '/dist/wpxero.asset.php';
        $args = require $asset_path;
        wp_enqueue_style('@wpxero/library', plugins_url('/dist/wpxero.css', __FILE__), array('wp-codemirror'), $args['version']);
        wp_register_script('@wpxero/library', plugins_url('/dist/wpxero.js', __FILE__), $args['dependencies'], $args['version']);
        wp_localize_script('@wpxero/library', 'WPXERO', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'home_url' => home_url('/'),
            'nonce' => wp_create_nonce('wpxero-nonce'),
            'rest_url' => rest_url('wpxero/v1'),
            'assetsPath' => plugins_url('/assets/', __FILE__),
        ));
    }



    public function register_blocks() {
        $dist_dir = __DIR__ . '/dist/';
        foreach (scandir($dist_dir) as $name) {
            if (!str_contains($name, '.')) {
                register_block_type($dist_dir . $name);
            }
        }
    }

    public function enqueue_global_style() {
        wp_enqueue_style('style-wpxero', plugins_url('dist/style-wpxero.css', __FILE__), array(), XERO_GOOGLE_MAP_VERSION);
        wp_enqueue_script('wpxero-helper', XERO_GOOGLE_MAP_ADMIN_URL . 'assets/scripts/helper.js', [], XERO_GOOGLE_MAP_VERSION, true);
        wp_localize_script('wpxero-helper', 'WPXERO', array(
            'timezone' => wp_timezone_string(),
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wpxero-nonce'),
        ));
    }

    public function enqueue_front_scripts() {
        $post = get_post();
        if ($post && $post->post_content) {
            $blocks = parse_blocks($post->post_content);
            foreach ($blocks as $block) {
                $this->style_block($block);
            }
        }

        /* block-templates */
        global $_wp_current_template_content;
        if (current_theme_supports('block-templates') && $_wp_current_template_content) {
            $blocks = parse_blocks($_wp_current_template_content);
            foreach ($blocks as $block) {
                $this->style_block($block);
            }
        }
        //enque swiper css from cdn
        // wp_enqueue_style('swiper', 'https://unpkg.com/swiper/swiper-bundle.min.css');
        //enque swiper js from cdn
        // wp_enqueue_script('swiper', 'https://unpkg.com/swiper/swiper-bundle.min.js', array(), '6.8.4', true);
    }



    public function style_block($block) {
        $attrs = $block['attrs'] ?? array();
        $block_id = $attrs['blockId'] ?? '';
        if ($block_id) {
            $css = $this->block_css($block);
            if ($css) {
                wp_add_inline_style('style-wpxero', $css);
            }
        }

        foreach ($block['innerBlocks'] ?? array() as $inner_block) {
            $this->style_block($inner_block);
        }
    }

    public function block_css($block) {

        $css = '';
        $registry = WP_Block_Type_Registry::get_instance();
        $attrs = $block['attrs'] ?? array();
        $block_id = $attrs['blockId'] ?? '';
        if ($block_id) {
            foreach ($attrs as $attr => $value) {
                if (is_array($value) && !empty($value)) {
                    $selector = $value['__selector__'] ?? null;
                    if (!$selector) {
                        $block_type = $registry->get_registered($block['blockName']);
                        if ($block_type) {
                            $selector = $block_type->attributes[$attr]['selector'] ?? null;
                        }
                    }
                    $css .= $this->build_css("wpxero-$block_id", $value, array('selector' => $selector));
                }
            }
        }
        return $css;
    }

    public function build_css($id, $attributes, $options = array()) {
        $builder = new xero\XeroGoogleMap\CSSBuilder();
        $builder->add_attributes($attributes);
        return $builder->to_css($id, $options);
    }
}



/**
 * Function works with the XeroGoogleMap class instance
 *
 * @return object XeroGoogleMap
 */
function xero_google_map() {
    return XeroGoogleMap::instance();
}
add_action('plugins_loaded', 'xero_google_map');
