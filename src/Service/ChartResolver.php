<?php

declare(strict_types=1);

namespace Sizer\Service;

defined('ABSPATH') || exit;

use Sizer\Repository\ChartRepository;

/**
 * Resolves which size chart applies to a given product, based on the explicit
 * per-product assignment stored in product meta.
 */
final class ChartResolver
{
    public const PRODUCT_META = '_sizer_chart_id';

    public function __construct(
        private readonly ChartRepository $charts,
    ) {
    }

    /**
     * Resolve the applicable chart for a product, or null when none is assigned.
     *
     * The resolved chart id is passed through the `sizer/resolved_chart_id`
     * filter so add-ons can supply a chart when no explicit per-product
     * assignment exists (e.g. a store-wide default), or override the one found.
     *
     * @return array{id: string, name: string, caption: string, columns: list<string>, rows: list<list<string>>}|null
     */
    public function forProduct(\WC_Product $product): ?array
    {
        $chart_id = (string) get_post_meta($product->get_id(), self::PRODUCT_META, true);

        /**
         * Filters the resolved size-chart id for a product.
         *
         * Fires for every product, even when no chart is assigned (the id is an
         * empty string in that case), so add-ons can provide a fallback chart.
         *
         * @param string      $chart_id Resolved chart id, or '' when none assigned.
         * @param \WC_Product $product  Product the chart is being resolved for.
         */
        $chart_id = (string) apply_filters('sizer/resolved_chart_id', $chart_id, $product);

        if ('' === $chart_id) {
            return null;
        }

        return $this->charts->find($chart_id);
    }
}
