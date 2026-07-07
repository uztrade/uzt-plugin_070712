<?php
/**
 * Удаление плагина: чистим таблицу ранжирования, transients, опции.
 */
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

$table = $wpdb->prefix . 'uzt_rankings';
$wpdb->query( "DROP TABLE IF EXISTS {$table}" );

delete_option( 'uzt_db_version' );

$like_top    = $wpdb->esc_like( '_transient_uzt_top_' )    . '%';
$like_top_t  = $wpdb->esc_like( '_transient_timeout_uzt_top_' ) . '%';
$like_br     = $wpdb->esc_like( '_transient_uzt_broker_' ) . '%';
$like_br_t   = $wpdb->esc_like( '_transient_timeout_uzt_broker_' ) . '%';

foreach ( array( $like_top, $like_top_t, $like_br, $like_br_t ) as $like ) {
	$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $like ) );
}
