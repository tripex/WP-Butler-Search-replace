<?php
declare( strict_types=1 );

namespace SmartSearchReplace\Admin;

use SmartSearchReplace\Security\Capabilities;
use SmartSearchReplace\Security\Nonce;
use SmartSearchReplace\Support\EngineFactory;

final class AdminPage {

	public const SLUG = 'smart-search-replace';

	public function register(): void {
		add_action( 'admin_menu', array( $this, 'addMenu' ) );
	}

	public function addMenu(): void {
		add_management_page(
			__( 'Smart Search Replace', 'smart-search-replace' ),
			__( 'Smart Search Replace', 'smart-search-replace' ),
			Capabilities::REQUIRED,
			self::SLUG,
			array( $this, 'render' )
		);
	}

	public function render(): void {
		Capabilities::ensure();

		$registry = EngineFactory::registry();
		$grouped  = array();
		foreach ( $registry->all() as $def ) {
			$grouped[ $def->group ][] = $def;
		}

		$nonce = Nonce::create();
		require SSR_PLUGIN_DIR . 'views/admin-page.php';
	}
}
