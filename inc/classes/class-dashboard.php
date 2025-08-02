<?php

/**
 * Admin Dashboard Page.
 *
 * @package interactive-lesson
 * @since 1.0.0
 */

namespace Interactive_Lesson\Inc;

use Interactive_Lesson\Inc\Traits\Singleton;
use Interactive_Lesson\Inc\Utils;

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Class Dashboard
 */
class Dashboard
{
    use Singleton;

    /**
     * Menu information.
     *
     * @since 1.0.0
     * @access private
     * @var array $menu_info Admin menu information.
     */
    private $menu_info;

    /**
     * Construct method.
     *
     * Initializes the class and sets up necessary hooks.
     */
    protected function __construct()
    {
        $this->menu_info = Utils::white_label()['admin_menu_page'] ?? [];
        $this->setup_hooks();
    }

    /**
     * Sets up hooks for the class.
     *
     * @since 1.0.0
     * @return void
     */
    protected function setup_hooks(): void
    {
        add_action('admin_menu', [$this, 'add_admin_menu'], 99);
        add_filter('admin_body_class', [$this, 'add_has_sticky_header']);
        add_action('admin_enqueue_scripts', [$this, 'register_admin_assets']);

        add_action('rest_api_init', [$this, 'register_settings']);
        add_action('admin_init', [$this, 'register_settings']);
    }

    /**
     * Adds the admin menu page.
     *
     * @since 1.0.0
     * @return void
     */
    public function add_admin_menu(): void
    {
        if (empty($this->menu_info)) {
            return;
        }

        add_menu_page(
            $this->menu_info['page_title'] ?? esc_html__('Interactive Lesson Page', 'interactive-lesson'),
            $this->menu_info['menu_title'] ?? esc_html__('Interactive Lesson', 'interactive-lesson'),
            'manage_options',
            $this->menu_info['menu_slug'] ?? INTERACTIVE_LESSON_NAME,
            [$this, 'render_page_callback'],
            $this->get_menu_icon(),
            //$this->menu_info['icon_url'] ?? '',
            $this->menu_info['position'] ?? null
        );
    }

    /**
     * Get the base64-encoded SVG icon for the menu.
     *
     * @return string Base64-encoded SVG icon.
     */
    private function get_menu_icon()
    {
        $svg = '<svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 222.873 222.884" enable-background="new 0 0 222.873 222.884" xml:space="preserve"><g><g><g><path fill="#BFBFBF" d="M63.079,157.804c-0.872-1.744-1.663-3.805-2.421-6.306L57.443,141H23.146c-1.21,0-1.812-0.604-1.812-1.812v-20.774H78.5c4.521,0,7.631,0.718,9.382,2.125L104.9,68.736c0.033-0.102,0.077-0.183,0.111-0.284c-0.031-0.03-0.058-0.066-0.089-0.096c-4.044-3.809-10.854-5.714-20.427-5.714H4.182C1.395,62.642,0,64.034,0,66.824v89.236c0,2.787,1.395,4.182,4.182,4.182h60.403C64.049,159.484,63.527,158.698,63.079,157.804z M21.333,83.694c0-1.205,0.603-1.812,1.812-1.812h57.028c3.625,0,6.25,0.676,7.877,2.024c1.626,1.348,2.44,3.463,2.44,6.343v4.74c0,2.697-0.836,4.74-2.51,6.135c-1.672,1.395-4.835,2.093-9.481,2.093H21.333V83.694z"/></g><g><g><path fill="#BFBFBF" d="M70.023,88.259H53.366c-2.685,0-4.606,2.596-3.82,5.164l1.046,3.415h22.014L70.023,88.259z"/></g><g><path fill="#BFBFBF" d="M216.716,63.617c-0.139-0.648-0.581-0.975-1.324-0.975h-18.824c-0.467,0-0.906,0.115-1.325,0.349c-0.419,0.23-0.721,0.719-0.906,1.464l-22.029,75.013c-0.188,0.651-0.559,0.975-1.116,0.975h-0.836c-0.557,0-0.93-0.324-1.115-0.975l-23.146-68.741c-1.025-2.971-2.348-5.064-3.974-6.272c-1.627-1.208-3.974-1.812-7.041-1.812h-13.805c-2.508,0-4.601,0.557-6.273,1.672c-1.674,1.115-3.022,3.254-4.044,6.412L88.37,139.468c-0.187,0.651-0.557,0.975-1.115,0.975h-0.698c-0.651,0-1.023-0.324-1.115-0.975l-4.419-14.677H78.5H59.15l7.609,24.854c0.649,2.139,1.324,3.905,2.021,5.3c0.698,1.392,1.51,2.488,2.44,3.276c0.928,0.791,1.974,1.324,3.137,1.604c1.162,0.277,2.532,0.417,4.114,0.417h15.895c2.695,0,4.81-0.744,6.343-2.23s2.999-4.276,4.392-8.368l22.17-66.228c0.092-0.651,0.464-0.978,1.115-0.978h0.559c0.649,0,1.021,0.327,1.115,0.978l21.891,66.228c1.394,4.092,2.857,6.882,4.392,8.368c1.534,1.485,3.647,2.23,6.343,2.23h16.313c2.695,0,4.832-0.719,6.414-2.161c1.579-1.439,3.019-4.254,4.322-8.436l26.771-83.798C216.786,65.012,216.857,64.268,216.716,63.617z"/></g></g></g><g><g><path fill="#BFBFBF" d="M49.505,52.521c9.531-14.393,23.11-26.35,40.041-33.908c40.388-18.029,86.691-5.277,112.655,28.167C179.193,14.543,139.694-4.378,97.635,0.869C62.965,5.194,34.027,25.022,16.869,52.521H49.505z"/></g><g><path fill="#BFBFBF" d="M222.004,97.647c-0.167-1.334-0.385-2.649-0.597-3.966c4.634,39.767-16.645,79.619-55.138,96.802c-36.971,16.504-78.906,7.221-105.654-20.12H16.854c22.27,35.693,63.88,57.203,108.372,51.652C186.294,214.397,229.623,158.715,222.004,97.647z"/></g></g></g></svg>';
        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }

