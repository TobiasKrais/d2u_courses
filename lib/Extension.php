<?php

namespace TobiasKrais\D2UCourses;

use rex_be_controller;
use rex_config;
use rex_plugin;
use rex_plugin_manager;

final class Extension
{
    public const STATE_ACTIVE = 'active';
    public const STATE_INACTIVE = 'inactive';

    private const DEFINITIONS = [
        'customer_bookings' => [
            'config' => 'extension_customer_bookings',
            'legacy_plugin' => 'customer_bookings',
            'title' => 'd2u_courses_customer_bookings',
            'pages' => ['d2u_courses/customer_bookings', 'd2u_courses/customer_bookings/customer_bookings', 'd2u_courses/customer_bookings/export'],
            'dependencies' => [],
        ],
        'kufer_sync' => [
            'config' => 'extension_kufer_sync',
            'legacy_plugin' => 'kufer_sync',
            'title' => 'd2u_courses_kufer_sync',
            'pages' => ['d2u_courses/kufer_sync'],
            'dependencies' => [],
        ],
        'locations' => [
            'config' => 'extension_locations',
            'legacy_plugin' => 'locations',
            'title' => 'd2u_courses_locations',
            'pages' => ['d2u_courses/location', 'd2u_courses/location/location', 'd2u_courses/location/category'],
            'dependencies' => [],
        ],
        'schedule_categories' => [
            'config' => 'extension_schedule_categories',
            'legacy_plugin' => 'schedule_categories',
            'title' => 'd2u_courses_schedule_categories',
            'pages' => ['d2u_courses/schedule_categories'],
            'dependencies' => [],
        ],
        'target_groups' => [
            'config' => 'extension_target_groups',
            'legacy_plugin' => 'target_groups',
            'title' => 'd2u_courses_target_groups',
            'pages' => ['d2u_courses/target_groups'],
            'dependencies' => [],
        ],
    ];

    public static function getConfigKey(string $key): string
    {
        return (string) self::requireDefinition($key)['config'];
    }

    /**
     * @return array<int,string>
     */
    public static function getKeys(): array
    {
        return array_keys(self::DEFINITIONS);
    }

    public static function getLegacyPluginName(string $key): ?string
    {
        $legacyPlugin = self::requireDefinition($key)['legacy_plugin'] ?? null;
        if (!is_string($legacyPlugin) || '' === $legacyPlugin) {
            return null;
        }

        return $legacyPlugin;
    }

    public static function isActive(string $key): bool
    {
        $configKey = self::getConfigKey($key);
        if (rex_config::has('d2u_courses', $configKey)) {
            return self::STATE_ACTIVE === (string) rex_config::get('d2u_courses', $configKey);
        }

        return self::isLegacyPluginInstalled($key);
    }

    public static function ensureConfigInitialized(): void
    {
        foreach (array_keys(self::DEFINITIONS) as $key) {
            $configKey = self::getConfigKey($key);
            if (!rex_config::has('d2u_courses', $configKey)) {
                rex_config::set('d2u_courses', $configKey, self::isLegacyPluginInstalled($key) ? self::STATE_ACTIVE : self::STATE_INACTIVE);
            }
        }
    }

    public static function migrateLegacyStates(): void
    {
        self::ensureConfigInitialized();
    }

    public static function hideInactiveBackendPages(): void
    {
        foreach (self::DEFINITIONS as $key => $definition) {
            if (self::isActive($key)) {
                continue;
            }

            foreach ($definition['pages'] as $pageId) {
                $page = rex_be_controller::getPageObject($pageId);
                if (null !== $page) {
                    $page->setHidden(true);
                }
            }
        }
    }

    public static function removeInactivePagesFromNavigation(array $page): array
    {
        $subpages = $page['subpages'] ?? [];
        if (!is_array($subpages)) {
            return $page;
        }

        self::unsetSubpage($subpages, ['location'], self::isActive('locations'));
        self::unsetSubpage($subpages, ['schedule_categories'], self::isActive('schedule_categories'));
        self::unsetSubpage($subpages, ['target_groups'], self::isActive('target_groups'));
        self::unsetSubpage($subpages, ['customer_bookings'], self::isActive('customer_bookings'));
        self::unsetSubpage($subpages, ['kufer_sync'], self::isActive('kufer_sync'));

        $page['subpages'] = $subpages;

        return $page;
    }

    public static function guardLegacyPage(string $key): bool
    {
        if (self::isActive($key)) {
            return true;
        }

        echo \rex_view::warning(sprintf((string) \rex_i18n::msg('d2u_courses_extension_disabled_notice'), \rex_i18n::msg((string) self::requireDefinition($key)['title'])));

        return false;
    }

    public static function getRequestedStatesFromSettings(array $settings): array
    {
        $states = [];
        foreach (array_keys(self::DEFINITIONS) as $key) {
            $states[$key] = self::STATE_ACTIVE === ($settings[self::getConfigKey($key)] ?? self::STATE_INACTIVE);
        }

        return $states;
    }

