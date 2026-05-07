<?php
namespace App\Core;

final class AppBrand
{
    public const NAME = 'SIS+';
    public const TAGLINE = 'SIS+';
    public const FOOTER_LABEL = 'SIS+';
    public const PRODUCER = 'SIS+ Platform';
    public const EXPORT_APP = 'SIS+ Export';

    public static function displayName(): string
    {
        return self::NAME;
    }
}
