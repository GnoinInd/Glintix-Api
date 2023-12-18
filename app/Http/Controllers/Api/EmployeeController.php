<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\User;

use App\Models\Client;
 use Illuminate\Support\Facades\Mail;
 use App\Mail\LeaveMail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\URL;
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


class EmployeeController extends Controller
{
    



    public function createEmployee(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:employees,email',
            'address' => 'required|string',
            'phone' => 'required|string|max:20',
        ]);
    
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        $employee = DB::table('employees')->insert([
            'name' => $request->name,
            'email' => $request->email,
            'address' => $request->address,
            'phone' => $request->phone,
        ]);
    
        return response()->json(['message' => 'Employee created successfully', 'employee_id' => $employee]);
    }
    
public function getEmployee(Request $request)
{
    
    try {
        $validatedData = $request->validate([
            'username' => 'required|string|max:255',
            'password' => 'required|string|min:6',           
        ]);
        $user = User::where('username', $validatedData['username'])->first();

        if (!$user || !Hash::check($validatedData['password'], $user->password)) {
            return response()->json(['message' => 'Invalid credentials or company not found'], 401);
        }

        $dbName = $user->dbName;
        Config::set('database.connections.dynamic', [
            'driver' => 'mysql',
            'host' => 'localhost', 
            'database' => $dbName, 
            'username' => $validatedData['username'], 
            'password' => $validatedData['password'],
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
            'engine' => null,
        ]);

        $dynamicDB = DB::connection('dynamic');
        $employees = $dynamicDB->table('employees')->get();
        return response()->json(['message' => 'All employee','data'=>$employees], 500);
        }  

        catch (Exception $e) {
            return response()->json(['message' => 'Error fetching employee'], 500);
        }
}

    public function specificEmployee(Request $request, $id)
    {
   
    try {
      $validatedData = $request->validate([
          'username' => 'required|string|max:255',
          'password' => 'required|string|min:6',           
      ]);
      $user = User::where('username', $validatedData['username'])->first();
      if (!$user || !Hash::check($validatedData['password'], $user->password)) {
          return response()->json(['message' => 'Invalid credentials or company not found'], 401);
      }
      

      $dbName = $user->dbName;
      Config::set('database.connections.dynamic', [
          'driver' => 'mysql',
          'host' => 'localhost',
          'database' => $dbName,
          'username' => $validatedData['username'], 
          'password' => $validatedData['password'], 
          'charset' => 'utf8mb4',
          'collation' => 'utf8mb4_unicode_ci',
          'prefix' => '',
          'strict' => true,
          'engine' => null,
      ]);

      $dynamicDB = DB::connection('dynamic');
      $employee = $dynamicDB->table('employees')->find($id);

      return response()->json(['message' => 'employee', 'data' => $employee], 200);
      }  

      catch (Exception $e) {
          return response()->json(['message' => 'Error fetching employee'], 500);
      }
}


public function loginEmp(Request $request)
{
   
    try {
        $validatedData = $request->validate([
            'username' => 'required',
            'userpassword' => 'required',
            'email' => 'required|email',
            'empPassword' => 'required|string|min:6',
        ]);

        $username = $validatedData['username'];
        
        $password = $validatedData['userpassword'];
        $empEmail = $validatedData['email'];
        $empPassword = $validatedData['empPassword'];
        
        $check = User::where('username', $username)->first();

        if ($check && Hash::check($password, $check->password)) {
            $dbName = $check->dbName;
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
           
            $hashedEmpPassword = Hash::make($empPassword);

            try {
                $checkPass = $dynamicDB->table('employees')->where('email', $empEmail)->first();

                if ($checkPass && Hash::check($empPassword, $checkPass->password)) {
                    session_start();
                    $_SESSION['username'] = $username;
                    $_SESSION['password'] = $password;
                    $_SESSION['dbName'] = $dbName;
                    $_SESSION['empEmail']=$empEmail;
                    $_SESSION['empPass'] = $empPassword;
                    return response()->json(['Success' => true, 'Message' => 'Login Successful']);

               
                } else {
                    return response()->json(['Success' => false, 'Message' => 'Email and Password are incorrect']);
                }
            } catch (\Illuminate\Database\QueryException $exception) {
              
                return response()->json(['Success' => false, 'Message' => 'Employees table not found']);
            }
        }

        return response()->json(['message' => 'Connection not possible']);
    } catch (\Exception $e) {
        return response()->json(['success' => false, 'error' => $e->getMessage()]);
    }
}




