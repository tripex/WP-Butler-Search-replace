<?php
declare( strict_types=1 );

namespace SmartSearchReplace\Engine;

/**
 * Immutable description of a search/replace operation.
 */
final class ReplacePlan {

	/**
	 * @param string[] $scope_ids
	 */
	public function __construct(
		public readonly string $search,
		public readonly string $replace,
		public readonly array $scope_ids,
		public readonly bool $regex = false,
		public readonly bool $case_sensitive = true,
		public readonly bool $whole_word = false,
		public readonly bool $protect_urls = false,
		public readonly bool $include_guid = false,
		public readonly int $batch_size = 200,
	) {}

	public function hash(): string {
		return hash(
			'sha256',
			wp_json_encode(
				array(
					$this->search,
					$this->replace,
					$this->scope_ids,
					$this->regex,
					$this->case_sensitive,
					$this->whole_word,
					$this->protect_urls,
					$this->include_guid,
				)
			)
		);
	}
}
