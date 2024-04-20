<?php

namespace App\Imports;

use App\Models\ProjectMaster;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date;



// class ProjectDataImport implements ToModel
class ProjectDataImport implements ToModel, WithHeadingRow, WithStartRow
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        $start_date = Date::excelToDateTimeObject($row['start_date'])->format('Y-m-d');
        $target_date = Date::excelToDateTimeObject($row['target_date'])->format('Y-m-d');
      

        return new ProjectMaster([
            'branch' => $row['branch'],
            'department' => $row['department'],
            'proj_name' => $row['proj_name'],
            'proj_title' => $row['proj_title'],
            'description' => $row['description'],
            'proj_code' => $row['proj_code'],
            'methodology' => $row['methodology'],
            'version' =>  $row['version'],
            // 'start_date' => $row['start_date'],
            'start_date' => $start_date,
            // 'target_date' => $row['target_date'],
            'target_date' => $target_date,
            'due_date' => $row['due_date'],
            'duration' => $row['duration'],
            'priority' => $row['priority'],
            'risk'  =>   $row['risk'],
            'company_code' => $row['company_code'],
            'resource_id' => $row['resource_id'],
            'resource_name' => $row['resource_name'],
            'location'  => $row['location'],
            'serial_no'  => $row['serial_no'],
            'memory_size'  => $row['memory_size'],
            'model'   =>  $row['model'],
            'comments'  => $row['comments'],
            'type_of_resource' => $row['type_of_resource'],
            'quantity'     => $row['quantity'],
            'storage_capacity'  => $row['storage_capacity'],
            'assumption'   =>  $row['assumption'],
            'resource_description'  => $row['resource_description'],
            'mac_address'   => $row['mac_address'],
            'subnet_mask'   => $row['subnet_mask'],
            'dns'        =>  $row['dns'],
            'ip_address'  => $row['ip_address'],
            'gateway'  =>  $row['gateway'],
            'soft_name'  => $row['soft_name'],
            'soft_version'  => $row['soft_version'],
            'year_of_licence' => $row['year_of_licence'],
            'soft_serial_no' => $row['soft_serial_no'],
            'soft_licence' => $row['soft_licence'],
            'title'   => $row['title'],
            'soft_quantity'  => $row['soft_quantity'],
            'soft_description' => $row['soft_description'],
            'role'   =>        $row['role'],
            'no_of_roles'  =>  $row['no_of_roles'],
            'human_resource_description' => $row['human_resource_description'],
            'cost_type'  =>     $row['cost_type'],
            'cost_resource_name' => $row['cost_resource_name'],
            'cost_quantity'   =>  $row['cost_quantity'],
            'cost'         =>    $row['cost'],
            'total_cost'  =>    $row['total_cost'],
            'cost_description' =>  $row['cost_description'],
            'client_id'   =>    $row['client_id'],
            'client_role'  =>  $row['client_role'],
            'client_name'  =>  $row['client_name'],
            'client_website' => $row['client_website'],
            'client_domain'  => $row['client_domain'],
            'client_insurance' => $row['client_insurance'],
            'document_type'  =>  $row['document_type'],
        ]);
        \Log::info('ProjectMaster instance created:', $projectMaster->toArray());

    
        return $projectMaster;
    }
    public function startRow(): int
    {
        return 2;
    }
}
