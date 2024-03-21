<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoleUserAssign extends Model
{
    use HasFactory;
    protected $fillable = ['emp_id','role_id','company_code']; 
}
