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

// /**
//  * Quiz Block REST API functionality
//  * 
//  * @package QuizBlock
//  */

// // Ensure this only runs once
// if (!function_exists('quiz_block_register_rest_routes')) {

//     /**
//      * Register all REST API endpoints and meta fields
//      */
//     function quiz_block_register_rest_routes()
//     {
//         // Register meta fields
//         register_meta('user', 'quiz_score', [
//             'type' => 'integer',
//             'description' => 'User quiz score',
//             'single' => true,
//             'show_in_rest' => true,
//             'default' => 0
//         ]);

//         register_meta('user', 'quiz_answer_', [
//             'type' => 'string',
//             'description' => 'User answer for quiz question',
//             'single' => true,
//             'show_in_rest' => true
//         ]);

//         register_meta('user', 'quiz_correct_', [
//             'type' => 'string',
//             'description' => 'Correctness of quiz answer (1 or 0)',
//             'single' => true,
//             'show_in_rest' => true
//         ]);

//         // Register submission endpoint
//         register_rest_route('quiz/v1', '/submit', [
//             'methods' => 'POST',
//             'callback' => 'quiz_block_handle_submission',
//             'permission_callback' => function () {
//                 return is_user_logged_in();
//             }
//         ]);

//         // Register results endpoint
//         register_rest_route('quiz/v1', '/results', [
//             'methods' => 'GET',
//             'callback' => 'quiz_block_handle_results_request',
//             'permission_callback' => function () {
//                 return is_user_logged_in();
//             }
//         ]);
//     }
//     add_action('rest_api_init', 'quiz_block_register_rest_routes');

//     /**
//      * Handle quiz submission
//      */
//     function quiz_block_handle_submission(WP_REST_Request $request)
//     {
//         // Verify nonce for security
//         $nonce = $request->get_header('X-WP-Nonce');
//         if (!wp_verify_nonce($nonce, 'wp_rest')) {
//             return new WP_Error('invalid_nonce', 'Invalid nonce.', ['status' => 403]);
//         }

//         $params = $request->get_json_params();
//         $question = sanitize_text_field($params['question'] ?? '');
//         $answer = sanitize_text_field($params['answer'] ?? '');
//         $correct_answer = sanitize_text_field($params['correct_answer'] ?? '');
//         $user_id = get_current_user_id();

//         // Validate input
//         if (empty($question) || empty($answer) || empty($correct_answer)) {
//             return new WP_Error('missing_params', 'Missing required parameters.', ['status' => 400]);
//         }

//         $question_hash = md5($question);
//         $is_correct = (trim($answer) === trim($correct_answer));

//         // Update user meta
//         update_user_meta($user_id, 'quiz_answer_' . $question_hash, $answer);
//         update_user_meta($user_id, 'quiz_correct_' . $question_hash, $is_correct ? '1' : '0');

//         // Update score if correct
//         if ($is_correct) {
//             $current_score = (int) get_user_meta($user_id, 'quiz_score', true);
//             update_user_meta($user_id, 'quiz_score', $current_score + 1);
//         }

//         return rest_ensure_response([
//             'success' => true,
//             'answer' => $answer,
//             'message' => $is_correct ? 'Correct!' : 'Incorrect. The correct answer is ' . $correct_answer . '.'
//         ]);
//     }

//     /**
//      * Handle quiz results request
//      */
//     function quiz_block_handle_results_request(WP_REST_Request $request)
//     {
//         $user_id = get_current_user_id();
//         global $wpdb;

//         $meta_keys = $wpdb->get_results(
//             $wpdb->prepare(
//                 "SELECT meta_key, meta_value FROM $wpdb->usermeta WHERE user_id = %d AND meta_key LIKE %s",
//                 $user_id,
//                 'quiz_answer_%'
//             )
//         );

//         $results = [];
//         foreach ($meta_keys as $meta) {
//             $question_hash = substr($meta->meta_key, strlen('quiz_answer_'));
//             $is_correct = get_user_meta($user_id, 'quiz_correct_' . $question_hash, true) === '1';
//             $results[] = [
//                 'question_hash' => $question_hash,
//                 'answer' => esc_html($meta->meta_value),
//                 'is_correct' => $is_correct
//             ];
//         }

//         return rest_ensure_response([
//             'success' => true,
//             'results' => $results,
//             'total_score' => (int) get_user_meta($user_id, 'quiz_score', true)
//         ]);
//     }
// }