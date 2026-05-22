<?php
declare( strict_types=1 );

namespace SmartSearchReplace\Scope;

final class ScopeRegistry {

	/** @var array<string, ScopeDefinition> */
	private array $scopes = array();

	private bool $built = false;

	public function __construct( private readonly Discoverer $discoverer ) {}

	public function register( ScopeDefinition $definition ): void {
		$this->scopes[ $definition->id ] = $definition;
	}

	/**
	 * @return array<string, ScopeDefinition>
	 */
	public function all(): array {
		if ( ! $this->built ) {
			$this->built = true;
			foreach ( $this->discoverer->discover() as $def ) {
				$this->register( $def );
			}
			/**
			 * Filter the full list of scope definitions.
			 *
			 * @param array<string, ScopeDefinition> $scopes
			 */
			$this->scopes = apply_filters( 'ssr_scopes', $this->scopes );
		}
		return $this->scopes;
	}

	public function get( string $id ): ?ScopeDefinition {
		$all = $this->all();
		return $all[ $id ] ?? null;
	}

	/**
	 * @param string[] $ids
	 * @return ScopeDefinition[]
	 */
	public function getMany( array $ids ): array {
		$out = array();
		foreach ( $ids as $id ) {
			$def = $this->get( $id );
			if ( $def ) {
				$out[] = $def;
			}
		}
		return $out;
	}
}
