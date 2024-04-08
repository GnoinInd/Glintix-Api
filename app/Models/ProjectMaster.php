<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectMaster extends Model
{
    use HasFactory;
    protected $fillable = ['id','branch','department','proj_name','proj_title','description','proj_code','methodology','version',
'start_date','target_date','due_date','duration','priority','risk','company_code','resource_id','resource_name','location',
'serial_no','memory_size','model','comments','type_of_resource','quantity','storage_capacity','assumption',
'resource_description','mac_address','subnet_mask','dns','ip_address','gateway','soft_name','soft_version',
'year_of_licence','soft_serial_no','soft_licence','title','soft_quantity','soft_description','role','no_of_roles',
'human_resource_description','cost_type','cost_resource_name','cost_quantity','cost','total_cost','cost_description',
'client_id','client_role','client_name','client_website','client_domain','client_insurance','document_type'
];

}
