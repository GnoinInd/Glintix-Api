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
    



     // public function register(Request $request)
    // {
    //     $validateData = $request->validate([
    //         'name' => 'required|string|min:3',
    //         'email' => 'required|email|unique:users',
    //         'password' => 'required|string|min:5',
    //     ]);

    //     $user = User::create([
    //         'name' => $request->name,
    //         'email' => $request->email,
    //         'password' => Hash::make($request->password)]);

    //     $token = $user->createToken('token_key')->accessToken;

    //     return response()->json([
    //         'token' =>$token,
    //         'user' => $user,
    //         'message' =>"User create successfully",
    //         'ststus' =>1
    //     ]);
    // }




// public function login(Request $request)
// {
//     $validateData = $request->validate([
//         'email' => 'required',
//         'password' => 'required'
//     ]);
//     $user = User::where(['email'=>$validateData['email'],'password' => $validateData['password']])->first();
//     $token = $user->createToken('token_key')->accessToken;

//     return response()->json([
//         'token' =>$token,
//         'user' =>$user,
//         'message' =>"Loggin successfully",
//         'status' =>1
//     ]);
// }






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







// public function login(Request $request)
// {
//     $validateData = Validator::make($request->all(), [
//         'email' => 'required',
//         'password' => 'required',
//     ]);

//     if ($validateData->fails()) {
//         return response()->json(['status' => 'fail', 'validation_errors' => $validateData->errors()], 422);
//     }

//     if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
//         $user = $request->user(); // Use $request->user() to get the authenticated user
//         $token = $user->createToken('token_key')->accessToken;
//         return response()->json(['status' => 'success', 'message' => 'User Login successfully', 'token' => $token]);
//     } else {
//         return response()->json(['status' => 'fail', 'message' => 'Username and password are invalid'], 401);
//     }
// }

 //
// public function registerCompany(Request $request)
// {
//     try {
//         // Validate the input data
//         $validator = Validator::make($request->all(), [
//             'name' => 'required|string',
//             'email' => 'required|email|unique:users',
//             'password' => 'required|string|min:6',
//             'company' => 'required|string',
//             'phone' => 'required|string',
//             'dbName' => 'required|string',
//         ]);

//         if ($validator->fails()) {
//             return response()->json(['errors' => $validator->errors()], 422);
//         }

       

//         // Create user in the default database (users table)
//         $user = new User([
//             'name' => $request->name,
//             'email' => $request->email,
//             'password' => Hash::make($request->password),
//             'dbName'   => $request->dbName,
//         ]);

//         $user->save();

//         // Dynamically create the database with the provided dbName
//         $dbName = $request->dbName;
//         DB::connection('mysql')->statement("CREATE DATABASE IF NOT EXISTS $dbName");

//         // Switch to the newly created database
//         config(["database.connections.mysql.database" => $dbName]);
//         DB::connection('mysql')->reconnect();

//         // Check if the 'clients' table exists in the dynamic database
//         if (!Schema::connection('mysql')->hasTable('clients')) {
//             // Create the 'clients' table in the newly created database
//             Schema::connection('mysql')->create('clients', function ($table) {
//                 $table->id();
//                 $table->string('name');
//                 $table->string('email')->unique();
//                 $table->string('password');
//                 $table->string('company');
//                 $table->string('phoneno');
//                 $table->string('dbName');
//                 $table->timestamps();
//             });
//         }
//         // Save dbName in the 'clients' table in the newly created database
//         DB::table('clients')->insert([
//             'name' => $request->name,
//             'email' => $request->email,
//             'password' => Hash::make($request->password),
//             'company' => $request->company,
//             'phoneno' => $request->phone,
//             'dbName' => $dbName,
         
//         ]);

//         return response()->json(['message' => 'Registration successful']);
//     } catch (\Exception $e) 
//     {
//         // Handle any exceptions that occur during the registration process
//         return response()->json(['message' => 'Registration failed. Please try again later.'], 500);
//     }

// }








// public function registerCompany(Request $request)
// {
//     try {
//         // Validate the input data
//         $validatedData = $request->validate([
//             'dbName' => 'required|string|max:255',
//             'name' => 'required|string|max:255',
//             'email' => 'required|email|unique:users,email',
//             'username' => 'required|string|max:255',
//             'password' => 'required|string|min:8',
//             'phone' => 'required|string|max:20',
           
           

             







//         ]);

//         // Create the dynamic database if not exists
//         $dbName = $validatedData['dbName'];
//         $dbUsername = $validatedData['username'];
//         $dbPassword = $validatedData['password'];

//         Config::set('database.connections.dynamic', [
//             'driver' => 'mysql',
//             'host' => 'localhost', // Set your database host
//             'database' => $dbName,
//             'username' => $dbUsername,
//             'password' => $dbPassword,
//             'charset' => 'utf8mb4',
//             'collation' => 'utf8mb4_unicode_ci',
//             'prefix' => '',
//             'strict' => true,
//             'engine' => null,
//         ]);

