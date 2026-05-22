<?php
declare( strict_types=1 );

namespace SmartSearchReplace\Scope;

/**
 * A concrete (table, columns, where) target for replacement.
 */
final class Target {

	/**
	 * @param string[]                                                                                                       $columns      Columns to scan.
	 * @param array<string, scalar|array<int, scalar>>                                                                       $where_equals Column => value (or values) equality filters on the same table.
	 * @param array{table:string, local_key:string, foreign_key:string, equals: array<string,scalar|array<int,scalar>>}|null $join_filter
	 *        Optional join filter: rows of $table whose $local_key matches a $foreign_key in $join.table where $join.equals hold.
	 */
	public function __construct(
		public readonly string $table,
		public readonly string $primary_key,
		public readonly array $columns,
		public readonly array $where_equals = array(),
		public readonly ?array $join_filter = null,
	) {}
}
