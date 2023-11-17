<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromQuery;
use Illuminate\Support\Facades\Session;
use Carbon\Carbon;


class MonthsData implements FromQuery
{
   
    protected $username;
    protected $password;
    protected $dbName;


    public function __construct($username,$password,$dbName)
    {
       
       
        $this->username = $username;
        $this->password = $password;
        $this->dbName = $dbName;
  
    }

    public function query()
  
    { 

        // Set the dynamic database connection
    config(['database.connections.dynamic' => [
    'driver' => 'mysql',
    'host' => 'localhost',
    'database' => $this->dbName,
    'username' => $this->username,
    'password' => $this->password,
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix' => '',
    'strict' => true,
    'engine' => null,
]]);

        $dynamicDB = DB::connection('dynamic');

        // $oneYearAgo = Carbon::now()->timezone('Asia/Kolkata')->subYear();

        $startDate = Carbon::now()->subMonth()->startOfDay();
        
        $endDate = Carbon::now();
   
         return $dynamicDB->table('user')
        ->whereBetween('created_at', [$startDate, $endDate])
        ->orderBy('created_at', 'desc');
    }



   
    // public function headings(): array
    // {
        
    //     return [
    //         'id',
    //         'title',
    //         'first name',
    //         'last name',
    //         'pref name',
    //         'dob',
    //         'gender',
    //         'blood group',
    //         'marital_status',

           
    //     ];
    // }






    // public function headings(): array
    // {
    //     // Get the column names from the database table
    //     $columns = $this->getTableColumns('user'); // Replace 'user' with your actual table name

    //     return $columns;
    // }

    // protected function getTableColumns($tableName): array
    // {
    //     $columns = DB::connection('dynamic')->getSchemaBuilder()->getColumnListing($tableName);
    //     return $columns;
    // }







}
