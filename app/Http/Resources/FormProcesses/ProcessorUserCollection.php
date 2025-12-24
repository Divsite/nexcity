<?php

namespace App\Http\Resources\FormProcesses;

use App\Models\Users\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ProcessorUserCollection extends ResourceCollection
{
    public function toArray(Request $request): array
    {
        $users = [];

        $path = "/".User::AVATAR_PATH;

        foreach ($this->collection as $item) {
            $users[] = [
                'id' => $item->id,
                'name' => $item->name,
                'username' => $item->username,
                'email' => $item->email,
                'avatar' => $path.$item->avatar,
            ];
        }

        return $users;
    }
}
