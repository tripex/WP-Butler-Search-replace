<?php
declare( strict_types=1 );

namespace SmartSearchReplace\Engine;

/**
 * Recursively replaces inside a (possibly serialized / JSON-encoded) value.
 *
 * Strategy:
 *   1. If the value is a string, attempt to unserialize. If it succeeds and
 *      reserializing produces a structurally equivalent payload, recurse
 *      into the deserialized structure and reserialize the result.
 *   2. Else if it looks like JSON, decode → recurse → re-encode (preserving
 *      slashes/unicode).
 *   3. Else apply the string transform directly.
 *   4. Arrays and objects are walked element-by-element / property-by-property.
 *
 * This avoids naive str_replace on the serialized byte representation, which
 * would corrupt length prefixes when the new value has a different byte size.
 */
final class SerializedReplacer {

	/**
	 * @param callable(string):string $transform
	 */
	public function __construct( private readonly mixed $transform ) {}

	public function process( mixed $value ): mixed {
		return $this->walk( $value, 0 );
	}

	private function walk( mixed $value, int $depth ): mixed {
		if ( $depth > 64 ) {
			return $value;
		}

		if ( is_string( $value ) ) {
			return $this->handleString( $value, $depth );
		}

		if ( is_array( $value ) ) {
			$out = array();
			foreach ( $value as $k => $v ) {
				$out[ $k ] = $this->walk( $v, $depth + 1 );
			}
			return $out;
		}

		if ( $value instanceof \stdClass ) {
			$out = new \stdClass();
			foreach ( get_object_vars( $value ) as $k => $v ) {
				$out->{$k} = $this->walk( $v, $depth + 1 );
			}
			return $out;
		}

		if ( is_object( $value ) ) {
			// Other objects: walk public props only; preserve class identity.
			$clone = clone $value;
			foreach ( get_object_vars( $clone ) as $k => $v ) {
				$clone->{$k} = $this->walk( $v, $depth + 1 );
			}
			return $clone;
		}

		return $value;
	}

	private function handleString( string $value, int $depth ): string {
		// Try serialized first.
		if ( $this->isSerialized( $value ) ) {
			$unserialized = @unserialize( $value, array( 'allowed_classes' => true ) );
			if ( false !== $unserialized || 'b:0;' === $value ) {
				$processed = $this->walk( $unserialized, $depth + 1 );
				return serialize( $processed );
			}
		}

		// Try JSON (object/array shape only — avoid touching plain numbers/strings).
		if ( $this->looksLikeJson( $value ) ) {
			$decoded = json_decode( $value, true );
			if ( null !== $decoded && JSON_ERROR_NONE === json_last_error() ) {
				$processed = $this->walk( $decoded, $depth + 1 );
				$encoded   = wp_json_encode( $processed, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
				if ( is_string( $encoded ) ) {
					return $encoded;
				}
			}
		}

		return ( $this->transform )( $value );
	}

	private function isSerialized( string $value ): bool {
		$value = trim( $value );
		if ( '' === $value ) {
			return false;
		}
		if ( 'N;' === $value ) {
			return true;
		}
		if ( strlen( $value ) < 4 ) {
			return false;
		}
		if ( ':' !== $value[1] ) {
			return false;
		}
		return in_array( $value[0], array( 's', 'a', 'O', 'i', 'd', 'b' ), true )
			&& ( str_ends_with( $value, ';' ) || str_ends_with( $value, '}' ) );
	}

	private function looksLikeJson( string $value ): bool {
		$trim = ltrim( $value );
		if ( '' === $trim ) {
			return false;
		}
		$first = $trim[0];
		if ( '{' !== $first && '[' !== $first ) {
			return false;
		}
		$last = rtrim( $value );
		$end  = $last[ strlen( $last ) - 1 ] ?? '';
		return ( '{' === $first && '}' === $end ) || ( '[' === $first && ']' === $end );
	}
}
