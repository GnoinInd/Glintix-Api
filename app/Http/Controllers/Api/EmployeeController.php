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
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
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
          'collation' => 'utf8mb4_unicode_ci',
          'prefix' => '',
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










}
