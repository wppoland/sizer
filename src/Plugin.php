<?php

declare(strict_types=1);

namespace Sizer;

use Sizer\Contract\HasHooks;

defined('ABSPATH') || exit;

final class Plugin
{
    private static ?self $instance = null;

    private Container $container;

    private bool $booted = false;

    private function __construct()
    {
        $this->container = new Container();
        (require __DIR__ . '/../config/services.php')($this->container);
    }

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    public function container(): Container
    {
        return $this->container;
    }

    /**
     * Absolute filesystem path inside the plugin directory.
     */
    public function path(string $relative = ''): string
    {
        return PLUGIN_DIR . ('' !== $relative ? '/' . ltrim($relative, '/') : '');
    }

    /**
     * URL inside the plugin directory.
     */
    public function url(string $relative = ''): string
    {
        return plugins_url($relative, PLUGIN_FILE);
    }

    public function boot(): void
    {
        if ($this->booted) {
            return;
        }
        $this->booted = true;

        $this->container->get(Migrator::class)->maybeMigrate();

        /** @var array<class-string<HasHooks>> $hooks */
        $hooks = require __DIR__ . '/../config/hooks.php';
        foreach ($hooks as $id) {
            // Admin-only services are not registered outside wp-admin; skip them
            // gracefully rather than resolving an unbound id.
            if (! $this->container->has($id)) {
                continue;
            }
            $service = $this->container->get($id);
            if ($service instanceof HasHooks) {
                $service->registerHooks();
            }
        }

        /**
         * Fires after the plugin has fully booted.
         *
         * @param Plugin $plugin The booted plugin instance.
         */
        do_action('sizer/booted', $this);
    }
}
