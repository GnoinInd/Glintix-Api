<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens; // Change from HasApiTokens to use Sanctum

class SuperUser extends Authenticatable
{
    use HasApiTokens, Notifiable; // Use HasApiTokens from Sanctum

    protected $fillable = ['name', 'username', 'password', 'email', 'phone'];
}