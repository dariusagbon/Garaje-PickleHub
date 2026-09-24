<?php
namespace App\Http\Controllers;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
class AdminBookingController extends Controller {
 public function index(){return view('admin.bookings.index',['bookings'=>Booking::latest()->get()]);}
 public function update(Request $r,Booking $booking){
  $d=$r->validate(['guest_name'=>'required|string|max:120','guest_email'=>'required|email','booking_date'=>'required|date','court'=>[ 'required', Rule::in(['PickleHub Court']) ],'hour'=>'required|integer|between:7,23','status'=>'required|in:confirmed,cancelled']);
  $conflict=Booking::where('id','!=',$booking->id)->where('booking_date',$d['booking_date'])->where('court',$d['court'])->where('hour',$d['hour'])->where('status','confirmed')->exists();
  if ($d['status'] === 'confirmed' && $conflict) return back()->withErrors(['booking_date'=>'That court slot is already booked.'])->withInput();
  $booking->update($d);
  return back()->with('message','Booking updated.');
 }
 public function destroy(Booking $booking){$booking->update(['status'=>'cancelled']);return back();}
}