//         // Use the dynamic database connection
//         $dynamicDB = DB::connection('dynamic');

//         // Check if the dynamic database exists, if not create it
//         if (!$this->databaseExists($dbName)) {
//             $this->createDatabase($dbName);
//             $dynamicDB->reconnect(); // Reconnect to the newly created database
//         }

//         // Check if the "clients" table exists, if not create it
//         if (!$this->tableExists($dynamicDB, 'clients')) {
//             $this->createClientsTable($dynamicDB);
//         }

//         // Insert the data into the "clients" table in the dynamic database
//         $clientData = [
//             'Name' => $validatedData['name'],
//             'email' => $validatedData['email'],
//             'username' => $validatedData['username'],
//             'password' => $validatedData['password'],
//             'phone' => $validatedData['phone'],
//             'dbName' => $validatedData['dbName'],
            
//         ];
//         $dynamicDB->table('clients')->insert($clientData);

//         // Save the request data to the default database's user table
//         // Assuming your user table is named 'users'
//         $user = new \App\Models\User;
//         $user->name = $validatedData['username'];
//         $user->email = $validatedData['email'];
//         $user->password = bcrypt($validatedData['password']);
//         $user->dbName = $validatedData['dbName'];

//         $user->save();

//         return response()->json(['message' => 'Company registered successfully'], 201);
//     } catch (Exception $e) {
//         return response()->json(['message' => 'Company registration failed. Please try again.'], 500);
//     }
// }

// private function databaseExists($database)
// {
//     $connection = Config::get('database.default');
//     $databaseExists = DB::connection($connection)
//         ->select("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '$database'");

//     return count($databaseExists) > 0;
// }

// private function createDatabase($database)
// {
//     $connection = Config::get('database.default');
//     DB::connection($connection)->statement("CREATE DATABASE $database");
// }

// private function tableExists($connection, $table)
// {
//     return Schema::connection($connection->getConfig('name'))->hasTable($table);
// }

// private function createClientsTable($connection)
// {
//     $connection->statement('CREATE TABLE clients (
//         Name VARCHAR(255),
//         email VARCHAR(255),
//         username VARCHAR(255),
//         password VARCHAR(255),
//         phone VARCHAR(20),
//         dbName VARCHAR(255)
//     );');
// }










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









// public function registerCompany(Request $request)
// {
//     try {
//         // Validate the input data
//         $validatedData = $request->validate([
//             'dbName' => 'required|string|max:255',
//             'name' => 'required|string|max:255',
//             'email' => 'required|email|unique:users,email',
//             'username' => 'required|string|max:255',
//             'password' => 'required|string|min:6',
//             'phone' => 'required|string|max:20',
//         ]);

//         // Save data to the default database users table
//         $user = new User;
//         $user->name = $validatedData['name'];
//         $user->email = $validatedData['email'];
//         $user->password = bcrypt($validatedData['password']);
//         $user->dbName = $validatedData['dbName'];
//         $user->username = $validatedData['username'];
//         $user->save();

//         // Create the dynamic database if not exists
//         $dbName = $validatedData['dbName'];
//         $dbUsername = $validatedData['username'];
//         $dbPassword = $validatedData['password'];

//         // Step 1: Connect to the default database
//         $defaultConnection = DB::connection()->getConfig('database');

//         // Step 2: Create the dynamic database if it doesn't exist
//         $query = "CREATE DATABASE IF NOT EXISTS `$dbName`";
//         DB::connection($defaultConnection)->statement($query);

//         // Step 3: Switch to the dynamic database
//         config(['database.connections.dynamic.database' => $dbName]);
//         DB::reconnect('dynamic');

//         // Step 4: Create the 'clients' table if it doesn't exist
//         $query = "CREATE TABLE IF NOT EXISTS clients (
//             id INT AUTO_INCREMENT PRIMARY KEY,
//             name VARCHAR(255) NULL,
//             email VARCHAR(255) NULL,
//             username VARCHAR(255) NULL,
//             password VARCHAR(255) NULL,
//             phone VARCHAR(20) NULL,
//             dbName VARCHAR(255) NULL
//         )";
//         DB::connection('dynamic')->statement($query);

//         // Insert the data into the 'clients' table
//         DB::connection('dynamic')->table('clients')->insert([
//             'name' => $validatedData['name'],
//             'email' => $validatedData['email'],
//             'username' => $validatedData['username'],
//             'password' => bcrypt($validatedData['password']),
//             'phone' => $validatedData['phone'],
//             'dbName' => $validatedData['dbName'],
//         ]);

