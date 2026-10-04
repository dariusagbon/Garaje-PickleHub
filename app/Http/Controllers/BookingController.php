<?php
namespace App\Http\Controllers;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
class BookingController extends Controller {
 public function availability(Request $request) { return response()->json(Booking::where('status','confirmed')->get(['booking_date','court','hour'])); }
 public function store(Request $request) {
  if (!$request->has('hours') && $request->has('hour')) $request->merge(['hours'=>[$request->input('hour')]]);
  $data=$request->validate(['guest_name'=>'required|string|max:120','guest_email'=>'required|email|max:255','booking_date'=>'required|date|after_or_equal:today','court'=>['required',Rule::in(['PickleHub Court'])],'hours'=>'required|array|min:1','hours.*'=>'required|integer|between:7,23']);
  $data['hours']=array_values(array_unique($data['hours']));
  $conflict=Booking::whereDate('booking_date',$data['booking_date'])->where('court',$data['court'])->whereIn('hour',$data['hours'])->where('status','confirmed')->exists();
  if ($conflict) return response()->json(['message'=>'One or more selected hours are already booked.'],409);
  try {
   $bookings=DB::transaction(function () use ($data) {
    return collect($data['hours'])->map(function ($hour) use ($data) {
     $booking=Booking::whereDate('booking_date',$data['booking_date'])->where('court',$data['court'])->where('hour',$hour)->first();
     $attributes=['guest_name'=>$data['guest_name'],'guest_email'=>$data['guest_email'],'booking_date'=>$data['booking_date'],'court'=>$data['court'],'hour'=>$hour,'status'=>'confirmed'];
     if ($booking) { $booking->update($attributes); return $booking->fresh(); }
     return Booking::create($attributes);
    });
   });
  } catch (\Illuminate\Database\QueryException $e) { return response()->json(['message'=>'That slot is already booked.'],409); }
  return response()->json(['message'=>count($data['hours']).' booking hours confirmed.','bookings'=>$bookings],201);
 }
}
