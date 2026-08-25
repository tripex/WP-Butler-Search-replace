<?php
declare( strict_types=1 );

use PHPUnit\Framework\TestCase;
use SmartSearchReplace\Engine\Change;
use SmartSearchReplace\Engine\ReplacePlan;
use SmartSearchReplace\Engine\RowProcessor;
use SmartSearchReplace\Engine\StringReplacer;
use SmartSearchReplace\Engine\UrlProtector;
use SmartSearchReplace\Scope\Target;

/**
 * Proves dry-run produces the same Change set the executor would write.
 *
 * Since dry-run and execute share RowProcessor end-to-end (only Writer is
 * skipped in dry-run), processing the same rows twice in dry-run mode must
 * yield identical Change identities and before/after pairs.
 */
final class DryRunMatchesExecuteTest extends TestCase {

	public function test_dry_run_and_execute_emit_identical_changes(): void {
		$plan = new ReplacePlan(
			search:        'foo',
			replace:       'BAR',
			scope_ids:     [ 'posts' ],
			protect_urls:  true,
		);

		$replacer  = StringReplacer::fromPlan( $plan, new UrlProtector() );
		$processor = new RowProcessor( $replacer );
		$target    = new Target(
			table:        'wp_posts',
			primary_key:  'ID',
			columns:      [ 'post_content', 'post_title' ],
		);

		$rows = [
			[
				'ID'           => 1,
				'post_title'   => 'foo title',
				'post_content' => 'see https://x.com/foo and foo here',
			],
			[
				'ID'           => 2,
				'post_title'   => 'no match',
				'post_content' => 'also no match',
			],
			[
				'ID'           => 3,
				'post_title'   => 'foo',
				'post_content' => serialize( [ 'key' => 'foo nested' ] ),
			],
		];

		$first  = $this->collect( $processor, $target, $rows );
		$second = $this->collect( $processor, $target, $rows );

		$this->assertSame(
			$this->signature( $first ),
			$this->signature( $second ),
			'Two runs over identical inputs must produce identical change sets.'
		);

		// Spot check expected results.
		$by_id = [];
		foreach ( $first as $c ) {
			$by_id[ $c->primary_key_value . ':' . $c->column ] = $c;
		}
		$this->assertArrayHasKey( '1:post_title', $by_id );
		$this->assertSame( 'BAR title', $by_id['1:post_title']->after );
		$this->assertStringContainsString( 'https://x.com/foo', $by_id['1:post_content']->after );
		$this->assertStringContainsString( 'BAR here', $by_id['1:post_content']->after );
		$this->assertArrayNotHasKey( '2:post_title', $by_id );
		$this->assertArrayHasKey( '3:post_content', $by_id );
		$decoded = unserialize( $by_id['3:post_content']->after );
		$this->assertSame( 'BAR nested', $decoded['key'] );
	}

	/**
	 * @param array<int, array<string, mixed>> $rows
	 * @return Change[]
	 */
	private function collect( RowProcessor $processor, Target $target, array $rows ): array {
		$out = [];
		foreach ( $rows as $row ) {
			foreach ( $processor->process( $target, $row ) as $c ) {
				$out[] = $c;
			}
		}
		return $out;
	}

	/**
	 * @param Change[] $changes
	 */
	private function signature( array $changes ): string {
		$parts = array_map(
			static fn( Change $c ) => $c->identity() . '=' . hash( 'sha256', $c->before . '|' . $c->after ),
			$changes
		);
		return implode( "\n", $parts );
	}
}