    public static function getStates(): array
    {
        $states = [];
        foreach (array_keys(self::DEFINITIONS) as $key) {
            $states[$key] = self::isActive($key);
        }

        return $states;
    }

    public static function applyStateChanges(array $previousStates, array $requestedStates): array
    {
        $normalizedStates = self::normalizeStates($requestedStates);
        $activated = [];
        $deactivated = [];

        foreach (array_keys(self::DEFINITIONS) as $key) {
            if (($previousStates[$key] ?? false) || !($normalizedStates[$key] ?? false)) {
                continue;
            }

            self::installLegacyPlugin($key);
            $activated[] = $key;
        }

        foreach (array_reverse(array_keys(self::DEFINITIONS)) as $key) {
            if (!($previousStates[$key] ?? false) || ($normalizedStates[$key] ?? false)) {
                continue;
            }

            $legacyPlugin = self::getLegacyPluginName($key);
            if (null !== $legacyPlugin && rex_plugin::exists('d2u_courses', $legacyPlugin)) {
                $plugin = rex_plugin::get('d2u_courses', $legacyPlugin);
                if ($plugin instanceof rex_plugin && $plugin->isInstalled()) {
                    self::uninstallLegacyPlugin($key);
                } else {
                    self::runLegacyScript($key, 'uninstall.php');
                }
            } else {
                self::runLegacyScript($key, 'uninstall.php');
            }
            $deactivated[] = $key;
        }

        foreach (array_reverse(array_keys(self::DEFINITIONS)) as $key) {
            if ($normalizedStates[$key] ?? false) {
                continue;
            }
            if (in_array($key, $deactivated, true)) {
                continue;
            }

            self::runLegacyScript($key, 'uninstall.php');
        }

        foreach ($normalizedStates as $key => $state) {
            rex_config::set('d2u_courses', self::getConfigKey($key), $state ? self::STATE_ACTIVE : self::STATE_INACTIVE);
        }

        return [
            'activated' => $activated,
            'deactivated' => $deactivated,
            'normalized' => $normalizedStates,
        ];
    }

    public static function installLegacyPlugin(string $key): void
    {
        $legacyPlugin = self::getLegacyPluginName($key);
        if (null === $legacyPlugin || !rex_plugin::exists('d2u_courses', $legacyPlugin)) {
            self::runLegacyScript($key, 'install.php');
            return;
        }

        $plugin = rex_plugin::get('d2u_courses', $legacyPlugin);
        if (!$plugin instanceof rex_plugin) {
            return;
        }

        $manager = rex_plugin_manager::factory($plugin);
        if (!$plugin->isInstalled()) {
            if (!$manager->install()) {
                throw new \RuntimeException($manager->getMessage());
            }

            return;
        }

        if (!$plugin->isAvailable() && !$manager->activate()) {
            throw new \RuntimeException($manager->getMessage());
        }
    }

    public static function uninstallLegacyPlugin(string $key): void
    {
        $legacyPlugin = self::getLegacyPluginName($key);
        if (null === $legacyPlugin || !rex_plugin::exists('d2u_courses', $legacyPlugin)) {
            return;
        }

        $plugin = rex_plugin::get('d2u_courses', $legacyPlugin);
        if (!$plugin instanceof rex_plugin || !$plugin->isInstalled()) {
            return;
        }

        $manager = rex_plugin_manager::factory($plugin);
        if (!$manager->uninstall()) {
            throw new \RuntimeException($manager->getMessage());
        }
    }

    private static function normalizeStates(array $states): array
    {
        foreach (array_keys(self::DEFINITIONS) as $key) {
            $states[$key] = (bool) ($states[$key] ?? false);
        }

        return $states;
    }

    private static function isLegacyPluginInstalled(string $key): bool
    {
        $legacyPlugin = self::getLegacyPluginName($key);
        if (null === $legacyPlugin || !rex_plugin::exists('d2u_courses', $legacyPlugin)) {
            return false;
        }

        $plugin = rex_plugin::get('d2u_courses', $legacyPlugin);

        return $plugin instanceof rex_plugin && $plugin->isAvailable();
    }

    private static function runLegacyScript(string $key, string $script): void
    {
        $scriptPath = dirname(__DIR__) .'/'. $script;
        if (file_exists($scriptPath)) {
            $d2uCoursesAction = $key;
            include $scriptPath;
        }
    }

    private static function requireDefinition(string $key): array
    {
        if (!array_key_exists($key, self::DEFINITIONS)) {
            throw new \InvalidArgumentException('Unknown d2u_courses extension: '. $key);
        }

        return self::DEFINITIONS[$key];
    }

    private static function unsetSubpage(array &$items, array $path, bool $keep): void
    {
        if ($keep || [] === $path) {
            return;
        }

        $key = array_shift($path);
        if (null === $key || !array_key_exists($key, $items)) {
            return;
        }

        if ([] === $path) {
            unset($items[$key]);

            return;
        }

        if (!is_array($items[$key])) {
            return;
        }

        self::unsetSubpage($items[$key], $path, $keep);
    }
}
