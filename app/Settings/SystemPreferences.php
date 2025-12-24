<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class SystemPreferences extends Settings
{
    public const IMAGE_PATH = 'uploads/images/';

    public string $name,
        $logo_sm,
        $logo_lg,
        $favicon;

    public static function group(): string
    {
        return 'system';
    }
}
