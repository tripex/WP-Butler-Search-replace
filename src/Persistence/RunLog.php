<?php
declare( strict_types=1 );

namespace SmartSearchReplace\Persistence;

/**
 * Lightweight log of replace runs. Stored locally, never transmitted.
 */
final class RunLog {

	public static function tableName(): string {
		global $wpdb;
		return $wpdb->prefix . 'ssr_runs';
	}

	public static function install(): void {
		global $wpdb;
		$table = self::tableName();

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$charset = $wpdb->get_charset_collate();
		$sql     = "CREATE TABLE {$table} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			created_at DATETIME NOT NULL,
			user_id BIGINT UNSIGNED NOT NULL,
			mode VARCHAR(16) NOT NULL,
			plan_hash CHAR(64) NOT NULL,
			scope_ids TEXT NOT NULL,
			changes_count INT UNSIGNED NOT NULL DEFAULT 0,
			rows_scanned INT UNSIGNED NOT NULL DEFAULT 0,
			PRIMARY KEY (id),
			KEY plan_hash (plan_hash)
		) {$charset};";
		dbDelta( $sql );
	}

	public static function record(
		int $user_id,
		string $mode,
		string $plan_hash,
		string $scope_ids_json,
		int $changes_count,
		int $rows_scanned
	): void {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->insert(
			self::tableName(),
			array(
				'created_at'    => current_time( 'mysql' ),
				'user_id'       => $user_id,
				'mode'          => $mode,
				'plan_hash'     => $plan_hash,
				'scope_ids'     => $scope_ids_json,
				'changes_count' => $changes_count,
				'rows_scanned'  => $rows_scanned,
			),
			array( '%s', '%d', '%s', '%s', '%s', '%d', '%d' )
		);
	}
}
