<?php
declare( strict_types=1 );

namespace SmartSearchReplace\Ajax;

use SmartSearchReplace\Persistence\RunLog;
use SmartSearchReplace\Security\Capabilities;
use SmartSearchReplace\Security\Nonce;
use SmartSearchReplace\Security\PlanFactory;
use SmartSearchReplace\Support\EngineFactory;

// phpcs:disable WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended -- Nonce verified at top of handle() via Nonce::ensureFromRequest().
final class PreviewController {

	public function register(): void {
		add_action( 'wp_ajax_smsr_preview_batch', array( $this, 'handle' ) );
	}

	public function handle(): void {
		Capabilities::ensureAjax();
		Nonce::ensureFromRequest();

		try {
			$plan = PlanFactory::fromRequest();
		} catch ( \InvalidArgumentException $e ) {
			wp_send_json_error( array( 'message' => $e->getMessage() ), 400 );
		}

		$target_index = isset( $_POST['target_index'] ) ? max( 0, (int) $_POST['target_index'] ) : 0;
		$cursor       = isset( $_POST['cursor'] ) && '' !== $_POST['cursor']
			? sanitize_text_field( (string) wp_unslash( $_POST['cursor'] ) )
			: null;

		$engine = EngineFactory::engine();
		$result = $engine->runBatch( $plan, $target_index, $cursor, false );

		if ( null === $result['next'] ) {
			// Running totals from the earlier batches of this run, reported
			// back by the requesting admin's session (informational log only).
			$completed_changes = isset( $_POST['completed_changes'] ) ? max( 0, (int) $_POST['completed_changes'] ) : 0;
			$completed_rows    = isset( $_POST['completed_rows'] ) ? max( 0, (int) $_POST['completed_rows'] ) : 0;

			RunLog::record(
				get_current_user_id(),
				'dry-run',
				$plan->hash(),
				wp_json_encode( $plan->scope_ids ) ?: '[]',
				$completed_changes + count( $result['changes'] ),
				$completed_rows + $result['rows_scanned']
			);
		}

		wp_send_json_success(
			array(
				'plan_hash'     => $plan->hash(),
				'changes'       => $result['changes'],
				'next'          => $result['next'],
				'rows_scanned'  => $result['rows_scanned'],
				'bytes_changed' => $result['bytes_changed'],
			)
		);
	}
}
