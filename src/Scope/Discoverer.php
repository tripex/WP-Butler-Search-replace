<?php
declare( strict_types=1 );

namespace SmartSearchReplace\Scope;

/**
 * Builds the list of available scope definitions for the current site.
 *
 * Detects post types (built-in + public CPTs), taxonomies, comments,
 * users, options and WooCommerce (incl. HPOS) dynamically.
 */
final class Discoverer {

	/**
	 * @return ScopeDefinition[]
	 */
	public function discover(): array {
		global $wpdb;

		$content_group  = __( 'Content', 'smart-search-replace' );
		$taxonomy_group = __( 'Taxonomies', 'smart-search-replace' );
		$users_group    = __( 'Users', 'smart-search-replace' );
		$settings_group = __( 'Settings', 'smart-search-replace' );
		$ecommerce      = __( 'WooCommerce', 'smart-search-replace' );

		$out = array();

		// ---- Built-in post-type scopes ----.
		$out[] = $this->postScope( 'posts', __( 'Posts', 'smart-search-replace' ), 'post', $content_group );
		$out[] = $this->postScope( 'pages', __( 'Pages', 'smart-search-replace' ), 'page', $content_group );
		$out[] = $this->postScope(
			'media',
			__( 'Media (attachments)', 'smart-search-replace' ),
			'attachment',
			$content_group,
			extra_meta_keys: array( '_wp_attachment_image_alt' ),
		);

		// ---- Comments ----.
		$out[] = new ScopeDefinition(
			id:      'comments',
			label:   __( 'Comments', 'smart-search-replace' ),
			group:   $content_group,
			targets: array(
				new Target(
					table:       $wpdb->comments,
					primary_key: 'comment_ID',
					columns:     array( 'comment_content', 'comment_author', 'comment_author_url', 'comment_author_email' ),
				),
				new Target(
					table:       $wpdb->commentmeta,
					primary_key: 'meta_id',
					columns:     array( 'meta_value' ),
				),
			),
		);

		// ---- Public CPTs ----.
		$cpts = get_post_types(
			array(
				'public'   => true,
				'_builtin' => false,
			),
			'objects'
		);
		foreach ( $cpts as $pt ) {
			if ( 'attachment' === $pt->name ) {
				continue;
			}
			$out[] = $this->postScope(
				'cpt_' . $pt->name,
				/* translators: %s: post-type label. */
				sprintf( __( 'Custom Type: %s', 'smart-search-replace' ), $pt->labels->name ?? $pt->name ),
				$pt->name,
				$content_group,
			);
		}

		// ---- Taxonomies ----.
		$out[] = new ScopeDefinition(
			id:      'terms',
			label:   __( 'Categories, Tags & Terms', 'smart-search-replace' ),
			group:   $taxonomy_group,
			targets: array(
				new Target(
					table:       $wpdb->terms,
					primary_key: 'term_id',
					columns:     array( 'name' ),
				),
				new Target(
					table:       $wpdb->term_taxonomy,
					primary_key: 'term_taxonomy_id',
					columns:     array( 'description' ),
				),
				new Target(
					table:       $wpdb->termmeta,
					primary_key: 'meta_id',
					columns:     array( 'meta_value' ),
				),
			),
		);

		// ---- Users ----.
		$out[] = new ScopeDefinition(
			id:      'users',
			label:   __( 'User profiles', 'smart-search-replace' ),
			group:   $users_group,
			targets: array(
				new Target(
					table:       $wpdb->users,
					primary_key: 'ID',
					columns:     array( 'display_name', 'user_url', 'user_nicename' ),
				),
				new Target(
					table:       $wpdb->usermeta,
					primary_key: 'umeta_id',
					columns:     array( 'meta_value' ),
				),
			),
		);

		// ---- Options ----.
		$out[] = new ScopeDefinition(
			id:          'options',
			label:       __( 'Site settings (options)', 'smart-search-replace' ),
			group:       $settings_group,
			targets:     array(
				new Target(
					table:       $wpdb->options,
					primary_key: 'option_id',
					columns:     array( 'option_value' ),
				),
			),
			description: __( 'Includes theme mods, widgets and any plugin settings stored in wp_options. Serialized data is handled safely.', 'smart-search-replace' ),
		);

		// ---- WooCommerce (if active) ----.
		if ( $this->isWooActive() ) {
			$out[] = $this->postScope(
				'woo_products',
				__( 'WooCommerce Products', 'smart-search-replace' ),
				'product',
				$ecommerce,
			);

			if ( $this->isHposEnabled() ) {
				$out[] = new ScopeDefinition(
					id:      'woo_orders_hpos',
					label:   __( 'WooCommerce Orders (HPOS)', 'smart-search-replace' ),
					group:   $ecommerce,
					targets: array(
						new Target(
							table:       $wpdb->prefix . 'wc_orders',
							primary_key: 'id',
							columns:     array( 'billing_email', 'customer_note' ),
						),
						new Target(
							table:       $wpdb->prefix . 'wc_orders_meta',
							primary_key: 'id',
							columns:     array( 'meta_value' ),
						),
					),
				);
			} else {
				$out[] = $this->postScope(
					'woo_orders',
					__( 'WooCommerce Orders', 'smart-search-replace' ),
					'shop_order',
					$ecommerce,
				);
			}
		}

		return $out;
	}

	/**
	 * @param string[] $extra_meta_keys
	 */
	private function postScope(
		string $id,
		string $label,
		string $post_type,
		string $group,
		array $extra_meta_keys = array()
	): ScopeDefinition {
		global $wpdb;

		$targets = array(
			new Target(
				table:        $wpdb->posts,
				primary_key:  'ID',
				// guid is stripped again by ReplaceEngine unless the plan
				// sets include_guid.
				columns:      array( 'post_title', 'post_content', 'post_excerpt', 'guid' ),
				where_equals: array( 'post_type' => $post_type ),
			),
			new Target(
				table:       $wpdb->postmeta,
				primary_key: 'meta_id',
				columns:     array( 'meta_value' ),
				join_filter: array(
					'table'       => $wpdb->posts,
					'local_key'   => 'post_id',
					'foreign_key' => 'ID',
					'equals'      => array( 'post_type' => $post_type ),
				),
			),
		);

		if ( ! empty( $extra_meta_keys ) ) {
			// Already covered by the postmeta target above, but we add the
			// constraint as documentation; the column is still meta_value.
			unset( $extra_meta_keys ); // Reserved for future targeted scans.
		}

		return new ScopeDefinition(
			id:      $id,
			label:   $label,
			group:   $group,
			targets: $targets,
		);
	}

	private function isWooActive(): bool {
		return class_exists( 'WooCommerce' );
	}

	private function isHposEnabled(): bool {
		if ( ! class_exists( '\\Automattic\\WooCommerce\\Utilities\\OrderUtil' ) ) {
			return false;
		}
		return (bool) call_user_func( array( '\\Automattic\\WooCommerce\\Utilities\\OrderUtil', 'custom_orders_table_usage_is_enabled' ) );
	}
}
