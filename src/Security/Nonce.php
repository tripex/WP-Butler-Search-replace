<?php
declare( strict_types=1 );

namespace SmartSearchReplace\Security;

final class Nonce {

	public const ACTION = 'ssr_action';
	public const FIELD  = 'ssr_nonce';

	public static function create(): string {
		return wp_create_nonce( self::ACTION );
	}

	public static function verify( string $nonce ): bool {
		return (bool) wp_verify_nonce( $nonce, self::ACTION );
	}

	public static function ensureFromRequest(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- This IS the nonce verification routine.
		$nonce = isset( $_REQUEST[ self::FIELD ] )
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- This IS the nonce verification routine.
			? sanitize_text_field( wp_unslash( (string) $_REQUEST[ self::FIELD ] ) )
			: '';
		if ( ! self::verify( $nonce ) ) {
			wp_send_json_error(
				array( 'message' => __( 'Security check failed. Reload the page and try again.', 'smart-search-replace' ) ),
				403
			);
		}
	}
}
