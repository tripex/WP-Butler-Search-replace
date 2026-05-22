<?php
declare( strict_types=1 );

namespace SmartSearchReplace\Engine;

/**
 * Splits a string into "safe" (free-text) and "no-go" (URL/link) segments so
 * that replacements can run only on safe segments.
 *
 * The detector is intentionally conservative: when in doubt, mark as no-go.
 * It is NOT a full HTML parser — it works on raw strings, including HTML
 * fragments, markdown, and plain text. Detected no-go zones include:
 *   - Full URLs with scheme (http, https, ftp, mailto, tel, file)
 *   - Bare host/path tokens with a known TLD (e.g. example.com/foo)
 *   - HTML attribute values for href/src/srcset/action/data-* /poster/cite/formaction
 *   - Markdown link/image URL component: ](URL)
 *   - HTML/XML tag bodies (defensive: never mutate inside < ... >)
 */
final class UrlProtector {

	/**
	 * Pattern fragments compiled once at construction.
	 *
	 * @var string[]
	 */
	private array $patterns;

	public function __construct() {
		// Common TLDs — not exhaustive, but enough to catch typical "naked"
		// host references like "example.com/foo" without overreaching.
		$tld = '(?:com|net|org|io|co|dev|app|info|biz|me|us|uk|de|fr|nl|se|no|fi|dk|es|it|pl|ru|jp|cn|au|ca|br|in|eu|ai|xyz|site|online|shop|store|blog|news|media|tech|cloud|email|today|gov|edu|mil|name|tv|fm|cc|to|gl|ly|sh|so|st|im|tk|pro|local|test)';

			$this->patterns = array(
				// Tag bodies: never touch inside <...>. Catches all attribute
				// content (href, src, etc.) without needing per-attribute rules.
				'~<[^>]*>~u',

				// Markdown link/image URL: ](url) or ](url "title").
				'~\][(]\s*[^)\s]+(?:\s+"[^"]*")?\s*[)]~u',

				// Scheme-qualified URLs.
				'~(?:https?|ftp|file)://[^\s<>"\'`]+~iu',

				// mailto: / tel: / sms:.
				'~(?:mailto|tel|sms|callto|skype):[^\s<>"\'`]+~iu',

				// Bare email addresses (best-effort; conservative local-part).
				'~[A-Za-z0-9._%+\-]+@[A-Za-z0-9.\-]+\.' . $tld . '\b~iu',

				// Protocol-relative URLs.
				'~//[A-Za-z0-9.-]+\.' . $tld . '(?:[/?#][^\s<>"\'`]*)?~iu',

				// Bare host+path: example.com/path  /  sub.example.co.uk
				// Require a path/query/fragment or a clear word boundary so we
				// don't match plain sentence words. We anchor on a TLD list.
				'~\b[A-Za-z0-9](?:[A-Za-z0-9-]*[A-Za-z0-9])?(?:\.[A-Za-z0-9](?:[A-Za-z0-9-]*[A-Za-z0-9])?)*\.' . $tld . '(?:[/?#][^\s<>"\'`]*)?(?=[\s<>"\'`,;:!?)\]]|$)~iu',
			);
	}

	/**
	 * Collect [start, end) byte-offsets of all no-go zones, merged & sorted.
	 *
	 * @return array<int, array{0:int,1:int}>
	 */
	public function findProtectedRanges( string $input ): array {
		if ( '' === $input ) {
			return array();
		}

		$ranges = array();
		foreach ( $this->patterns as $pattern ) {
			if ( preg_match_all( $pattern, $input, $matches, PREG_OFFSET_CAPTURE ) ) {
				foreach ( $matches[0] as $m ) {
					$start    = (int) $m[1];
					$end      = $start + strlen( $m[0] );
					$ranges[] = array( $start, $end );
				}
			}
		}

		return $this->mergeRanges( $ranges );
	}

	/**
	 * Run $transform only on segments of $input that are NOT inside a protected range.
	 *
	 * @param callable(string):string $transform
	 */
	public function applyToSafeSegments( string $input, callable $transform ): string {
		$ranges = $this->findProtectedRanges( $input );
		if ( empty( $ranges ) ) {
			return $transform( $input );
		}

		$out    = '';
		$cursor = 0;
		foreach ( $ranges as [ $start, $end ] ) {
			if ( $start > $cursor ) {
				$out .= $transform( substr( $input, $cursor, $start - $cursor ) );
			}
			$out   .= substr( $input, $start, $end - $start );
			$cursor = $end;
		}
		if ( $cursor < strlen( $input ) ) {
			$out .= $transform( substr( $input, $cursor ) );
		}
		return $out;
	}

	/**
	 * @param array<int, array{0:int,1:int}> $ranges
	 * @return array<int, array{0:int,1:int}>
	 */
	private function mergeRanges( array $ranges ): array {
		if ( count( $ranges ) < 2 ) {
			return $ranges;
		}
		usort(
			$ranges,
			static fn( $a, $b ) => $a[0] <=> $b[0]
		);
		$merged = array();
		$cur    = $ranges[0];
		for ( $i = 1, $n = count( $ranges ); $i < $n; $i++ ) {
			if ( $ranges[ $i ][0] <= $cur[1] ) {
				$cur[1] = max( $cur[1], $ranges[ $i ][1] );
			} else {
				$merged[] = $cur;
				$cur      = $ranges[ $i ];
			}
		}
		$merged[] = $cur;
		return $merged;
	}
}
