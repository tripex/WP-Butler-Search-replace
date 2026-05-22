<?php
/**
 * Plugin Name:       Smart Search Replace
 * Plugin URI:        https://wordpress.org/plugins/smart-search-replace/
 * Description:       Human-friendly search & replace for WordPress with URL protection, smart scopes, dry-run preview and safe serialized-data handling.
 * Version:           0.1.0
 * Requires at least: 6.4
 * Requires PHP:      8.1
 * Author:            Smart Search Replace contributors
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       smart-search-replace
 * Domain Path:       /languages
 *
 * @package SmartSearchReplace
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SSR_VERSION', '0.1.0' );
define( 'SSR_PLUGIN_FILE', __FILE__ );
define( 'SSR_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SSR_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'SSR_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

require_once SSR_PLUGIN_DIR . 'src/Support/Autoloader.php';
\SmartSearchReplace\Support\Autoloader::register();

add_action(
	'plugins_loaded',
	static function (): void {
		\SmartSearchReplace\Plugin::instance()->boot();
	}
);

register_activation_hook(
	__FILE__,
	static function (): void {
		\SmartSearchReplace\Persistence\RunLog::install();
	}
);