    /**
     * Checks if the current screen is the plugin's menu page.
     *
     * @since 1.0.0
     * @return bool True if the current screen is the menu page, false otherwise.
     */
    public function is_menu_page(): bool
    {
        $screen = get_current_screen();
        $admin_scripts_bases = ['toplevel_page_' . INTERACTIVE_LESSON_NAME];

        return isset($screen->base) && in_array($screen->base, $admin_scripts_bases, true);
    }

    /**
     * Adds a sticky header class to the admin body.
     *
     * @since 1.0.0
     * @param string $classes Space-separated string of class names.
     * @return string Updated classes with sticky header class if applicable.
     */
    public function add_has_sticky_header(string $classes): string
    {
        if ($this->is_menu_page()) {
            $classes .= ' at-has-hdr-stky';
        }

        return trim($classes);
    }

    /**
     * Renders the root div for the React application.
     *
     * @since 1.0.0
     * @return void
     */
    public function render_page_callback(): void
    {
        printf('<div id="%s"></div>', esc_attr(INTERACTIVE_LESSON_NAME));
    }

    /**
     * Registers and enqueues admin assets.
     *
     * @since 1.0.0
     * @return void
     */
    public function register_admin_assets(): void
    {

        if (!$this->is_menu_page()) {
            return;
        }

        $suffix = is_rtl() ? '-rtl' : '';

        // Register styles.
        wp_register_style('atomic', INTERACTIVE_LESSON_BUILD_PATH_URL . "/library/atomic-css/atomic.min{$suffix}.css", [], filemtime(INTERACTIVE_LESSON_BUILD_PATH . "/library/atomic-css/atomic.min{$suffix}.css"), 'all');
        wp_register_style('interactive-lesson-admin', INTERACTIVE_LESSON_BUILD_PATH_URL . "/admin/index{$suffix}.css", ['atomic', 'wp-components'], filemtime(INTERACTIVE_LESSON_BUILD_PATH . "/admin/index{$suffix}.css"), 'all');

        // Enqueue Styles.
        wp_enqueue_style('interactive-lesson-admin');


        $asset_config_file = sprintf('%s/admin/index.asset.php', INTERACTIVE_LESSON_BUILD_PATH);
        if (!file_exists($asset_config_file)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Interactive_Lesson: Asset config file not found at ' . $asset_config_file);
            }
            return;
        }

        $editor_asset   = include_once $asset_config_file;
        $js_dependencies = (! empty($editor_asset['dependencies'])) ? $editor_asset['dependencies'] : [];
        $version         = (! empty($editor_asset['version'])) ? $editor_asset['version'] : filemtime($asset_config_file);

        // Register scripts.
        wp_register_script(
            'interactive-lesson-admin',
            INTERACTIVE_LESSON_BUILD_PATH_URL . '/admin/index.js',
            array_unique(array_merge($js_dependencies, [])),
            $version,
            true
        );

        // Enqueue scripts.
        wp_enqueue_script('interactive-lesson-admin');

        /* Localize */
        $localize = apply_filters(
            'interactive_lesson_admin_localize',
            [
                'version'     => $version,
                'root_id'     => INTERACTIVE_LESSON_NAME,
                'nonce'       => wp_create_nonce('wp_rest'),
                'store'       => INTERACTIVE_LESSON_NAME . '-store',
                'rest_url'    => esc_url_raw(get_rest_url()),
                'white_label' => Utils::white_label(),
            ]
        );
        wp_localize_script('interactive-lesson-admin', 'InteractiveLessonLocalize', $localize);
    }

    /**
     * Register settings.
     * Common callback function of rest_api_init and admin_init
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function register_settings()
    {
        register_setting(
            'interactive_lesson_settings_group',
            INTERACTIVE_LESSON_OPTION_NAME,
            array(
                'type'         => 'object',
                'default'      => Utils::get_default_options(),
                'show_in_rest' => array(
                    'schema' => Utils::get_settings_schema(),
                ),
            )
        );
    }
}
