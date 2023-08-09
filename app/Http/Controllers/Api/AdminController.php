<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;

use App\Models\Client;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;


use App\Http\Controllers\Api\AdminController;

//use App\Http\Controllers\Api\Auth;
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
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Session\Middleware\StartSession;


class AdminController extends Controller
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
        $user = auth()->user(); // Get the authenticated user
        $token = $user->createToken('token_key')->accessToken;

       // $request->session()->put('access_token', $token);

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
            // Validate the input data
            $validatedData = $request->validate([
                'dbName' => 'required|string|max:255',
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'username' => 'required|string|max:255',
                'password' => 'required|string|min:6',
                'phone' => 'required|string|max:20',
            ]);

           

            // Save data to the default database users table
            $user = new User;
            $user->name = $validatedData['name'];
            $user->email = $validatedData['email'];
            $user->password = bcrypt($validatedData['password']);
            // $user->password = $validatedData['password'];
            $user->dbName = $validatedData['dbName'];
            $user->username = $validatedData['username'];
            $user->save();

            // Create the dynamic database if not exists
            $dbName = $validatedData['dbName'];
            $dbUsername = $validatedData['username'];
            $dbPassword = $validatedData['password'];

            $this->createDynamicDatabase($dbName, $dbUsername, $dbPassword);

            // Connect to the dynamic database
            Config::set('database.connections.dynamic', [
                'driver' => 'mysql',
                'host' => 'localhost', // Set your database host
                'database' => $dbName,
                'username' => $dbUsername,
                'password' => $dbPassword,
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
                'strict' => true,
                'engine' => null,
            ]);

            // Use the dynamic database connection
            $dynamicDB = DB::connection('dynamic');

            // Check if the "clients" table exists, if not create it
            if (!$this->tableExists($dynamicDB, 'clients')) {
                $this->createClientsTable($dynamicDB);
            }

            // Insert the data into the "clients" table in the dynamic database
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
        // Use the default database connection to create the dynamic database
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







public function logoutSession(Request $request)
{
    session_start();
    // Clear the session data

    //unset($_SESSION['username']);
    session_unset();
    //unset($_SESSION["username"],$_SESSION["password"],$_SESSION["dbName"]);
    
    session_destroy();

    // Return a success response
    return response()->json(['message' => 'Logged out successfully'], 200);
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
    
        // Store data in the employees table using the query builder
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
          // Validate the input data
        $validatedData = $request->validate([
            'username' => 'required|string|max:255',
            'password' => 'required|string|min:6',           
        ]);

        // Fetch the company data from the default database users table
        $user = User::where('username', $validatedData['username'])->first();


        // Check if company exists and fetch the dbName
        if (!$user || !Hash::check($validatedData['password'], $user->password)) {
            return response()->json(['message' => 'Invalid credentials or company not found'], 401);
        }

        $dbName = $user->dbName;

        // Set database connection configuration for the dynamic database
        Config::set('database.connections.dynamic', [
            'driver' => 'mysql',
            'host' => 'localhost', // Set your database host
            'database' => $dbName, // The dynamic database name fetched from the users table
            'username' => $validatedData['username'], // Use dynamic database username from request
            'password' => $validatedData['password'], // Use dynamic database password from request
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
            'engine' => null,
        ]);

        // Use the dynamic database connection
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
        // Validate the input data
      $validatedData = $request->validate([
          'username' => 'required|string|max:255',
          'password' => 'required|string|min:6',           
      ]);

      // Fetch the company data from the default database users table
      $user = User::where('username', $validatedData['username'])->first();


      // Check if company exists and fetch the dbName
      if (!$user || !Hash::check($validatedData['password'], $user->password)) {
          return response()->json(['message' => 'Invalid credentials or company not found'], 401);
      }
      

      $dbName = $user->dbName;

      // Set database connection configuration for the dynamic database
      Config::set('database.connections.dynamic', [
          'driver' => 'mysql',
          'host' => 'localhost', // Set your database host
          'database' => $dbName, // The dynamic database name fetched from the users table
          'username' => $validatedData['username'], // Use dynamic database username from request
          'password' => $validatedData['password'], // Use dynamic database password from request
          'charset' => 'utf8mb4',
          'collation' => 'utf8mb4_unicode_ci',
          'prefix' => '',
          'strict' => true,
          'engine' => null,
      ]);

      // Use the dynamic database connection
      $dynamicDB = DB::connection('dynamic');
      $employee = $dynamicDB->table('employees')->find($id);

      return response()->json(['message' => 'employee', 'data' => $employee], 200);
      }  

      catch (Exception $e) {
          return response()->json(['message' => 'Error fetching employee'], 500);
      }
}




