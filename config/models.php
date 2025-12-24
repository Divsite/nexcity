<?php

use App\Models\Profiles\Profile;

return [
    'profile' => env('USER_PROFILE_MODEL', Profile::class),
];
