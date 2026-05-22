<?php
declare( strict_types=1 );

namespace SmartSearchReplace\Engine;

use SmartSearchReplace\Scope\Resolver;
use SmartSearchReplace\Scope\ScopeRegistry;
use SmartSearchReplace\Scope\Target;

/**
 * Orchestrates a single batch of the replace operation. The same code path is
 * used for dry-run and execute; only the $write flag differs. This guarantees
 * preview parity with execution.
 *
 * @phpstan-type BatchResult array{
 *   changes: array<int, array<string, mixed>>,
 *   next: array{target_index:int, cursor:int|string|null}|null,
 *   rows_scanned: int,
 *   bytes_changed: int,
 * }
 */
final class ReplaceEngine {

	public function __construct(
		private readonly ScopeRegistry $registry,
		private readonly Resolver $resolver,
		private readonly UrlProtector $url_protector,
		private readonly Writer $writer,
	) {}

	/**
	 * @return Target[]
	 */
	public function targetsFor( ReplacePlan $plan ): array {
		$defs    = $this->registry->getMany( $plan->scope_ids );
		$targets = $this->resolver->resolve( $defs );

		// guid is excluded from the posts table unless include_guid is set.
		$out = array();
		foreach ( $targets as $t ) {
			if ( ! $plan->include_guid ) {
				$columns = array_values( array_filter( $t->columns, static fn( $c ) => 'guid' !== $c ) );
				if ( empty( $columns ) ) {
					continue;
				}
				$out[] = new Target(
					table:        $t->table,
					primary_key:  $t->primary_key,
					columns:      $columns,
					where_equals: $t->where_equals,
					join_filter:  $t->join_filter,
				);
			} else {
				$out[] = $t;
			}
		}
		return $out;
	}

	/**
	 * Run one batch starting at ($target_index, $cursor). Returns the
	 * batch result and the next position (or null when fully done).
	 *
	 * @return BatchResult
	 */
	public function runBatch(
		ReplacePlan $plan,
		int $target_index,
		int|string|null $cursor,
		bool $write
	): array {
		$targets = $this->targetsFor( $plan );
		if ( $target_index >= count( $targets ) ) {
			return array(
				'changes'       => array(),
				'next'          => null,
				'rows_scanned'  => 0,
				'bytes_changed' => 0,
			);
		}

		$target    = $targets[ $target_index ];
		$replacer  = StringReplacer::fromPlan( $plan, $this->url_protector );
		$processor = new RowProcessor( $replacer );
		$runner    = new BatchRunner( $processor );

		$batch = $runner->runBatch( $target, $cursor, $plan->batch_size );

		$serialized_changes = array();
		$bytes_changed      = 0;
		foreach ( $batch['changes'] as $change ) {
			if ( $write ) {
				$this->writer->write( $change );
			}
			$bytes_changed       += abs( strlen( $change->after ) - strlen( $change->before ) );
			$serialized_changes[] = array(
				'table'    => $change->table,
				'pk_col'   => $change->primary_key_column,
				'pk'       => $change->primary_key_value,
				'column'   => $change->column,
				'diff'     => DiffBuilder::build( $change->before, $change->after ),
				'identity' => $change->identity(),
			);
		}

		$next_cursor = $batch['next_cursor'];
		if ( null === $next_cursor ) {
			$next_index = $target_index + 1;
			$next       = $next_index < count( $targets )
				? array(
					'target_index' => $next_index,
					'cursor'       => null,
				)
				: null;
		} else {
			$next = array(
				'target_index' => $target_index,
				'cursor'       => $next_cursor,
			);
		}

		return array(
			'changes'       => $serialized_changes,
			'next'          => $next,
			'rows_scanned'  => $batch['rows_scanned'],
			'bytes_changed' => $bytes_changed,
		);
	}
}
