<?php
declare( strict_types=1 );

namespace SmartSearchReplace\Admin;

final class Assets {

	public function register(): void {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	public function enqueue( string $hook ): void {
		if ( 'tools_page_' . AdminPage::SLUG !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'ssr-admin',
			SSR_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			SSR_VERSION
		);

		wp_enqueue_script(
			'ssr-admin',
			SSR_PLUGIN_URL . 'assets/js/admin.js',
			array( 'wp-i18n' ),
			SSR_VERSION,
			true
		);

		wp_set_script_translations( 'ssr-admin', 'smart-search-replace' );

		wp_localize_script(
			'ssr-admin',
			'SSR',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'strings' => array(
					'dryRunDone'    => __( 'Preview complete.', 'smart-search-replace' ),
					'executeDone'   => __( 'Execution complete.', 'smart-search-replace' ),
					'confirmPhrase' => __( 'I UNDERSTAND', 'smart-search-replace' ),
					'confirmPrompt' => __( 'Type I UNDERSTAND to confirm. This writes to your database.', 'smart-search-replace' ),
					'noScope'       => __( 'Select at least one scope first.', 'smart-search-replace' ),
					'mismatch'      => __( 'Settings changed since preview. Run preview again.', 'smart-search-replace' ),
					'changesFound'  => __( 'changes found', 'smart-search-replace' ),
					'rowsScanned'   => __( 'rows scanned', 'smart-search-replace' ),
				),
			)
		);
	}
}
