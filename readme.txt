=== Plogins Sizer - Size Guide for WooCommerce ===
Contributors: motylanogha
Tags: woocommerce, size guide, size chart, product, fashion
Requires at least: 6.5
Tested up to: 7.0
Requires PHP: 8.1
Stable tag: 0.1.3
Requires Plugins: woocommerce
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Add size guides and size charts to your WooCommerce products via an accessible modal.

== Description ==

Sizer adds a "Size guide" button to your WooCommerce product pages. Shoppers click it and a size chart opens in a modal, so they can check measurements without leaving the product.

You build each chart once in the admin (a labelled table of columns and rows, plus an optional caption) and assign it to whichever products it applies to. The button is injected right after the add-to-cart button. If a product has no chart assigned, nothing is added to the page.

Source and bug reports live on GitHub: https://github.com/wppoland/plogins-sizer

**What it does**

* Build size charts as labelled tables and reuse the same chart across many products.
* Pick a chart per product from the Product data → Size guide tab.
* Opens in a native `<dialog>` element with a labelled heading, a close button, and keyboard support.
* Set the button text and the modal heading from one settings screen.
* Stylesheet uses CSS custom properties (accent colour, radius, dialog colours) and includes a dark-scheme and reduced-motion variant.
* No external requests and no tracking; charts are stored in your own database.

== Installation ==

1. Upload the plugin to `/wp-content/plugins/plogins-sizer`, or install via Plugins → Add New.
2. Activate it. WooCommerce must be active.
3. Go to WooCommerce → Size Guides to create a chart and set the button label.
4. Assign a chart on a product (Product data → Size guide).

== Frequently Asked Questions ==

= Documentation and links =

* **Documentation** - https://plogins.com/plogins-sizer/docs/
* **Plugin page** - https://plogins.com/plogins-sizer/
* **Source code** - https://github.com/wppoland/plogins-sizer
* **Bug reports and feature requests** - https://github.com/wppoland/plogins-sizer/issues


= Does it require WooCommerce? =

Yes. Sizer extends WooCommerce single product pages.

= Where does the size guide appear? =

On the single product page, as a button shown after the add-to-cart button. The button opens the chart in an accessible modal.

= Can I override the styling? =

Yes. Templates can be overridden from your theme under a `sizer/` folder, and the storefront CSS exposes custom properties you can re-theme.

= Is the size-guide modal accessible? =

Yes. It uses a native `<dialog>` with a labelled heading, close button, keyboard support and respects `prefers-reduced-motion`.

= Can one chart apply to many products? =

Yes. Build a chart once under WooCommerce → Size Guides, then assign it on each product's Size guide tab.

== Screenshots ==

1. The size guide modal on a product page.
2. Building a reusable size chart in the admin.

== External Services ==

Sizer does not connect to any external services. It makes no API calls and loads no remote scripts, fonts, or stylesheets. Your size charts and button/heading settings are stored in your own WordPress database (the `sizer_charts` and `sizer_settings` options), and each product's assigned chart is kept in that product's `_sizer_chart_id` post meta. No data leaves your site, and nothing is tracked.

== Changelog ==

= 0.1.3 =
* Renamed to Plogins Sizer for WooCommerce for a more distinctive plugin name.

= 0.1.2 =
* `sizer/match_size` filter and `SizeMatcher` service for matching shopper measurements to chart rows.
* `sizer/chart` filter on resolved chart data before render.

= 0.1.1 =
* `sizer/chart_units` filter and `sizer/chart_controls` action for PRO unit switching on rendered charts.

= 0.1.0 =
* Initial release: reusable size charts, per-product assignment, and an accessible modal shown after the add-to-cart button.