public function logincompany(Request $request)
    {
         // Start the session

      //$request->session()->start();

        // Validate the input data
    $validatedData = $request->validate([
        'username' => 'required|string|max:255',
        'password' => 'required|string|min:6',           
    ]);

    // Find the user by username
    $user = User::where('username', $validatedData['username'])->first();

    if ($user && Hash::check($validatedData['password'], $user->password)) {
        // If authentication is successful, fetch the dbName from the users table
        $dbName = $user->dbName;

        // Store the user's credentials and dbName in the session
        $this->storeSessionCredentials($request, $validatedData['username'], $validatedData['password'], $dbName);

        // Return a success response
        return response()->json(['message' => 'Login successful'], 200);
    } else {
        // If authentication fails, return an error response
        return response()->json(['message' => 'Invalid credentials'], 401);
    }
}

private function storeSessionCredentials($request, $username, $password, $dbName)
{
    // Set database connection configuration for the dynamic database
    Config::set('database.connections.dynamic', [
        'driver' => 'mysql',
        'host' => 'localhost', // Set your database host
        'database' => $dbName,
        'username' => $username,
        'password' => $password,
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
        'strict' => true,
        'engine' => null,
    ]);

    // Store the user's credentials and dbName in the session

    // $request->session()->put('username', $username);
    // $request->session()->put('password', $password);
    // $request->session()->put('dbName', $dbName);

           session_start(); 
           $_SESSION["username"] = $username;
           $_SESSION["password"] = $password;
           $_SESSION["dbName"] = $dbName;




}







public function addEmployee(Request $request)
{
    // Retrieve the credentials from the session
    session_start();

    if (isset($_SESSION["username"]) && isset($_SESSION["password"]) && isset($_SESSION["dbName"]))
    {
        $username = $_SESSION["username"];
        $password = $_SESSION["password"];
        $dbName = $_SESSION["dbName"];
    
        $passcode = Hash::make($password);

        
    // Use the stored credentials to connect to the dynamic database
    Config::set('database.connections.dynamic', [
        'driver' => 'mysql',
        'host' => 'localhost', // Set your database host
        'database' => $dbName,
        'username' => $username,
        'password' => $password,
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
        'strict' => true,
        'engine' => null,
    ]);

     // Check if the 'employees' table exists in the dynamic database
     if (!Schema::connection('dynamic')->hasTable('employees')) {
        // If the 'employees' table doesn't exist, create it
        Schema::connection('dynamic')->create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('username');
            $table->string('password');
            $table->string('designation');
            $table->string('address')->nullable();
            $table->timestamps(); // This will create created_at and updated_at columns
        });
    }

    //$now = now()->toDateString();

    $date = Carbon::createFromFormat('Y-m-d', '2023-08-08');

   
    // Now, insert the new Employee record in the 'employees' table
    $employee = DB::connection('dynamic')->table('employees')->insert([
        'name' => $request->input('name'),
        'email' => $request->input('email'),
        'username' => $username,
        'password' => $passcode,
        'designation' => $request->input('designation'),
        'address' => $request->input('address'),
        'created_at' => $date,
        'updated_at' => $date,
    ]);
    // Return a success response
    return response()->json(['message' => 'Employee added successfully', 'data' => $employee], 200);

      
    }
    else
    {
        return response()->json(['message' => 'Sorry Session out,to add pls login'], 400);
    }

   
 
    

    
}















 public function allEmployee(Request $request)
   {
   // Retrieve the credentials from the session
   session_start();
   if (isset($_SESSION["username"]) && $_SESSION["password"] && $_SESSION["dbName"])
   {
        $username = $_SESSION["username"];
        $password = $_SESSION["password"];
        $dbName = $_SESSION["dbName"];

    
   // Use the stored credentials to connect to the dynamic database
   Config::set('database.connections.dynamic', [
    'driver' => 'mysql',
    'host' => 'localhost', // Set your database host
    'database' => $dbName,
    'username' => $username,
    'password' => $password,
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix' => '',
    'strict' => true,
    'engine' => null,
]);
 // Check if the 'employees' table exists in the dynamic database
 if (!Schema::connection('dynamic')->hasTable('employees')) {
    // If the 'employees' table doesn't exist, return an error response
    return response()->json(['message' => 'Dynamic database table not found'], 404);
}

// Find the employee by ID in the 'employees' table
 $employee = DB::connection('dynamic')->table('employees')->get();

// If the employee is not found, return an error response
 if (!$employee) {
    return response()->json(['message' => 'Employee not found'], 404);
}

 // Return the employee data as a JSON response
 return response()->json(['message' => 'all Employee', 'data' => $employee]);




   }
   else
   {
    return response()->json(['message' => 'sorry session out,pls login']);
   }


    
  
   
}












 public function singleEmployee(Request $request, $employeeId)
{
 // Retrieve the credentials from the session
   session_start();
   
   if (isset($_SESSION["username"]) && isset($_SESSION["password"]) && isset($_SESSION["dbName"]))
   {
      
   $username = $_SESSION["username"];
   $password = $_SESSION["password"];
   $dbName = $_SESSION["dbName"];

   // Use the stored credentials to connect to the dynamic database
   Config::set('database.connections.dynamic', [
    'driver' => 'mysql',
    'host' => 'localhost', // Set your database host
    'database' => $dbName,
    'username' => $username,
    'password' => $password,
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix' => '',
    'strict' => true,
    'engine' => null,
]);
 // Check if the 'employees' table exists in the dynamic database
 if (!Schema::connection('dynamic')->hasTable('employees')) {
    // If the 'employees' table doesn't exist, return an error response
    return response()->json(['message' => 'Dynamic database table not found'], 404);
}

// Find the employee by ID in the 'employees' table
$employee = DB::connection('dynamic')->table('employees')->find($employeeId);

// If the employee is not found, return an error response
if (!$employee) {
    return response()->json(['message' => 'Employee not found'], 404);
}

// Return the employee data as a JSON response
return response()->json(['message' => 'Employee found', 'data' => $employee]);
  
 }
  else
  {
    return response()->json(['message' => 'sorry session out,pls login']);
  }

  
}









    public function editEmployee(Request $request, $employeeId)
{
    // Retrieve the credentials from the session
    session_start();

    if (isset($_SESSION["username"]) && isset($_SESSION["password"]) && isset($_SESSION["dbName"]))
    {
        $username = $_SESSION["username"];
        $password = $_SESSION["password"];
        $dbName = $_SESSION["dbName"];
       // echo $dbName;die();
       
    
        // Use the stored credentials to connect to the dynamic database
        Config::set('database.connections.dynamic', [
            'driver' => 'mysql',
            'host' => 'localhost', // Set your database host
            'database' => $dbName,
            'username' => $username,
            'password' => $password,
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
            'engine' => null,
        ]);
    
        // Check if the 'employees' table exists in the dynamic database
        if (!Schema::connection('dynamic')->hasTable('employees')) {
            // If the 'employees' table doesn't exist, return an error response
            return response()->json(['message' => 'Dynamic database table not found'], 404);
        }
    
        // Find the employee by ID in the 'employees' table
        $employee = DB::connection('dynamic')->table('employees')->find($employeeId);
    
        // If the employee is not found, return an error response
        if (!$employee) {
            return response()->json(['message' => 'Employee not found'], 404);
        }
    
        // Update the employee record in the 'employees' table
        DB::connection('dynamic')->table('employees')->where('id', $employeeId)->update([
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'designation' => $request->input('designation'),
            'address' => $request->input('address'),
        ]);
    
        // Return a success response
        return response()->json(['message' => 'Employee updated successfully through session'], 200);
    }
    else
    {
        return response()->json(['message' => 'sorry session out,pls login']);
    }
   
}

    

