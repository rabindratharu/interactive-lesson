<?php

/**
 * PHPUnit bootstrap file for Interactive Lesson plugin
 *
 * @package interactive-lesson
 * @since 1.0.0
 */

$_tests_dir = getenv('WP_TESTS_DIR') ?: '/tmp/wordpress-tests-lib';
$_core_dir = getenv('WP_DEVELOP_DIR') ?: '/tmp/wordpress';

if (!file_exists($_tests_dir . '/includes/functions.php')) {
    echo "Could not find $_tests_dir/includes/functions.php\n";
    exit(1);
}

require_once $_tests_dir . '/includes/functions.php';

function _manually_load_plugin()
{
    require dirname(__DIR__, 2) . '/interactive-lesson.php';
}
tests_add_filter('muplugins_loaded', '_manually_load_plugin');

require $_tests_dir . '/includes/bootstrap.php';
