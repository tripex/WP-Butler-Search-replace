<?php
declare( strict_types=1 );

namespace SmartSearchReplace\Engine;

/**
 * Compiles a user-supplied regex pattern into a safe PCRE pattern that uses
 * a non-printable delimiter (so users do not need to know about delimiters)
 * and is forced to Unicode mode.
 *
 * Rejects deprecated/dangerous modifiers (e, \K is allowed but we keep it
 * simple in MVP and reject it too).
 */
final class RegexMatcher {

	private const DELIMITER = "\x1f"; // ASCII unit separator — disallowed in user input by sanitization.

	/**
	 * @throws \InvalidArgumentException When the pattern is invalid or contains a forbidden construct.
	 */
	public static function compile( string $user_pattern, bool $case_sensitive ): string {
		if ( '' === $user_pattern ) {
			throw new \InvalidArgumentException( esc_html__( 'Empty regex pattern.', 'smart-search-replace' ) );
		}
		if ( str_contains( $user_pattern, self::DELIMITER ) ) {
			throw new \InvalidArgumentException( esc_html__( 'Pattern contains forbidden control character.', 'smart-search-replace' ) );
		}
		if ( preg_match( '~\\\\K~', $user_pattern ) ) {
			throw new \InvalidArgumentException( esc_html__( 'The \K escape is not supported in this MVP.', 'smart-search-replace' ) );
		}

		$flags  = 'u';
		$flags .= $case_sensitive ? '' : 'i';

		$compiled = self::DELIMITER . $user_pattern . self::DELIMITER . $flags;

		// Validate by running against an empty string.
		set_error_handler(
			static function (): bool {
				return true;
			}
		);
		$ok = @preg_match( $compiled, '' );
		restore_error_handler();

		if ( false === $ok ) {
			throw new \InvalidArgumentException( esc_html( self::lastErrorMessage() ) );
		}

		return $compiled;
	}

	public static function validate( string $user_pattern, bool $case_sensitive ): ?string {
		try {
			self::compile( $user_pattern, $case_sensitive );
			return null;
		} catch ( \InvalidArgumentException $e ) {
			return $e->getMessage();
		}
	}

	private static function lastErrorMessage(): string {
		$code = preg_last_error();
		return match ( $code ) {
			PREG_INTERNAL_ERROR        => 'Internal PCRE error.',
			PREG_BACKTRACK_LIMIT_ERROR => 'Pattern is too complex (backtrack limit).',
			PREG_RECURSION_LIMIT_ERROR => 'Pattern is too complex (recursion limit).',
			PREG_BAD_UTF8_ERROR        => 'Invalid UTF-8 in input.',
			PREG_BAD_UTF8_OFFSET_ERROR => 'Invalid UTF-8 offset.',
			PREG_JIT_STACKLIMIT_ERROR  => 'Pattern uses too much memory.',
			default                    => 'Invalid regular expression.',
		};
	}
}
