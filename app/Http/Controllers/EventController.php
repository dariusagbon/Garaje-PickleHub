<?php
namespace App\Http\Controllers;
use App\Models\Event;
use App\Models\EventMatch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
class EventController extends Controller {
 public function show(Event $event){$event->load(['playerRegistrations','matches.players']); return view('events.show',compact('event'));}
 public function register(Request $r, Event $event){
  $data=$r->validate(['player_name'=>'required|string|max:100']);
  $registration=$event->playerRegistrations()->where('user_id',$r->user()->id)->first();
  if(!$registration && $event->playerRegistrations()->count()>=$event->capacity) return back()->withErrors(['event'=>'This event is full.']);
  $event->playerRegistrations()->updateOrCreate(['user_id'=>$r->user()->id],['player_name'=>$data['player_name']]);
  $event->users()->syncWithoutDetaching([$r->user()->id]);
  return back()->with('message',$registration ? 'Your player name was updated.' : 'You are registered!');
 }
 public function randomize(Event $event){
  if ($event->matches()->exists() && !auth()->user()?->is_admin) abort(403);
  $registrations=$event->playerRegistrations()->inRandomOrder()->get();
  $event->matches()->delete();
  foreach($registrations->chunk(4) as $players) if($players->count()===4){
   $match=$event->matches()->create();
   $match->players()->attach($players->take(2)->pluck('id')->mapWithKeys(fn($id)=>[$id=>['team'=>1]])->all());
   $match->players()->attach($players->skip(2)->pluck('id')->mapWithKeys(fn($id)=>[$id=>['team'=>2]])->all());
  }
  return back()->with('message','Matches randomized from the registered players.');
 }
 public function score(Request $r, Event $event, EventMatch $match){
  abort_unless($match->event_id===$event->id,404);
  $data=$r->validate(['score_pin'=>'required|string|max:32','score_a'=>'required|integer|min:0','score_b'=>'required|integer|min:0']);
  if (!$event->score_pin || !Hash::check($data['score_pin'], $event->score_pin)) {
   return back()->withErrors(['score_pin'=>'The scorekeeper PIN is incorrect.'])->withInput();
  }
  unset($data['score_pin']);
  $high=max($data['score_a'],$data['score_b']); $low=min($data['score_a'],$data['score_b']);
  if($high<11 || $high-$low<2) return back()->withErrors(['score_a'=>'A game must be won by at least 2 points and reach 11 points.']);
  $match->update($data); return back()->with('message','Score saved.');
 }
 public function index(){return view('admin.events.index',['events'=>Event::with('playerRegistrations')->latest()->get()]);}
 public function create(){return view('admin.events.form',['event'=>new Event]);}
 public function store(Request $r){$data=$r->validate(['title'=>'required|max:255','date'=>'required|date','time'=>'required','description'=>'nullable|string','capacity'=>'required|integer|min:1','score_pin'=>'required|string|min:4|max:32']);$data['score_pin']=Hash::make($data['score_pin']);Event::create($data);return redirect()->route('admin.events.index');}
 public function edit(Event $event){return view('admin.events.form',compact('event'));}
 public function update(Request $r,Event $event){$data=$r->validate(['title'=>'required|max:255','date'=>'required|date','time'=>'required','description'=>'nullable|string','capacity'=>'required|integer|min:1','score_pin'=>'nullable|string|min:4|max:32']);if(!empty($data['score_pin']))$data['score_pin']=Hash::make($data['score_pin']);else unset($data['score_pin']);$event->update($data);return redirect()->route('admin.events.index');}
 public function destroy(Event $event){$event->delete();return back();}
}
