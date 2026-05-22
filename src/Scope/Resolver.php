<?php
declare( strict_types=1 );

namespace SmartSearchReplace\Scope;

/**
 * Flattens a set of ScopeDefinitions into a deduplicated list of Targets
 * the engine should scan.
 */
final class Resolver {

	/**
	 * @param ScopeDefinition[] $defs
	 * @return Target[]
	 */
	public function resolve( array $defs ): array {
		/** @var array<string, Target> $by_key */
		$by_key = array();
		foreach ( $defs as $def ) {
			foreach ( $def->targets as $t ) {
				$key = $this->keyFor( $t );
				if ( isset( $by_key[ $key ] ) ) {
					$existing       = $by_key[ $key ];
					$by_key[ $key ] = new Target(
						table:        $existing->table,
						primary_key:  $existing->primary_key,
						columns:      array_values( array_unique( array_merge( $existing->columns, $t->columns ) ) ),
						where_equals: $existing->where_equals,
						join_filter:  $existing->join_filter,
					);
				} else {
					$by_key[ $key ] = $t;
				}
			}
		}
		return array_values( $by_key );
	}

	private function keyFor( Target $t ): string {
		return hash(
			'sha256',
			wp_json_encode(
				array(
					$t->table,
					$t->primary_key,
					$t->where_equals,
					$t->join_filter,
				)
			)
		);
	}
}
