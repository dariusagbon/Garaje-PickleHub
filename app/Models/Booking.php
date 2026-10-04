<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Booking extends Model {
 protected $fillable=['guest_name','guest_email','booking_date','court','hour','status'];
 protected $casts=['booking_date'=>'date:Y-m-d'];
}
