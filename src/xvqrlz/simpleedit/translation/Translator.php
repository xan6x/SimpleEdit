<?php

declare(strict_types=1);

namespace xvqrlz\simpleedit\translation;

use pocketmine\command\CommandSender;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\Server;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;

final class Translator{
    public const DEFAULT_LOCALE = self::ENGLISH;
    public const ENGLISH = 'en_US';
    public const RUSSIAN = 'ru_RU';

    public const LOCALES = [
        self::ENGLISH,
        self::RUSSIAN,
    ];

    /** @var array<string, array<string, string>> */
    private static array $translations = [];

    public static function initialize(PluginBase $plugin): void
    {
        foreach (self::LOCALES as $locale) {
            $path = 'data/locale/' . $locale . '.ini';
            $plugin->saveResource($path, true);
            self::$translations[$locale] = \array_map(
                '\\stripcslashes',
                \parse_ini_file($plugin->getDataFolder() . $path, false, \INI_SCANNER_RAW)
            );
        }
    }

    public static function translate(
        string $key,
        $locale = self::DEFAULT_LOCALE,
        array $args = []
    ): string {
        if ($locale instanceof Player) {
            $locale = $locale->getLanguageCode();
        } elseif ($locale instanceof CommandSender) {
            $locale = $locale instanceof ConsoleCommandSender
                ? self::DEFAULT_LOCALE
                : $locale->getLanguageCode();
        } elseif (!\is_string($locale)) {
            $locale = self::DEFAULT_LOCALE;
        }

        if (!isset(self::$translations[$locale])) {
            $locale = self::DEFAULT_LOCALE;
        }

        $translation = self::$translations[$locale][$key] ?? self::$translations[self::DEFAULT_LOCALE][$key] ?? $key;

        return empty($args) ? $translation : \sprintf($translation, ...$args);
    }
}