public function empCheckIn(Request $request)
{
    session_start();
    
    if (
        isset($_SESSION['username']) &&
        isset($_SESSION['password']) &&
        isset($_SESSION['dbName']) &&
        isset($_SESSION['empEmail']) &&
        isset($_SESSION['empPass'])
    ) {  
        $username = $_SESSION["username"];
        $password = $_SESSION["password"];
        $dbName = $_SESSION["dbName"];
        $email = $_SESSION['empEmail'];
        $empPass = $_SESSION['empPass'];

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

    
        $currentEmp = $dynamicDB->table('employees')->where('email', $email)->first();

        if (!$currentEmp) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $id = $currentEmp->id;
          if (!$dynamicDB->getSchemaBuilder()->hasTable('attendences')) {
              $dynamicDB->getSchemaBuilder()->create('attendences', function (Blueprint $table) {
                  $table->id();
                  $table->unsignedBigInteger('employee_id');
                  $table->time('check_in')->nullable();
                  $table->time('check_out')->nullable();
                  $table->string('location')->nullable();
                  $table->float('latitude', 10, 6)->nullable();
                  $table->float('longitude', 10, 6)->nullable();
                  $table->unsignedInteger('break')->nullable();
                  $table->unsignedInteger('production_time')->nullable();
                  $table->enum('status', ['active', 'inactive'])->default('active');
                  $table->date('date');
                  $table->timestamps();
                  $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
              });
          }

        $currentTime = Carbon::now()->timezone('Asia/Kolkata')->format('H:i:s');
        $todayAttend = $dynamicDB->table('attendences')
            ->where('employee_id', $id)
            ->whereDate('date', now()->toDateString())
            ->first();
        
        if ($todayAttend) {
            $status = $todayAttend->status;
            $out = $todayAttend->check_out;
            
            if ($out === null && $status == 'active')
            {
                return response()->json(['message' => 'You already checked in'], 200);
            } elseif ($status == 'inactive')
            { 
                $previousBreak = $todayAttend->break;     
                $checkOutTime = Carbon::parse($todayAttend->check_out); 
                $timeDifferenceInMinutes = $checkOutTime->diffInMinutes($currentTime);
                $updatedBreak = $previousBreak === null ? $timeDifferenceInMinutes : $previousBreak + $timeDifferenceInMinutes;
                $dynamicDB->table('attendences')->where('id', $todayAttend->id)->update([
                    'check_out' => $currentTime,
                    'break' => $updatedBreak,
                    'status' => 'active',
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);
                  return response()->json(['message' => 'Check-in updated successfully'], 200);
            } 
            elseif($status == 'active')
            {
              return response()->json(['message' => 'you allready checkin']);
            }
            else 
            {
                return response()->json(['message' => 'Something went wrong'], 500);
            }
        } 
        
        else {
            $dynamicDB->table('attendences')->insert([
                'employee_id' => $id,
                'check_in' => $currentTime,
                'status' => 'active',
                'date' => now()->toDateString(),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),

            ]);

            return response()->json(['message' => 'Check-in recorded successfully'], 200);
        }
    }
    return response()->json(['message' => 'You are logged out'], 403);
}





