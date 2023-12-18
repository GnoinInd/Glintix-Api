<?php

namespace App\Exports;

//use Maatwebsite\Excel\Concerns\FromCollection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromQuery;
use Illuminate\Support\Facades\Session;
use Carbon\Carbon;


class YearwiseData implements FromQuery
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
            $startDate = Carbon::now()->subYear()->startOfDay();   
            $endDate = Carbon::now();  
            return $dynamicDB->table('user')->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at', 'desc');
            
    }






}
