<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\User;
use App\Exports\ThreeMonthsRecordexport;
//use App\Models\Client;
 use Illuminate\Support\Facades\Mail;
 use App\Mail\LeaveMail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\URL;
use App\Http\Controllers\Api\AdminController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Session\Middleware\StartSession;
use App\Models\PasswordReset;
use App\Models\SuperUser;
use App\Models\UserOtp;
use Exception;
use Twilio\Rest\Client;
use App\Mail\SuccessfulLoginNotification;
use App\Mail\OtpMail;
use App\Mail\WelcomeMail;
use Laravel\Sanctum\PersonalAccessTokenFactory;
use Cache;
use Illuminate\Validation\ValidationException;
use App\Models\CompanyModuleAccess;

class LeaveController extends Controller
{
    public function addLeaveCount(Request $request)
    {
      try{     
        $token = $request->user()->currentAccessToken();
        $tokenRole = $token['tokenable']['role'];
        $status = $token['tokenable']['status'];
        $code = $token['tokenable']['company_code'];
        $company = User::where('company_code',$code)->where('status','Active')->first(); 
        $username = $company->username;
        $password = $company->dbPass;
        $dbName = $company->dbName;
        $moduleId = 4;
        $empModule = CompanyModuleAccess::where('company_code',$code)->where('module_id',$moduleId)
        ->where('status','Active')->first();
        $accessLeave = $token['tokenable']['create'];
        if($tokenRole == 'admin' && $accessLeave != 1)
        {
            return response()->json(['success'=>false,'message' => 'you have no permission'],403);
        }
        if(!$empModule)
        {
            return response()->json(['success'=>false,'message' => 'you can not access Leave Module'],403);
        }
        if (($accessLeave == 1 && $tokenRole == 'admin') || $tokenRole == 'Super Admin')
        {
            $validatedData = $request->validate([
                'annual_leave' => 'required',
                'medical_leave' => 'required',
                'other_leave' => 'required',
            ]);
            Config::set('database.connections.dynamic',[
                'driver' => 'mysql',
                'host'  => 'localhost',
                'database' => $dbName,
                'username' => $username,
                'password' => $password,
                'charset'  => 'utf8mb4',
                'collection' => 'utf8mb4_unicode_ci',
                'prefix' => '',
                'strict' => true,
                'engine' => null,
            ]);
            $dynamicDB = DB::connection('dynamic');
            $date = Carbon::now()->timezone('Asia/kolkata')->format('Y-m-d H:i:s');
            if (!$dynamicDB->getSchemaBuilder()->hasTable('leave_details'))
            {
              $dynamicDB->getSchemaBuilder()->create('leave_details', function (Blueprint $table) {
                 $table->id();
                 $table->string('company_code');
                 $table->string('annual_leave');
                 $table->string('medical_leave');
                 $table->string('casual_leave');
                 $table->string('paid_leave');
                 $table->string('other_leave');
                 $table->enum('status',['active','inactive'])->default('active');
                 $table->timestamps();

             });
           }
           $dynamicDB->table('leave_details')->insert([
            'company_code' => $code,
            'annual_leave' => $request->input('annual_leave'),
            'medical_leave' => $request->input('medical_leave'),
            'other_leave' => $request->input('other_leave'),
            'casual_leave' => $request->input('casual_leave'),
            'paid_leave' => $request->input('paid_leave'),
            'created_at' => $date,
            'updated_at' => $date,
           ]);
           $leaveRecord = $dynamicDB->table('leave_details')->orderBy('id','desc')->first();
           return response()->json(['success'=>true,'message' => 'leave details stored successfully','leaveData'=>$leaveRecord],201);                                  


        }
        return response()->json(['success' => false,'message' => 'you have no permission'],403);
     }
     catch(\Exception $e)
    {
        return response()->json(['message' => 'An error occurred. Please try again.',$e->getMessage()], 500);
    }
      
    }





