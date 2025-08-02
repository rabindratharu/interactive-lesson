<?php

/**
 * Plugin Main Class
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
 * Plugin Main Class
 *
 * This class initializes the plugin, sets up hooks, and manages activation/deactivation.
 * @since 1.0.0
 */
final class Plugin
{

	use Singleton;

	/**
	 * Plugin version
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public const VERSION = '1.0.0';

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	protected function __construct()
	{
		$this->setup_classes();
		$this->setup_hooks();
	}

	/**
	 * Load required plugin classes.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	protected function setup_classes()
	{
		Utils::get_instance();
		Register_Block::get_instance();
		Register_Post_Types::get_instance();
		Register_Taxonomies::get_instance();
		Meta_Boxes::get_instance();
		Reviews::get_instance();
		Rest_Endpoint::get_instance();
		Api_Settings::get_instance();
		Customizer::get_instance();
		if (is_admin()) {
			Dashboard::get_instance();
		}

		Assets::get_instance();
	}

	/**
	 * Setup plugin hooks.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	protected function setup_hooks()
	{
		add_action('init', [$this, 'load_textdomain'], -999);
	}

	/**
	 * Load plugin textdomain for translation.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function load_textdomain()
	{
		load_plugin_textdomain(
			'interactive-lesson',
			false,
			dirname(plugin_basename(__FILE__)) . '/languages'
		);
	}

	/**
	 * Plugin activation hook.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function activate()
	{
		$current_version = get_option('interactive_lesson_version', '0.0.0');
		$new_version     = self::VERSION;

		if (version_compare($current_version, $new_version, '<')) {
			flush_rewrite_rules();
			update_option('interactive_lesson_version', $new_version);
		}
	}

	/**
	 * Plugin deactivation hook.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function deactivate()
	{
		flush_rewrite_rules();
	}

	/**
	 * Prevent cloning.
	 *
	 * @since 1.0.0
	 */
	public function __clone()
	{
		_doing_it_wrong(
			__FUNCTION__,
			esc_html__('Cloning is forbidden.', 'interactive-lesson'),
			self::VERSION
		);
	}

	/**
	 * Prevent unserialization.
	 *
	 * @since 1.0.0
	 */
	public function __wakeup()
	{
		_doing_it_wrong(
			__FUNCTION__,
			esc_html__('Unserializing is forbidden.', 'interactive-lesson'),
			self::VERSION
		);
	}
}
