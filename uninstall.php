<?php
/**
 * Uninstall handler — drop our run-log table and remove any options.
 *
 * @package SmartSearchReplace
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

$table = $wpdb->prefix . 'smsr_runs';
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Schema teardown on plugin uninstall; identifier is prefix + literal.
$wpdb->query( "DROP TABLE IF EXISTS {$table}" );

delete_option( 'smsr_settings' );
