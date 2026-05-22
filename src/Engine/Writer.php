<?php
declare( strict_types=1 );

namespace SmartSearchReplace\Engine;

/**
 * Persists a Change to the database using $wpdb->update with proper escaping.
 *
 * Identifier whitelisting is enforced at the call sites (Target only exposes
 * code-defined columns/tables); we also re-validate here as a defence in depth.
 */
final class Writer {

	public function write( Change $change ): bool {
		global $wpdb;

		$this->assertIdentifier( $change->table );
		$this->assertIdentifier( $change->column );
		$this->assertIdentifier( $change->primary_key_column );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$result = $wpdb->update(
			$change->table,
			array( $change->column => $change->after ),
			array( $change->primary_key_column => $change->primary_key_value ),
			array( '%s' ),
			array( is_int( $change->primary_key_value ) ? '%d' : '%s' )
		);
		return false !== $result;
	}

	private function assertIdentifier( string $name ): void {
		if ( ! preg_match( '/^[A-Za-z0-9_]+$/', $name ) ) {
			throw new \InvalidArgumentException( esc_html( 'Unsafe SQL identifier: ' . $name ) );
		}
	}
}