//         return response()->json(['message' => 'Company registered successfully']);
//     } catch (\Exception $e) {
//         return response()->json(['message' => 'Failed to register company', 'error' => $e->getMessage()], 500);
//     }
// }



















// public function registerCompany(Request $request)
// {
//     try {
//         DB::beginTransaction(); // Start the transaction

//         // Validate the input data
//         $validatedData = $request->validate([
//             'dbName' => 'required|string|max:255',
//             'name' => 'required|string|max:255',
//             'email' => 'required|email|unique:users,email',
//             'username' => 'required|string|max:255',
//             'password' => 'required|string|min:6',
//             'phone' => 'required|string|max:20',
//         ]);

//         // Save data to the default database users table
//         $user = new User;
//         $user->name = $validatedData['name'];
//         $user->email = $validatedData['email'];
//         $user->password = bcrypt($validatedData['password']);
//         $user->dbName = $validatedData['dbName'];
//         $user->username = $validatedData['username'];
//         $user->save();

//         // Step 1: Create the dynamic database if not exists
//         $dbName = $validatedData['dbName'];
//         $dbUsername = $validatedData['username'];
//         $dbPassword = $validatedData['password'];

//         $query = "CREATE DATABASE IF NOT EXISTS `$dbName`";
//         DB::connection('mysql')->statement($query);

//         // Step 2: Set the username and password for the dynamic database
//         $query = "GRANT ALL ON `$dbName`.* TO '$dbUsername'@'localhost' IDENTIFIED BY '$dbPassword'";
//         DB::connection('mysql')->statement($query);

//         // Step 3: Switch to the dynamic database
//         config(['database.connections.dynamic.database' => $dbName]);
//         DB::reconnect('dynamic');

//         // Step 4: Create the 'clients' table if it doesn't exist
//         $query = "CREATE TABLE IF NOT EXISTS clients (
//             id INT AUTO_INCREMENT PRIMARY KEY,
//             name VARCHAR(255) NOT NULL,
//             email VARCHAR(255) NOT NULL,
//             username VARCHAR(255) NOT NULL,
//             password VARCHAR(255) NOT NULL,
//             phone VARCHAR(20) NOT NULL,
//             dbName VARCHAR(255) NOT NULL
//         )";
//         DB::connection('dynamic')->statement($query);

//         // Insert the data into the 'clients' table
//         DB::connection('dynamic')->table('clients')->insert([
//             'name' => $validatedData['name'],
//             'email' => $validatedData['email'],
//             'username' => $validatedData['username'],
//             'password' => bcrypt($validatedData['password']),
//             'phone' => $validatedData['phone'],
//             'dbName' => $validatedData['dbName'],
//         ]);

//         DB::commit(); // Commit the transaction as everything was successful

//         return response()->json(['message' => 'Company registered successfully']);
//     } catch (\Illuminate\Validation\ValidationException $e) {
//         // Handle validation errors
//         DB::rollBack(); // Roll back the transaction if validation fails
//         $errors = $e->validator->errors()->all();
//         return response()->json(['message' => 'Validation failed', 'errors' => $errors], Response::HTTP_BAD_REQUEST);
//     } catch (\Exception $e) {
//         // Handle other exceptions
//         DB::rollBack(); // Roll back the transaction if an exception occurs
//         return response()->json(['message' => 'Failed to register company', 'error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
//     }
// }






















//     public function loginCompany(Request $request)
// {
//     try {
//         // Validate the input data
//         $validatedData = $request->validate([
//             'username' => 'required|string|max:255',
//             'password' => 'required|string|min:6',
//         ]);
   

//         // Fetch the company data from the default database users table
//         $user = User::where('username', $validatedData['username'])->first();
    
//         // Check if company exists
//         if (!$user) {
//             return response()->json(['message' => 'Company not found'], 404);
//         }
   
//         // Verify company's password
//         if (!password_verify($validatedData['password'], $user->password)) {
//             return response()->json(['message' => 'Invalid credentials'], 401);
//         }
  
//         // Set database connection configuration for dynamic database
//         Config::set('database.connections.dynamic', [
//             'driver' => 'mysql',
//             'host' => 'localhost', // Set your database host
//             'database' => $user->dbName,
//             'username' => $user->username, // Use dynamic database username
//             'password' => $user->password, // Use dynamic database password
//             'charset' => 'utf8mb4',
//             'collation' => 'utf8mb4_unicode_ci',
//             'prefix' => '',
//             'strict' => true,
//             'engine' => null,
//         ]);


//         // Use the dynamic database connection
//         $dynamicDB = DB::connection('dynamic');
   

//         // Check if the "clients" table exists in the dynamic database
//         if (!$this->tableExists($dynamicDB, 'clients'))
//         {
//             return response()->json(['message' => 'Clients table not found'], 404);
//         }
    
