=== Smart Search Replace ===
Contributors: smartsearchreplace
Tags: search, replace, search and replace, migration, database
Requires at least: 6.4
Tested up to: 6.4
Requires PHP: 8.1
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Human-friendly search & replace with URL protection, smart scopes, dry-run preview and safe serialized-data handling. 100% free.

== Description ==

Smart Search Replace lets you find and replace text across your WordPress site without learning your database schema.

* Pick scopes in human terms: Posts, Pages, Products, Media, Users, Comments, Settings.
* Protect URLs and links from being changed — replace "coating" in body text without touching `example.com/coating-service`.
* Safe serialized data handling (theme mods, widgets, Elementor data, ACF fields).
* Dry-run preview that matches execution 1:1.
* Batched processing for large sites — no timeouts.

No paid tier. No telemetry. Everything is free and open source.

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/smart-search-replace/`.
2. Activate it in **Plugins**.
3. Open **Tools → Smart Search Replace**.

== Frequently Asked Questions ==

= Will this corrupt serialized data? =

No. The engine deserializes, replaces, and reserializes — so byte-length prefixes stay correct.

= How does URL protection work? =

When enabled, the replacer detects URLs, link attributes, and markdown links in each field and skips them while still replacing matches in the surrounding text.

= Can I undo a change? =

Not yet. Take a database backup before running execute. A future version may add an undo log.

== Changelog ==

= 0.1.0 =
* Initial release: smart scopes, URL protection, dry-run/execute parity, safe serialized handling.
