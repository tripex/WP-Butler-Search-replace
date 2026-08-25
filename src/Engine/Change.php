<?php
declare( strict_types=1 );

namespace SmartSearchReplace\Engine;

/**
 * A single column change emitted by the engine.
 */
final class Change {

	public function __construct(
		public readonly string $table,
		public readonly string $primary_key_column,
		public readonly int|string $primary_key_value,
		public readonly string $column,
		public readonly string $before,
		public readonly string $after,
	) {}

	public function identity(): string {
		return $this->table . '#' . $this->primary_key_value . '#' . $this->column;
	}
}
