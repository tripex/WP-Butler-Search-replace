<?php
declare( strict_types=1 );

/**
 * Minimal bootstrap for unit tests.
 *
 * These tests deliberately avoid loading the WordPress test suite — they
 * cover pure PHP engine logic (UrlProtector, SerializedReplacer, etc.).
 * Integration tests that need $wpdb/WP_UnitTestCase live elsewhere and
 * are run via wp-env / phpunit polyfills.
 */

define( 'ABSPATH', __DIR__ . '/' );
define( 'SMSR_PLUGIN_DIR', dirname( __DIR__ ) . '/' );
define( 'SMSR_PLUGIN_FILE', SMSR_PLUGIN_DIR . 'smart-search-replace.php' );
define( 'SMSR_PLUGIN_URL', 'http://example.test/wp-content/plugins/smart-search-replace/' );
define( 'SMSR_VERSION', '0.1.0' );

require_once SMSR_PLUGIN_DIR . 'src/Support/Autoloader.php';
\SmartSearchReplace\Support\Autoloader::register();

if ( ! function_exists( 'wp_json_encode' ) ) {
	function wp_json_encode( $data, $options = 0, $depth = 512 ) {
		return json_encode( $data, $options, $depth );
	}
}
if ( ! function_exists( 'apply_filters' ) ) {
	function apply_filters( $tag, $value, ...$args ) {
		return $value;
	}
}
if ( ! function_exists( '__' ) ) {
	function __( $text, $domain = '' ) { return $text; }
}
if ( ! function_exists( 'esc_html' ) ) {
	function esc_html( $text ) { return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' ); }
}
if ( ! function_exists( 'esc_html__' ) ) {
	function esc_html__( $text, $domain = '' ) { return esc_html( $text ); }
}
if ( ! function_exists( 'sanitize_key' ) ) {
	function sanitize_key( $key ) {
		$key = strtolower( (string) $key );
		return preg_replace( '/[^a-z0-9_\-]/', '', $key );
	}
}
