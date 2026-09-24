<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
class Event extends Model {
 protected $fillable=['title','date','time','description','capacity','score_pin'];
 protected $casts=['date'=>'date'];
 public function users(): BelongsToMany { return $this->belongsToMany(User::class); }
 public function registrations(): int { return $this->playerRegistrations()->count(); }
 public function playerRegistrations() { return $this->hasMany(EventRegistration::class); }
 public function matches() { return $this->hasMany(EventMatch::class); }
}
