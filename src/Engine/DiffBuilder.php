<?php
declare( strict_types=1 );

namespace SmartSearchReplace\Engine;

/**
 * Builds a compact diff record for a single column change.
 */
final class DiffBuilder {

	private const CONTEXT_CHARS = 60;
	private const MAX_SNIPPETS  = 5;

	/**
	 * @return array{before:string,after:string,snippets:array<int,array{before:string,after:string}>}
	 */
	public static function build( string $before, string $after ): array {
		return array(
			'before'   => self::truncate( $before, 1000 ),
			'after'    => self::truncate( $after, 1000 ),
			'snippets' => self::snippets( $before, $after ),
		);
	}

	/**
	 * @return array<int,array{before:string,after:string}>
	 */
	private static function snippets( string $before, string $after ): array {
		// Find divergence points without LCS — we walk both strings until
		// they diverge, jump to the next match, and capture context. This
		// is intentionally simple; the engine already knows precise change
		// locations are not required for an MVP preview.
		$out = array();
		$i   = 0;
		$j   = 0;
		while ( $i < strlen( $before ) && $j < strlen( $after ) && count( $out ) < self::MAX_SNIPPETS ) {
			if ( $before[ $i ] === $after[ $j ] ) {
				++$i;
				++$j;
				continue;
			}
			$start_b = max( 0, $i - self::CONTEXT_CHARS );
			$start_a = max( 0, $j - self::CONTEXT_CHARS );
			$end_b   = min( strlen( $before ), $i + self::CONTEXT_CHARS );
			$end_a   = min( strlen( $after ), $j + self::CONTEXT_CHARS );
			$out[]   = array(
				'before' => substr( $before, $start_b, $end_b - $start_b ),
				'after'  => substr( $after, $start_a, $end_a - $start_a ),
			);
			$i       = $end_b;
			$j       = $end_a;
		}
		return $out;
	}

	private static function truncate( string $s, int $limit ): string {
		if ( strlen( $s ) <= $limit ) {
			return $s;
		}
		return substr( $s, 0, $limit ) . '…';
	}
}
