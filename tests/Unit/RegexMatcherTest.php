<?php
declare( strict_types=1 );

use PHPUnit\Framework\TestCase;
use SmartSearchReplace\Engine\RegexMatcher;

final class RegexMatcherTest extends TestCase {

	public function test_compiles_basic_pattern(): void {
		$p = RegexMatcher::compile( 'foo+', true );
		$this->assertSame( 1, preg_match( $p, 'foooo' ) );
	}

	public function test_case_insensitive_flag(): void {
		$p = RegexMatcher::compile( 'FOO', false );
		$this->assertSame( 1, preg_match( $p, 'foo' ) );
	}

	public function test_rejects_empty(): void {
		$this->expectException( InvalidArgumentException::class );
		RegexMatcher::compile( '', true );
	}

	public function test_rejects_K_escape(): void {
		$this->expectException( InvalidArgumentException::class );
		RegexMatcher::compile( 'foo\\Kbar', true );
	}

	public function test_rejects_invalid_pattern(): void {
		$this->expectException( InvalidArgumentException::class );
		RegexMatcher::compile( '(unclosed', true );
	}

	public function test_validate_returns_string_on_error(): void {
		$this->assertIsString( RegexMatcher::validate( '(unclosed', true ) );
		$this->assertNull( RegexMatcher::validate( 'ok', true ) );
	}

	public function test_replacement_with_backref(): void {
		$p = RegexMatcher::compile( '(\d+)', true );
		$this->assertSame( '[42]', preg_replace( $p, '[$1]', '42' ) );
	}
}
