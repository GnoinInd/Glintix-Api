<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyModuleAccess extends Model
{
    use HasFactory;
    protected $fillable = ['company_code','module_id','status'];

    public function module()
    {
        return $this->belongsTo(Module::class);
    }

}

