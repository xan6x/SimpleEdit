<?php

declare(strict_types=1);

namespace xvqrlz\simpleedit\translation;

use pocketmine\command\CommandSender;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

final class Translator {
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
        $localeFolder = $plugin->getDataFolder() . 'locale/';

        if (!is_dir($localeFolder)) {
            @mkdir($localeFolder, 0777, true);
        }

        foreach (self::LOCALES as $locale) {
            $fileName = "locale/$locale.ini";
            $filePath = $localeFolder . "$locale.ini";

            if (!file_exists($filePath)) {
                $plugin->saveResource($fileName);
            }

            if (file_exists($filePath)) {
                self::$translations[$locale] = array_map(
                    'stripcslashes',
                    parse_ini_file($filePath, false, INI_SCANNER_RAW)
                );
            } else {
                $plugin->getLogger()->warning("Localization file $fileName is missing.");
            }
        }
    }

    public static function translate(
        string $key,
        $locale = self::DEFAULT_LOCALE,
        array $args = []
    ): string {
        if ($locale instanceof Player) {
            $locale = $locale->getLocale();
        } elseif ($locale instanceof CommandSender) {
            $locale = $locale instanceof ConsoleCommandSender
                ? self::DEFAULT_LOCALE
                : $locale->getLocale();
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