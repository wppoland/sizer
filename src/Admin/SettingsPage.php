<?php

declare(strict_types=1);

namespace Sizer\Admin;

defined('ABSPATH') || exit;

use Sizer\Contract\HasHooks;
use Sizer\Plugin;
use Sizer\Repository\ChartRepository;
use Sizer\Service\Settings;

/**
 * WooCommerce submenu admin: a two-tab page for global display settings and the
 * reusable size-chart manager. Settings use the Settings API; charts use a
 * nonce-protected custom form posting to admin-post.
 *
 * @package Sizer\Admin
 */
final class SettingsPage implements HasHooks
{
    private const PAGE         = 'sizer';
    private const SETTINGS_GRP = 'sizer_settings_group';
    private const SECTION      = 'sizer_display';
    private const SAVE_ACTION  = 'sizer_save_charts';

    public function __construct(
        private readonly Settings $settings,
        private readonly ChartRepository $charts,
    ) {
    }

    public function registerHooks(): void
    {
        add_action('admin_menu', [$this, 'addMenu']);
        add_action('admin_init', [$this, 'registerSettings']);
        add_action('admin_post_' . self::SAVE_ACTION, [$this, 'handleChartSave']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    public function addMenu(): void
    {
        add_submenu_page(
            'woocommerce',
            __('Sizer: Size Guides', 'plogins-sizer'),
            __('Size Guides', 'plogins-sizer'),
            'manage_woocommerce',
            self::PAGE,
            [$this, 'render'],
        );
    }

    public function enqueueAssets(string $hook): void
    {
        if (! str_contains($hook, self::PAGE)) {
            return;
        }

        wp_enqueue_style(
            'sizer-admin',
            Plugin::instance()->url('assets/css/admin.css'),
            [],
            \Sizer\VERSION,
        );

        wp_enqueue_script(
            'sizer-admin',
            Plugin::instance()->url('assets/js/admin.js'),
            [],
            \Sizer\VERSION,
            ['in_footer' => true, 'strategy' => 'defer'],
        );

        wp_localize_script('sizer-admin', 'sizerAdmin', [
            'i18n' => [
                'confirmDelete' => __('Delete this size chart? This cannot be undone.', 'plogins-sizer'),
                'columnLabel'   => __('Column heading', 'plogins-sizer'),
                'removeColumn'  => __('Remove column', 'plogins-sizer'),
                'removeRow'     => __('Remove row', 'plogins-sizer'),
                'cell'          => __('Cell value', 'plogins-sizer'),
            ],
        ]);
    }

    // ------------------------------------------------------------------ Settings tab.

    public function registerSettings(): void
    {
        register_setting(
            self::SETTINGS_GRP,
            Settings::OPTION,
            [
                'type'              => 'array',
                'sanitize_callback' => [$this, 'sanitizeSettings'],
                'default'           => [],
            ],
        );

        add_settings_section(
            self::SECTION,
            __('How the size guide appears', 'plogins-sizer'),
            static function (): void {
                echo '<p class="sizer-section-lead">' . esc_html__(
                    'On any product with a chart assigned, shoppers see a “Size guide” link just below the add-to-cart button. Selecting it opens an accessible pop-up that shows the chart. These two settings control the wording shoppers see, leave them as they are and the guide works out of the box.',
                    'plogins-sizer',
                ) . '</p>';
            },
            self::PAGE,
        );

        add_settings_field('trigger_label', __('Link wording', 'plogins-sizer'), [$this, 'fieldTriggerLabel'], self::PAGE, self::SECTION);
        add_settings_field('modal_title', __('Pop-up heading', 'plogins-sizer'), [$this, 'fieldModalTitle'], self::PAGE, self::SECTION);
    }

    public function fieldTriggerLabel(): void
    {
        printf(
            '<input type="text" class="regular-text" name="%1$s[trigger_label]" value="%2$s" placeholder="%3$s" />',
            esc_attr(Settings::OPTION),
            esc_attr($this->settings->triggerLabel()),
            esc_attr__('Size guide', 'plogins-sizer'),
        );
        echo '<p class="description">' . esc_html__(
            'The clickable text shown on the product page that opens the chart. Keep it short, “Size guide”, “Size chart” or “Find my fit” all read well next to the price.',
            'plogins-sizer',
        ) . '</p>';
        $this->renderTriggerPreview();
    }

    public function fieldModalTitle(): void
    {
        printf(
            '<input type="text" class="regular-text" name="%1$s[modal_title]" value="%2$s" placeholder="%3$s" />',
            esc_attr(Settings::OPTION),
            esc_attr($this->settings->modalTitle()),
            esc_attr__('Size guide', 'plogins-sizer'),
        );
        echo '<p class="description">' . esc_html__(
            'The title shown at the top of the pop-up once it opens. Leave it blank to reuse the link wording above.',
            'plogins-sizer',
        ) . '</p>';
    }

    /**
     * Tiny inline preview of the storefront trigger so the wording change is
     * visible without leaving the settings page. Presentation only.
     */
    private function renderTriggerPreview(): void
    {
        echo '<p class="sizer-preview" aria-hidden="true">';
        echo '<span class="sizer-preview-label">' . esc_html__('Preview', 'plogins-sizer') . '</span>';
        echo '<span class="sizer-preview-trigger">' . esc_html($this->settings->triggerLabel()) . '</span>';
        echo '</p>';
    }

    /**
     * Sanitise the display settings.
     *
     * @param mixed $raw Raw POST value.
     * @return array<string, mixed>
     */
    public function sanitizeSettings(mixed $raw): array
    {
        if (! is_array($raw)) {
            return [];
        }

        return [
            'trigger_label' => sanitize_text_field((string) ($raw['trigger_label'] ?? '')),
            'modal_title'   => sanitize_text_field((string) ($raw['modal_title'] ?? '')),
        ];
    }

    // ------------------------------------------------------------------ Charts tab.

    /**
     * Persist the chart manager form (nonce + capability protected).
     */
    public function handleChartSave(): void
    {
        if (! current_user_can('manage_woocommerce')) {
            wp_die(esc_html__('You are not allowed to manage size charts.', 'plogins-sizer'));
        }

        check_admin_referer(self::SAVE_ACTION);

        $charts = [];
        // The whole structure is deep-sanitised here (every scalar through
        // sanitize_text_field) and again, field by field, in Repository::normalise().
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified above via check_admin_referer().
        $raw = isset($_POST['charts']) && is_array($_POST['charts'])
            ? map_deep(wp_unslash($_POST['charts']), 'sanitize_text_field')
            : [];

        if (is_array($raw)) {
            foreach ($raw as $chart) {
                if (! is_array($chart)) {
                    continue;
                }
                // Repository::normalise() sanitises every field.
                $charts[] = $this->charts->normalise($chart);
            }
        }

        $this->charts->save($charts);

        wp_safe_redirect(
            add_query_arg(
                ['page' => self::PAGE, 'tab' => 'charts', 'updated' => '1'],
                admin_url('admin.php'),
            ),
        );
        exit;
    }

    public function render(): void
    {
        if (! current_user_can('manage_woocommerce')) {
            return;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only tab switch.
        $tab = isset($_GET['tab']) ? sanitize_key((string) wp_unslash($_GET['tab'])) : 'settings';
        if (! in_array($tab, ['settings', 'charts'], true)) {
            $tab = 'settings';
        }

        echo '<div class="wrap sizer-admin">';
        echo '<h1>' . esc_html(get_admin_page_title()) . '</h1>';

        $base = admin_url('admin.php?page=' . self::PAGE);
        echo '<nav class="nav-tab-wrapper sizer-tabs">';
        printf(
            '<a href="%1$s" class="nav-tab %2$s">%3$s</a>',
            esc_url($base . '&tab=settings'),
            'settings' === $tab ? 'nav-tab-active' : '',
            esc_html__('Settings', 'plogins-sizer'),
        );
        printf(
            '<a href="%1$s" class="nav-tab %2$s">%3$s</a>',
            esc_url($base . '&tab=charts'),
            'charts' === $tab ? 'nav-tab-active' : '',
            esc_html__('Size charts', 'plogins-sizer'),
        );
        echo '</nav>';

        if ('charts' === $tab) {
            $this->renderChartsTab();
        } else {
            $this->renderSettingsTab();
        }

        echo '</div>';
    }

    private function renderSettingsTab(): void
    {
        echo '<form method="post" action="options.php" class="sizer-settings-form">';
        echo '<div class="sizer-panel">';
        settings_fields(self::SETTINGS_GRP);
        do_settings_sections(self::PAGE);
        echo '</div>';
        submit_button();
        echo '</form>';
    }

    private function renderChartsTab(): void
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only success flag.
        if (isset($_GET['updated'])) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Size charts saved.', 'plogins-sizer') . '</p></div>';
        }

        $charts = array_values($this->charts->all());

        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" id="sizer-charts-form">';
        echo '<input type="hidden" name="action" value="' . esc_attr(self::SAVE_ACTION) . '" />';
        wp_nonce_field(self::SAVE_ACTION);

        echo '<p class="description sizer-charts-intro">' . esc_html__(
            'Build reusable charts here, then assign them to a product (Product data → Size guide). Each chart is a simple labelled table.',
            'plogins-sizer',
        ) . '</p>';

        echo '<div id="sizer-charts" class="sizer-charts">';

        if (empty($charts)) {
            echo '<p class="sizer-empty" id="sizer-charts-empty">' . esc_html__(
                'No size charts yet. Add your first one below.',
                'plogins-sizer',
            ) . '</p>';
        }

        foreach ($charts as $index => $chart) {
            $this->renderChartEditor((int) $index, $chart);
        }

        echo '</div>';

        echo '<p class="sizer-charts-actions">';
        echo '<button type="button" class="button button-secondary" id="sizer-add-chart">' . esc_html__('+ Add size chart', 'plogins-sizer') . '</button>';
        echo '</p>';

        // Hidden template for JS-cloned new charts (index __i__).
        echo '<template id="sizer-chart-template">';
        $this->renderChartEditor(0, ['id' => '', 'name' => '', 'caption' => '', 'columns' => ['', ''], 'rows' => [['', '']]], '__i__');
        echo '</template>';

        submit_button(__('Save charts', 'plogins-sizer'));
        echo '</form>';
    }

    /**
     * Render a single chart editor card.
     *
     * @param int                                                                              $index Numeric index, or token base.
     * @param array{id: string, name: string, caption: string, columns: list<string>, rows: list<list<string>>} $chart Chart data.
     * @param string|null                                                                      $token Optional placeholder token overriding $index in field names.
     */
    private function renderChartEditor(int $index, array $chart, ?string $token = null): void
    {
        $key     = $token ?? (string) $index;
        $name    = 'charts[' . $key . ']';
        $columns = ! empty($chart['columns']) ? $chart['columns'] : ['', ''];
        $rows    = ! empty($chart['rows']) ? $chart['rows'] : [['', '']];
        $col_n   = count($columns);

        echo '<fieldset class="sizer-chart-card" data-chart>';
        echo '<legend class="screen-reader-text">' . esc_html__('Size chart', 'plogins-sizer') . '</legend>';

        echo '<div class="sizer-chart-head">';
        printf(
            '<input type="hidden" name="%1$s[id]" value="%2$s" />',
            esc_attr($name),
            esc_attr($chart['id']),
        );
        printf(
            '<label class="sizer-field"><span>%1$s</span><input type="text" name="%2$s[name]" value="%3$s" class="regular-text" required placeholder="%4$s" /></label>',
            esc_html__('Chart name', 'plogins-sizer'),
            esc_attr($name),
            esc_attr($chart['name']),
            esc_attr__("e.g. Men's T-Shirts", 'plogins-sizer'),
        );
        printf(
            '<label class="sizer-field"><span>%1$s</span><input type="text" name="%2$s[caption]" value="%3$s" class="regular-text" placeholder="%4$s" /></label>',
            esc_html__('Caption (optional)', 'plogins-sizer'),
            esc_attr($name),
            esc_attr($chart['caption']),
            esc_attr__('e.g. All measurements in cm', 'plogins-sizer'),
        );
        echo '<button type="button" class="button-link sizer-delete-chart" data-confirm aria-label="' . esc_attr__('Delete chart', 'plogins-sizer') . '">' . esc_html__('Delete', 'plogins-sizer') . '</button>';
        echo '</div>';

        echo '<div class="sizer-table-wrap">';
        echo '<table class="sizer-chart-table widefat" data-table>';

        // Column headers row.
        echo '<thead><tr>';
        foreach ($columns as $c => $col) {
            printf(
                '<th data-col><input type="text" name="%1$s[columns][%2$d]" value="%3$s" aria-label="%4$s" placeholder="%5$s" /><button type="button" class="sizer-remove-col" aria-label="%6$s">&times;</button></th>',
                esc_attr($name),
                (int) $c,
                esc_attr((string) $col),
                esc_attr__('Column heading', 'plogins-sizer'),
                esc_attr__('Size', 'plogins-sizer'),
                esc_attr__('Remove column', 'plogins-sizer'),
            );
        }
        echo '<th class="sizer-col-add"><button type="button" class="button sizer-add-col" aria-label="' . esc_attr__('Add column', 'plogins-sizer') . '">+</button></th>';
        echo '</tr></thead>';

        echo '<tbody data-rows>';
        foreach ($rows as $r => $row) {
            echo '<tr data-row>';
            for ($c = 0; $c < $col_n; $c++) {
                $cell = isset($row[$c]) ? (string) $row[$c] : '';
                printf(
                    '<td><input type="text" name="%1$s[rows][%2$d][%3$d]" value="%4$s" aria-label="%5$s" /></td>',
                    esc_attr($name),
                    (int) $r,
                    (int) $c,
                    esc_attr($cell),
                    esc_attr__('Cell value', 'plogins-sizer'),
                );
            }
            echo '<td class="sizer-row-remove"><button type="button" class="button-link sizer-remove-row" aria-label="' . esc_attr__('Remove row', 'plogins-sizer') . '">&times;</button></td>';
            echo '</tr>';
        }
        echo '</tbody>';
        echo '</table>';
        echo '</div>';

        echo '<p><button type="button" class="button sizer-add-row">' . esc_html__('+ Add row', 'plogins-sizer') . '</button></p>';

        echo '</fieldset>';
    }
}
