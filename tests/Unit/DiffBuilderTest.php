<?php
declare( strict_types=1 );

use PHPUnit\Framework\TestCase;
use SmartSearchReplace\Engine\DiffBuilder;

final class DiffBuilderTest extends TestCase {

	public function test_truncation_does_not_split_multibyte_chars(): void {
		// 'é' (2 bytes) straddles the 1000-byte truncation limit.
		$before = str_repeat( 'a', 999 ) . 'é tail beyond the limit ' . str_repeat( 'b', 50 );
		$out    = DiffBuilder::build( $before, 'x' );

		$this->assertSame( 1, preg_match( '~~u', $out['before'] ), 'Truncated diff must stay valid UTF-8.' );
		$this->assertStringEndsWith( '…', $out['before'] );
	}

	public function test_snippets_are_valid_utf8(): void {
		$before = str_repeat( 'æøå', 60 ) . 'foo' . str_repeat( 'æøå', 60 );
		$after  = str_replace( 'foo', 'bar', $before );
		$out    = DiffBuilder::build( $before, $after );

		foreach ( $out['snippets'] as $snippet ) {
			$this->assertSame( 1, preg_match( '~~u', $snippet['before'] ) );
			$this->assertSame( 1, preg_match( '~~u', $snippet['after'] ) );
		}
	}
}
