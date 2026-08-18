=== ART Portfolio ===
Contributors: artbashlykov
Tags: portfolio, gallery, live preview, iframe, gutenberg
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.3.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Display a portfolio grid with live iframe previews of pages from the same WordPress site.

== Description ==

ART Portfolio lets you create portfolio items and show them as a responsive card grid. Each card can link to a page, post, or other public content on the same site and reveal a live interactive preview in an iframe on hover (or first tap on mobile).

Main features:

* Custom post type for portfolio works and collections
* Featured image, badge, excerpt, project parameters, and a block-level button
* Gutenberg block and `[art_portfolio]` shortcode sharing one renderer
* Optional collection filters, pagination, and color settings on the gallery block
* Lazy live preview: iframes are created only after interaction
* Preview mode query flag that does not change the original page

== Installation ==

1. Upload the `art-portfolio` folder to `/wp-content/plugins/` or install the plugin through the WordPress admin.
2. Activate the plugin on the Plugins screen.
3. Add works under **ART Portfolio**, then insert the **ART Portfolio: Gallery** block or the `[art_portfolio]` shortcode.

== Frequently Asked Questions ==

= Does live preview work for external sites? =

Same-site pages are the supported scenario. External URLs can be saved, but browsers may block them in an iframe because of CSP or X-Frame-Options.

= Are iframes loaded on the first page view? =

No. An iframe is created only after the visitor interacts with that specific card.

== Changelog ==

= 1.3.0 =
* Restored the card cover image picker inside work settings.
* Fixed shortcode output in Gutenberg, widgets, and the `[art-portfolio]` alias.
* Made the admin shortcode copy button work without the Clipboard API.
* Aligned pagination controls and stopped appending `#art-portfolio` to gallery links.

= 1.2.0 =
* Replaced the works limit with pagination. Default is 10 cards per page.
* Added an admin Shortcode screen with insertion rules and copyable examples.
* Keep Cyrillic page slugs readable when selecting and saving a Live Preview URL.

= 1.1.0 =
* Added collections, gallery filters, block color settings, and newest-first ordering.
* Moved the excerpt into work settings and kept the button label on the block.

= 1.0.0 =
* Initial release: portfolio CPT, Gutenberg block, shortcode, and lazy live preview.
