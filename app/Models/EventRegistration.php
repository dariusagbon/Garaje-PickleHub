<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class EventRegistration extends Model
{
    protected $fillable = ['event_id', 'user_id', 'player_name'];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Games this player has been drawn into. */
    public function matches(): BelongsToMany
    {
        return $this->belongsToMany(EventMatch::class, 'event_match_players', 'event_registration_id', 'match_id')
            ->withPivot('team');
    }
}
