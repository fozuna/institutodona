<?php
namespace App\Core;

final class AppBrand
{
    public const NAME = 'VIVA+ DIGITAL';
    public const TAGLINE = 'VIVA+ DIGITAL';
    public const FOOTER_LABEL = 'VIVA+ DIGITAL';
    public const PRODUCER = 'VIVA+ DIGITAL Platform';
    public const EXPORT_APP = 'VIVA+ DIGITAL Export';

    public static function displayName(): string
    {
        return self::NAME;
    }
}
