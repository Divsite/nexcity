<?php

namespace App\Utilities\Themes;

use Illuminate\Support\Collection;

class Theme
{
    const LIGHT = "light";
    const DARK = "dark";

    public static function themeType(): Collection
    {
        return collect([
            self::LIGHT => self::LIGHT,
            self::DARK => self::DARK,
        ]);
    }
}
