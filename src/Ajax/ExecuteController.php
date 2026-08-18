<?php
declare( strict_types=1 );

namespace SmartSearchReplace\Ajax;

use SmartSearchReplace\Persistence\RunLog;
use SmartSearchReplace\Security\Capabilities;
use SmartSearchReplace\Security\Nonce;
use SmartSearchReplace\Security\PlanFactory;
use SmartSearchReplace\Support\EngineFactory;

// phpcs:disable WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended -- Nonce verified at top of handle() via Nonce::ensureFromRequest().
final class ExecuteController {

	public function register(): void {
		add_action( 'wp_ajax_ssr_execute_batch', array( $this, 'handle' ) );
	}

	public function handle(): void {
		Capabilities::ensureAjax();
		Nonce::ensureFromRequest();

		try {
			$plan = PlanFactory::fromRequest();
		} catch ( \InvalidArgumentException $e ) {
			wp_send_json_error( array( 'message' => $e->getMessage() ), 400 );
		}

		$confirm = isset( $_POST['confirm'] ) ? sanitize_text_field( (string) wp_unslash( $_POST['confirm'] ) ) : '';
		if ( 'I UNDERSTAND' !== $confirm ) {
			wp_send_json_error(
				array( 'message' => __( 'Execute requires explicit confirmation.', 'smart-search-replace' ) ),
				400
			);
		}

		$expected_hash = isset( $_POST['expected_plan_hash'] )
			? sanitize_text_field( (string) wp_unslash( $_POST['expected_plan_hash'] ) )
			: '';
		if ( $expected_hash !== $plan->hash() ) {
			wp_send_json_error(
				array( 'message' => __( 'Plan changed since preview. Please re-run preview.', 'smart-search-replace' ) ),
				409
			);
		}

		$target_index = isset( $_POST['target_index'] ) ? max( 0, (int) $_POST['target_index'] ) : 0;
		$cursor       = isset( $_POST['cursor'] ) && '' !== $_POST['cursor']
			? sanitize_text_field( (string) wp_unslash( $_POST['cursor'] ) )
			: null;

		$engine = EngineFactory::engine();
		$result = $engine->runBatch( $plan, $target_index, $cursor, true );

		if ( null === $result['next'] ) {
			RunLog::record(
				get_current_user_id(),
				'execute',
				$plan->hash(),
				wp_json_encode( $plan->scope_ids ) ?: '[]',
				count( $result['changes'] ),
				$result['rows_scanned']
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
