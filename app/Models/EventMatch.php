<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventMatch extends Model
{
    protected $fillable = ['event_id', 'score_a', 'score_b'];
    protected $casts = ['score_a' => 'integer', 'score_b' => 'integer'];
    public function event(): BelongsTo { return $this->belongsTo(Event::class); }
    public function players() { return $this->belongsToMany(EventRegistration::class, 'event_match_players', 'match_id', 'event_registration_id')->withPivot('team'); }
    public function teamA() { return $this->players()->wherePivot('team', 1); }
    public function teamB() { return $this->players()->wherePivot('team', 2); }
    public function isComplete(): bool { return $this->score_a !== null && $this->score_b !== null; }
    public function winnerLabel(): ?string
    {
        if (!$this->isComplete() || $this->score_a === $this->score_b) return null;
        return $this->score_a > $this->score_b ? 'Team A wins' : 'Team B wins';
    }
}
