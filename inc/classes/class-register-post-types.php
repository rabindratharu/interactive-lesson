<?php

/**
 * Register Custom Post Types
 *
 * @package interactive-lesson
 * @since 1.0.0
 */

namespace Interactive_Lesson\Inc;

use Interactive_Lesson\Inc\Traits\Singleton;

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Register Post Types class.
 *
 * Handles registration of custom post types for the current theme/plugin.
 *
 * @since 1.0.0
 */
class Register_Post_Types
{
    use Singleton;

    /**
     * Private constructor to prevent direct object creation.
     *
     * Sets up hooks for post type registration.
     *
     * @since 1.0.0
     */
    protected function __construct()
    {
        $this->setup_hooks();
    }

    /**
     * Set up action hooks.
     *
     * @since 1.0.0
     * @return void
     */
    protected function setup_hooks()
    {
        add_action('init', [$this, 'register_post_types'], 5);
    }

    /**
     * Register custom post types.
     *
     * @since 1.0.0
     * @return void
     */
    public function register_post_types()
    {
        if (!is_blog_installed()) {
            return;
        }

        $custom_post_types = self::get_post_type_args();

        foreach ($custom_post_types as $post_type => $args) {
            if (post_type_exists($post_type)) {
                continue;
            }

            $labels = $this->get_post_type_labels(
                $args['singular_name'],
                $args['general_name'],
                $args['menu_name']
            );

            $post_type_args = [
                'label'               => esc_html__($args['singular_name'], 'interactive-lesson'),
                'description'         => esc_html__($args['singular_name'] . ' Post Type', 'interactive-lesson'),
                'labels'              => $labels,
                'supports'            => $args['supports'],
                'hierarchical'        => false,
                'public'              => true,
                'show_ui'             => true,
                'show_in_menu'        => $args['show_in_menu'],
                'show_in_rest'        => true,
                'menu_icon'           => $args['dashicon'],
                'show_in_admin_bar'   => true,
                'show_in_nav_menus'   => $args['show_in_nav_menus'],
                'can_export'          => true,
                'has_archive'         => $args['has_archive'],
                'exclude_from_search' => $args['exclude_from_search'],
                'publicly_queryable'  => true,
                'capability_type'     => $args['capability_type'],
                'rewrite'             => [
                    'slug'       => 'interactive-lesson', // Changed to a simpler slug
                    'with_front' => false,
                    'pages'      => true,
                    'feeds'      => true,
                ],
            ];

            $result = register_post_type($post_type, $post_type_args);
            if (is_wp_error($result) && defined('WP_DEBUG') && WP_DEBUG) {
                error_log(sprintf('Interactive Lesson: Failed to register post type %s: %s', $post_type, $result->get_error_message()));
            }
        }
    }

    /**
     * Get labels for a custom post type.
     *
     * @since 1.0.0
     * @param string $singular_name Singular name of the post type.
     * @param string $general_name General name of the post type.
     * @param string $menu_name Menu name for the post type.
     * @return array Array of labels for the post type.
     */
    private function get_post_type_labels($singular_name, $general_name, $menu_name)
    {
        return [
            'name'                  => esc_html__($general_name, 'interactive-lesson'),
            'singular_name'         => esc_html__($singular_name, 'interactive-lesson'),
            'menu_name'             => esc_html__($menu_name, 'interactive-lesson'),
            'name_admin_bar'        => esc_html__($singular_name, 'interactive-lesson'),
            'archives'              => esc_html__($singular_name . ' Archives', 'interactive-lesson'),
            'attributes'            => esc_html__($singular_name . ' Attributes', 'interactive-lesson'),
            'parent_item_colon'     => esc_html__('Parent ' . $singular_name . ':', 'interactive-lesson'),
            'all_items'             => esc_html__($general_name, 'interactive-lesson'),
            'add_new_item'          => esc_html__('Add ' . $singular_name, 'interactive-lesson'),
            'add_new'               => esc_html__('Add', 'interactive-lesson'),
            'new_item'              => esc_html__('New ' . $singular_name, 'interactive-lesson'),
            'edit_item'             => esc_html__('Edit ' . $singular_name, 'interactive-lesson'),
            'update_item'           => esc_html__('Update ' . $singular_name, 'interactive-lesson'),
            'view_item'             => esc_html__('View ' . $singular_name, 'interactive-lesson'),
            'view_items'            => esc_html__('View ' . $general_name, 'interactive-lesson'),
            'search_items'          => esc_html__('Search ' . $singular_name, 'interactive-lesson'),
            'not_found'             => esc_html__('Not found', 'interactive-lesson'),
            'not_found_in_trash'    => esc_html__('Not found in Trash', 'interactive-lesson'),
            'featured_image'        => esc_html__('Featured Image', 'interactive-lesson'),
            'set_featured_image'    => esc_html__('Set featured image', 'interactive-lesson'),
            'remove_featured_image' => esc_html__('Remove featured image', 'interactive-lesson'),
            'use_featured_image'    => esc_html__('Use as featured image', 'interactive-lesson'),
            'insert_into_item'      => esc_html__('Insert into ' . $singular_name, 'interactive-lesson'),
            'uploaded_to_this_item' => esc_html__('Uploaded to this ' . $singular_name, 'interactive-lesson'),
            'items_list'            => esc_html__($general_name . ' list', 'interactive-lesson'),
            'items_list_navigation' => esc_html__($general_name . ' list navigation', 'interactive-lesson'),
            'filter_items_list'     => esc_html__('Filter ' . $general_name . ' list', 'interactive-lesson'),
        ];
    }

    /**
     * Get custom post type arguments.
     *
     * @since 1.0.0
     * @return array Array of post type arguments.
     */
    public static function get_post_type_args()
    {
        return [
            'interactive_lesson' => [
                'menu_name'           => esc_html__('Interactive Lessons', 'interactive-lesson'),
                'singular_name'       => esc_html__('Interactive Lesson', 'interactive-lesson'),
                'general_name'        => esc_html__('Interactive Lessons', 'interactive-lesson'),
                'dashicon'            => 'dashicons-star-filled',
                'has_archive'         => true,
                'exclude_from_search' => false,
                'show_in_nav_menus'   => false,
                'show_in_menu'        => true,
                'capability_type'     => 'post',
                'supports'            => ['title', 'editor', 'revisions', 'thumbnail', 'custom-fields'],
            ],
        ];
    }

    /**
     * Activate callback: Register post types and flush rewrite rules.
     */
    public static function activate()
    {
        self::get_instance()->register_post_types();
        flush_rewrite_rules();
    }

    /**
     * Deactivate callback: Flush rewrite rules.
     */
    public static function deactivate()
    {
        flush_rewrite_rules();
    }
}
