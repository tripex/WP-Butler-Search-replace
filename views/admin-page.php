<?php
/**
 * @var array<string, \SmartSearchReplace\Scope\ScopeDefinition[]> $grouped
 * @var string $nonce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap ssr-wrap">
	<h1><?php esc_html_e( 'Smart Search Replace', 'smart-search-replace' ); ?></h1>

	<p class="description">
		<?php esc_html_e( 'Replace text across your site safely. Preview every change before writing to the database.', 'smart-search-replace' ); ?>
	</p>

	<form id="ssr-form" onsubmit="return false;">
		<input type="hidden" name="<?php echo esc_attr( \SmartSearchReplace\Security\Nonce::FIELD ); ?>" value="<?php echo esc_attr( $nonce ); ?>" />

		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><label for="ssr-search"><?php esc_html_e( 'Search for', 'smart-search-replace' ); ?></label></th>
				<td><textarea id="ssr-search" name="search" rows="2" class="large-text code" spellcheck="false"></textarea></td>
			</tr>
			<tr>
				<th scope="row"><label for="ssr-replace"><?php esc_html_e( 'Replace with', 'smart-search-replace' ); ?></label></th>
				<td><textarea id="ssr-replace" name="replace" rows="2" class="large-text code" spellcheck="false"></textarea></td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Options', 'smart-search-replace' ); ?></th>
				<td>
					<fieldset>
						<label><input type="checkbox" name="protect_urls" value="1" /> <?php esc_html_e( 'Protect URLs and links from changes', 'smart-search-replace' ); ?></label><br />
						<label><input type="checkbox" name="case_sensitive" value="1" checked /> <?php esc_html_e( 'Case sensitive', 'smart-search-replace' ); ?></label><br />
						<label><input type="checkbox" name="whole_word" value="1" /> <?php esc_html_e( 'Match whole words only', 'smart-search-replace' ); ?></label><br />
						<label><input type="checkbox" name="regex" value="1" /> <?php esc_html_e( 'Use regular expression', 'smart-search-replace' ); ?></label>
						<span id="ssr-regex-error" class="ssr-error" hidden></span>
					</fieldset>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Where should we look?', 'smart-search-replace' ); ?></th>
				<td>
					<?php foreach ( $grouped as $group_name => $defs ) : ?>
						<fieldset class="ssr-scope-group">
							<legend><strong><?php echo esc_html( $group_name ); ?></strong></legend>
							<?php foreach ( $defs as $def ) : ?>
								<label>
									<input type="checkbox" name="scope_ids[]" value="<?php echo esc_attr( $def->id ); ?>" />
									<?php echo esc_html( $def->label ); ?>
									<?php if ( '' !== $def->description ) : ?>
										<span class="description"><?php echo esc_html( $def->description ); ?></span>
									<?php endif; ?>
								</label><br />
							<?php endforeach; ?>
						</fieldset>
					<?php endforeach; ?>
					<details class="ssr-advanced">
						<summary><?php esc_html_e( 'Advanced', 'smart-search-replace' ); ?></summary>
						<label><input type="checkbox" name="include_guid" value="1" /> <?php esc_html_e( 'Include the guid column (not recommended)', 'smart-search-replace' ); ?></label>
					</details>
				</td>
			</tr>
		</table>

		<p class="submit">
			<button type="button" class="button button-primary" id="ssr-preview"><?php esc_html_e( 'Preview (dry-run)', 'smart-search-replace' ); ?></button>
			<button type="button" class="button button-secondary" id="ssr-execute" disabled><?php esc_html_e( 'Execute changes', 'smart-search-replace' ); ?></button>
		</p>
	</form>

	<div id="ssr-progress" class="ssr-progress" hidden>
		<p><span id="ssr-progress-text"></span></p>
		<div class="ssr-progress-bar"><div id="ssr-progress-fill"></div></div>
	</div>

	<div id="ssr-results" class="ssr-results" hidden>
		<h2><?php esc_html_e( 'Preview results', 'smart-search-replace' ); ?></h2>
		<p id="ssr-results-summary"></p>
		<table class="widefat striped" id="ssr-results-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Table', 'smart-search-replace' ); ?></th>
					<th><?php esc_html_e( 'Row', 'smart-search-replace' ); ?></th>
					<th><?php esc_html_e( 'Column', 'smart-search-replace' ); ?></th>
					<th><?php esc_html_e( 'Before', 'smart-search-replace' ); ?></th>
					<th><?php esc_html_e( 'After', 'smart-search-replace' ); ?></th>
				</tr>
			</thead>
			<tbody></tbody>
		</table>
	</div>
</div>
