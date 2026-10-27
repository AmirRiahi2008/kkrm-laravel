<?php

declare(strict_types=1);

namespace App\Support;

final class ContentTypes
{
    public const POSTS = ['news', 'article', 'announcement', 'resolution', 'training', 'welfare', 'condolence', 'group_event'];

    public const DOCUMENTS = ['law', 'regulation', 'statute', 'directive', 'circular', 'form', 'qualification_table', 'tariff'];

    public const POST_STATUSES = ['draft', 'review', 'published', 'archived'];

    public const CONTENT_STATUSES = ['draft', 'published', 'archived'];
}
