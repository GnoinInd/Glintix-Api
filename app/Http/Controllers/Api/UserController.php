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
        // Revoke the access tokens for the authenticated user
        $user->tokens->each(function ($token, $key) {
            $token->delete();
        });
       // session()->forget('access_token');

        return response()->json(['message' => 'Logged out successfully'], 200);
    } else {
        // The user is not authenticated, handle this scenario as needed
        return response()->json(['message' => 'User not authenticated','user' => $user], 401);
    }
}


public function registerCompany(Request $request)
{
    try {
      
        $validatedData = $request->validate([
            'dbName' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'username' => 'required|string|max:255',
            'password' => 'required|string|min:6',
            'phone' => 'required|string|max:20',
        ]);

        $user = new User;
        $user->name = $validatedData['name'];
        $user->email = $validatedData['email'];
        $user->password = bcrypt($validatedData['password']);
        $user->dbName = $validatedData['dbName'];
        $user->username = $validatedData['username'];
        $user->save();
        $dbName = $validatedData['dbName'];
        $dbUsername = $validatedData['username'];
        $dbPassword = $validatedData['password'];

        $this->createDynamicDatabase($dbName, $dbUsername, $dbPassword);

       
        Config::set('database.connections.dynamic', [
            'driver' => 'mysql',
            'host' => 'localhost', 
            'database' => $dbName,
            'username' => $dbUsername,
            'password' => $dbPassword,
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
            'engine' => null,
        ]);

        $dynamicDB = DB::connection('dynamic');

        if (!$this->tableExists($dynamicDB, 'clients')) {
            $this->createClientsTable($dynamicDB);
        }

        $clientData = [
            'Name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'username' => $validatedData['username'],
            'password' => $validatedData['password'],
            'phone' => $validatedData['phone'],
            'dbName' => $validatedData['dbName'],
        ];
        $dynamicDB->table('clients')->insert($clientData);

        return response()->json(['message' => 'Company registered successfully'], 201);
    } catch (Exception $e) {
        return response()->json(['message' => 'Company registration failed. Please try again.'], 500);
    }
}

private function createDynamicDatabase($dbName, $dbUsername, $dbPassword)
{
    $defaultDB = DB::getDefaultConnection();
    DB::statement("CREATE DATABASE IF NOT EXISTS $dbName");
    DB::statement("GRANT ALL ON $dbName.* TO '$dbUsername'@'localhost' IDENTIFIED BY '$dbPassword'");
    DB::statement("FLUSH PRIVILEGES");
    DB::setDefaultConnection($defaultDB);
}

private function tableExists($connection, $table)
{
    return Schema::connection($connection->getConfig('name'))->hasTable($table);
}

private function createClientsTable($connection)
{
    $connection->statement('CREATE TABLE clients (
        Name VARCHAR(255),
        email VARCHAR(255),
        username VARCHAR(255),
        password VARCHAR(255),
        phone VARCHAR(20),
        dbName VARCHAR(255)
    );');
}

public function loginCompany(Request $request)
{
try {
    
    $validatedData = $request->validate([
        'username' => 'required|string|max:255',
        'password' => 'required|string|min:6',
    ]);


    $user = User::where('username', $validatedData['username'])->first();

    
    if (!$user) {
        return response()->json(['message' => 'Company not found'], 404);
    }

    if(!Hash::check($validatedData['password'], $user->password))
     {
         return response()->json(['message' => 'Invalid credentials'], 401);
     }


    Config::set('database.connections.dynamic', [
        'driver' => 'mysql',
        'host' => 'localhost', 
        'database' => $user->dbName,
        'username' => $validatedData['username'],
        'password' => $validatedData['password'],
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
        'strict' => true,
        'engine' => null,
    ]);
    $dynamicDB = DB::connection('dynamic');

    if (!$this->tableExists($dynamicDB, 'clients')) {
        return response()->json(['message' => 'Clients table not found'], 404);
    }
    $clientsData = $dynamicDB->table('clients')->get();

    foreach ($clientsData as $client) {
        $username = $client->username;
        $password = $client->password;
        $dbName = $client->dbName;
        $clientInfo[] = ['username' => $username, 'password' => $password, 'dbname' => $dbName];
    }
   
    Session::put('clients_data', $clientInfo);

    return response()->json(['data in session' => Session::get('clients_data')], 200);
} catch (Exception $e) {
    return response()->json(['message' => 'Company login failed. Please try again.'], 500);
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




public function addEmployee(Request $request)
{
try {
   
    $validatedData = $request->validate([
        'username' => 'required|string|max:255',
        'password' => 'required|string|min:6',
        'name' => 'required|string|max:255',
        'email' => 'required|string|email|max:255',
        'phone' => 'required|string|max:15',
        'designation' => 'required|string|max:255',
        'address' => 'required|string|max:255',           
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
    if (!$this->tableExists($dynamicDB, 'employees')) {
        $dynamicDB->statement("
            CREATE TABLE employees (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NULL,
                email VARCHAR(255) NULL,
                phone VARCHAR(15) NULL,
                password VARCHAR(255) NULL,
                status ENUM('active', 'inactive') NULL DEFAULT 'active',
                designation VARCHAR(255) NULL,
                address VARCHAR(255) NULL
            )
        ");
    }
    $dynamicDB->table('employees')->insert([
        'name' => $validatedData['name'],
        'email' => $validatedData['email'],
        'phone' => $validatedData['phone'],
        'password' => bcrypt($validatedData['password']),
        'status' => 'active',
        'designation' => $validatedData['designation'],
        'address' => $validatedData['address']
    ]);

    return response()->json(['message' => 'Employee added successfully'], 200);
} catch (Exception $e) {
    return response()->json(['message' => 'Error adding employee'], 500);
}
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


    
}
