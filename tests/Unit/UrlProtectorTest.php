<?php
declare( strict_types=1 );

use PHPUnit\Framework\TestCase;
use SmartSearchReplace\Engine\UrlProtector;

final class UrlProtectorTest extends TestCase {

	private function replace( string $input, string $search, string $replace ): string {
		$protector = new UrlProtector();
		return $protector->applyToSafeSegments(
			$input,
			static fn( string $chunk ) => str_replace( $search, $replace, $chunk )
		);
	}

	public function test_replaces_in_plain_text_but_not_in_anchor_href(): void {
		$input    = '<p>Coating service.</p><a href="https://example.com/coating">Coating</a>';
		$expected = '<p>Ceramic coating service.</p><a href="https://example.com/coating">Ceramic coating</a>';
		$this->assertSame(
			$expected,
			$this->replace( $input, 'Coating', 'Ceramic coating' )
		);
	}

	public function test_replaces_in_text_but_not_in_bare_host_url(): void {
		$input    = 'Our coating works. See example.com/coating-service for details.';
		$result   = $this->replace( $input, 'coating', 'ceramic coating' );
		$this->assertStringContainsString( 'Our ceramic coating works.', $result );
		$this->assertStringContainsString( 'example.com/coating-service', $result );
		$this->assertStringNotContainsString( 'example.com/ceramic coating', $result );
	}

	public function test_protects_markdown_link_target(): void {
		$input    = '[Coating](/coating) is great.';
		$result   = $this->replace( $input, 'Coating', 'Wrap' );
		// The link target /coating must be preserved; only visible "Coating" text changes.
		$this->assertStringContainsString( '](/coating)', $result );
		$this->assertStringContainsString( 'Wrap', $result );
	}

	public function test_protects_mailto(): void {
		$input  = 'Email coating@example.com about coating.';
		$result = $this->replace( $input, 'coating', 'wrap' );
		$this->assertStringContainsString( 'coating@example.com', $result );
		$this->assertStringContainsString( 'about wrap.', $result );
	}

	public function test_protects_https_url_in_plain_text(): void {
		$input  = 'See https://example.com/coating for coating tips.';
		$result = $this->replace( $input, 'coating', 'wrap' );
		$this->assertStringContainsString( 'https://example.com/coating', $result );
		$this->assertStringContainsString( 'wrap tips', $result );
	}

	public function test_no_change_when_only_match_is_inside_url(): void {
		$input  = '<a href="/coating">Link</a>';
		$result = $this->replace( $input, 'coating', 'wrap' );
		$this->assertSame( $input, $result );
	}
}
