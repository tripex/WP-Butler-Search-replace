<?php
declare( strict_types=1 );

namespace SmartSearchReplace;

use SmartSearchReplace\Admin\AdminPage;
use SmartSearchReplace\Admin\Assets;
use SmartSearchReplace\Ajax\ExecuteController;
use SmartSearchReplace\Ajax\PreviewController;
use SmartSearchReplace\Ajax\ScopeController;

final class Plugin {

	private static ?Plugin $instance = null;

	public static function instance(): Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {}

	public function boot(): void {
		load_plugin_textdomain( 'smart-search-replace', false, dirname( SMSR_PLUGIN_BASENAME ) . '/languages' );

		if ( is_admin() ) {
			( new AdminPage() )->register();
			( new Assets() )->register();
			( new PreviewController() )->register();
			( new ExecuteController() )->register();
			( new ScopeController() )->register();
		}
	}
}
