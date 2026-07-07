<?php
/**
 * Plugin Name: Uztrading Plugin (Top Lists & Broker Cards)
 * Plugin URI:  https://uztrading.net
 * Description: Серверные шорткоды [uzt_top_list], [uzt_broker_card] и [uzt_faq]. Данные тянутся из CPT `broker` + ACF; порядок и награды хранятся в отдельной таблице ранжирования и правятся из админки без правки кода.
 * Version:     1.1.0
 * Author:      uztrading.net
 * License:     GPL-2.0-or-later
 * Text Domain: uzt
 * Requires PHP: 7.4
 * Requires at least: 6.0
 * Requires Plugins: advanced-custom-fields
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'UZT_PLUGIN_VERSION', '1.1.0' );
define( 'UZT_PLUGIN_FILE', __FILE__ );
define( 'UZT_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'UZT_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'UZT_PLUGIN_SLUG', 'uzt-plugin' );

/** Автоподключение всех классов плагина. */
require_once UZT_PLUGIN_DIR . 'includes/class-uzt-rankings.php';
require_once UZT_PLUGIN_DIR . 'includes/class-uzt-repository.php';
require_once UZT_PLUGIN_DIR . 'includes/class-uzt-schema.php';
require_once UZT_PLUGIN_DIR . 'includes/class-uzt-shortcodes.php';
require_once UZT_PLUGIN_DIR . 'includes/class-uzt-admin.php';
require_once UZT_PLUGIN_DIR . 'includes/class-uzt-plugin.php';

/** Bootstrap. */
UZT_Plugin::instance();

/** Активация: создаём таблицу ранжирования и сидим пилотный топ-10. */
register_activation_hook( __FILE__, array( 'UZT_Rankings', 'activate' ) );

/** Деактивация: чистим transients (таблица остаётся, чтобы не терять редакторскую работу). */
register_deactivation_hook( __FILE__, array( 'UZT_Repository', 'flush_all_cache' ) );