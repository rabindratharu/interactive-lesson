<?php

/**
 * Customizer class
 *
 * @package interactive-lesson
 * @since 1.0.0
 */

namespace Interactive_Lesson\Inc;

use Interactive_Lesson\Inc\Traits\Singleton;
use Interactive_Lesson\Inc\Utils;
use WP_Customize_Manager;
use Interactive_Lesson\Inc\Controls\Text as InteractiveLessonTextControl;

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Register custom controls and settings.
 *
 * @since 1.0.0
 */
class Customizer
{
    use Singleton;

    /**
     * WordPress Customizer Manager instance.
     *
     * @var WP_Customize_Manager
     */
    private $wp_customize;

    /**
     * Private constructor to prevent direct object creation.
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
        add_action('customize_register', [$this, 'customize_register_callback']);
    }

    public function customize_register_callback(WP_Customize_Manager $wp_customize)
    {

        $controls_dir = trailingslashit(INTERACTIVE_LESSON_PATH) . 'inc/controls/';

        if (!is_dir($controls_dir) || !is_readable($controls_dir)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Interactive Lesson: Controls directory not found or not readable at ' . $controls_dir);
            }
            return;
        }

        $control_files = glob($controls_dir . 'class-*.php');
        if (empty($control_files)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Interactive Lesson: No control files found in ' . $controls_dir);
            }
            return;
        }

        foreach ($control_files as $file) {
            try {

                // Get class name from file
                $base = sanitize_file_name(basename($file, '.php'));
                $class_slug = str_replace('class-', '', $base);
                $class_name = str_replace('-', '_', $class_slug);
                $full_class = __NAMESPACE__ . '\\Controls\\' . str_replace('-', '_', ucwords($class_slug, '-'));

                // Include and validate control file
                if (is_readable($file)) {
                    require_once $file;
                } else {
                    throw new \Exception('Unable to read control file: ' . $file);
                }
            } catch (\Exception $e) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('Interactive Lesson: Failed to register control ' . ($full_class ?? 'unknown') . ': ' . $e->getMessage());
                }
            }
        }

        // Add a section
        $wp_customize->add_section('interactive_lesson_controls_section', array(
            'title'         => esc_html__('Interactive Lesson', 'interactive-lesson'),
            'priority'      => 120,
        ));

        // Add setting
        $wp_customize->add_setting('interactive_lesson_text_control', array(
            'default'           => esc_html__('Default', 'interactive-lesson'),
            'transport'         => 'refresh',
            'sanitize_callback' => 'sanitize_text_field',
        ));

        // Add control
        $wp_customize->add_control(new InteractiveLessonTextControl(
            $wp_customize,
            'interactive_lesson_text_control',
            array(
                'label'         => esc_html__('Text Control', 'interactive-lesson'),
                'description'   => esc_html__('Description for this control.', 'interactive-lesson'),
                'section'       => 'interactive_lesson_controls_section',
            )
        ));
    }
}