public function destroyEmployee(Request $request, $employeeId)
{
    // Retrieve the credentials from the session
    session_start();
    if (isset($_SESSION["username"]) && isset($_SESSION["password"]) && isset($_SESSION["dbName"]))
    {
        $username = $_SESSION["username"];
        $password = $_SESSION["password"];
        $dbName = $_SESSION["dbName"];

        // Use the stored credentials to connect to the dynamic database
    Config::set('database.connections.dynamic', [
        'driver' => 'mysql',
        'host' => 'localhost', // Set your database host
        'database' => $dbName,
        'username' => $username,
        'password' => $password,
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
        'strict' => true,
        'engine' => null,
    ]);

    // Check if the 'employees' table exists in the dynamic database
    if (!Schema::connection('dynamic')->hasTable('employees')) {
        // If the 'employees' table doesn't exist, return an error response
        return response()->json(['message' => 'Dynamic database table not found'], 404);
    }

    // Find the employee by ID in the 'employees' table
    $employee = DB::connection('dynamic')->table('employees')->find($employeeId);

    // If the employee is not found, return an error response
    if (!$employee) {
        return response()->json(['message' => 'Employee not found'], 404);
    }

    // Delete employee from the 'employees' table
    DB::connection('dynamic')->table('employees')->where('id', $employeeId)->delete();

    // Return a success response
    return response()->json(['message' => 'Employee deleted successfully through session']);
    
    }
    else
    {
        return response()->json(['message' => 'Sorry session out,pls login']);
    }
    
    
}





// public function profile(Request $request)
// {
//     {
//         // Retrieve the credentials from the session
//         session_start();

//         if (isset($_SESSION["username"]) && $_SESSION["password"] && $_SESSION["dbName"])
//         {
//              $username = $_SESSION["username"];
//              $password = $_SESSION["password"];
//              $dbName = $_SESSION["dbName"];
//         }

 
// }


public function profile(Request $request)
{
    session_start();
    if(isset($_SESSION["username"]) && isset($_SESSION["password"]) && isset($_SESSION["dbName"]))
    {
        $username = $_SESSION["username"];
        $dbName = $_SESSION["dbName"];
        $user = User::where('username',$username)
        ->where('dbName',$dbName)->first();
        
        if($user)
        {
           return response()->json(['message' => 'data found','data' => $user]);
        }
        else{
            return response()->json(['message' => 'profile details not found']);
            }
    }
    else 
    {
        return response()->json(['message' => 'Session out,pls login']);
    }
}









}
