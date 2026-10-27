<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Post;
use App\Models\User;

final class PostPolicy
{
    public function view(User $user, Post $post): bool
    {
        return $user->role->managesContent() || $post->author_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->is_active;
    }

    public function update(User $user, Post $post): bool
    {
        return $user->role->managesContent() || ($post->author_id === $user->id && in_array($post->status, ['draft', 'review'], true));
    }

    public function delete(User $user, Post $post): bool
    {
        return $user->role->managesContent() || ($post->author_id === $user->id && $post->status === 'draft');
    }

    public function publish(User $user, Post $post): bool
    {
        return $user->role->managesContent();
    }
}
