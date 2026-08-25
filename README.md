# Smart Search Replace

Free, GPLv2+ WordPress plugin for safe, human-friendly search & replace.
All features free. No telemetry. No upsell.

## What it does (MVP)

- **Smart scope** — pick *Posts*, *Pages*, *Products*, *Media*, *Users*, *Comments*, *Settings*, etc.
  in plain language instead of `wp_postmeta` and `wp_options`.
- **URL protection** — checkbox that prevents the replacer from touching anything inside
  URLs, links, or markdown link targets. Replace `coating → ceramic coating` in body text
  without breaking `example.com/coating-service`.
- **Safe serialized data** — deserialize → replace → reserialize; byte-counts stay correct.
  Handles arrays, `stdClass`, nested JSON in option values, etc.
- **Dry-run with preview** that matches execution 1:1.
- **Batched** keyset-paginated scans; no timeouts on large tables.

## How the scope model works

You select scopes by **what they mean**, not where they live in the schema:

| User picks                | The engine actually scans                                                    |
|---------------------------|------------------------------------------------------------------------------|
| Posts                     | `wp_posts (post_title, post_content, post_excerpt)` where `post_type=post` + matching `wp_postmeta` rows |
| Media                     | `wp_posts` attachments + their `wp_postmeta` (incl. `_wp_attachment_image_alt`) |
| User profiles             | `wp_users (display_name, user_url, user_nicename)` + `wp_usermeta`           |
| Site settings (options)   | `wp_options.option_value` — serialized values handled safely                 |
| WooCommerce Products      | `wp_posts` where `post_type=product` + matching `wp_postmeta`                |
| WooCommerce Orders (HPOS) | `wp_wc_orders` + `wp_wc_orders_meta` if HPOS is enabled, else legacy posts   |

Public CPTs are discovered at runtime — they show up automatically as
"Custom Type: …" entries. The "Advanced" fold-out is the escape hatch for
raw table access; the primary UI never shows raw table names.

The `guid` column is excluded by default per WordPress's own guidance.

## How URL protection works

When **Protect URLs and links** is checked, the engine splits each field into
"safe" and "no-go" segments. Replacements run only on safe segments. No-go
includes:

- Any HTML tag body (`< ... >`) — covers `href`, `src`, `srcset`, etc.
- Scheme-qualified URLs (`https://…`, `mailto:…`, `tel:…`)
- Markdown link/image targets (`](url)`)
- Bare host references with a known TLD (`example.com/coating-service`)
- Bare email addresses

The detector is conservative: when in doubt, mark as no-go. The result is
deterministic interval arithmetic — no fragile regex lookaheads.

## Project layout

```
src/
  Engine/        — Replace plan, string replacer, URL protector, serialized walker, batch runner
  Scope/         — Definitions, registry, discoverer (CPT/Woo/HPOS), resolver
  Admin/         — Admin page, asset enqueue
  Ajax/          — Preview & Execute controllers, scope listing
  Security/      — Capability + nonce + plan factory
  Persistence/   — Run log (local only)
tests/Unit/      — Engine tests (URL, serialized, regex, dry-run/execute parity)
views/           — Admin templates
```

## Development

```bash
composer install
composer test      # phpunit
composer phpcs     # WordPress coding standards (security + i18n + DB sniffs)
```

All 25 unit tests pass. PHPCS reports 0 errors and 0 warnings on the
focused (security/i18n/DB) ruleset.

## What's deliberately NOT in MVP

- Regex preset library (basic regex mode is in; presets in next round)
- Rich visual diff renderer
- Undo / restore from log
- Multisite-wide runs
- WP-CLI command
- Scheduled / cron runs

These are tracked for follow-up. No pro tier. Everything stays free.