//         echo $user->dbName;
//         die();

//         // Fetch clients data from the "clients" table in the dynamic database
//         $clientsData = $dynamicDB->table('clients')->get();

//         return response()->json(['data' => $clientsData], 200);
//     } catch (Exception $e) {
//         return response()->json(['message' => 'Company login failed. Please try again.'], 500);
//     }
// }





//    public function loginCompany(Request $request)
//    {
//     try {
//         // Set database connection configuration for the dynamic database
//         Config::set('database.connections.dynamic', [
//             'driver' => 'mysql',
//             'host' => 'localhost', // Set your database host
//             'database' => 'tcs_1234', // The database name (dbName)
//             'username' => 'tcsemployee', // The database username
//             'password' => '123456', // The database password
//             'charset' => 'utf8mb4',
//             'collation' => 'utf8mb4_unicode_ci',
//             'prefix' => '',
//             'strict' => true,
//             'engine' => null,
//         ]);

//          // Use the dynamic database connection
//     $dynamicDB = DB::connection('dynamic');

//     // Check if the "clients" table exists in the dynamic database
//     if (!$this->tableExists($dynamicDB, 'clients')) {
//         return response()->json(['message' => 'Clients table not found'], 404);
//     }

//     // Fetch clients data from the "clients" table in the dynamic database
//     $clientsData = $dynamicDB->table('clients')->get();

//     return response()->json(['data' => $clientsData], 200);
// } catch (Exception $e) {
//     return response()->json(['message' => 'Error fetching data from the dynamic database'], 500);
// }

//    }






// public function loginCompany(Request $request)
// {
//     try {
//         // Validate the input data
//         $validatedData = $request->validate([
//             'username' => 'required|string|max:255',
//             'password' => 'required|string|min:6',
//         ]);

//         // Fetch the company data from the default database users table
//         $user = User::where('username', $validatedData['username'])->first();

//         // Check if company exists
//         if (!$user) {
//             return response()->json(['message' => 'Company not found'], 404);
//         }

//         // Verify company's password
//         if (!password_verify($validatedData['password'], $user->password)) {
//             return response()->json(['message' => 'Invalid credentials'], 401);
//         }

//         // Set database connection configuration for the dynamic database
//         Config::set('database.connections.dynamic', [
//             'driver' => 'mysql',
//             'host' => 'localhost', // Set your database host
//             'database' => $user->dbName, // The database name (dbName) from the user
//             'username' => $validatedData['username'], // Use dynamic database username from request
//             'password' => $validatedData['password'], // Use dynamic database password from request
//             'charset' => 'utf8mb4',
//             'collation' => 'utf8mb4_unicode_ci',
//             'prefix' => '',
//             'strict' => true,
//             'engine' => null,
//         ]);

//         // Use the dynamic database connection
//         $dynamicDB = DB::connection('dynamic');

//         // Check if the "clients" table exists in the dynamic database
//         if (!$this->tableExists($dynamicDB, 'clients')) {
//             return response()->json(['message' => 'Clients table not found'], 404);
//         }

//         // Fetch clients data from the "clients" table in the dynamic database
//         $clientsData = $dynamicDB->table('clients')->get();
 
//         // Store $clientsData in the session with a 2-minute expiration time
//         $expirationTime = Carbon::now()->addMinutes(2);
//         Session::put('clients_data', $clientsData, $expirationTime);

   

//         return response()->json(['data' => $clientsData], 200);
//     } catch (Exception $e) {
//         return response()->json(['message' => 'Company login failed. Please try again.'], 500);
//     }
// }







