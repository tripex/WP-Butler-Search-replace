<?php
declare( strict_types=1 );

namespace SmartSearchReplace\Ajax;

use SmartSearchReplace\Security\Capabilities;
use SmartSearchReplace\Security\Nonce;
use SmartSearchReplace\Support\EngineFactory;

final class ScopeController {

	public function register(): void {
		add_action( 'wp_ajax_ssr_list_scopes', array( $this, 'handle' ) );
	}

	public function handle(): void {
		Capabilities::ensure();
		Nonce::ensureFromRequest();

		$registry = EngineFactory::registry();
		$grouped  = array();
		foreach ( $registry->all() as $def ) {
			$grouped[ $def->group ][] = array(
				'id'          => $def->id,
				'label'       => $def->label,
				'description' => $def->description,
			);
		}
		wp_send_json_success( array( 'groups' => $grouped ) );
	}
}
