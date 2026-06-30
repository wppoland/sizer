<?php

declare(strict_types=1);

namespace Sizer\Service;

defined('ABSPATH') || exit;

/**
 * Typed accessor over the `sizer_settings` option. Falls back to bundled
 * defaults so callers never have to guard against missing keys.
 */
final class Settings
{
    public const OPTION = 'sizer_settings';

    /** @var array<string, mixed>|null */
    private ?array $cache = null;

    /**
     * All settings, merged over defaults.
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        if (null !== $this->cache) {
            return $this->cache;
        }

        /** @var array<string, mixed> $defaults */
        $defaults = require \Sizer\PLUGIN_DIR . '/config/defaults.php';

        $stored = get_option(self::OPTION, []);
        if (! is_array($stored)) {
            $stored = [];
        }

        return $this->cache = array_merge($defaults, $stored);
    }

    public function triggerLabel(): string
    {
        $label = trim((string) ($this->all()['trigger_label'] ?? ''));

        return '' !== $label ? $label : __('Size guide', 'plogins-sizer');
    }

    public function modalTitle(): string
    {
        $title = trim((string) ($this->all()['modal_title'] ?? ''));

        return '' !== $title ? $title : $this->triggerLabel();
    }
}