    public function addLeaveType(Request $request)
    {
        try{

       
        $token = $request->user()->currentAccessToken();
        $tokenRole = $token['tokenable']['role'];
        $status = $token['tokenable']['status'];
        $code = $token['tokenable']['company_code'];
        $company = User::where('company_code',$code)->where('status','Active')->first(); 
        $username = $company->username;
        $password = $company->dbPass;
        $dbName = $company->dbName;
        $moduleId = 4;
        $empModule = CompanyModuleAccess::where('company_code',$code)->where('module_id',$moduleId)
        ->where('status','Active')->first();
        $accessLeave = $token['tokenable']['create'];
        if($tokenRole == 'admin' && $accessLeave != 1)
        {
            return response()->json(['success'=>false,'message' => 'you have no permission'],403);
        }
        if(!$empModule)
        {
            return response()->json(['success'=>false,'message' => 'you can not access Leave Module'],403);
        }
        if (($accessLeave == 1 && $tokenRole == 'admin') || $tokenRole == 'Super Admin')
        {
            $validatedData = $request->validate([
                'leave_name' => 'required',
            ]);
            Config::set('database.connections.dynamic',[
                'driver' => 'mysql',
                'host'  => 'localhost',
                'database' => $dbName,
                'username' => $username,
                'password' => $password,
                'charset'  => 'utf8mb4',
                'collection' => 'utf8mb4_unicode_ci',
                'prefix' => '',
                'strict' => true,
                'engine' => null,
            ]);
            $dynamicDB = DB::connection('dynamic');
            $date = Carbon::now()->timezone('Asia/kolkata')->format('Y-m-d H:i:s');
            if (!$dynamicDB->getSchemaBuilder()->hasTable('leave_type'))
            {
              $dynamicDB->getSchemaBuilder()->create('leave_type', function (Blueprint $table) {
                 $table->id();
                 $table->string('company_code');
                 $table->string('leave_name');
                 $table->enum('status',['active','inactive'])->default('active');
                 $table->timestamps();

             });
           }
           $dynamicDB->table('leave_type')->insert([
            'company_code' => $code,
            'leave_name' => $request->input('leave_name'),
            'created_at' => $date,
            'updated_at' => $date,
           ]);
           $leaveType = $dynamicDB->table('leave_type')->orderBy('id','desc')->first();
           return response()->json(['success'=>true,'message' => 'leave details stored successfully','leaveData'=>$leaveType],201);                                  
        }   
          return response()->json(['success' => false,'message' => 'you have no permission'],403);

     }
    
      catch(\Exception $e)
     {
       return response()->json(['message' => 'An error occurred. Please try again.',$e->getMessage()], 500);
     }

    }




    
    public function leaveApplication(Request $request)
    {
        try{
        $token = $request->user()->currentAccessToken();
        $tokenRole = $token['tokenable']['role'];
        $status = $token['tokenable']['status'];
        $code = $token['tokenable']['company_code'];
        $company = User::where('company_code',$code)->where('status','Active')->first(); 
        $username = $company->username;
        $password = $company->dbPass;
        $dbName = $company->dbName;
        $moduleId = 4;
        $empModule = CompanyModuleAccess::where('company_code',$code)->where('module_id',$moduleId)
        ->where('status','Active')->first();
        $accessLeave = $token['tokenable']['create'];
        if($tokenRole == 'admin' && $accessLeave != 1)
        {
            return response()->json(['success'=>false,'message' => 'you have no permission'],403);
        }
        if(!$empModule)
        {
            return response()->json(['success'=>false,'message' => 'you can not access Leave Module'],403);
        }
        if (($accessLeave == 1 && $tokenRole == 'admin') || $tokenRole == 'Super Admin')
        {
            $validatedData = $request->validate([
                'leavetype' => 'required',
                'startdate' => 'required|date', 
                'enddate' => 'required|date', 
                'reason' => 'required',  
            ]);
            Config::set('database.connections.dynamic',[
                'driver' => 'mysql',
                'host'  => 'localhost',
                'database' => $dbName,
                'username' => $username,
                'password' => $password,
                'charset'  => 'utf8mb4',
                'collection' => 'utf8mb4_unicode_ci',
                'prefix' => '',
                'strict' => true,
                'engine' => null,
            ]);
            $dynamicDB = DB::connection('dynamic');
            $date = Carbon::now()->timezone('Asia/kolkata')->format('Y-m-d H:i:s');
            if (!$dynamicDB->getSchemaBuilder()->hasTable('leave_application'))
            {
              $dynamicDB->getSchemaBuilder()->create('leave_application', function (Blueprint $table) {
                 $table->id();
                 $table->string('application_date');
                 $table->string('emp_name');
                 $table->string('reporting_manager');
                 $table->string('leave_name');
                 $table->enum('application_type',['regular','previous month']);
                 $table->string('start_date');
                 $table->string('end_date');
                 $table->string('total_days');
                 $table->string('leave_type');
                 $table->string('reason');
                 $table->enum('status',['active','inactive'])->default('active');
                 $table->timestamps();

             });
           }
           $dynamicDB->table('leave_type')->insert([
            'application_date' => $date,
            'emp_name' => $emp_name,
            'leave_name' => $request->input('leave_name'),
            'created_at' => $date,
            'updated_at' => $date,
           ]);
           $leaveData = $dynamicDB->table('leave_application')->orderBy('id','desc')->first();
           return response()->json(['success'=>true,'message' => 'leave details stored successfully','leaveData'=>$leaveData],201);                                  
        }   
          return response()->json(['success' => false,'message' => 'you have no permission'],403);

     }
    
      catch(\Exception $e)
     {
       return response()->json(['message' => 'An error occurred. Please try again.',$e->getMessage()], 500);
     }

    }

 
  









}
