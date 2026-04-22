<?php

namespace App\Support;

use App\Models\User;

class WebViewAuthPayload
{
    /**
     * @return array{id:int|string|null, user_id:int|string|null, username:string, display_name:string, email:string}
     */
    public static function fromUser(User $user): array
    {
        return [
            'id' => $user->getKey(),
            'user_id' => $user->getKey(),
            'username' => (string) $user->email,
            'display_name' => $user->display_name,
            'email' => (string) $user->email,
        ];
    }
}
