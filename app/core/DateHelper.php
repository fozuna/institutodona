<?php
namespace App\Core;

final class DateHelper
{
    public const APP_TIMEZONE = 'America/Campo_Grande';
    public const DEFAULT_STORAGE_TIMEZONE = 'UTC';

    public static function boot(): void
    {
        date_default_timezone_set(self::APP_TIMEZONE);
    }

    public static function now(string $format = 'd/m/Y H:i'): string
    {
        return (new \DateTimeImmutable('now', self::appTimezone()))->format($format);
    }

    public static function formatDate(?string $value, string $format = 'd/m/Y'): string
    {
        $raw = trim((string)$value);
        if ($raw === '') {
            return '';
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw) === 1) {
            $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $raw, self::appTimezone());
            return $date ? $date->format($format) : $raw;
        }

        return self::formatDateTime($raw, $format);
    }

    public static function formatDateTime(?string $value, string $format = 'd/m/Y H:i', ?string $sourceTimezone = null): string
    {
        $date = self::parseDateTime($value, $sourceTimezone);
        return $date ? $date->setTimezone(self::appTimezone())->format($format) : '';
    }

    public static function appTimezone(): \DateTimeZone
    {
        return new \DateTimeZone(self::APP_TIMEZONE);
    }

    public static function storageTimezone(?string $timezone = null): \DateTimeZone
    {
        $name = trim((string)($timezone ?? getenv('APP_STORAGE_TIMEZONE') ?: self::DEFAULT_STORAGE_TIMEZONE));
        return new \DateTimeZone($name !== '' ? $name : self::DEFAULT_STORAGE_TIMEZONE);
    }

    private static function parseDateTime(?string $value, ?string $sourceTimezone = null): ?\DateTimeImmutable
    {
        $raw = trim((string)$value);
        if ($raw === '') {
            return null;
        }

        try {
            if (preg_match('/(Z|[+\-]\d{2}:\d{2})$/', $raw) === 1) {
                return new \DateTimeImmutable($raw);
            }

            return new \DateTimeImmutable($raw, self::storageTimezone($sourceTimezone));
        } catch (\Throwable $e) {
            return null;
        }
    }
}
