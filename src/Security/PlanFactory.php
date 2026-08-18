<?php
declare( strict_types=1 );

namespace SmartSearchReplace\Security;

use SmartSearchReplace\Engine\ReplacePlan;
use SmartSearchReplace\Engine\RegexMatcher;

/**
 * Builds a validated ReplacePlan from $_POST.
 *
 * Callers (PreviewController, ExecuteController) invoke Nonce::ensureFromRequest()
 * before calling fromRequest(), so $_POST access here is gated by a verified
 * nonce — PHPCS cannot see across call boundaries, so the file-level disable
 * below is justified.
 */
// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified in calling controllers via Nonce::ensureFromRequest().
final class PlanFactory {

	private const MAX_PATTERN_LENGTH = 4096;

	/**
	 * @throws \InvalidArgumentException
	 */
	public static function fromRequest(): ReplacePlan {
		$search  = self::readString( 'search' );
		$replace = self::readString( 'replace' );

		if ( '' === $search ) {
			throw new \InvalidArgumentException( esc_html__( 'Search pattern cannot be empty.', 'smart-search-replace' ) );
		}
		if ( strlen( $search ) > self::MAX_PATTERN_LENGTH || strlen( $replace ) > self::MAX_PATTERN_LENGTH ) {
			throw new \InvalidArgumentException( esc_html__( 'Search or replace pattern is too long.', 'smart-search-replace' ) );
		}
		if ( ! self::isUtf8( $search ) || ! self::isUtf8( $replace ) ) {
			throw new \InvalidArgumentException( esc_html__( 'Patterns must be valid UTF-8.', 'smart-search-replace' ) );
		}

		$scope_ids = isset( $_POST['scope_ids'] ) && is_array( $_POST['scope_ids'] )
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.Security.ValidatedSanitizedInput.MissingUnslash
			? array_values( array_filter( array_map( 'sanitize_key', wp_unslash( $_POST['scope_ids'] ) ) ) )
			: array();

		if ( empty( $scope_ids ) ) {
			throw new \InvalidArgumentException( esc_html__( 'Select at least one scope.', 'smart-search-replace' ) );
		}

		$regex          = self::readBool( 'regex' );
		$case_sensitive = self::readBool( 'case_sensitive', true );
		$whole_word     = self::readBool( 'whole_word' );
		$protect_urls   = self::readBool( 'protect_urls' );
		$include_guid   = self::readBool( 'include_guid' );

		if ( $regex ) {
			$err = RegexMatcher::validate( $search, $case_sensitive );
			if ( null !== $err ) {
				throw new \InvalidArgumentException( esc_html( $err ) );
			}
		}

		$batch_size = isset( $_POST['batch_size'] ) ? max( 10, min( 1000, (int) $_POST['batch_size'] ) ) : 200;

		return new ReplacePlan(
			search:         $search,
			replace:        $replace,
			scope_ids:      $scope_ids,
			regex:          $regex,
			case_sensitive: $case_sensitive,
			whole_word:     $whole_word,
			protect_urls:   $protect_urls,
			include_guid:   $include_guid,
			batch_size:     $batch_size,
		);
	}

	/**
	 * PCRE-based UTF-8 validity check — avoids depending on the optional
	 * mbstring extension.
	 */
	private static function isUtf8( string $s ): bool {
		return 1 === @preg_match( '~~u', $s );
	}

	private static function readString( string $key ): string {
		if ( ! isset( $_POST[ $key ] ) ) {
			return '';
		}
		// We deliberately do NOT strip characters from the search/replace
		// inputs; users may need to match arbitrary text. We only unslash
		// (WP magic quotes) and verify UTF-8 elsewhere.
		return (string) wp_unslash( $_POST[ $key ] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	}

	private static function readBool( string $key, bool $default = false ): bool {
		if ( ! isset( $_POST[ $key ] ) ) {
			return $default;
		}
		$v = sanitize_text_field( (string) wp_unslash( $_POST[ $key ] ) );
		return in_array( $v, array( '1', 'true', 'on', 'yes' ), true );
	}
}
