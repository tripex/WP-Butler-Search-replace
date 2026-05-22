<?php
declare( strict_types=1 );

namespace SmartSearchReplace\Engine;

use SmartSearchReplace\Scope\Target;

/**
 * Applies the configured replacer to each scanned column of a row.
 */
final class RowProcessor {

	public function __construct(
		private readonly StringReplacer $replacer,
	) {}

	/**
	 * @param array<string, mixed> $row
	 * @return Change[]
	 */
	public function process( Target $target, array $row ): array {
		$changes = array();
		$pk      = $row[ $target->primary_key ] ?? null;
		if ( null === $pk ) {
			return array();
		}

		foreach ( $target->columns as $col ) {
			if ( ! array_key_exists( $col, $row ) ) {
				continue;
			}
			$original = (string) $row[ $col ];
			if ( '' === $original ) {
				continue;
			}

			$serialized_walker = new SerializedReplacer(
				function ( string $chunk ): string {
					return $this->replacer->replace( $chunk );
				}
			);

			$new = $serialized_walker->process( $original );
			if ( ! is_string( $new ) ) {
				continue;
			}
			if ( $new === $original ) {
				continue;
			}

			$changes[] = new Change(
				table:              $target->table,
				primary_key_column: $target->primary_key,
				primary_key_value:  is_numeric( $pk ) ? (int) $pk : (string) $pk,
				column:             $col,
				before:             $original,
				after:              $new,
			);
		}

		return $changes;
	}
}
