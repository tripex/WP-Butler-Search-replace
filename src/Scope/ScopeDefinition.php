<?php
declare( strict_types=1 );

namespace SmartSearchReplace\Scope;

/**
 * Human-friendly grouping that maps to one or more concrete Targets.
 */
final class ScopeDefinition {

	/**
	 * @param Target[] $targets
	 */
	public function __construct(
		public readonly string $id,
		public readonly string $label,
		public readonly string $group,
		public readonly array $targets,
		public readonly string $description = '',
	) {}
}