public function loginCompany(Request $request)
{
try {
    // Validate the input data
    $validatedData = $request->validate([
        'username' => 'required|string|max:255',
        'password' => 'required|string|min:6',
    ]);

    // Fetch the company data from the default database users table
    $user = User::where('username', $validatedData['username'])->first();

    // Check if company exists
    if (!$user) {
        return response()->json(['message' => 'Company not found'], 404);
    }

    if(!Hash::check($validatedData['password'], $user->password))
     {
         return response()->json(['message' => 'Invalid credentials'], 401);
     }

    // Set database connection configuration for the dynamic database
    Config::set('database.connections.dynamic', [
        'driver' => 'mysql',
        'host' => 'localhost', // Set your database host
        'database' => $user->dbName, // The database name (dbName) from the user
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

    // Check if the "clients" table exists in the dynamic database
    if (!$this->tableExists($dynamicDB, 'clients')) {
        return response()->json(['message' => 'Clients table not found'], 404);
    }

    // Fetch clients data from the "clients" table in the dynamic database
    $clientsData = $dynamicDB->table('clients')->get();

    foreach ($clientsData as $client) {
        $username = $client->username;
        $password = $client->password;
        $dbName = $client->dbName;
        $clientInfo[] = ['username' => $username, 'password' => $password, 'dbname' => $dbName];
    }
    // return response()->json(['message' => 'Client data', 'data' => $clientInfo], 200);
    // die();
    

// Store $clientsData in the session with a 2-minute expiration time

    //$expirationTime = Carbon::now()->addMinutes(2);
    Session::put('clients_data', $clientInfo);
    //Session::put('clients_data_expiration', $expirationTime);

    // Return JSON response with the session data
    return response()->json(['data in session' => Session::get('clients_data')], 200);
} catch (Exception $e) {
    return response()->json(['message' => 'Company login failed. Please try again.'], 500);
}
}




public function logoutSession(Request $request)
{

try {
    // Clear the user's session data (logout the user)
    Auth::logout();

    // Clear any data stored in the session
    $request->session()->flush();

    // Return a JSON response indicating successful logout
    return response()->json(['message' => 'Logout successful'], 200);
} catch (\Exception $e) {
    // Handle any potential exceptions or errors during logout
    return response()->json(['message' => 'Logout failed', 'error' => $e->getMessage()], 500);
}
}




// public function logoutSession(Request $request)
// {
//     try {
//         // Perform any other necessary logout tasks, e.g., logging out the user.
//         Auth::logout();

//         // Perform any other cleanup or custom session-related actions in the 'check.login' middleware.

//         // Return a JSON response indicating successful logout
//         return response()->json(['message' => 'Logout successful'], 200);
//     } catch (\Exception $e) {
//         // Handle any potential exceptions or errors during logout
//         return response()->json(['message' => 'Logout failed', 'error' => $e->getMessage()], 500);
//     }
// }





// public function loginCompany(Request $request)
// {
//     try {
//         // Validate the input data
//         $validatedData = $request->validate([
//             'username' => 'required|string|max:255',
//             'password' => 'required|string|min:6',
//         ]);

//         // Fetch the company data from the default database users table
//         $user = User::where('username', $validatedData['username'])->first();

//         // Check if company exists
//         if (!$user) {
//             return response()->json(['message' => 'Company not found'], 404);
//         }
    
//         // Verify company's password
    
//         // if (!password_verify($validatedData['password'], $user->password)) {
//         //     return response()->json(['message' => 'Invalid credentials'], 401);
//         // }


//         if(!Hash::check($validatedData['password'], $user->password))
//         {
//             return response()->json(['message' => 'Invalid credentials'], 401);
//         }

//         // Set database connection configuration for the dynamic database
//         Config::set('database.connections.dynamic', [
//             'driver' => 'mysql',
//             'host' => 'localhost', // Set your database host
//             'database' => $user->dbName, // The database name (dbName) from the user
//             'username' => $validatedData['username'], // Use dynamic database username from request
//             'password' => $validatedData['password'], // Use dynamic database password from request
//             'charset' => 'utf8mb4',
//             'collation' => 'utf8mb4_unicode_ci',
//             'prefix' => '',
//             'strict' => true,
//             'engine' => null,
//         ]);

//         // Use the dynamic database connection
//         $dynamicDB = DB::connection('dynamic');

//         // Check if the "clients" table exists in the dynamic database
//         if (!$this->tableExists($dynamicDB, 'clients')) {
//             return response()->json(['message' => 'Clients table not found'], 404);
//         }

//         // Check if the session data has expired
//         $expirationTime = Session::get('clients_data_expiration');
//         if ($expirationTime && Carbon::now()->greaterThan($expirationTime)) {
//             // Clear the session data if it has expired
//             Session::forget('clients_data');
//             Session::forget('clients_data_expiration');
//             return response()->json(['data' => []], 200);
//         }

//         // Fetch clients data from the "clients" table in the dynamic database
//         $clientsData = $dynamicDB->table('clients')->get();

//         // Store $clientsData in the session with a 1-minute expiration time
//         $expirationTime = Carbon::now()->addMinutes(1);
//         Session::put('clients_data', $clientsData);
//         Session::put('clients_data_expiration', $expirationTime);

//         // Return JSON response with the session data
//         return response()->json(['data' => Session::get('clients_data')], 200);
//     } catch (Exception $e) {
//         return response()->json(['message' => 'Company login failed. Please try again.'], 500);
//     }
// }






















// public function registerCompany(Request $request)
// {
//     try {
//         // Validate the input data
//         $validator = Validator::make($request->all(), [
//             'name' => 'required|string',
//             'email' => 'required|email|unique:users',
//             'password' => 'required|string|min:6',
//             'company' => 'required|string',
//             'phone' => 'required|string',
//         ]);

//         if ($validator->fails()) {
//             return response()->json(['errors' => $validator->errors()], 422);
//         }

//         // Generate a random string to append to the dbName
//         $randomString = Str::random(8);
//         $dbName = $request->dbName . '_' . $randomString;

//         // Create user in the default database (users table)
//         $user = new User([
//             'name' => $request->name,
//             'email' => $request->email,
//             'password' => Hash::make($request->password),
//             'dbName' => $dbName,
//         ]);

//         $user->save();

//         // Dynamically create the database with the provided dbName
//         DB::connection('mysql')->statement("CREATE DATABASE IF NOT EXISTS $dbName");

//         // Switch to the newly created database
//         config(["database.connections.mysql.database" => $dbName]);
//         DB::connection('mysql')->reconnect();

//         // Check if the 'clients' table exists in the dynamic database
//         if (!Schema::connection('mysql')->hasTable('clients')) {
//             // Create the 'clients' table in the newly created database
//             Schema::connection('mysql')->create('clients', function ($table) {
//                 $table->id();
//                 $table->string('name');
//                 $table->string('email')->unique();
//                 $table->string('password');
//                 $table->string('company');
//                 $table->string('phoneno');
//                 $table->string('dbName');
//                 $table->timestamps();
//             });
//         }
//         // Save dbName in the 'clients' table in the newly created database
//         DB::table('clients')->insert([
//             'name' => $request->name,
//             'email' => $request->email,
            
           
//             'phoneno' => $request->phone,
//             'address' => $request->address,
           
//         ]);

//         return response()->json(['message' => 'Registration successful']);
//     } catch (\Exception $e) {
//         // Handle any exceptions that occur during the registration process
//         return response()->json(['message' => 'Registration failed. Please try again later.'], 500);
//     }
// }






// public function logincompany(Request $request)
// {
//     try {
//         // Validate the input data
//         $validator = Validator::make($request->all(), [
//             'email' => 'required|email',
//             'password' => 'required|string',
//         ]);

//         if ($validator->fails()) {
//             return response()->json(['errors' => $validator->errors()], 422);
//         }

//         // Retrieve the user by email from the default database (users table)
//         $user = User::where('email', $request->email)->first();

//         // Check if the user exists and the password matches
//         if ($user && Hash::check($request->password, $user->password)) {
//             // Get the dbName associated with the user from the default database
//             $dbName = $user->dbName;

//             // Switch to the dynamic database using the retrieved dbName
//             config(["database.connections.mysql.database" => $dbName]);
//             DB::connection('mysql')->reconnect();

//             // Create the 'employees' table if it doesn't exist
//             if (!Schema::connection('mysql')->hasTable('employees')) {
//                 Schema::connection('mysql')->create('employees', function ($table) {
//                     $table->id();
//                     $table->string('name');
//                     $table->string('email')->unique();
//                     $table->string('address');
//                     $table->string('phone');
//                     $table->timestamps();
//                 });
//             }

//             // Now, you can perform operations on the dynamic database

//             return response()->json(['message' => 'Login successful']);
//         } else {
//             return response()->json(['message' => 'Invalid credentials'], 401);
//         }
//     } catch (\Exception $e) {
//         // Handle any exceptions that occur during the login process
//         return response()->json(['message' => 'Login failed. Please try again later.'], 500);
//     }
// }



// public function logincompany(Request $request)
// {
//     try {
//         // Validate the input data
//         $validator = Validator::make($request->all(), [
//             'email' => 'required|email',
//             'password' => 'required|string',
//         ]);

//         if ($validator->fails()) {
//             return response()->json(['errors' => $validator->errors()], 422);
//         }

//         // Retrieve the user by email from the default database (users table)
//         $user = User::where('email', $request->email)->first();

//         // Check if the user exists and the password matches in the default database
//         if ($user && Hash::check($request->password, $user->password))
//          {
//             // Get the dbName associated with the user from the default database
//             $dbName = $user->dbName;
            

//             // Switch to the dynamic database using the retrieved dbName
//             config(["database.connections.mysql.database" => $dbName]);
//             DB::connection('mysql')->reconnect();

//             // Now, you are connected to the dynamic database, so check email and password again
//             // Assuming the 'clients' table exists in the dynamic database
//             $client = DB::table('clients')->where('email', $request->email)->first();

//             // Check if the client exists and the password matches in the dynamic database
//             if ($client && Hash::check($request->password, $client->password)) 
//             {
//                 // Check if the 'employees' table exists in the dynamic database
//                 if (!Schema::connection('mysql')->hasTable('employees')) 
//                 {
//                     // Create the 'employees' table in the newly created database
//                     Schema::connection('mysql')->create('employees', function ($table) 
//                     {
//                         $table->id();
//                         $table->string('name');
//                         $table->string('email')->unique();
//                         $table->string('address');
//                         $table->string('phone');
//                         $table->timestamps();
//                     });
//                 }   
                             
              

//                 // Now, you can perform operations on the dynamic database

//                 return response()->json(['message' => 'Login successful' ]);
//             }
//              else 
//             {
//                 return response()->json(['message' => 'Invalid credentials'], 401);
//             }
//         } 
//         else 
//         {
//             return response()->json(['message' => 'Invalid credentials'], 401);
//         }
//     } catch (\Exception $e) {
//         // Handle any exceptions that occur during the login process
//         return response()->json(['message' => 'Login failed. Please try again later.'], 500);
//     }
// }





// public function logincompany(Request $request)
// {
//     try {
//         // Validate the input data
//         $validator = Validator::make($request->all(), [
//             'email' => 'required|email',
//             'password' => 'required|string',
//         ]);

//         if ($validator->fails()) {
//             return response()->json(['errors' => $validator->errors()], 422);
//         }

//         // Retrieve the user by email from the default database (users table)
//         $user = User::where('email', $request->email)->first();

//         // Check if the user exists and the password matches in the default database
//         if ($user && Hash::check($request->password, $user->password))
//          {
//             // Get the dbName associated with the user from the default database
//             $dbName = $user->dbName;
            

//             // Switch to the dynamic database using the retrieved dbName
//             config(["database.connections.mysql.database" => $dbName]);
//             DB::connection('mysql')->reconnect();

//             // Now, you are connected to the dynamic database, so check email and password again
//             // Assuming the 'clients' table exists in the dynamic database
//             $client = DB::table('clients')->where('email', $request->email)->first();

//             // Check if the client exists and the password matches in the dynamic database
//             if ($client && Hash::check($request->password, $client->password)) 
//             {
             
//                 $clients = DB::table('clients')->get();

//                 // Store the clients data in the session

//                  session(['clients_data' => $clients]);
//                  session()->put('expiration_time', now()->addMinutes(2));
//                  $sessionData = session()->all();

//                 // Now, you can perform operations on the dynamic database

//                 return response()->json(['message' => 'Login successful', 'clients' => $sessionData]);         
              
                
//             }
//              else 
//             {
//                 return response()->json(['message' => 'Invalid credentials'], 401);
//             }
//         } 
//         else 
//         {
//             return response()->json(['message' => 'Invalid credentials'], 401);
//         }
//     } catch (\Exception $e) {
//         // Handle any exceptions that occur during the login process
//         return response()->json(['message' => 'Login failed. Please try again later.'], 500);
//     }
// }





// public function logincompany(Request $request)
// {
//     try {
//         // Validate the input data
//         $validator = Validator::make($request->all(), [
//             'email' => 'required|email',
//             'password' => 'required|string',
//         ]);

//         if ($validator->fails()) {
//             return response()->json(['errors' => $validator->errors()], 422);
//         }

//         // Retrieve the user by email from the default database (users table)
//         $user = User::where('email', $request->email)->first();

//         // Check if the user exists and the password matches in the default database
//         if ($user && Hash::check($request->password, $user->password)) 
//         {
//             // Get the dbName associated with the user from the default database
//             $dbName = $user->dbName;

//             // Switch to the dynamic database using the retrieved dbName
//             config(["database.connections.mysql.database" => $dbName]);
//             DB::connection('mysql')->reconnect();

//             // Now, you are connected to the dynamic database, so check email and password again
//             // Assuming the 'clients' table exists in the dynamic database
//             $client = DB::table('clients')->where('email', $request->email)->first();

//             // Check if the client exists and the password matches in the dynamic database
//             if ($client && Hash::check($request->password, $client->password)) 
//             {  
//                      $clients = DB::table('clients')->get();

//                      //$datas = session()->put('user_details',$clients);
//                      //$data = session()->all();

//                      session()->put('name',$client->name);
//                      $name = session('name');
//                      session()->put('phone',$client->phoneno);
//                      $phone = session('phone');
//                      session()->put('company', $client->company);
//                      $company = session('company');
                   
                     
//                      // Set the session expiration time to 2 minutes from now
//                     session()->put('expiration_time', now()->addMinutes(2));

//                 // Now, you can perform operations on the dynamic database
//                 return response()->json(['message' => 'Login successful and data in session',
//                  'clients Name' => $name,'clients phone number' => $phone,'company' => $company]);         
//             }
//             else 
//             {
//                 return response()->json(['message' => 'Invalid credentials'], 401);
//             }
//         } 
//         else 
//         {
//             return response()->json(['message' => 'Invalid credentials'], 401);
//         }
//     } catch (\Exception $e) {
//         // Handle any exceptions that occur during the login process
//         return response()->json(['message' => 'Login failed. Please try again later.'], 500);
//     }

    
// }







// public function logincompany(Request $request)
// {
//     try {
//         // Validate the input data
//         $validator = Validator::make($request->all(), [
//             'email' => 'required|email',
//             'password' => 'required|string',
//         ]);

//         if ($validator->fails()) {
//             return response()->json(['errors' => $validator->errors()], 422);
//         }

//         // Retrieve the user by email from the default database (users table)
//         $user = User::where('email', $request->email)->first();

//         // Check if the user exists and the password matches in the default database
//         if ($user && Hash::check($request->password, $user->password)) 
//         {
//             // Get the dbName associated with the user from the default database
//             $dbName = $user->dbName;

//             // Switch to the dynamic database using the retrieved dbName
//             config(["database.connections.mysql.database" => $dbName]);
//             DB::connection('mysql')->reconnect();

//             // Now, you are connected to the dynamic database, so check email and password again
//             // Assuming the 'clients' table exists in the dynamic database
//             $client = DB::table('clients')->where('email', $request->email)->first();

//             // Check if the client exists and the password matches in the dynamic database
//             if ($client && Hash::check($request->password, $client->password)) 
//             {  
//                 $clients = DB::table('clients')->get();

//                 // Store specific data in the session
//                 session()->put('name', $client->name);
//                 session()->put('phone', $client->phoneno);
//                 session()->put('company', $client->company);

//                 // Set the session expiration time to 2 minutes from now
//                 session()->put('expiration_time', now()->addMinutes(2));

//                 // Now, you can perform operations on the dynamic database
//                 $response = response()->json(['message' => 'Login successful and data in session',
//                     'clients Name' => session('name'),
//                     'clients phone number' => session('phone'),
//                     'company' => session('company')
//                 ]);

//                 // Check if the session has expired
//                 if (session('expiration_time') < now()) {
//                     // Session has expired, clear the session data
//                     session()->destroy('name');
//                     session()->destroy('phone');
//                     session()->destroy('company');

//                     return response()->json(['message' => 'Session expired'], 401);
//                 }

//                 return $response;        
//             }
//             else 
//             {
//                 return response()->json(['message' => 'Invalid credentials'], 401);
//             }
//         } 
//         else 
//         {
//             return response()->json(['message' => 'Invalid credentials'], 401);
//         }
//     } catch (\Exception $e) {
//         // Handle any exceptions that occur during the login process
//         return response()->json(['message' => 'Login failed. Please try again later.'], 500);
//     }
// }











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











// public function logincompany(Request $request)
// {
//     try {
//         // Validate the input data
//         $validator = Validator::make($request->all(), [
//             'email' => 'required|email',
//             'password' => 'required|string',
//         ]);

//         if ($validator->fails()) {
//             return response()->json(['errors' => $validator->errors()], 422);
//         }

//         // Retrieve the user by email from the default database (users table)
//         $user = User::where('email', $request->email)->first();

//         // Check if the user exists and the password matches in the default database
//         if ($user && Hash::check($request->password, $user->password)) 
//         {
//             // Get the dbName associated with the user from the default database
//             $dbName = $user->dbName;

//             // Switch to the dynamic database using the retrieved dbName
//             config(["database.connections.mysql.database" => $dbName]);
//             DB::connection('mysql')->reconnect();

//             // Now, you are connected to the dynamic database, so check email and password again
//             // Assuming the 'clients' table exists in the dynamic database
//             $client = DB::table('clients')->where('email', $request->email)->first();

//             // Check if the client exists and the password matches in the dynamic database
//             if ($client && Hash::check($request->password, $client->password)) 
//             {  
//                 //$clients = DB::table('clients')->get();
//                 // Store the clients data in the session
//                 session()->put('clients_data', $client);

//                 // Set the session expiration time to 2 minutes from now
//                 session()->put('expiration_time', now()->addMinutes(2)->timestamp);

//                 // Now, you can perform operations on the dynamic database
//                 return response()->json(['message' => 'Login successful', 'clients' => $client]);         
//             }
//             else 
//             {
//                 return response()->json(['message' => 'Invalid credentials'], 401);
//             }
//         } 
//         else 
//         {
//             return response()->json(['message' => 'Invalid credentials'], 401);
//         }
//     } catch (\Exception $e) {
//         // Handle any exceptions that occur during the login process
//         return response()->json(['message' => 'Login failed. Please try again later.'], 500);
//     }
// }















public function addEmployee(Request $request)
{
try {
    // Validate the input data
    $validatedData = $request->validate([
        'username' => 'required|string|max:255',
        'password' => 'required|string|min:6',
        'name' => 'required|string|max:255',
        'email' => 'required|string|email|max:255',
        'phone' => 'required|string|max:15',
        'designation' => 'required|string|max:255',
        'address' => 'required|string|max:255',           
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

    // Check if the "employees" table exists in the dynamic database
    if (!$this->tableExists($dynamicDB, 'employees')) {
        // Create the "employees" table with the required fields
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

    // Insert the new employee data into the "employees" table
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


    
}
