<?php

namespace App\Policies;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ConversationPolicy
{
    /**
     * Determine whether the user can view any conversations.
     * En este caso devolvemos true para que pueda usar viewAny si lo necesitamos.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the conversation.
     * Solo los participantes pueden verla.
     */
    public function view(User $user, Conversation $conversation): bool
    {
        return $conversation->users()
            ->where('user_id', $user->id)
            ->exists();
    }

    /**
     * Determine whether the user can create a conversation.
     * Dejamos true; el controlador ya impide chatear contigo mismo.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the conversation.
     * Por defecto false (no es algo que permitamos).
     */
    public function update(User $user, Conversation $conversation): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the conversation.
     * Por defecto false.
     */
    public function delete(User $user, Conversation $conversation): bool
    {
        return false;
    }

    public function restore(User $user, Conversation $conversation): bool
    {
        return false;
    }

    public function forceDelete(User $user, Conversation $conversation): bool
    {
        return false;
    }
}
