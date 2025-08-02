<?php

/**
 * Plugin Name:       Interactive Lesson
 * Description:       Create interactive quizzes and lessons with Gutenberg.
 * Version:           1.0.0
 * Requires at least: 6.7
 * Requires PHP:      7.4
 * Author:            Rabindra Tharu
 * Author URI:        https://github.com/rabindratharu
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       interactive-lesson
 *
 * @package interactive-lesson
 */

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

use Interactive_Lesson\Inc\Plugin;
use Interactive_Lesson\Inc\Register_Post_Types;

/**
 * Define plugin constants.
 */
define('INTERACTIVE_LESSON_PATH', plugin_dir_path(__FILE__));
define('INTERACTIVE_LESSON_URL', plugin_dir_url(__FILE__));
define('INTERACTIVE_LESSON_BASENAME', plugin_basename(__FILE__));
define('INTERACTIVE_LESSON_BUILD_PATH', INTERACTIVE_LESSON_PATH . 'assets/build');
define('INTERACTIVE_LESSON_BUILD_PATH_URL', INTERACTIVE_LESSON_URL . 'assets/build');
define('INTERACTIVE_LESSON_NAME', 'interactive-lesson');
define('INTERACTIVE_LESSON_OPTION_NAME', 'interactive-lesson');

/**
 * Bootstrap the plugin.
 */
require_once INTERACTIVE_LESSON_PATH . 'inc/helpers/autoloader.php';

// Check if the class exists and WordPress environment is valid
if (class_exists('Interactive_Lesson\Inc\Plugin')) {
    // Instantiate the plugin
    $the_plugin = Plugin::get_instance();

    // Register activation and deactivation hooks
    register_activation_hook(__FILE__, [$the_plugin, 'activate']);
    register_deactivation_hook(__FILE__, [$the_plugin, 'deactivate']);
    register_activation_hook(__FILE__, [Register_Post_Types::class, 'activate']);
    register_deactivation_hook(__FILE__, [Register_Post_Types::class, 'deactivate']);
}
