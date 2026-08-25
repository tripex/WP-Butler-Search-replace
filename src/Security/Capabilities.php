<?php
declare( strict_types=1 );

namespace SmartSearchReplace\Security;

final class Capabilities {

	public const REQUIRED = 'manage_options';

	public static function ensure(): void {
		if ( ! current_user_can( self::REQUIRED ) ) {
			wp_die(
				esc_html__( 'You do not have permission to access this tool.', 'smart-search-replace' ),
				'',
				array( 'response' => 403 )
			);
		}
	}

	/**
	 * Same check, but responds with JSON so AJAX callers get a parseable
	 * error instead of an HTML wp_die() page.
	 */
	public static function ensureAjax(): void {
		if ( ! current_user_can( self::REQUIRED ) ) {
			wp_send_json_error(
				array( 'message' => __( 'You do not have permission to access this tool.', 'smart-search-replace' ) ),
				403
			);
		}
	}
}
