<?php

namespace App\Services\Dashboard;

use App\Models\Article;
use App\Models\ContactMessage;
use App\Models\Page;

class DashboardTodoService
{
    /** @return array<string, int> */
    public function counts(bool $isSuperAdmin): array
    {
        $todo = [
            'drafts' => Article::where('is_published', false)->count() + Page::where('is_published', false)->count(),
            'scheduled' => Page::where('is_published', true)->where('published_at', '>', now())->count(),
        ];

        if ($isSuperAdmin) {
            $todo['messages'] = ContactMessage::whereNull('read_at')->count();
        }

        return $todo;
    }
}
