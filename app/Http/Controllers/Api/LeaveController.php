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
        if(!$token)
        {
            return response()->json(['success'=>false,'message'=>'token not found!'],404); 
        }
        $tokenRole = $token['tokenable']['role'];
        $status = $token['tokenable']['status'];
        $code = $token['tokenable']['company_code'];
        $company = User::where('company_code',$code)->where('status','Active')->first(); 
        $username = $company->username;
        $password = $company->dbPass;
        $dbName = $company->dbName;
        $emp_name = $token['tokenable']['name'];
        $emp_id = $token['tokenable']['emp_id'];
        $validatedData = $request->validate([
            'leave_type' => 'required',
            'start_date' => 'required|date', 
            'end_date' => 'required|date',
            'application_type' => 'required',
            'leave_name' => 'required', 
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
           $start_date = Carbon::parse($validatedData['start_date']);
           $end_date = Carbon::parse($validatedData['end_date']);
           $duration = $end_date->diffInDays($start_date) + 1;
           if (!$dynamicDB->getSchemaBuilder()->hasTable('leave_application'))
           {
              $dynamicDB->getSchemaBuilder()->create('leave_application', function (Blueprint $table) {
                 $table->id();
                 $table->date('application_date');
                 $table->bigInteger('emp_id')->unsigned();
                 $table->string('emp_name');
                 $table->string('emp_code')->nullable();
                 $table->string('reporting_manager')->nullable();
                 $table->string('leave_name');
                 $table->enum('application_type',['regular','previous month']);
                 $table->date('start_date');
                 $table->date('end_date');
                 $table->string('total_days');
                 $table->string('leave_type');
                 $table->string('reason');
                 $table->enum('status', ['pending', 'approved','reject'])->default('pending');
                 $table->string('approved_by')->nullable();
                 $table->timestamps();

             });
           }
           $dynamicDB->table('leave_application')->insert([
            'application_date' => $date,
            'emp_id'         =>   $emp_id,
            'emp_name' => $emp_name,
            // 'emp_code' => $emp_code,
            // 'reporting_manager'  => $rep_man;
            'start_date'  => $request->input('start_date'),
            'leave_name' => $request->input('leave_name'),
            'end_date'   => $request->input('end_date'),
            'total_days' => $duration,
            'application_type' => $request->input('application_type'),
            'leave_type'  => $request->input('leave_type'),
            'reason'    => $request->input('reason'),
            'created_at' => $date,
            'updated_at' => $date,
           ]);
           $leaveData = $dynamicDB->table('leave_application')->orderBy('id','desc')->first();
           return response()->json(['success'=>true,'message' => 'leave details stored successfully','leaveData'=>$leaveData],201);                                    

     }
    
      catch(\Exception $e)
     {
       return response()->json(['message' => 'An error occurred. Please try again.',$e->getMessage()], 500);
     }

    }

 
  


    public function allLeaveApplication(request $request)
    {
        try
        {
            $token = $request->user()->currentAccessToken();
            $tokenRole = $token['tokenable']['role'];
            $status = $token['tokenable']['status'];
            $code = $token['tokenable']['company_code'];
            $company = User::where('company_code', $code)->where('status','active')->first();     
            if (!$company) {
                return response()->json(['success' => false,'message' => 'Company not found.'], 404);
            }
            $username = $company->username;
            $password = $company->dbPass;
            $dbName = $company->dbName;   
            if ($tokenRole == 'admin' || $tokenRole == 'Super Admin')
            {
                Config::set('database.connections.dynamic', [
                    'driver' => 'mysql',
                    'host' => 'localhost',
                    'database' => $dbName,
                    'username' => $username,
                    'password' => $password,
                    'charset' => 'utf8mb4',
                    'collation' => 'utf8mb4_unicode_ci',
                    'prefix' => '',
                    'strict' => true,
                    'engine' => null,
                ]);
            
                $dynamicDB = DB::connection('dynamic');
               $allData = $dynamicDB->table('leave_application')->get();
               return response()->json(['success'=>true,'message' => 'Data Found',$allData],200);
            }
        }
        catch (\Exception $e) {
            // Log::error('Error deleting department: ' . $e->getMessage());
            return response()->json(['success'=>false,'message' => 'An error occurred.', 'error' => $e->getMessage()], 500);
        }

    }




public function leaveApplicationApprove(Request $request, $leaveId)
{
    try
    {
        $token = $request->user()->currentAccessToken();
        $tokenRole = $token['tokenable']['role'];
        $status = $token['tokenable']['status'];
        $code = $token['tokenable']['company_code']; 
        $company = User::where('company_code', $code)->where('status','active')->first();     
        if (!$company) {
          return response()->json(['success' => false,'message' => 'Company not found.'], 404);
        }
        $username = $company->username;
        $password = $company->dbPass;
        $dbName = $company->dbName;   
        if ($tokenRole == 'admin' || $tokenRole == 'Super Admin')
        {
            Config::set('database.connections.dynamic', [
                'driver' => 'mysql',
                'host' => 'localhost',
                'database' => $dbName,
                'username' => $username,
                'password' => $password,
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
                'strict' => true,
                'engine' => null,
            ]);
        
            $dynamicDB = DB::connection('dynamic');


            $leave = $dynamicDB->table('leave_application')->find($leaveId);

            if (!$leave) {
                return response()->json(['success'=>false,'message' => 'Employee not found'], 404);
            }
            $date = Carbon::now()->timezone('Asia/Kolkata')->format('Y-m-d H:i:s');
             $dynamicDB->table('leave_application')->where('id', $leaveId)->update([
                'approved_by' => $tokenRole,
                'updated_at' => $date,
            ]);
           return response()->json(['success'=>true,'message' => 'leave status updated successfully'],200);
        }
    }
    catch (\Exception $e) {
        // Log::error('Error deleting department: ' . $e->getMessage());
        return response()->json(['success'=>false,'message' => 'An error occurred.', 'error' => $e->getMessage()], 500);
    }

}










}
