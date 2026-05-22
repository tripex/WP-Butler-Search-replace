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
}
