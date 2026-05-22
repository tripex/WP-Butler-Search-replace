<?php
declare( strict_types=1 );

namespace SmartSearchReplace\Engine;

/**
 * Performs literal or regex replacement on a string, optionally restricted to
 * "safe" (non-URL) segments via UrlProtector.
 */
final class StringReplacer {

	public function __construct(
		private readonly ReplacePlan $plan,
		private readonly UrlProtector $url_protector,
		private readonly ?string $compiled_regex = null,
	) {}

	public static function fromPlan( ReplacePlan $plan, UrlProtector $protector ): self {
		$compiled = null;
		if ( $plan->regex ) {
			$compiled = RegexMatcher::compile( $plan->search, $plan->case_sensitive );
		}
		return new self( $plan, $protector, $compiled );
	}

	/**
	 * Apply the configured replacement to $input. Returns the (possibly)
	 * modified string. Never throws on per-match errors; returns input
	 * unchanged if regex execution fails.
	 */
	public function replace( string $input ): string {
		if ( '' === $input ) {
			return $input;
		}

		$transform = function ( string $chunk ): string {
			return $this->runReplace( $chunk );
		};

		if ( $this->plan->protect_urls ) {
			return $this->url_protector->applyToSafeSegments( $input, $transform );
		}
		return $transform( $input );
	}

	private function runReplace( string $chunk ): string {
		if ( $this->plan->regex ) {
			if ( null === $this->compiled_regex ) {
				return $chunk;
			}
			$result = @preg_replace( $this->compiled_regex, $this->plan->replace, $chunk );
			return is_string( $result ) ? $result : $chunk;
		}

		if ( '' === $this->plan->search ) {
			return $chunk;
		}

		if ( $this->plan->whole_word ) {
			$pattern = '~(?<![\p{L}\p{N}_])' . preg_quote( $this->plan->search, '~' ) . '(?![\p{L}\p{N}_])~u'
				. ( $this->plan->case_sensitive ? '' : 'i' );
			$result  = @preg_replace( $pattern, $this->plan->replace, $chunk );
			return is_string( $result ) ? $result : $chunk;
		}

		if ( $this->plan->case_sensitive ) {
			return str_replace( $this->plan->search, $this->plan->replace, $chunk );
		}
		return str_ireplace( $this->plan->search, $this->plan->replace, $chunk );
	}

	/**
	 * Whether $input would change under this replacer (cheap check).
	 */
	public function wouldChange( string $input ): bool {
		return $this->replace( $input ) !== $input;
	}
}
