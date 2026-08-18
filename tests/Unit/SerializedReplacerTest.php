<?php
declare( strict_types=1 );

use PHPUnit\Framework\TestCase;
use SmartSearchReplace\Engine\SerializedReplacer;

// A loaded class with a wakeup side effect — the replacer must never let
// unserialize() instantiate it from stored data.
final class EvilGadget {
	public static int $wakeups = 0;
	public string $prop        = '';

	public function __wakeup(): void {
		++self::$wakeups;
	}
}

final class SerializedReplacerTest extends TestCase {

	private function replacer( string $from, string $to ): SerializedReplacer {
		return new SerializedReplacer(
			static fn( string $s ) => str_replace( $from, $to, $s )
		);
	}

	public function test_simple_string(): void {
		$r = $this->replacer( 'foo', 'bar' );
		$this->assertSame( 'bar baz', $r->process( 'foo baz' ) );
	}

	public function test_serialized_array_keeps_byte_counts(): void {
		$data       = [ 'a' => 'foo', 'b' => [ 'c' => 'foobar' ] ];
		$serialized = serialize( $data );
		$r          = $this->replacer( 'foo', 'longer-replacement' );

		$result = $r->process( $serialized );
		$this->assertIsString( $result );
		$decoded = unserialize( $result );
		$this->assertSame( 'longer-replacement', $decoded['a'] );
		$this->assertSame( 'longer-replacementbar', $decoded['b']['c'] );
	}

	public function test_stdclass_object_in_serialized(): void {
		$obj         = new stdClass();
		$obj->title  = 'Hello foo';
		$obj->nested = [ 'foo', 'baz' ];
		$serialized  = serialize( $obj );

		$r      = $this->replacer( 'foo', 'WORLD' );
		$result = $r->process( $serialized );
		$decoded = unserialize( $result );
		$this->assertSame( 'Hello WORLD', $decoded->title );
		$this->assertSame( [ 'WORLD', 'baz' ], $decoded->nested );
	}

	public function test_unchanged_serialized_is_byte_identical(): void {
		$data       = [ 'unrelated' => 'nothing' ];
		$serialized = serialize( $data );
		$r          = $this->replacer( 'absent', 'absent' );
		$this->assertSame( $serialized, $r->process( $serialized ) );
	}

	public function test_json_in_string(): void {
		$json = '{"title":"foo bar","nested":{"x":"foo"}}';
		$r    = $this->replacer( 'foo', 'baz' );
		$out  = $r->process( $json );
		$decoded = json_decode( $out, true );
		$this->assertSame( 'baz bar', $decoded['title'] );
		$this->assertSame( 'baz', $decoded['nested']['x'] );
	}

	public function test_json_without_match_is_byte_identical(): void {
		// Non-canonical formatting (spaces, \uXXXX escapes) must survive
		// untouched when nothing matches — no cosmetic rewrites.
		$json = '{ "a": 1, "b": "café" }';
		$r    = $this->replacer( 'absent', 'absent' );
		$this->assertSame( $json, $r->process( $json ) );
	}

	public function test_unknown_class_is_never_instantiated_and_left_untouched(): void {
		$payload = 'a:2:{s:3:"obj";O:10:"EvilGadget":1:{s:4:"prop";s:3:"foo";}s:5:"other";s:7:"foo bar";}';

		$r      = $this->replacer( 'foo', 'WORLD' );
		$result = $r->process( $payload );

		$this->assertSame( 0, EvilGadget::$wakeups, 'Stored object must not be instantiated (object injection).' );
		$this->assertStringContainsString( 'O:10:"EvilGadget":1:{s:4:"prop";s:3:"foo";}', $result );
		$this->assertStringContainsString( 's:9:"WORLD bar"', $result );
	}

	public function test_multibyte_safe(): void {
		$data = [ 'mb' => 'cåføé' ];
		$ser  = serialize( $data );
		$r    = $this->replacer( 'cåføé', 'résumé' );
		$out  = $r->process( $ser );
		$dec  = unserialize( $out );
		$this->assertSame( 'résumé', $dec['mb'] );
	}
}
