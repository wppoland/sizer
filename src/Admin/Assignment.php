<?php

declare(strict_types=1);

namespace Sizer\Admin;

defined('ABSPATH') || exit;

use Sizer\Contract\HasHooks;
use Sizer\Repository\ChartRepository;
use Sizer\Service\ChartResolver;

/**
 * Admin assignment UI: a per-product select in the Product data → Size guide
 * panel that picks which reusable chart shows on the product page.
 *
 * @package Sizer\Admin
 */
final class Assignment implements HasHooks
{
    private const NONCE = 'sizer_product_chart';

    public function __construct(
        private readonly ChartRepository $charts,
    ) {
    }

    public function registerHooks(): void
    {
        add_filter('woocommerce_product_data_tabs', [$this, 'addProductTab']);
        add_action('woocommerce_product_data_panels', [$this, 'renderProductPanel']);
        add_action('woocommerce_process_product_meta', [$this, 'saveProductMeta']);
    }

    /**
     * @param array<string, array<string, mixed>> $tabs Existing product data tabs.
     * @return array<string, array<string, mixed>>
     */
    public function addProductTab(array $tabs): array
    {
        $tabs['sizer'] = [
            'label'    => __('Size guide', 'plogins-sizer'),
            'target'   => 'sizer_product_data',
            'class'    => [],
            'priority' => 65,
        ];

        return $tabs;
    }

    public function renderProductPanel(): void
    {
        global $post;

        $current = $post instanceof \WP_Post
            ? (string) get_post_meta($post->ID, ChartResolver::PRODUCT_META, true)
            : '';

        echo '<div id="sizer_product_data" class="panel woocommerce_options_panel">';
        wp_nonce_field(self::NONCE, self::NONCE);

        echo '<div class="options_group">';
        $this->selectField(
            ChartResolver::PRODUCT_META,
            __('Size chart', 'plogins-sizer'),
            $current,
            __(', No chart, ', 'plogins-sizer'),
            __('Choose a chart to show on this product, or leave it blank to hide the size guide here.', 'plogins-sizer'),
        );
        echo '</div>';
        echo '</div>';
    }

    public function saveProductMeta(int $post_id): void
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified on next line.
        $nonce = isset($_POST[self::NONCE]) ? sanitize_text_field(wp_unslash((string) $_POST[self::NONCE])) : '';
        if ('' === $nonce || ! wp_verify_nonce($nonce, self::NONCE)) {
            return;
        }

        if (! current_user_can('edit_product', $post_id)) {
            return;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified above.
        $value = isset($_POST[ChartResolver::PRODUCT_META])
            ? sanitize_key((string) wp_unslash($_POST[ChartResolver::PRODUCT_META]))
            : '';

        if ('' === $value) {
            delete_post_meta($post_id, ChartResolver::PRODUCT_META);
            return;
        }

        update_post_meta($post_id, ChartResolver::PRODUCT_META, $value);
    }

    /**
     * Render a WooCommerce-styled chart select inside the product panel.
     */
    private function selectField(string $id, string $label, string $current, string $blankLabel, string $description): void
    {
        $options = ['' => $blankLabel];
        foreach ($this->charts->choices() as $value => $name) {
            $options[$value] = $name;
        }

        echo '<p class="form-field">';
        printf('<label for="%1$s">%2$s</label>', esc_attr($id), esc_html($label));
        printf('<select id="%1$s" name="%1$s" class="select short">', esc_attr($id));
        foreach ($options as $value => $name) {
            printf(
                '<option value="%1$s" %2$s>%3$s</option>',
                esc_attr((string) $value),
                selected($current, (string) $value, false),
                esc_html((string) $name),
            );
        }
        echo '</select>';
        printf('<span class="description">%s</span>', esc_html($description));
        echo '</p>';
    }
}