public function empCheckOut(Request $request)
{
    session_start();
    if(isset($_SESSION['username'])
    && isset($_SESSION['password'])
    && isset($_SESSION['dbName'])
    && isset($_SESSION['empEmail'])
    && isset($_SESSION['empPass']))
    {
        $username = $_SESSION["username"];
        $password = $_SESSION["password"];
        $dbName = $_SESSION["dbName"];
        $email = $_SESSION['empEmail'];
        $empPass = $_SESSION['empPass'];
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
        
        $currentEmp = $dynamicDB->table('employees')->where('email',$email)->first();

        if(!$currentEmp)
        {
            return response()->json(['message' => 'Access Denied']);
        }
        $id = $currentEmp->id;
        $currentTime = Carbon::now()->timezone('Asia/Kolkata')->format('H:i:s');
        $todayAttend = $dynamicDB->table('attendences')->where('employee_id',$id)->whereDate('date',now()->toDateString())->first();

        if($todayAttend)
        {
            $status = $todayAttend->status;
            $out = $todayAttend->check_out;
            if($out === null && $status == 'active') 
            {
                $checkIn = Carbon::parse($todayAttend->check_in);
                $timeDifference = $checkIn->diffInMinutes($currentTime);
                $dynamicDB->table('attendences')->where('id', $todayAttend->id)->update([
                    'check_out' => $currentTime,
                    'production_time' => $timeDifference, 
                    'status' => 'inactive', 
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),

                ]);
                return response()->json(['message' => 'Check-out time recorded successfully'], 200);
            }
            elseif($out !== null && $status == 'active')
            {
                $checkOut = Carbon::parse($out);
                $timeDifference = $checkOut->diffInMinutes($currentTime);
                $previousProduction = $todayAttend->production_time;
                $updatedProduction =  $previousProduction + $timeDifference;
               
                $dynamicDB->table('attendences')->where('id', $todayAttend->id)->update([
                    'check_out' => $currentTime,
                    'production_time' => $updatedProduction,
                    'status' => 'inactive',
                    'created_at' => Carbon::now(), 
                ]);
                return response()->json(['message' => 'Check-out time recorded successfully'], 200);
            }
            elseif($status == 'inactive')
            {
                return response()->json(['message' => 'You have already checked out']);
            }
            else
            {
                return response()->json(['message' => 'Something went wrong']);
            }
        }
        return response()->json(['message' => 'You need to check in first']);
    }

    return response()->json(['message' => 'You are logged out, please log in'], 403);
}


public function emplogout(Request $request)
{   
    session_start();
    if(isset($_SESSION['username']) && 
    isset($_SESSION['password']) && 
    isset($_SESSION['dbName']) && 
    isset($_SESSION['empEmail']) &&
    isset($_SESSION['empPass']))
    {
      
      session_unset();
      session_destroy();
      return response()->json(['message' => 'Successfully logout']);
    }
    return response()->json(['message' => 'You already logout']);
}

public function applyAttend(Request $request)
{
    $validatedData = $request->validate([
        'reason'    =>  'required',
        'check_in' => 'nullable',
        'check_out' => 'nullable',
    ]);

    session_start();
    if(isset($_SESSION['username']) && 
    isset($_SESSION['password']) && 
    isset($_SESSION['dbName']) && 
    isset($_SESSION['empEmail']) && 
    isset($_SESSION['empPass']))
    {
        $username = $_SESSION["username"];
        $password = $_SESSION["password"];
        $dbName = $_SESSION["dbName"];
        $email = $_SESSION['empEmail'];
        $empPass = $_SESSION['empPass'];
        

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

        $emp = $dynamicDB->table('employees')->where('email',$email)->first();
        $empId = $emp->id;
        $date = Carbon::now()->timezone('Asia/Kolkata')->format('Y-m-d H:i:s');
        $currentDate = Carbon::now()->toDateString();

       

        if (!$dynamicDB->getSchemaBuilder()->hasTable('miss_attendences')) {
            $dynamicDB->getSchemaBuilder()->create('miss_attendences', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('employee_id');
                $table->string('reason');
                $table->string('check_in')->nullable();
                $table->string('check_out')->nullable();
                $table->string('location')->nullable();
                $table->enum('status',['pending','reject','accepted'])->default('pending');
                $table->date('date');
                $table->timestamps();
            });
     
    }

       $dynamicDB->table('miss_attendences')->insert([
          'employee_id' => $empId,
          'reason' => $request->input('reason'),
          'check_in' => $request->input('check_in'),
          'check_out' => $request->input('check_out'),
          'date'   =>  $currentDate,
          'created_at' => $date,
          'updated_at' => $date,
    ]);
  return response()->json(['message' => 'miss attendence request succesfully created']);

    }
    return response()->json(['message' => 'session out,pls login']);
}








}
