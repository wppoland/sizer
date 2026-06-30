<?php
/**
 * The accessible native <dialog> modal containing the size chart.
 *
 * @var string                                                                              $sizer_dialog_id Dialog element id.
 * @var string                                                                              $sizer_title     Modal heading.
 * @var array{id: string, name: string, caption: string, columns: list<string>, rows: list<list<string>>} $sizer_chart Chart data.
 *
 * @package Sizer/Templates
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

$sizer_dialog_id = isset($sizer_dialog_id) ? (string) $sizer_dialog_id : '';
$sizer_title     = isset($sizer_title) ? (string) $sizer_title : '';
$sizer_chart     = isset($sizer_chart) && is_array($sizer_chart) ? $sizer_chart : [];

if ('' === $sizer_dialog_id) {
    return;
}

$sizer_heading_id = $sizer_dialog_id . '-title';
?>
<dialog
    id="<?php echo esc_attr($sizer_dialog_id); ?>"
    class="sizer-dialog"
    aria-labelledby="<?php echo esc_attr($sizer_heading_id); ?>"
>
    <div class="sizer-dialog__inner">
        <div class="sizer-dialog__header">
            <h2 id="<?php echo esc_attr($sizer_heading_id); ?>" class="sizer-dialog__title">
                <?php echo esc_html($sizer_title); ?>
            </h2>
            <button type="button" class="sizer-dialog__close" data-sizer-close aria-label="<?php esc_attr_e('Close size guide', 'plogins-sizer'); ?>">
                <svg width="20" height="20" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                    <path fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" d="M6 6l12 12M18 6L6 18" />
                </svg>
            </button>
        </div>
        <div class="sizer-dialog__body">
            <?php
            \Sizer\Plugin::instance()
                ->container()
                ->get(\Sizer\Util\TemplateLoader::class)
                ->render('single-product/chart-table', ['chart' => $sizer_chart]);
            ?>
        </div>
    </div>
</dialog>
