<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\User;

use App\Models\Client;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Route;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session;
use Carbon\Carbon;
use Illuminate\Http\Response;




class UserController extends Controller
{
    






public function register(Request $request)
{
    $validateData = Validator::make($request->all(),[
    'name'=>'required|min:3',
    'email' => 'required|email|unique:users',
    'password' => 'required|min:5'
    ]);
    if ($validateData->fails()) {
        return response()->json(['message' => 'Validation failed'], 422);
    }


    $data = $request->all();
    $data['password'] = Hash::make($request->password);

    $user =User::create($data);

    if($user)
    {
        return response()->json(['status'=>'success','message'=>'User registration successfully','data'=>$data]);
    }
    else{
        return response()->json(['status'=>'Fail','message'=>'User registration Fail']);
    }

    

    
    
}




public function login(Request $request)
{
$data = [
    'email' => $request->email,
    'password' => $request->password
];
if (auth()->attempt($data)) {
    $user = auth()->user(); 
    $token = $user->createToken('token_key')->accessToken;

    return response()->json(['token' => $token,'data'=>$data], 200);
} 
else
 {
    return response()->json(['error' => 'Unauthorised'], 401);
}

}



public function getuser($id)
{
$user = User::find($id);

//    $user = DB::table('users')
//                 ->where('id', $id)
//                 ->first();
if(is_null($user))
{
return response()->json(['status' =>'fail','message' =>'User not Found'],403);
}
else{
    return response()->json([
        'user'=>$user,
        'message' => 'User Found',
        'status' =>1
    ]);
   }
}



public function logout()
{
    $user = Auth::user();

    if ($user) {
        $user->tokens->each(function ($token, $key) {
            $token->delete();
        });

        return response()->json(['message' => 'Logged out successfully'], 200);
    } else {
        return response()->json(['message' => 'User not authenticated','user' => $user], 401);
    }
}






public function logoutSession(Request $request)
{

try {
  
    Auth::logout();

    $request->session()->flush();
    return response()->json(['message' => 'Logout successful'], 200);
} catch (\Exception $e) {
    return response()->json(['message' => 'Logout failed', 'error' => $e->getMessage()], 500);
}
}




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
