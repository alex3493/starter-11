<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * App\Models\Chat
 *
 * @property int $id
 * @property string $topic
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ChatMessage> $chatMessages
 * @property-read int|null $chat_messages_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $users
 * @property-read int|null $users_count
 * @method static \Database\Factories\ChatFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|Chat newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Chat newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Chat query()
 * @method static \Illuminate\Database\Eloquent\Builder|Chat whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Chat whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Chat whereTopic($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Chat whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Chat filter(array $filters)
 * @mixin \Eloquent
 */
class Chat extends Model
{
    use HasFactory;

    protected $fillable = ['topic'];

    public function users(): BelongsToMany
    {
        return $this
            ->belongsToMany(User::class)
            ->withPivot('id')
            // When we load users relation we should order by pivot id (chat owner order).
            ->orderBy('chat_user.id');
    }

    public function chatMessages(): HasMany
    {
        return $this->hasMany(ChatMessage::class);
    }

    public function scopeFilter($query, array $filters): void
    {
        $query->when($filters['search'] ?? null, function ($query, $search) {
            $query->where(function ($query) use ($search) {
                $query->where('topic', 'like', '%'.$search.'%');
            })->orWhereHas('users', function ($query) use ($search) {
                $query->where('name', 'like', '%'.$search.'%')->orWhere('email',
                    'like', '%'.$search.'%');
            })->orWhereHas('chatMessages', function ($query) use ($search) {
                $query->where('message', 'like', '%'.$search.'%');
            });
        });
    }
}
