<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventMatch extends Model
{
    /** A game is won by the first team to reach this many points... */
    public const POINTS_TO_WIN = 11;

    /** ...with at least this lead. */
    public const WIN_BY = 2;

    protected $fillable = ['event_id', 'score_a', 'score_b'];

    protected $casts = [
        'score_a' => 'integer',
        'score_b' => 'integer',
        'completed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        // Scores autosave during play; the game counts as finished only once
        // the score is a winning one. Correcting it back reopens the game.
        static::saving(function (EventMatch $match) {
            if (! self::isWinningScore($match->score_a, $match->score_b)) {
                $match->completed_at = null;
            } elseif (! $match->completed_at) {
                $match->completed_at = now();
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Scoring rules
    |--------------------------------------------------------------------------
    */

    /** A team has reached 11 and leads by 2: the game is over. */
    public static function isWinningScore(?int $a, ?int $b): bool
    {
        $high = max((int) $a, (int) $b);
        $low = min((int) $a, (int) $b);

        return $high >= self::POINTS_TO_WIN && $high - $low >= self::WIN_BY;
    }

    /**
     * Whether a score can occur in a real game. The game stops as soon as it is
     * won, so 13–5 is impossible (it ended at 11–5), while 13–11 is fine.
     */
    public static function isPossibleScore(int $a, int $b): bool
    {
        if (! self::isWinningScore($a, $b)) {
            return true;
        }

        return max($a, $b) === self::POINTS_TO_WIN || abs($a - $b) === self::WIN_BY;
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships and scopes
    |--------------------------------------------------------------------------
    */

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function players()
    {
        return $this->belongsToMany(
            EventRegistration::class,
            'event_match_players',
            'match_id',
            'event_registration_id',
        )->withPivot('team');
    }

    public function teamA()
    {
        return $this->players()->wherePivot('team', 1);
    }

    public function teamB()
    {
        return $this->players()->wherePivot('team', 2);
    }

    public function scopeFinished(Builder $query): Builder
    {
        return $query->whereNotNull('completed_at');
    }

    public function scopeUnfinished(Builder $query): Builder
    {
        return $query->whereNull('completed_at');
    }

    /*
    |--------------------------------------------------------------------------
    | State
    |--------------------------------------------------------------------------
    */

    public function isComplete(): bool
    {
        return $this->completed_at !== null;
    }

    public function winnerLabel(): ?string
    {
        if (! $this->isComplete()) {
            return null;
        }

        return $this->score_a > $this->score_b ? 'Team A wins' : 'Team B wins';
    }
}
