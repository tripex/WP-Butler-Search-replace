<?php
declare( strict_types=1 );

namespace SmartSearchReplace\Support;

final class Autoloader {

	private const PREFIX  = 'SmartSearchReplace\\';
	private const BASEDIR = SMSR_PLUGIN_DIR . 'src/';

	public static function register(): void {
		spl_autoload_register( array( self::class, 'load' ) );
	}

	public static function load( string $class ): void {
		if ( ! str_starts_with( $class, self::PREFIX ) ) {
			return;
		}
		$relative = substr( $class, strlen( self::PREFIX ) );
		$path     = self::BASEDIR . str_replace( '\\', '/', $relative ) . '.php';
		if ( is_file( $path ) ) {
			require_once $path;
		}
	}
}
