<?php

namespace App\Imports;

use App\Models\ProjectMaster;
use Maatwebsite\Excel\Concerns\ToModel;

class ProjectDataImport implements ToModel
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        return new ProjectMaster([
            'branch' => $row['1'],
            'department' => $row['2'],
            'proj_name' => $row['3'],
            'proj_title' => $row['4'],
            'description' => $row['5'],
            'proj_code' => $row['6'],
            'methodology' => $row['7'],
            'version' =>  $row['8'],
            'start_date' => $row['9'],
            'target_date' => $row['10'],
            'due_date' => $row['11'],
            'duration' => $row['12'],
            'priority' => $row['13'],
            'risk'  =>   $row['14'],
            'company_code' => $row['15'],
            'resource_id' => $row['16'],
            'resource_name' => $row['17'],
            'location'  => $row['18'],
            'serial_no'  => $row['19'],
            'memory_size'  => $row['20'],
            'model'   =>  $row['21'],
            'comments'  => $row['22'],
            'type_of_resource' => $row['23'],
            'quantity'     => $row['24'],
            'storage_capacity'  => $row['25'],
            'assumption'   =>  $row['26'],
            'resource_description'  => $row['27'],
            'mac_address'   => $row['28'],
            'subnet_mask'   => $row['29'],
            'dns'        =>  $row['30'],
            'ip_address'  => $row['31'],
            'gateway'  =>  $row['32'],
            'soft_name'  => $row['33'],
            'soft_version'  => $row['34'],
            'year_of_licence' => $row['35'],
            'soft_serial_no' => $row['36'],
            'soft_licence' => $row['37'],
            'title'   => $row['38'],
            'soft_quantity'  => $row['39'],
            'soft_description' => $row['40'],
            'role'   =>        $row['41'],
            'no_of_roles'  =>  $row['42'],
            'human_resource_description' => $row['43'],
            'cost_type'  =>     $row['44'],
            'cost_resource_name' => $row['45'],
            'cost_quantity'   =>  $row['46'],
            'cost'         =>    $row['47'],
            'total_cost'  =>    $row['48'],
            'cost_description' =>  $row['49'],
            'client_id'   =>    $row['50'],
            'client_role'  =>  $row['51'],
            'client_name'  =>  $row['52'],
            'client_website' => $row['53'],
            'client_domain'  => $row['54'],
            'client_insurance' => $row['55'],
            'document_type'  =>  $row['56'],
        ]);
    }
}
