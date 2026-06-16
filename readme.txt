=== Sizer - Size Guide and Charts for WooCommerce ===
Contributors: wppoland
Tags: woocommerce, size guide, size chart, product, fashion
Requires at least: 6.5
Tested up to: 7.0
Requires PHP: 8.1
Stable tag: 0.1.0
Requires Plugins: woocommerce
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Add size guides and size charts to your WooCommerce products via an accessible modal.

== Description ==

Sizer adds a "Size guide" button to your WooCommerce product pages. Shoppers click it and a size chart opens in a modal, so they can check measurements without leaving the product.

You build each chart once in the admin (a labelled table of columns and rows, plus an optional caption) and assign it to whichever products it applies to. The button is injected right after the add-to-cart button. If a product has no chart assigned, nothing is added to the page.

Source and bug reports live on GitHub: https://github.com/wppoland/sizer

**What it does**

* Build size charts as labelled tables and reuse the same chart across many products.
* Pick a chart per product from the Product data → Size guide tab.
* Opens in a native `<dialog>` element with a labelled heading, a close button, and keyboard support.
* Set the button text and the modal heading from one settings screen.
* Stylesheet uses CSS custom properties (accent colour, radius, dialog colours) and includes a dark-scheme and reduced-motion variant.
* No external requests and no tracking; charts are stored in your own database.

== Installation ==

1. Upload the plugin to `/wp-content/plugins/sizer`, or install via Plugins → Add New.
2. Activate it. WooCommerce must be active.
3. Go to WooCommerce → Size Guides to create a chart and set the button label.
4. Assign a chart on a product (Product data → Size guide).

== Frequently Asked Questions ==

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

== Changelog ==

= 0.1.0 =
* Initial release: reusable size charts, per-product assignment, and an accessible modal shown after the add-to-cart button.
