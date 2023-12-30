<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Exception;
use Twilio\Rest\Client;

class UserOtp extends Model
{
    use HasFactory;
    protected $fillable = ['user_id','otp','expire_at'];



}
