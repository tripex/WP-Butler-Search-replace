<?php
declare( strict_types=1 );

use PHPUnit\Framework\TestCase;
use SmartSearchReplace\Engine\ReplacePlan;
use SmartSearchReplace\Engine\StringReplacer;
use SmartSearchReplace\Engine\UrlProtector;

final class StringReplacerTest extends TestCase {

	private function plan( array $overrides = [] ): ReplacePlan {
		return new ReplacePlan(
			search:         $overrides['search']         ?? 'foo',
			replace:        $overrides['replace']        ?? 'bar',
			scope_ids:      $overrides['scope_ids']      ?? [ 'posts' ],
			regex:          $overrides['regex']          ?? false,
			case_sensitive: $overrides['case_sensitive'] ?? true,
			whole_word:     $overrides['whole_word']     ?? false,
			protect_urls:   $overrides['protect_urls']   ?? false,
			include_guid:   $overrides['include_guid']   ?? false,
		);
	}

	public function test_literal_replace(): void {
		$r = StringReplacer::fromPlan( $this->plan(), new UrlProtector() );
		$this->assertSame( 'bar baz', $r->replace( 'foo baz' ) );
	}

	public function test_case_insensitive(): void {
		$r = StringReplacer::fromPlan( $this->plan( [ 'case_sensitive' => false ] ), new UrlProtector() );
		$this->assertSame( 'bar baz', $r->replace( 'FOO baz' ) );
	}

	public function test_whole_word(): void {
		$r = StringReplacer::fromPlan( $this->plan( [ 'whole_word' => true ] ), new UrlProtector() );
		$this->assertSame( 'bar food', $r->replace( 'foo food' ) );
	}

	public function test_regex_with_backref(): void {
		$r = StringReplacer::fromPlan(
			$this->plan( [ 'regex' => true, 'search' => '(\d+)', 'replace' => '[$1]' ] ),
			new UrlProtector()
		);
		$this->assertSame( 'item [42]', $r->replace( 'item 42' ) );
	}

	public function test_protect_urls_combined(): void {
		$r = StringReplacer::fromPlan(
			$this->plan( [ 'search' => 'coating', 'replace' => 'wrap', 'protect_urls' => true ] ),
			new UrlProtector()
		);
		$out = $r->replace( 'coating at https://example.com/coating works' );
		$this->assertStringContainsString( 'wrap at', $out );
		$this->assertStringContainsString( 'https://example.com/coating', $out );
	}
}
