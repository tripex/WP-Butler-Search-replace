<?php
declare( strict_types=1 );

namespace SmartSearchReplace\Engine;

use SmartSearchReplace\Scope\Target;

/**
 * Keyset-paginates over a Target's rows and yields Change records.
 *
 * Identifier whitelisting is enforced by quoting through wpdb and by only
 * accepting columns/tables that originate from a ScopeDefinition (which is
 * itself code-defined or filter-supplied — never raw user input).
 */
final class BatchRunner {

	public function __construct(
		private readonly RowProcessor $row_processor,
	) {}

	/**
	 * Fetch one batch of rows after $cursor (exclusive) and return changes plus
	 * the new cursor. When the new cursor is null, the target is exhausted.
	 *
	 * @return array{changes: Change[], next_cursor: int|string|null, rows_scanned: int}
	 */
	public function runBatch( Target $target, int|string|null $cursor, int $batch_size ): array {
		global $wpdb;

		$select_cols = $this->safeSelectColumns( $target );
		$table       = $this->safeIdentifier( $target->table );
		$pk          = $this->safeIdentifier( $target->primary_key );

		$where_parts = array();
		$where_args  = array();

		if ( null !== $cursor ) {
			$where_parts[] = "{$pk} > %s";
			$where_args[]  = (string) $cursor;
		}

		foreach ( $target->where_equals as $col => $val ) {
			$col_safe = $this->safeIdentifier( $col );
			if ( is_array( $val ) ) {
				$placeholders  = implode( ', ', array_fill( 0, count( $val ), '%s' ) );
				$where_parts[] = "{$col_safe} IN ({$placeholders})";
				foreach ( $val as $v ) {
					$where_args[] = (string) $v;
				}
			} else {
				$where_parts[] = "{$col_safe} = %s";
				$where_args[]  = (string) $val;
			}
		}

		$join_sql = '';
		if ( null !== $target->join_filter ) {
			$jt       = $this->safeIdentifier( $target->join_filter['table'] );
			$lk       = $this->safeIdentifier( $target->join_filter['local_key'] );
			$fk       = $this->safeIdentifier( $target->join_filter['foreign_key'] );
			$join_sql = " INNER JOIN {$jt} ON {$table}.{$lk} = {$jt}.{$fk} ";
			foreach ( $target->join_filter['equals'] as $col => $val ) {
				$col_safe = $this->safeIdentifier( $col );
				if ( is_array( $val ) ) {
					$placeholders  = implode( ', ', array_fill( 0, count( $val ), '%s' ) );
					$where_parts[] = "{$jt}.{$col_safe} IN ({$placeholders})";
					foreach ( $val as $v ) {
						$where_args[] = (string) $v;
					}
				} else {
					$where_parts[] = "{$jt}.{$col_safe} = %s";
					$where_args[]  = (string) $val;
				}
			}
		}

		$where_sql = empty( $where_parts ) ? '' : ( ' WHERE ' . implode( ' AND ', $where_parts ) );

		$qualified_select = array();
		foreach ( $select_cols as $c ) {
			$qualified_select[] = "{$table}." . $this->safeIdentifier( $c );
		}
		$qualified_pk = "{$table}.{$pk}";
		$select_sql   = implode( ', ', array_unique( array_merge( array( $qualified_pk ), $qualified_select ) ) );

		$sql = "SELECT {$select_sql} FROM {$table}{$join_sql}{$where_sql} ORDER BY {$qualified_pk} ASC LIMIT %d";

		$args   = $where_args;
		$args[] = $batch_size;

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery
		$prepared = $wpdb->prepare( $sql, $args );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.PreparedSQL.NotPrepared -- Schema-aware batched scan; identifiers whitelisted in safeIdentifier(); results not cacheable.
		$rows = $wpdb->get_results( $prepared, ARRAY_A );

		if ( ! is_array( $rows ) || empty( $rows ) ) {
			return array(
				'changes'      => array(),
				'next_cursor'  => null,
				'rows_scanned' => 0,
			);
		}

		$changes      = array();
		$last_pk      = null;
		$rows_scanned = 0;
		foreach ( $rows as $row ) {
			++$rows_scanned;
			$last_pk = $row[ $target->primary_key ] ?? $last_pk;
			foreach ( $this->row_processor->process( $target, $row ) as $change ) {
				$changes[] = $change;
			}
		}

		$next_cursor = ( count( $rows ) < $batch_size ) ? null : $last_pk;

		return array(
			'changes'      => $changes,
			'next_cursor'  => $next_cursor,
			'rows_scanned' => $rows_scanned,
		);
	}

	/**
	 * @return string[]
	 */
	private function safeSelectColumns( Target $target ): array {
		$cols = array();
		foreach ( $target->columns as $c ) {
			$cols[] = $c;
		}
		return $cols;
	}

	private function safeIdentifier( string $name ): string {
		if ( ! preg_match( '/^[A-Za-z0-9_]+$/', $name ) ) {
			throw new \InvalidArgumentException( esc_html( 'Unsafe SQL identifier: ' . $name ) );
		}
		return '`' . $name . '`';
	}
}
