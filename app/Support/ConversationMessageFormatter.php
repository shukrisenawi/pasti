<?php

namespace App\Support;

use App\Models\User;

class ConversationMessageFormatter
{
    public function format(?string $body, User $sender): string
    {
        $body = trim((string) $body);
        if ($body === '') {
            return '';
        }

        return str_replace(
            ['@nama', '@pasti'],
            [
                $sender->display_name,
                $sender->guru?->pasti?->name ?? '',
            ],
            $body
        );
    }
}
