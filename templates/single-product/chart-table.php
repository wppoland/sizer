<?php
/**
 * Renders a single size chart as an accessible table. Shared by the modal,
 * the tab, and any future render paths.
 *
 * @var array{id: string, name: string, caption: string, columns: list<string>, rows: list<list<string>>} $sizer_chart
 *
 * @package Sizer/Templates
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

$sizer_chart   = isset($sizer_chart) && is_array($sizer_chart) ? $sizer_chart : [];
$sizer_columns = isset($sizer_chart['columns']) && is_array($sizer_chart['columns']) ? $sizer_chart['columns'] : [];
$sizer_rows    = isset($sizer_chart['rows']) && is_array($sizer_chart['rows']) ? $sizer_chart['rows'] : [];
$sizer_caption = isset($sizer_chart['caption']) ? (string) $sizer_chart['caption'] : '';
$sizer_name    = isset($sizer_chart['name']) ? (string) $sizer_chart['name'] : '';

if (empty($sizer_rows) || empty($sizer_columns)) :
    ?>
    <p class="sizer-chart__empty"><?php esc_html_e('No size information is available yet.', 'sizer'); ?></p>
    <?php
    return;
endif;
?>
<div class="sizer-chart__scroll">
    <table class="sizer-chart__table">
        <caption class="screen-reader-text">
            <?php echo '' !== $sizer_name ? esc_html($sizer_name) : esc_html__('Size chart', 'sizer'); ?>
        </caption>
        <thead>
            <tr>
                <?php foreach ($sizer_columns as $sizer_col) : ?>
                    <th scope="col"><?php echo esc_html((string) $sizer_col); ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($sizer_rows as $sizer_row) : ?>
                <tr tabindex="0">
                    <?php foreach ($sizer_columns as $sizer_c => $sizer_col) : ?>
                        <?php $sizer_cell = is_array($sizer_row) && isset($sizer_row[$sizer_c]) ? (string) $sizer_row[$sizer_c] : ''; ?>
                        <?php if (0 === $sizer_c) : ?>
                            <th scope="row"><?php echo esc_html($sizer_cell); ?></th>
                        <?php else : ?>
                            <td><?php echo esc_html($sizer_cell); ?></td>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php if ('' !== $sizer_caption) : ?>
    <p class="sizer-chart__caption"><?php echo esc_html($sizer_caption); ?></p>
<?php endif; ?>
