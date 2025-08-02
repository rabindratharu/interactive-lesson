<?php

/**
 * Register Block
 *
 * @package interactive-lesson
 * @since 1.0.0
 */

namespace Interactive_Lesson\Inc;

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

use Interactive_Lesson\Inc\Traits\Singleton;

/**
 * Register block class.
 *
 * Handles registration of custom post types for the current theme/plugin.
 *
 * @since 1.0.0
 */
class Register_Block
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
        add_action('init', [$this, 'register_block_types']);
        add_action('block_categories_all', [$this, 'register_block_category'], 10, 2);
        add_action('wp_enqueue_scripts', [$this, 'localize_block_scripts']);
    }

    /**
     * Register all block types from assets/build/block/directory/block.json.
     *
     * @since 1.0.0
     * @return void
     */
    public function register_block_types()
    {
        if (! function_exists('register_block_type')) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Interactive Lesson: register_block_type function not found. Ensure WordPress version supports blocks.');
            }
            return;
        }

        // Ensure the constant is defined
        if (! defined('INTERACTIVE_LESSON_PATH')) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Interactive Lesson: INTERACTIVE_LESSON_PATH constant is not defined.');
            }
            return;
        }

        $block_path = INTERACTIVE_LESSON_PATH . 'assets/build/block/';
        if (! is_dir($block_path)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Interactive Lesson: Block directory not found at ' . $block_path);
            }
            return;
        }

        $block_json_files = glob($block_path . '*/block.json');
        if (empty($block_json_files)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Interactive Lesson: No block.json files found in ' . $block_path);
            }
            return;
        }

        foreach ($block_json_files as $filename) {
            if (! is_readable($filename)) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('Interactive Lesson: Cannot read block.json file: ' . $filename);
                }
                continue;
            }

            $block_folder = dirname($filename);
            try {
                $result = register_block_type($block_folder);
                if (false === $result && defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('Interactive Lesson: Failed to register block from ' . $filename);
                }
            } catch (\Exception $e) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('Interactive Lesson: Error registering block from ' . $filename . ': ' . $e->getMessage());
                }
            }
        }
    }

    /**
     * Register a custom block category.
     *
     * @since 1.0.0
     * @param array $categories Existing block categories.
     * @param WP_Block_Editor_Context $context Block editor context.
     * @return array Modified block categories.
     */
    public function register_block_category($categories, $context)
    {
        $new_category = [
            'slug'  => 'interactive-lesson',
            'title' => esc_html__('Interactive Lesson', 'interactive-lesson'),
            'icon'  => 'book-alt',
        ];

        // Check if the category already exists to avoid duplicates
        if (! in_array($new_category['slug'], array_column($categories, 'slug'), true)) {
            $categories = array_merge([$new_category], $categories);
        }

        return $categories;
    }

    /**
     * Localize scripts for the quiz block.
     *
     * @since 1.0.0
     * @return void
     */
    public function localize_block_scripts()
    {
        // Localize the view script for the quiz block
        $handle = 'interactive-lesson-quiz-block-view-script';
        wp_localize_script(
            $handle,
            'quizBlockData',
            [
                'restUrl'   => esc_url_raw(rest_url('quiz/v1/submit')),
                'nonce'     => wp_create_nonce('wp_rest')
            ]
        );

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Interactive Lesson: Localized script ' . $handle . ' with REST URL and nonce.');
        }
    }
}
