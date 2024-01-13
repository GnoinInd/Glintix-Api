<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;

class CompanyUserAccess extends Model
{
    use HasApiTokens, HasFactory;
    protected $fillable = ['name','emp_id','emp_code','email','username','password','dbName','company_code','role','status','read','create','edit','delete'];
}
