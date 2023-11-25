<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Exports\ThreeMonthsRecordexport;

use App\Models\Client;
//use Mail;
// use Illuminate\Mail\Mailable;
 use Illuminate\Support\Facades\Mail;
 use App\Mail\LeaveMail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Response;

use Illuminate\Support\Facades\URL;

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
//use Illuminate\Http\Response;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Session\Middleware\StartSession;
use App\Models\PasswordReset;



class AdminController extends Controller
{



public function register(Request $request)
    {
        $validatedData = Validator::make($request->all(),[
        'name'=>'required|min:3',
        'email' => 'required|email|unique:users',
        'password' => 'required|min:5'
        ]);
        if ($validatedData->fails()) {
            return response()->json(['message' => 'Validation failed'], 422);
        }
    

        $data = $request->all();
        $data['password'] = Hash::make($request->password);

        $user = User::create($data);

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
                'Name' => $request->input('name'),
                'email' => $request->input('email'),
                'username' => $request->input('username'),
                'password' =>$request->input('password'),
                'phone' => $request->input('phone'),
                'dbName' => $request->input('dbName'),
            ];
            $dynamicDB->table('clients')->insert($clientData);

            return response()->json(['message' => 'Company registered successfully'], 201);
        } catch (Exception $e) {
            return response()->json(['message' => 'Company registration failed. Please try again.'], 500);
        }
    }


    // private function createDynamicDatabase($dbName, $dbUsername, $dbPassword)
    // {
    //     // Use the default database connection to create the dynamic database
    //     $defaultDB = DB::getDefaultConnection();
    
    //     // Create the database if it doesn't exist
    //     $createDatabaseQuery = "CREATE DATABASE IF NOT EXISTS ?";
    //     $createDatabaseBindings = [$dbName];
    //     DB::statement($createDatabaseQuery, $createDatabaseBindings);
    
    //     // Grant privileges to the user for the dynamic database
    //     $grantQuery = "GRANT ALL ON ?.* TO ?@'localhost' IDENTIFIED BY ?";
    //     $grantBindings = [$dbName, $dbUsername, $dbPassword];
    //     DB::statement($grantQuery, $grantBindings);
    
    //     // Flush privileges
    //     DB::statement("FLUSH PRIVILEGES");
    
    //     // Switch back to the default database connection
    //     DB::setDefaultConnection($defaultDB);
    // }
    


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
        // $connection->statement('CREATE TABLE clients (
        //     Name VARCHAR(255),
        //     email VARCHAR(255),
        //     username VARCHAR(255),
        //     password VARCHAR(255),
        //     phone VARCHAR(20),
        //     dbName VARCHAR(255)
        // );');




        if (!$connection->getSchemaBuilder()->hasTable('clients')) {
            $connection->getSchemaBuilder()->create('clients', function (Blueprint $table) {
                $table->id();
                $table->string('Name', 255);
                $table->string('email', 255);
                $table->string('username', 255);
                $table->string('password', 255);
                $table->string('phone', 20);
                $table->string('dbName', 255);
                $table->timestamps();
            });
        }


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
        return response()->json(['success' => true, 'message' => 'Login Successfully' ], 200);
    } else {
        // If authentication fails, return an error response
        return response()->json(['success' => false, 'message' => 'Invalid credentials'], 401);
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
           return response()->json(['success' => true ,'message' => 'data found','data' => $user]);
        }
        else{
            return response()->json(['success' => false, 'message' => 'profile details not found']);
            }
    }
    else 
    {
        return response()->json(['message' => 'Session out,pls login']);
    }
}



public function forgetpassword(Request $request)
{
        
         $validatedData = $request->validate([
            'email' => 'required|email',
                     
        ]);
  try
  {
    $user = User::where('email',$request->email)->get();
    
    if(count($user) > 0)
    
   {
        
      $token = str::random(30);
      $domain = URL::to('/');
      $url = $domain.'/reset-password?token='.$token;
      $data['url'] = $url;
      $data['email'] = $request->email;
      $data['title'] = 'Password Reset';
      $data['body'] = 'Please click on below link to reset your password';


      
      Mail::send('forget-password-mail', ['data' => $data],function($message) use ($data) {
        
         $message->to($data['email'])->subject($data['title']);

    });


    // $to_name = 'gemsfiem@gmail.com';
    // $to_email = 'gnoinhrms@gmail.com';
    // $data = array('name'=>"Ogbonna Vitalis(sender_name)", "body" => "A test mail");

    // Mail::send('emailsmail', $data, function($message) use ($to_name, $to_email) {
    //     $message->to($to_email, $to_name)
    //     ->subject('Laravel Test Mail');
    //     $message->from('gnoinhrms@gmail.com','Test Mail');
    //     });
    
    
      

      $datetime = Carbon::now()->timezone('Asia/Kolkata')->format('Y-m-d H:i:s');
      PasswordReset::updateOrCreate(
        ['email' => $request->email],
        ['email' => $request->email,
        'token' => $token,
        'created_at' => $datetime
        ] 
      );
      
      return response()->json(['success' => 'pls check your mail to reset your password']);

    }
    else
    {
        return response()->json(['success' => false, 'msg' => 'User not found']);
    }
  

  }
  catch(\Exception $e)
  {
    Log::error('Email sending error: ' . $e->getMessage());
    
    // return response()->json(['success' => false, 'msg' => 'Email sending failed']);
    
   return response()->json(['success' => false,'msg'=>$e->getMessage()]);
  }



}






public function resetpasswordLoad(Request $request)
    
{
    $token = $request->token;

    
    // $resetData = PasswordReset::where('token',$request->token)->get();
    $userData = DB::table('password_resets')->where('token',$token)->get();
    
    
  
    
   
    if(isset($request->token) && count($userData) > 0)
    {
     //$user = User::where('email',$resetData[0]['email'])->get();
    // $userData = DB::table('password_resets')->where('token',$token)->get();
    $usersData = DB::table('password_resets')->where('token',$token)->value('email');
     
      return view('resetPassword',compact('usersData'));
    }
    else
    {
    return view('404');
    }
}





public function resetPassword(Request $request)
{
    $request->validate([
   'password' => 'required|string|min:6|confirmed'
    ]);
   $email = $request->email;
    $userUpdate = DB::table('users')->where('email',$email)->value('id');
    //dd($userUpdate);
    $user = User::find($userUpdate);
//    dd($user->id);
   $user->password = Hash::make($request->password);
   $user->save();

   PasswordReset::where('email',$email)->delete();
   return "<h1> Your Password has been reset Successfully </h1>";
    
}








public function addEmployee(Request $request)
{

    $validatedData = $request->validate([
          //basic details
        'name' => 'required',
        'email' => 'required|email',
        'designation' => 'required|string|min:6',
        'address' => 'required',

        // 'gender' => 'required',
        // 'official_mail' => 'nullable',
        // 'd.o.b' => 'required',
        // 'joining_date' => 'required',
        // 'office_shift' => 'required',
        // 'contact_number' => 'required',
        // 'reference_contact_number' => 'nullable',
        
        // 'marital_status' => 'required',
        // 'nationality' => 'required',
        // 'highest_qualification' => 'required',
        // 'univercity' => 'required',
        // 'passing_year' => 'required',
        // 'marks' => 'nullable',
        // 'grade' => 'required',
        // 'resume_upload' => 'nullable',
        // 'adhar_no' => 'required',
        // 'pan_no' =>'required',
        // 'voter_id' => 'required',
        // 'driving_licence' => 'required',
        // 'passport_no' => 'nullable',
        // 'passport_to' => 'nullable',
        // 'passport_from' => 'nullable',
        // 'blood_group' => 'required',

        // 'father_name' => 'required',
        // 'father_contact_no' => 'nullable',
        // 'father_adhar_no' => 'required',
        // 'mother_name' => 'required',

        // 'bank_name' => 'required',
        // 'account_no' => 'required',
        // 'ifsc' => 'required',
    ]);
  

    session_start();

try {
    if (isset($_SESSION["username"]) && isset($_SESSION["password"]) && isset($_SESSION["dbName"])) {
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
            
            // $table->enum('gender',['male','female','other']);
            // $table->string('official_mail');
            // $table->date('dob');
            // $table->date('joining_date');
            // $table->enum('office_shift',['day','night']);  //
            // $table->integer('contact_number');
            // $table->integer('reference_contact_number');
            // $table->enum('marital_status',['single','married']);
            // $table->string('nationality');
            // $table->string('highest_qualification');
            // $table->string('univercity');
            // $table->string('passing_year');
            // $table->float('marks');
            // $table->string('grade');
            // $table->string('resume_upload');
            // $table->integer('adhar_no');
            // $table->string('pan_no');
            // $table->string('voter_id');
            // $table->string('driving_licence');
            // $table->string('passport_no');
            // $table->date('passport_to');
            // $table->date('passport_from');
            // $table->string('blood_group');
            // $table->string('father_name');
            // $table->integer('father_contact_no');
            // $table->integer('father_adhar_no');
            // $table->string('mother_name');
            // $table->string('bank_name');
            // $table->string('account_no');
            // $table->string('ifsc');

            // $table->date('hiredate');
            // $table->unsignedBigInteger('dept_id');
            // $table->decimal('salary', 10, 2)->default(0.00);
            // $table->decimal('bonus', 10, 2)->default(0.00);
            // $table->unsignedBigInteger('ctc');
            $table->string('designation');
            $table->string('address')->nullable();
            // $table->unsignedBigInteger('project_id')->nullable();
            $table->timestamps(); 
    
            // Define the foreign key constraint for project_id
            // $table->foreign('project_id')->references('id')->on('projects');
    
           
        });
    }



        // $date = Carbon::createFromFormat('Y-m-d', '2023-08-08');
        $date = Carbon::now()->timezone('Asia/Kolkata')->format('Y-m-d H:i:s');

        // Now, insert the new Employee record in the 'employees' table
        $employee = DB::connection('dynamic')->table('employees')->insert([
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'username' => $username,
            'password' => $passcode,
            'designation' => $request->input('designation'),
            'address' => $request->input('address'),

            // 'gender' => $request->input('gender'),
            // 'official_mail' => $request->input('official_mail'),
            // 'dob' => $request->input('d.o.b' ),
            // 'joining_date' => $request->input('joining_date'),
            // 'office_shift' =>$request->input('office_shift'),
            // 'contact_number' => $request->input('contact_number'),
            // 'reference_contact_number' => $request->input('reference_contact_number'),
            // 'marital_status' => $request->input('marital_status'),
            // 'nationality' => $request->input('nationality'),
            // 'highest_qualification' => $request->input('highest_qualification'),
            // 'univercity' => $request->input('univercity'),
            // 'passing_year' => $request->input('passing_year'),
            // 'marks' => $request->input('marks'),
            // 'grade'  => $request->input('grade'),
            // 'resume_upload' => $request->input('resume_upload'),
            // 'adhar_no' => $request->input('adhar_no'),
            // 'pan_no' => $request->input('pan_no'),
            // 'voter_id' => $request->input('voter_id'),
            // 'driving_licence' => $request->input('driving_licence'),
            // 'passport_no' => $request->input('passport_no'),
            // 'passport_to' => $request->input('passport_to'),
            // 'passport_from' => $request->input('passport_from'),
            // 'blood_group' => $request->input('blood_group'),
            // 'father_name' => $request->input('father_name'),
            // 'father_contact_no' => $request->input('father_contact_no'),
            // 'father_adhar_no' => $request->input('father_adhar_no'),
            // 'mother_name' => $request->input('mother_name'),
            // 'bank_name' => $request->input('bank_name'),
            // 'account_no' => $request->input('account_no'),
            // 'ifsc' => $request->input('ifsc'), 


            'created_at' => $date,
            'updated_at' => $date,
        ]);
        // Return a success response
        return response()->json(['message' => 'Employee added successfully', 'data' => $employee], 200);
    } else {
        return response()->json(['message' => 'Sorry Session out, to add please login'], 400);
    }
} catch (Exception $e) {
    // Handle exceptions here, e.g., log the error or return an error response
    return response()->json(['message' => 'An error occurred: ' . $e->getMessage()], 500);
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
        $date = Carbon::now()->timezone('Asia/kolkata')->format('Y-m-d H:i:s');
        // Update the employee record in the 'employees' table
        DB::connection('dynamic')->table('employees')->where('id', $employeeId)->update([
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'designation' => $request->input('designation'),
            'address' => $request->input('address'),
            'updated_at' => $date,
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











public function searchEmpByValue(Request $request)
{
   try
   {
     
    $validatedData = $request->validate([
        // 'option' => 'required|in:name,email,designation,address',
        'option' => 'required',
        'value' => 'required',
        
    ]);

    session_start();
    if(isset($_SESSION["username"]) && isset($_SESSION["password"]) && isset($_SESSION["dbName"]))
    {
       $username = $_SESSION["username"];
       $password = $_SESSION["password"];
       $dbName = $_SESSION["dbName"];

       $option = $validatedData['option'];
       $value = $validatedData['value'];
       
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
    
     $dynamicDB = DB::connection('dynamic');
     $results = $dynamicDB->table('employees')->where($option,'like', '%' . $value . '%')->get();
    // $employee = DB::connection('dynamic')->table('employees')->find($employeeId);
    // $results = Employee::where($option, 'like', '%' . $value . '%')->get();
    if( count($results) > 0)
    {
        return response()->json(['data' => $results]);
    }
    return response()->json(['message' => 'Data not Found']);

    }

    return response()-json(['message' => 'pls login first']);
   }

   catch(\Exception $e)
   {
    return response()->json(['error' => $e->getMessage()]);
   }
  

    
}


public function latestMember(Request $request)
{
    session_start();
    if(isset($_SESSION["username"]) && isset($_SESSION["password"]) && isset($_SESSION["dbName"]))
    {
       $username = $_SESSION["username"];
       $password = $_SESSION["password"];
       $dbName = $_SESSION["dbName"];

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
    
     $dynamicDB = DB::connection('dynamic');

    $recentEmp = $dynamicDB->table('employees')->orderBy('created_at','desc')->limit(10)->get();
    return response()->json(['data' => $recentEmp]);

    }
    return response()->json(['message' => 'pls login']);
}





















// public function loginEmp(Request $request)
// {
//     try {
//         $validatedData = $request->validate([
//          'username' => 'required',
//          'userpassword' => 'required',
//          'email' => 'required|email',
//          'empPassword' => 'required|string|min:6',
//                  ]);

//                  $username = $validatedData['username'];
//                  $password = $validatedData['userpassword'];
//                  $empEmail = $validatedData['email'];
//                  $empPassword = $validatedData['empPassword'];
                 

//          $check = User::where('username', $username)->first();
         
          

//         if ($check && Hash::check($password, $check->password)) 
//         {
//             $dbName = $check->dbName;
            
//                // Set database connection
//                Config::set('database.connections.dynamic', [
//                 'driver' => 'mysql',
//                 'host' => 'localhost', 
//                 'database' => $dbName, 
//                 'username' => $username,
//                 'password' => $password, 
//                 'charset' => 'utf8mb4',
//                 'collation' => 'utf8mb4_unicode_ci',
//                 'prefix' => '',
//                 'strict' => true,
//                 'engine' => null,
//             ]);
    
//             // Use the dynamic database connection
//             $dynamicDB = DB::connection('dynamic');
             
          
            


//             $checkEmp = $dynamicDB->table('employees')->where('email',$empEmail)->get();
//             $checkPass = $dynamicDB->table('employees')->where('password',$empPassword)->get();
//             echo $checkEmp;die();
//              if($checkEmp && $checkPass)
//             // if($checkEmp->count() > 0 && $checkPass->count() > 0)
//             {
//                return response()->json(['Success' =>'true','Message'=> 'Login Successful']);
//             }
//             else{
//                 return response()->json(['Success' =>'false','Message'=> 'Email and Password is incorrect']);
//             }
                
//         //   $currentDatabaseName = DB::connection()->getDatabaseName();
//         //   echo "Current Database Name: " . $currentDatabaseName;die();

//             // if ($checkEmp && Hash::check($empPassword, $checkEmp->password)) {
//             //     return response()->json(['success' => 'true', 'message' => 'Login Successful']);
//             // }

         
//         } 
//         return response()->json(['meaasge' => 'Connection not possible']);
//     } catch (\Exception $e) {
//         return response()->json(['success' => 'false', 'error' => $e->getMessage()]);
//     }
// }









public function applyLeave(Request $request)
{
    $validatedData = $request->validate([
        'leavetype' => 'required',
        'startdate' => 'required|date', 
        'enddate' => 'required|date', 
        'reason' => 'required',  
        // 'attachment' => 'nullable',
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

        $date = Carbon::now()->timezone('Asia/Kolkata')->format('Y-m-d H:i:s');

        // Connect to dynamic database
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
            return response()->json(['message' => 'Employee not found'],404);
        }
        $id = $currentEmp->id;
        $name = $currentEmp->name;
        
            
          if (!$dynamicDB->getSchemaBuilder()->hasTable('leaves')) {
            $dynamicDB->getSchemaBuilder()->create('leaves', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('employee_id');
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();
                // $table->enum('leave_type',['sick leave','earned leave','casual leave','other'])->nullable();
                $table->string('leave_type')->nullable();
                $table->integer('duration')->nullable();
                $table->string('reason')->nullable();
                $table->enum('status', ['pending', 'approved','reject'])->default('pending');
                // $table->string('why_reject')->nullable();
                $table->string('approved_by')->nullable();
                $table->date('approval_date')->nullable();
                $table->string('attachment')->nullable();
                $table->date('date');
                $table->timestamps();

                // Define the foreign key constraint
                $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            });
        }

          $currentTime = Carbon::now()->timezone('Asia/Kolkata')->format('H:i:s');

          $start_date = Carbon::parse($validatedData['startdate']);
          $end_date = Carbon::parse($validatedData['enddate']);
          $duration = $end_date->diffInDays($start_date);
          $updateDuration = $duration + 1 ;

          try{
            $data = [
                'employee_id' => $id,
                'start_date' => $validatedData['startdate'],
                'end_date' => $validatedData['enddate'],
                'leave_type' => $validatedData['leavetype'],
                'reason' => $validatedData['reason'],
                'duration' => $updateDuration,
                'created_at' => $date, 
                'updated_at' => $date,
                'date' => now()->toDateString(),
            ];
            $dynamicDB->table('leaves')->insert($data);
            // $details = ['title' => "This is leave Application", 'message' => 'My Employee id is '.$id .'. I am applying for '.$validatedData['leavetype'].'leave'];
            $details = ['title' => "Leave Application",'applicantName' => $name,'designation' => $currentEmp->designation,
            'leave_type'=>$validatedData['leavetype'],'startdate' =>$validatedData['startdate'],'enddate' =>$validatedData['enddate'],
            'days' => $updateDuration,'reason' => $validatedData['reason'],'date' =>now()->toDateString()];
            // Mail::to($email)->send(new LeaveMail($details));
            Mail::to("gemsfiem@gmail.com")->send(new LeaveMail($details)); 
            
            return response()->json(['message' => 'employee leave request created successfully']);
          }
          catch (\Exception $e) {
            // Handle the exception here, you can log the error or return an error response.
            return response()->json(['error' => $e->getMessage()], 500);
        }  
       

    }
     return response()->json(['message' => 'Access Denied']);

}




public function approveLeave(Request $request)
{
    $validatedData = $request->validate([
        'employee_id' => 'required|integer',
        'status' => 'required|string']);
    session_start();
    if(isset($_SESSION["username"]) && isset($_SESSION["password"]) && isset($_SESSION["dbName"]))
    {
        $username = $_SESSION["username"];
        $password = $_SESSION["password"];
        $dbName = $_SESSION["dbName"];

        $date = Carbon::now()->timezone('Asia/Kolkata')->format('Y-m-d H:i:s');
        $empId = $request->input('employee_id');
        $status = $request->input('status');
        

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
        
            
            $user = User::where('username',$username)->first();
            $role = $user->role;
            if($user)
            {
                $employee = $dynamicDB->table('employees')->where('id', $empId)->first();
                if ($employee) {
                    // $designation = $employee->designation;
            
                  $dynamicDB->table('leaves')
                  ->where('employee_id', $empId) 
                  ->update([
                      'status' => $status,
                      'approved_by' => $role,
                      'updated_at' => $date,
                        ]);
                        return response()->json(['message' => 'leave status updated']);
                }
                return response()->json(['message' => 'employee not found']);

            
            
            }
            return response()->json(['message' => 'user not found']);
            
            
        
           
       

    }
}










public function datewiseAttend(Request $request, $date)
{
    session_start();
    if (isset($_SESSION["username"]) && isset($_SESSION["password"]) && isset($_SESSION["dbName"])) {
        $username = $_SESSION["username"];
        $dbName = $_SESSION["dbName"];
        $user = User::where('username', $username)
            ->where('dbName', $dbName)->first();

        if ($user) {

            // Connect to dynamic database
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
            $allAttend = $dynamicDB->table('attendences')->whereDate('date', $date)->get();
            if ($allAttend->count() > 0) {
                return response()->json(['message' => 'Datewise all Employee Attendence', 'data' => $allAttend]);
            } else {
                return response()->json(['message' => 'No data found']);
            }
        } else {
            return response()->json(['message' => 'Profile details not found']);
        }
    } else {
        return response()->json(['message' => 'Session out, please login']);
    }
}







public function monthwiseAttend(Request $request, $year, $month)
{
    session_start();
    if (isset($_SESSION["username"]) && isset($_SESSION["password"]) && isset($_SESSION["dbName"])) {
        $username = $_SESSION["username"];
        $dbName = $_SESSION["dbName"];
        $user = User::where('username', $username)
            ->where('dbName', $dbName)->first();

        if ($user) {

            // Connect to dynamic database
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
            
            // Query attendance data for the specific month and year
            $startOfMonth = Carbon::createFromDate($year, $month, 1)->startOfMonth();
            $endOfMonth = Carbon::createFromDate($year, $month, 1)->endOfMonth();
            
            $monthwiseAttend = $dynamicDB->table('attendences')
                ->whereBetween('date', [$startOfMonth, $endOfMonth])
                ->get();
            
            if ($monthwiseAttend->count() > 0) {
                return response()->json(['message' => 'Monthwise Employee Attendance', 'data' => $monthwiseAttend]);
            } else {
                return response()->json(['message' => 'No data found']);
            }
        } else {
            return response()->json(['message' => 'Profile details not found']);
        }
    } else {
        return response()->json(['message' => 'Session out, please login']);
    }
}














// public function idWise(Request $request,$id) //admin check attendence id wise
// {

//     session_start();
//     if(isset($_SESSION["username"]) && isset($_SESSION["password"]) && isset($_SESSION["dbName"]))
//     {
//         $username = $_SESSION["username"];
//         $dbName = $_SESSION["dbName"];
//         $user = User::where('username',$username)
//         ->where('dbName',$dbName)->first();
        
//         if($user)
//         {
           
//            // Connect to dynamic database
//          Config::set('database.connections.dynamic', [
//             'driver' => 'mysql',
//             'host' => 'localhost',
//             'database' => $dbName,
//             'username' => $username,
//             'password' => $password,
//             'charset' => 'utf8mb4',
//             'collation' => 'utf8mb4_unicode_ci',
//             'prefix' => '',
//             'strict' => true,
//             'engine' => null,
//         ]);
//         $dynamicDB = DB::connection('dynamic');
//         $allAttend = $dynamicDB->table('attendences')->whereDate('date',$date)->get();
//         if($allAtend)
//         {
//        return response()->json(['message' => 'Datewise all Employee Attendence','data' => $allAttend]);
//         }
//        else
//        {
//            return response()->json(['message' => 'No data found']);
//        }
//         }
//         else
//         {
//             return response()->json(['message' => 'profile details not found']);
//          }
//     }
//     else 
//     {
//         return response()->json(['message' => 'Session out,pls login']);
//     }

 
  

// }








public function idWiseMonthwiseAttend(Request $request, $year, $month, $employeeId)
{
    session_start();
    if (isset($_SESSION["username"]) && isset($_SESSION["password"]) && isset($_SESSION["dbName"])) {
        $username = $_SESSION["username"];
        $dbName = $_SESSION["dbName"];
        $user = User::where('username', $username)
            ->where('dbName', $dbName)->first();

        if ($user) {

            // Connect to dynamic database
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
            
            // Query attendance data for the specific month, year, and employee ID
            $startOfMonth = Carbon::createFromDate($year, $month, 1)->startOfMonth();
            $endOfMonth = Carbon::createFromDate($year, $month, 1)->endOfMonth();
            
            $idWiseMonthwiseAttend = $dynamicDB->table('attendences')
                ->where('employee_id', $employeeId)
                ->whereBetween('date', [$startOfMonth, $endOfMonth])
                ->get();
            
            if ($idWiseMonthwiseAttend->count() > 0) {
                return response()->json(['message' => 'ID Wise Monthwise Employee Attendance', 'data' => $idWiseMonthwiseAttend]);
            } else {
                return response()->json(['message' => 'No data found']);
            }
        } else {
            return response()->json(['message' => 'Profile details not found']);
        }
    } else {
        return response()->json(['message' => 'Session out, please login']);
    }
}






public function addHoliday(Request $request)
{
    session_start();
    if (isset($_SESSION["username"]) && isset($_SESSION["password"]) && isset($_SESSION["dbName"])) {
        $username = $_SESSION["username"];
        $password = $_SESSION["password"];
        $dbName = $_SESSION["dbName"];

        $user = User::where('username', $username)->first();
        $email = $user->email;

        // Connect to dynamic database
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

        try {
            $table = 'holidays';
            if (!$dynamicDB->getSchemaBuilder()->hasTable($table)) {
                $dynamicDB->getSchemaBuilder()->create($table, function (Blueprint $table) {
                    $table->id();
                    $table->date('holiday');
                    $table->timestamps();
                });
            }

            $holidayDates = $request->input('holidays'); // Frontend side select option name = holidays
            foreach ($holidayDates as $date) {
                $dynamicDB->table($table)->insert([
                    'holiday' => $date
                ]);
            }
            return response()->json(['message' => 'Holidays added successfully']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    return response()->json(['message' => 'Please login']);
}


 












public function workingDay(Request $request)
{
    $validatedData = $request->validate([
        'working-days' => 'required',  //mon - sat or mon - fri or mon - sun
        'shift' => 'required',         //dayshift or nightshift  
    ]);     

    session_start();
    if(isset($_SESSION["username"]) && isset($_SESSION["password"]) && isset($_SESSION["dbName"]))
    {
        
        $username = $_SESSION["username"];
        $password = $_SESSION["password"];
        $dbName = $_SESSION["dbName"];
        $user = User::where('username',$username)
        ->where('dbName',$dbName)->first();
        
        
 if($user)
 {
   
    // Connect to dynamic database
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


        
         if (!$dynamicDB->getSchemaBuilder()->hasTable('workings')) {
            $dynamicDB->getSchemaBuilder()->create('workings', function (Blueprint $table) {
                $table->id();
                
                $table->time('working_days')->nullable();
                $table->time('weekoff')->nullable();
                $table->timestamps();
              
            });
            // return response()->json(['message' => 'table create successfully']);
        }
        // calculation will start here
        return response()->json(['message' => 'working table data will create from here']);
      
}

     return response()->json(['message' => 'user not found']);


 

    }
    return response()->json(['message' => 'pls login']);


   
}


 







// public function calculateWorkingDays(Request $request)
// {
//     $workingDaysOption = $request->input('workingDaysOption');
//     $startDate = Carbon::parse($request->input('startDate'));
//     $endDate = Carbon::parse($request->input('endDate'));

//     $holidays = Holiday::pluck('holiday')->toArray(); // Fetch holidays from the table

//     $workingDaysCount = $this->calculateWorkingDaysBetweenDates($startDate, $endDate, $workingDaysOption, $holidays);

//     return response()->json(['workingDays' => $workingDaysCount]);
// }

// private function calculateWorkingDaysBetweenDates($startDate, $endDate, $workingDaysOption, $holidays)
// {
//     // Initialize the working days count
//     $workingDays = 0;

//     // Loop through each date between the start and end dates
//     while ($startDate <= $endDate) {
//         // Check if the current date is a weekend (Saturday or Sunday)
//         $isWeekend = $startDate->isWeekend();

//         // Check if the current date is a holiday
//         $isHoliday = in_array($startDate->format('Y-m-d'), $holidays);

//         // Determine if the current date is a working day based on the selected option
//         if ($workingDaysOption === 'monday_to_friday' && !$isWeekend && !$isHoliday) {
//             $workingDays++;
//         } elseif ($workingDaysOption === 'monday_to_saturday' && $startDate->dayOfWeek !== Carbon::SUNDAY && !$isHoliday) {
//             $workingDays++;
//         }

//         // Move to the next day
//         $startDate->addDay();
//     }

//     return $workingDays;
// }







// public function calculateAndStoreWorkingDays(Request $request)
// {
//     try {
//         // Retrieve user inputs
//         $workingDaysOption = $request->input('workingDaysOption');
//         $startDate = Carbon::parse($request->input('startDate'));
//         $endDate = Carbon::parse($request->input('endDate'));

//         session_start();
//         if (isset($_SESSION["username"]) && isset($_SESSION["password"]) && isset($_SESSION["dbName"])) {
//             $username = $_SESSION["username"];
//             $password = $_SESSION["password"];
//             $dbName = $_SESSION["dbName"];
//         }

//         // Dynamically set the database connection for the dynamic database
//         Config::set('database.connections.dynamic', [
//             'driver' => 'mysql',
//             'host' => 'localhost',
//             'database' => $dbName,
//             'username' => $username,
//             'password' => $password,
//             'charset' => 'utf8mb4',
//             'collation' => 'utf8mb4_unicode_ci',
//             'prefix' => '',
//             'strict' => true,
//             'engine' => null,
//         ]);

//         $dynamicDB = DB::connection('dynamic');

//         // Check if the dynamic table exists, and create it if not
//         if (!Schema::connection('dynamic')->hasTable('workings')) {
//             Schema::connection('dynamic')->create('workings', function (Blueprint $table) {
//                 $table->id();
//                 $table->string('month');
//                 $table->integer('working_days')->nullable();
//                 $table->timestamps();
//             });
//         }

//         // Calculate and store working days for each month
//         for ($month = $startDate->month; $month <= $endDate->month; $month++) {
//             $daysInMonth = Carbon::createFromDate($startDate->year, $month)->daysInMonth;
//             $startOfMonth = $month === $startDate->month ? $startDate->day : 1;
//             $endOfMonth = $month === $endDate->month ? $endDate->day : $daysInMonth;

//             $workingDaysCount = $this->calculateWorkingDaysBetweenDates(
//                 Carbon::create($startDate->year, $month, $startOfMonth),
//                 Carbon::create($startDate->year, $month, $endOfMonth),
//                 $workingDaysOption,
//                 []
//             );

//             // Store the calculated working days in the dynamic table
//             $dynamicDB->table('workings')->insert([
//                 'month' => Carbon::create($startDate->year, $month)->format('F'),
//                 'working_days' => $workingDaysCount,
//                 'created_at' => now(),
//                 'updated_at' => now()
//             ]);
//         }

//         return response()->json(['message' => 'Working days calculated and stored successfully']);
//     } catch (\Exception $e) {
//         return response()->json(['error' => $e->getMessage()], 500);
//     }
// }











// public function calculateAndStoreWorkingDays(Request $request)
//     {
//         try {
//             // Retrieve user inputs
//             $workingDaysOption = $request->input('workingDaysOption');
//             $startDate = Carbon::parse($request->input('startDate'));
//             $endDate = Carbon::parse($request->input('endDate'));

//             // ... Session and database connection setup ...

//             $dynamicDB = DB::connection('dynamic');

//             // Check if the dynamic table exists, and create it if not
//             if (!Schema::connection('dynamic')->hasTable('workings')) {
//                 Schema::connection('dynamic')->create('workings', function (Blueprint $table) {
//                     $table->id();
//                     $table->string('month');
//                     $table->integer('working_days')->nullable();
//                     $table->timestamps();
//                 });
//             }

//             // Calculate and store working days for each month
//             for ($month = $startDate->month; $month <= $endDate->month; $month++) {
//                 $daysInMonth = Carbon::createFromDate($startDate->year, $month)->daysInMonth;
//                 $startOfMonth = $month === $startDate->month ? $startDate->day : 1;
//                 $endOfMonth = $month === $endDate->month ? $endDate->day : $daysInMonth;

//                 $workingDaysCount = $this->calculateWorkingDaysBetweenDates(
//                     Carbon::create($startDate->year, $month, $startOfMonth),
//                     Carbon::create($startDate->year, $month, $endOfMonth),
//                     $workingDaysOption,
//                     []
//                 );

//                 // Store the calculated working days in the dynamic table
//                 $dynamicDB->table('workings')->insert([
//                     'month' => Carbon::create($startDate->year, $month)->format('F'),
//                     'working_days' => $workingDaysCount,
//                     'created_at' => now(),
//                     'updated_at' => now()
//                 ]);
//             }

//             return response()->json(['message' => 'Working days calculated and stored successfully']);
//         } catch (\Exception $e) {
//             return response()->json(['error' => $e->getMessage()], 500);
//         }
//     }

//     private function calculateWorkingDaysBetweenDates($startDate, $endDate, $workingDaysOption, $holidays)
//     {
//         $workingDays = 0;
//         $currentDate = $startDate;
    
//         while ($currentDate <= $endDate) {
//             // Check if the current date is a working day based on the working days option,
//             // weekends, and holidays. Increment $workingDays if it's a working day.
//             if ($this->isWorkingDay($currentDate, $workingDaysOption, $holidays)) {
//                 $workingDays++;
//             }
            
//             $currentDate->addDay();
//         }
    
//         return $workingDays;
//     }
    
//     private function isWorkingDay($date, $workingDaysOption, $holidays)
//     {
//         // Implement your logic here to determine if a given date is a working day.
//         // Consider working days option, weekends, and holidays.
        
//         // Placeholder logic: Check if the day is not a weekend (Saturday or Sunday)
//         // and is not a holiday.
//         if ($date->isWeekday() && !in_array($date->format('Y-m-d'), $holidays)) {
//             return true;
//         }
        
//         return false;
//     }







// public function calculateAndStoreWorkingDays(Request $request)
// {
//     try {
//         // Retrieve user inputs
//         $workingDaysOption = $request->input('workingDaysOption');
//         $startDate = Carbon::parse($request->input('startDate'));
//         $endDate = Carbon::parse($request->input('endDate'));

//         // Check if session variables are set
//         session_start();
//         if (
//             isset($_SESSION["username"]) &&
//             isset($_SESSION["password"]) &&
//             isset($_SESSION["dbName"])
//         ) {
//             $username = $_SESSION["username"];
//             $password = $_SESSION["password"];
//             $dbName = $_SESSION["dbName"];

//             // Dynamically set the database connection for the dynamic database
//             Config::set('database.connections.dynamic', [
//                 'driver' => 'mysql',
//                 'host' => 'localhost',
//                 'database' => $dbName,
//                 'username' => $username,
//                 'password' => $password,
//                 'charset' => 'utf8mb4',
//                 'collation' => 'utf8mb4_unicode_ci',
//                 'prefix' => '',
//                 'strict' => true,
//                 'engine' => null,
//             ]);

//             $dynamicDB = DB::connection('dynamic');

//             // Check if the dynamic table exists, and create it if not
//             if (!$dynamicDB->getSchemaBuilder()->hasTable('workings')) {
//                 $dynamicDB->getSchemaBuilder()->create('workings', function (Blueprint $table) {
//                     $table->id();
//                     $table->integer('year');
//                     $table->integer('month');
//                     $table->integer('working_days');
//                     $table->integer('weekends');
//                     $table->integer('holidays');
//                     $table->timestamps();
//                 });
//             }

//             // Calculate and store working days for each month
//             while ($startDate <= $endDate) {
//                 $year = $startDate->year;
//                 $month = $startDate->month;

//                 $weekendDays = $this->calculateWeekendDays($workingDaysOption);
//                 $holidays = $this->getHolidaysForMonth($dynamicDB, $year, $month);

//                 $workingDaysCount = $this->calculateWorkingDays($startDate, $weekendDays, $holidays);

//                 // Store the calculated data in the dynamic table
//                 $dynamicDB->table('workings')->insert([
//                     'year' => $year,
//                     'month' => $month,
//                     'working_days' => $workingDaysCount,
//                     'weekends' => $weekendDays,
//                     'holidays' => count($holidays),
//                     'created_at' => now(),
//                     'updated_at' => now(),
//                 ]);

//                 $startDate->addMonth(); // Move to the next month
//             }

//             return response()->json(['message' => 'Working days calculated and stored successfully']);
//         } else {
//             return response()->json(['message' => 'Please login'], 401);
//         }
//     } catch (\Exception $e) {
//         return response()->json(['error' => $e->getMessage()], 500);
//     }
// }

// // Other methods remain the same...

// private function getHolidaysForMonth($dynamicDB, $year, $month)
// {
//     // Fetch holidays for the specified year and month from the dynamic database
//     $holidays = $dynamicDB->table('holidays')
//         ->whereYear('holiday', $year)
//         ->whereMonth('holiday', $month)
//         ->pluck('holiday')
//         ->toArray();

//     return $holidays;
// }

// // Other methods remain the same...












public function calculateAndStoreWorkingDays(Request $request)
{
    try {
        // Retrieve user inputs

        // $workingDaysOption = $request->input('workingDaysOption');
        // $startDate = Carbon::parse($request->input('startDate'));
        // $endDate = Carbon::parse($request->input('endDate'));
        $start = '2023-06-10';
        $end = '2023-11-30';
        $workingDaysOption = 'mon-fri';
        $startDate = Carbon::parse($start); //2023-06-01 00:00:00
        $endDate = Carbon::parse($end);
       

        session_start();
        if (
            isset($_SESSION["username"]) &&
            isset($_SESSION["password"]) &&
            isset($_SESSION["dbName"])
        ) {
            $username = $_SESSION["username"];
            $password = $_SESSION["password"];
            $dbName = $_SESSION["dbName"];

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

            if (!$dynamicDB->getSchemaBuilder()->hasTable('workings')) {
                $dynamicDB->getSchemaBuilder()->create('workings', function (Blueprint $table) {
                    $table->id();
                    $table->integer('year');
                    $table->integer('month');
                    $table->string('monthName');
                    $table->integer('working_days');
                    $table->integer('weekends');
                    $table->integer('holidays');
                    $table->timestamps();
                });
            }

            // Calculate and store working days for each month
            $processingDate = $startDate->copy();
           $workingCount = $dynamicDB->table('workings')->count();
           if (!($workingCount > 0))    //or ($workingCount <= 0)
           {
            while ($processingDate <= $endDate) {
                $year = $processingDate->year;
                $month = $processingDate->month;
                $monthName = $processingDate->format('F');
                // echo $processingDate;die();
                // $isweek = $processingDate->isWeekend();
                // echo $isweek; die();
                $weekendDays = $this->calculateWeekendDays($workingDaysOption);
                $holidays = $this->getHolidaysForMonth($dynamicDB, $year, $month);
                $workingDaysCount = $this->calculateWorkingDays($processingDate, $weekendDays, $holidays,$workingDaysOption);
                //    echo $workingDaysCount;die();
                // Store the calculated data in the dynamic table
                
                $dynamicDB->table('workings')->insert([
                    'year' => $year,
                    'month' => $month,
                    'monthName' => $monthName,
                    'working_days' => $workingDaysCount,
                    'weekends' => $weekendDays,
                    'holidays' => count($holidays),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                // echo $processingDate;die();
                // $processingDate->addMonth(); // Move to the next month
                // echo $processingDate->startOfMonth();die();
                $processingDate->startOfMonth()->addMonth(); //startOfMonth (2023-08-10 to change 2023-08-01)
                // echo $processingDate;die();
            }   // end while
            return response()->json(['message' => 'Working days calculated and stored successfully']);

           }
           return response()->json(['message' => 'Data already updated']);
            
            

           
        } else {
            return response()->json(['message' => 'Please login'], 401);
        }
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
}

private function calculateWeekendDays($workingDaysOption)
{
    // Calculate the number of weekend days based on the working days option
    if ($workingDaysOption === 'mon-fri') {
        return 2; // Saturday and Sunday are considered as weekend
    } elseif ($workingDaysOption === 'mon-sat') {
        return 1; // Only Sunday is considered as weekend
    } elseif ($workingDaysOption === 'mon-sun') {
        return 0; // No weekends, all days are working days
    } else {
        return 0; // Default to no weekends
    }
}


private function getHolidaysForMonth($dynamicDB, $year, $month)
{
    // Fetch holidays for the specified year and month from the dynamic database
    $holidays = $dynamicDB->table('holidays') 
        ->whereYear('holiday', $year)
        ->whereMonth('holiday', $month)
        ->pluck('holiday')
        ->toArray();

    return $holidays;
}





private function calculateWorkingDays($processingDate, $weekendDays, $holidays, $workingDaysOption) 
   // $processingDate passed and renamed it $startDate,now $startDate month is fixed(like 6,7,8 or 9 till endDate month
   // but $currentDate increment by day so when month is over then loop will exit
{
    $workingDays = 0;
    $currentDate = $processingDate->copy();
    //  return $startDate;die();

    while ($currentDate->month === $processingDate->month) {   // check $currentDate->month after addDay()
        if (
            (!$currentDate->isWeekend() && $workingDaysOption === 'mon-fri') ||
            (!$currentDate->isSunday() && $workingDaysOption === 'mon-sat') ||
            // (!$currentDate->isWeekend() && $workingDaysOption === 'mon-sat') ||
            $workingDaysOption === 'mon-sun'
        ) {
            $formattedDate = $currentDate->toDateString();    // $formattedDate like 2023-06-10
        //    echo $formattedDate;die();
            if (!in_array($formattedDate, $holidays)) {
                $workingDays++; 
                
            }
        }
       
        $currentDate->addDay();
        
    }

    return $workingDays;
    
    
}









// // private function calculateWorkingDays($startDate, $weekendDays, $holidays)
// // {
// //     $workingDays = 0;
// //     $currentDate = $startDate->copy();

// //     while ($currentDate->month === $startDate->month) {
// //         if (!$currentDate->isWeekend() && !in_array($currentDate->toDateString(), $holidays)) {
// //             $workingDays++;
// //         }

// //         $currentDate->addDay();
// //     }

// //     return $workingDays - $weekendDays - count($holidays);
// // }



// // private function calculateWorkingDays($startDate, $weekendDays, $holidays)
// // {
// //     $workingDays = 0;
// //     $currentDate = $startDate->copy();

// //     while ($currentDate->month === $startDate->month) {
// //         if (!$currentDate->isWeekend() || $weekendDays === 0) {
// //             $formattedDate = $currentDate->toDateString();
// //             if (!in_array($formattedDate, $holidays)) {
// //                 $workingDays++;
// //             }
// //         }

// //         $currentDate->addDay();
// //     }

// //     return $workingDays;
// // }







// private function calculateWorkingDays($startDate, $endDate, $workingDaysOption, $holidays)
// {
//     $workingDays = 0;
//     $currentDate = $startDate->copy();

//     while ($currentDate <= $endDate) {
//         $dayOfWeek = $currentDate->dayOfWeek;

//         if (
//             ($workingDaysOption === 'mon-fri' && $dayOfWeek >= Carbon::MONDAY && $dayOfWeek <= Carbon::FRIDAY) ||
//             ($workingDaysOption === 'mon-sat' && $dayOfWeek >= Carbon::MONDAY && $dayOfWeek <= Carbon::SATURDAY) ||
//             $workingDaysOption === 'mon-sun'
//         ) {
//             $formattedDate = $currentDate->toDateString();
//             if (!in_array($formattedDate, $holidays)) {
//                 $workingDays++;
//             }
//         }

//         $currentDate->addDay();
//     }

//     return $workingDays;
// }




public function calculateAndStoreWorkingDayss(Request $request)
{


$start = '2023-08-20';
$end = '2023-11-20';

$startDate = Carbon::parse($start);
$endDate = Carbon::parse($end);

$currentDate = $startDate->copy();
$abc = $currentDate->month;
$bcd = $endDate->month;

$totalSaturdays = 0;
$totalSundays = 0;

while ($currentDate <= $endDate) {
    $dayOfMonth = $currentDate->day; // 20
    $monthDays = $currentDate->daysInMonth; //31
   
    // Calculate weekends only within the range of 20th to last day of the month
    for ($day = $dayOfMonth; $day <= $monthDays; $day++) {
        $dateString = "{$currentDate->year}-{$currentDate->month}-$day";
        $currentDay = Carbon::parse($dateString);

        if ($currentDay->month ===  $abc) {
            if ($currentDay->dayOfWeek === Carbon::SATURDAY || $currentDay->dayOfWeek === Carbon::SUNDAY) {
                $totalWeekends++;
            } 
        }
        elseif(!$currentDay->month === $bcd)
        {
            if ($currentDate->dayOfWeek === Carbon::SATURDAY || $currentDate->dayOfWeek === Carbon::SUNDAY) {
                $totalWeekends++;
            }
        }
        elseif($currentDay->month === $bcd)
        {
            $dayofendmonth = $endDate->day;
            if ($currentDate->dayOfWeek === Carbon::SATURDAY || $currentDate->dayOfWeek === Carbon::SUNDAY) {
                $totalWeekends++;
        }
          
    }

    // Move to the next month
    $currentDate->addMonth();
}

echo "Total Saturdays: $totalSaturdays\n";
echo "Total Sundays: $totalSundays\n";
die();





    try {


// $startDateString = '2023-09-16';
// $startDate = Carbon::parse($startDateString);
// $year = $startDate->year;
// $month = $startDate->month;
// $totalDaysInMonth = $startDate->daysInMonth;


// $saturdaysCount = 0;
// $sundaysCount = 0;

// for ($day = $startDate->day; $day <= $totalDaysInMonth; $day++) {
//     $dateString = "$year-$month-$day";
//     $currentDate = Carbon::parse($dateString);

//     if ($currentDate->dayOfWeek === Carbon::SATURDAY) {
//         $saturdaysCount++;
//     }

//     if ($currentDate->dayOfWeek === Carbon::SUNDAY) {
//         $sundaysCount++;
//     }
// }

// echo "Number of Saturdays from $startDateString: $saturdaysCount\n";
// echo "Number of Sundays from $startDateString: $sundaysCount";die();






//         $year = 2023;
//         $month = 10;
//         $totalDaysInMonth = Carbon::create($year, $month)->daysInMonth;

//       $saturdaysCount = 0;
//       $sundaysCount = 0;
//       for ($day = 1; $day <= $totalDaysInMonth; $day++) {
//         $dateString = "$year-$month-$day";
//         $currentDate = Carbon::parse($dateString);

//     if ($currentDate->dayOfWeek === Carbon::SATURDAY) {
//         $saturdaysCount++;
//     }

//     if ($currentDate->dayOfWeek === Carbon::SUNDAY) {
//         $sundaysCount++;
//     }
// }
// $totalHoliday = $saturdaysCount + $sundaysCount;

// echo $totalHoliday;
// die();





        // Retrieve user inputs
        $start = '2023-08-20';
        $end = '2023-11-20';
        // $workingDaysOption = $request->input('workingDaysOption'); // Options: 'mon-fri', 'mon-sat', 'mon-sun'
        $workingDaysOption = 'mon-fri';
        $startDate = Carbon::parse($start);
        $endDate = Carbon::parse($end);
        
        // $months = $startDate->month;
        // echo $months;die(); 

        // $year = 2023;
        // $month = 2; 
        // $daysInMonth = Carbon::create($year, $month)->daysInMonth;
        // echo "Number of days in the specified month: $daysInMonth";die();
        $againDate = $startDate->copy();
         //like 2023-06-20
        
        $processDate = Carbon::parse($againDate);
        
        $MonthInNumber = $processDate->month; //  8 (august)
       
        $yearInNumber = $processDate->year; // 2023
         $dayOfMonth = $processDate->day;  // 20 (starting month day)
        // $daysInMonth = Carbon::create($yearInNumber,$MonthInNumber)->daysInMonth;
        $daysInMonth = $processDate->daysInMonth;
        $daysTotal = $daysInMonth - $dayOfMonth + 1; // 31 - 20 + 1 =11
      
       
        if($processDate->month === $MonthInNumber)
        {
            for($day = $dayOfMonth;$day <= $daysInMonth;$day++)
            {
                $dateString = "$yearInNumber-$MonthInNumber-$day";
                $currentDate = Carbon::parse($dateString);

              if ($currentDate->dayOfWeek === Carbon::SATURDAY) 
                {
                 $saturdaysCount++;
                }
             if ($currentDate->dayOfWeek === Carbon::SUNDAY)
                {
                 $sundaysCount++;
                }
             }

        }
        
        

        



        session_start();
        if (
            isset($_SESSION["username"]) &&
            isset($_SESSION["password"]) &&
            isset($_SESSION["dbName"])
        ) {
            $username = $_SESSION["username"];
            $password = $_SESSION["password"];
            $dbName = $_SESSION["dbName"];

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

            if (!$dynamicDB->getSchemaBuilder()->hasTable('workings')) {
                $dynamicDB->getSchemaBuilder()->create('workings', function (Blueprint $table) {
                    $table->id();
                    $table->integer('year');
                    $table->integer('month');
                    $table->integer('working_days');
                    $table->integer('weekends');
                    $table->integer('holidays');
                    $table->timestamps();
                });
            }

            // Calculate and store working days for each month
            $processingDate = $startDate->copy();
            while ($processingDate <= $endDate) {
                $year = $processingDate->year;
                $month = $processingDate->month;

                $weekendDays = $this->calculateWeekendDayss($workingDaysOption);
                $holidays = $this->getHolidaysForMonths($dynamicDB, $year, $month);

                $workingDaysCount = $this->calculateWorkingDayss($processingDate, $endDate, $workingDaysOption, $holidays);

                // Store the calculated data in the dynamic table
                $dynamicDB->table('workings')->insert([
                    'year' => $year,
                    'month' => $month,
                    'working_days' => $workingDaysCount,
                    'weekends' => $weekendDays,
                    'holidays' => count($holidays),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $processingDate->addMonth(); // Move to the next month
            }

            return response()->json(['message' => 'Working days calculated and stored successfully']);
        } else {
            return response()->json(['message' => 'Please login'], 401);
        }
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
}

}



private function calculateWeekendDayss($workingDaysOption)
{
    if ($workingDaysOption === 'mon-fri') {
        return 2; // Saturday and Sunday are considered as weekend
    } elseif ($workingDaysOption === 'mon-sat') {
        return 1; // Only Sunday is considered as weekend
    } else {
        return 0; // No weekends, all days are working days
    }
}

private function getHolidaysForMonths($dynamicDB, $year, $month)
{
    $holidays = $dynamicDB->table('holidays') 
        ->whereYear('holiday', $year)
        ->whereMonth('holiday', $month)
        ->pluck('holiday')
        ->toArray();

    return $holidays;
}

private function calculateWorkingDayss($startDate, $endDate, $workingDaysOption, $holidays)
{
    $workingDays = 0;
    $currentDate = $startDate->copy();

    while ($currentDate <= $endDate) {
        $dayOfWeek = $currentDate->dayOfWeek;
        echo $dayOfWeek;die();

        if (
            ($workingDaysOption === 'mon-fri' && $dayOfWeek >= Carbon::MONDAY && $dayOfWeek <= Carbon::FRIDAY) ||
            ($workingDaysOption === 'mon-sat' && $dayOfWeek >= Carbon::MONDAY && $dayOfWeek <= Carbon::SATURDAY) ||
            $workingDaysOption === 'mon-sun'
        ) {
            $formattedDate = $currentDate->toDateString();
            if (!in_array($formattedDate, $holidays)) {
                $workingDays++;
            }
        }

        $currentDate->addDay();
    }

    return $workingDays;
}




public function addAnnouncement(Request $request)
{
    try{
        session_start();
        if (
            isset($_SESSION["username"]) &&
            isset($_SESSION["password"]) &&
            isset($_SESSION["dbName"])
        ) {
            $username = $_SESSION["username"];
            $password = $_SESSION["password"];
            $dbName = $_SESSION["dbName"];
    
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
            if (!$dynamicDB->getSchemaBuilder()->hasTable('announcements')) {
                $dynamicDB->getSchemaBuilder()->create('announcements', function (Blueprint $table) {
                    $table->id();
                    $table->date('date');
                    $table->text('announcement');
                    $table->timestamps();
                });
            }
        $announcement ="this is for test";
             // Store the calculated data in the dynamic table
             $dynamicDB->table('announcements')->insert([
                'announcement' => $announcement,
                'date' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        return response()->json(['message' => 'announcement create sucessfully']);
    
        }
        return response()->json(['message' => 'pls login']);
    }
    catch(\Exception $e)
    {
        return response()->json(['error' => $e->getMessage()], 500);
    }
  
}






public function addProject(Request $request)
{
    $validatedData = $request->validate([
        'project_name' => 'required',
        'project_date' => 'required|date',
        'project_endDate' => 'required|date',
        'team_number' => 'required',
        'project_leader' => 'required'
    ]);  
    session_start();
    if(isset($_SESSION["username"]) && isset($_SESSION["password"]) && isset($_SESSION["dbName"]))
    {
        $username = $_SESSION["username"];
        $password = $_SESSION["password"];
        $dbName = $_SESSION["dbName"];

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
        
                // Check if the 'projects' table exists in the dynamic database
        if (!Schema::connection('dynamic')->hasTable('projects')) {
            // If the 'projects' table doesn't exist, create it
            Schema::connection('dynamic')->create('projects', function (Blueprint $table) {
                $table->id();
                $table->string('project_name');
                $table->date('asign_date');
                $table->date('end_date');
                $table->unsignedBigInteger('project_leader');
                $table->integer('team_member')->nullable();
                $table->timestamps(); 
           
                $table->foreign('project_leader')->references('id')->on('employees');
            });
           
        }
        
         $projectName = $validatedData['project_name'];
         
         $projectLeader =  $validatedData['project_leader']; 
         $projectDate = $validatedData['project_date'];
         $endDate = $validatedData['project_endDate'];
         $teamNumber = $validatedData['team_number'];
         $date = Carbon::now()->timezone('Asia/Kolkata')->format('Y-m-d H:i:s');
         
         $dynamicDB->table('projects')->insert([
         'project_name' => $projectName,
         'asign_date' => $projectDate,
         'end_date' => $endDate,
         'project_leader' => $projectLeader,
         'team_member' => $teamNumber,
         'created_at' =>$date,
         'updated_at' => $date,
        ]);
        return response()->json(['message' => 'Projects details stored']);
    

    }
    return response()->json(['message' => 'pls login']);
    
}



public function addProfessionalTax(Request $request)
{
    $validatedData = $request->validate([
        'month' => 'required',
        'state' => 'required',
        'year' => 'required',

    ]); 
    session_start();
    if(isset($_SESSION["username"]) && isset($_SESSION["password"]) && isset($_SESSION["dbName"]))
    {
        $username = $_SESSION["username"];
        $password = $_SESSION["password"];
        $dbName = $_SESSION["dbName"];

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

           // Check if the 'professionalTaxes' table exists in the dynamic database
           if (!Schema::connection('dynamic')->hasTable('professionalTaxes')) {
            // If the 'professionalTaxes' table doesn't exist, create it
            Schema::connection('dynamic')->create('projects', function (Blueprint $table) {
                $table->id();
                $table->string('month');
                $table->year('year');
                $table->string('state');
                $table->string('brunch')->nullable();
                $table->timestamps(); 
                  
            });
           
        }
        $month = $validatedDate['month'];
        $year = $validatedData['year'];
        $state = $validatedData['state'];
        $date = Carbon::now()->timezone('Asia/Kolkata')->format('Y-m-d H:i:s');

        $dynamicDB->table('projects')->insert([
            'month' => $month,
            'year' => $year,
            'state' => $state,
            'created_at' =>$date,
            'updated_at' => $date,
           ]);
           return response()->json(['message' => 'Professional tax added successfully']);
       



    }
    return response()->json(['message' => 'pls login']);
}




public function calculateWeekend(Request $request)
{
    $year = 2023; // Year
    $month = 9;   // Month (August)

    // Create Carbon instances for the first day and last day of the month
    $firstDay = Carbon::create($year, $month)->startOfMonth();
    
    $lastDay = Carbon::create($year, $month)->endOfMonth();

    // Initialize a counter for weekend days
    $weekendDays = 0;

    // Loop through each day in the month and count weekends
    $currentDay = clone $firstDay;
    while ($currentDay <= $lastDay) {
        // Check if it's Saturday (6) or Sunday (0)
        if ($currentDay->dayOfWeek === 6 || $currentDay->dayOfWeek === 0) {
            $weekendDays++;
        }
       
        // Move to the next day
        $currentDay->addDay();
    }

    // Return the total weekend days
    return response()->json(['weekendDays' => $weekendDays]);
}





public function taxInformation(Request $request)
{
    session_start();
}










public function salaryStructure(Request $request)
{
    $validatedData = $request->validate([
    //     'basic salary' => 'required',
    //     'house rent Allowence' => 'required',
    //     'medical & conveyance' => 'required',
    //     'statuory bonus' => 'required',
    //     'tax deduction' => 'required'
           'date'    => 'required|date'
    ]);
    session_start();
    if(isset($_SESSION["username"]) && isset($_SESSION["password"]) && isset($_SESSION["dbName"]))
    {
     $username = $_SESSION["username"];
     $password = $_SESSION["password"];
     $dbName = $_SESSION["dbName"];

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



    // Check if the 'tax_information' table exists in the dynamic database
    if (!Schema::connection('dynamic')->hasTable('tax_information')) {
        // If the 'tax_information' table doesn't exist, create it
        Schema::connection('dynamic')->create('tax_information', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id'); // Reference to employee
            $table->string('tax_code'); // Tax code or identifier
            $table->decimal('tax_rate', 5, 2); // Tax rate as a decimal (e.g., 25.00%)
            $table->timestamps(); 

            $table->foreign('employee_id')->references('id')->on('employees');
              
        });
       
    }


        // Check if the 'deductions' table exists in the dynamic database
        if (!Schema::connection('dynamic')->hasTable('deductions')) {
            // If the 'deductions' table doesn't exist, create it
            Schema::connection('dynamic')->create('deductions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id'); // Reference to employee
            $table->string('deduction_name'); // Name or description of the deduction
            $table->decimal('deduction_amount', 10, 2); // Deduction amount
            $table->timestamps(); 

            $table->foreign('employee_id')->references('id')->on('employees');
                  
            });
           
        }



             // Check if the 'bonuses' table exists in the dynamic database
             if (!Schema::connection('dynamic')->hasTable('bonuses')) {
                // If the 'bonuses' table doesn't exist, create it
            Schema::connection('dynamic')->create('bonuses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id'); // Reference to employee
            $table->string('bonus_name'); // Name or description of the bonus
            $table->decimal('bonus_amount', 10, 2); // Bonus amount
            $table->timestamps();

            $table->foreign('employee_id')->references('id')->on('employees');

                      
                });
               
            }





     
      if (!Schema::connection('dynamic')->hasTable('salaries')) {
        
        Schema::connection('dynamic')->create('salaries', function (Blueprint $table) {
            $table->id(); // Auto-incrementing primary key column
            $table->unsignedBigInteger('employee_id'); // Reference to employee
            // $table->enum('isActive',['yes','no']);
            $table->decimal('amount', 10, 2); // Salary amount with two decimal places
            $table->enum('frequency', ['monthly', 'bi-weekly']); // Payment frequency
            $table->string('payment_method'); // Payment method (e.g., direct deposit)
            $table->string('bank_account_number')->nullable(); // Bank account number
            $table->string('bank_name')->nullable(); // Add bank name column
            $table->unsignedBigInteger('tax_information_id')->nullable(); // Reference to tax information
            $table->unsignedBigInteger('deductions_id')->nullable(); // Reference to deductions
            $table->unsignedBigInteger('bonuses_id')->nullable(); // Reference to bonuses
            $table->date('payroll_period'); // Period for which the salary is paid
            $table->date('date_of_payment'); // Date when the salary is paid
            $table->decimal('net_salary', 10, 2); // Net salary after deductions
            $table->timestamps(); // Created at and updated at timestamps
            
            // Foreign key constraints
            $table->foreign('employee_id')->references('id')->on('employees');
            $table->foreign('tax_information_id')->references('id')->on('tax_information');
            $table->foreign('deductions_id')->references('id')->on('deductions');
            $table->foreign('bonuses_id')->references('id')->on('bonuses');
              
        });
       
    }
    $date = $validatedData['date'];
    $start = Carbon::parse($date);
    // $end = $start->endOfMonth();
    $year = $start->year;
    
   
    $weekends = $this->calculateWeekends($start,$year);
   
    // return response()->json(['weekendDays' => $weekends]);
    return response()->json(['message' => 'created']);


    }
    return response()->json(['message' => 'pls login']);
}




private function calculateWeekends($start,$year)
{ 
    // $year = 2023; // Year
    // $month = 9;   // Month (August)
    $month = $start->format('n'); 
    // $newYear = $year; 

    // $monthNumber = $start->format('n');

    // Create Carbon instances for the first day and last day of the month
    $firstDay = Carbon::create($year, $month)->startOfMonth();
    $lastDay = Carbon::create($year, $month)->endOfMonth();
    
   
//    print($firstDay); 
//    die();

    // Initialize a counter for weekend days
    $weekendDays = 0;

    // Loop through each day in the month and count weekends
    $currentDay = clone $firstDay;
    while ($currentDay <= $lastDay) {
        // Check if it's Saturday (6) or Sunday (0)
        if ($currentDay->dayOfWeek === 6 || $currentDay->dayOfWeek === 0) {
            $weekendDays++;
        }
        
        // Move to the next day
        $currentDay->addDay();
    }

    // Return the total weekend days
    return response()->json(['weekendDays' => $weekendDays]);
}



public function salaryComponent(Request $request)
{
    //  $validatedData = $request->validate([
    //     //Allowances
    //    'Dearness Allowance' => 'required',
    //    'House Rent Allowance' => 'required',
    //    'Leave Travel Allowance' => 'required',
    //    'Conveyance allowance' => 'required',
    //    'Medical allowance' => 'required',
    //       //Other indirect components in your salary
    //     'Overtime payment' => 'required',
    //     'Bonus'  =>  'required',
    //     'Performance-linked incentive' => 'required',
    //     'Salary arrears' =>  'required',
    //     'Travel and food reimbursements' => 'required',
    //     'Gratuity' => 'required',
    //     'Professional Tax' => 'required',
    //     'Tax Deduction at Source (TDS)' => 'required',
    //     'ESIC' => 'required',

    // ]);

    session_start();

 if(isset($_SESSION["username"]) && isset($_SESSION["password"]) && isset($_SESSION["dbName"]))
    {
        $username = $_SESSION["username"];
        $password = $_SESSION["password"];
        $dbName = $_SESSION["dbName"];
        

        $date = Carbon::now()->timezone('Asia/Kolkata')->format('Y-m-d H:i:s');

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
        if (!$dynamicDB->getSchemaBuilder()->hasTable('salary_components')) {
            $dynamicDB->getSchemaBuilder()->create('salary_components', function (Blueprint $table) {
                $table->id();
                $table->enum('dearness_allowance',['yes','no'])->nullable();
                $table->enum('houserent_allowance',['yes','no'])->nullable();
                $table->enum('medical_allowance',['yes','no'])->nullable();
                $table->enum('bonus',['yes','no'])->nullable();
                $table->enum('Performance_linked_incentive',['yes','no'])->nullable();
                $table->enum('salary_arrears',['yes','no'])->nullable();
                $table->enum('travel_and_food_reimbursements',['yes','no'])->nullable();
                $table->enum('gratuity',['yes','no'])->nullable();
                $table->enum('professional_tax',['yes','no'])->nullable();
                $table->enum('tax_deduction_at_source',['yes','no'])->nullable();
                $table->enum('esic',['yes','no'])->nullable();
                $table->timestamps();
            });
        }

        $dynamicDB->table('salary_components')->insert([
            'dearness_allowance' => 'yes',
            'houserent_allowance' => 'yes',
            'medical_allowance' => 'no',
            'bonus' => 'yes',
            'Performance_linked_incentive' => 'no',
            'salary_arrears' => 'no',
            'travel_and_food_reimbursements' => 'yes',
            'gratuity' => 'yes',
            'professional_tax' =>'yes',
            'tax_deduction_at_source' => 'yes',
            'esic' => 'yes',
            'created_at' => $date,
            'updated_at' => $date,
        ]);
           
   return response()->json(['message' => 'successfully stored']);


    }
    return response()->json(['message' => 'session out,pls login']);

}






public function salaryComponents(Request $request)
{
      $validatedData = $request->validate([
         //Allowances
        'dearness_allowance' => 'nullable|boolean',
        'houserent_allowance' => 'nullable|boolean',
        'leave_travel_allowance' => 'nullable|boolean',
        'conveyance_allowance' => 'nullable|boolean',
        'medical_allowance' => 'nullable|boolean',
           //Other indirect components in your salary
        'overtime_payment' => 'nullable|boolean',
        'bonus'  =>  'nullable|boolean',
        'performance_linked_incentive' => 'nullable|boolean',
        'salary_arrears' =>  'nullable|boolean',
        'travel_and_food_reimbursements' => 'nullable|boolean',
        'gratuity' => 'nullable|boolean',
        'professional_tax' => 'nullable|boolean',
        'tax_deduction_at_source' => 'nullable|boolean',  //
        'esic' => 'nullable|boolean',

     ]);

    session_start();

 if(isset($_SESSION["username"]) && isset($_SESSION["password"]) && isset($_SESSION["dbName"]))
    {
        $username = $_SESSION["username"];
        $password = $_SESSION["password"];
        $dbName = $_SESSION["dbName"];
        

        $date = Carbon::now()->timezone('Asia/Kolkata')->format('Y-m-d H:i:s');

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
        if (!$dynamicDB->getSchemaBuilder()->hasTable('salary_components')) {
            $dynamicDB->getSchemaBuilder()->create('salary_components', function (Blueprint $table) {
                $table->id();
                $table->boolean('dearness_allowance')->nullable();
                $table->boolean('houserent_allowance')->nullable();
                $table->boolean('leave_travel_allowance')->nullable();
                $table->boolean('conveyance_allowance')->nullable();
                $table->boolean('medical_allowance')->nullable();
                $table->boolean('overtime_payment')->nullable();
                $table->boolean('bonus')->nullable();
                $table->boolean('performance_linked_incentive')->nullable();
                $table->boolean('salary_arrears')->nullable();
                $table->boolean('travel_and_food_reimbursements')->nullable();
                $table->boolean('gratuity')->nullable();
                $table->boolean('professional_tax')->nullable();
                $table->boolean('tax_deduction_at_source')->nullable();
                $table->boolean('esic')->nullable();
                $table->timestamps();
            });
        }

        $dynamicDB->table('salary_components')->insert([
            'dearness_allowance' => $validatedData['dearness_allowance'],
            'houserent_allowance' => $validatedData['houserent_allowance'],
            'leave_travel_allowance' => $validatedData['leave_travel_allowance'],
            'conveyance_allowance' => $validatedData['conveyance_allowance'],
            'medical_allowance' => $validatedData['medical_allowance'],

            'overtime_payment' =>$validatedData['overtime_payment'],
            'bonus' => $validatedData['bonus'],
            'performance_linked_incentive'  => $validatedData['performance_linked_incentive'],
            'salary_arrears' => $validatedData['salary_arrears'],
            'travel_and_food_reimbursements' => $validatedData['travel_and_food_reimbursements'],
            'gratuity' => $validatedData['gratuity'],
            'professional_tax' => $validatedData['professional_tax'],
            'tax_deduction_at_source' => $validatedData['tax_deduction_at_source'],
            'esic' => $validatedData['esic'],
            'created_at' => $date,
            'updated_at' => $date,
        ]);
           
   return response()->json(['message' => 'successfully components are stored']);


    }
    return response()->json(['message' => 'session out,pls login']);

}








public function calculateSalary(Request $request)
{
  session_start();
  if(isset($_SESSION["username"]) && isset($_SESSION["password"]) && isset($_SESSION["dbName"]))
  {
    $username = $_SESSION["username"];
    $password = $_SESSION["password"];
    $dbName = $_SESSION["dbName"];

    $date = Carbon::now()->timezone('Asia/Kolkata')->format('Y-m-d H:i:s');

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
        $employee_id = $validatedData['id'];
        $amount = $dynamicDB->table('salary')->where('id',$employee_id)->value('amount');

  } 
  return response()->json(['message' => 'session logout,pls login']);

}


// public function showForm()
// {
//     return view('car.form');
// }

// public function processForm(Request $request)
// {
//     $abc = $request->has_car;
//     // echo $abc;die();
//     $hasCar = $request->input('has_car') == 'yes' ? 'Yes' : 'No';

//     return "User has a car: $hasCar";
// }




public function empAllowance(Request $request)
{
  
    session_start();

 if(isset($_SESSION["username"]) && isset($_SESSION["password"]) && isset($_SESSION["dbName"]))
    {
        $username = $_SESSION["username"];
        $password = $_SESSION["password"];
        $dbName = $_SESSION["dbName"];

        $date = Carbon::now()->timezone('Asia/Kolkata')->format('Y-m-d H:i:s');

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
        if (!$dynamicDB->getSchemaBuilder()->hasTable('emp_allowances')) {
            $dynamicDB->getSchemaBuilder()->create('emp_allowances', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('employee_id');
                $table->string('allowance_type');
                $table->decimal('amount', 10, 2); // Example for a decimal field with 2 decimal places
                $table->string('currency')->nullable();
                $table->date('effective_date');
                $table->date('end_date')->nullable();
                $table->string('frequency')->nullable();
                $table->boolean('taxable')->default(false);
                $table->text('comments')->nullable();
                $table->timestamps();
                
                    // Define foreign key constraint
                $table->foreign('employee_id')->references('id')->on('employees');
               
            });
        }
        // $dynamicDB->table('emp_allowances')->insert([
        //     'employee_id' => ,
        //     'allowance_type' => ,
        //     'amount' => ,
        //     'currency' => ,
        //     'effective_date' => ,
        //     'end_date' =>,
        //     'frequency' => ,
        //     'taxable'  => ,
        //     'comments' => ,
        //     'created_at' => $date,
        //     'updated_at' => $date,
        // ]);
        return response()->json(['message' => 'successfully created']);

    }
    return response()->json(['message' => 'session out,pls login']);

}




public function companyAllowance(Request $request)
{ 
    session_start();
    if(isset($_SESSION["username"]) && isset($_SESSION["password"]) && isset($_SESSION["dbName"]))
    {
        $username = $_SESSION["username"];
        $password = $_SESSION["password"];
        $dbName  =  $_SESSION["dbName"];
        
        $date = Carbon::now()->timezone('Asia/Kolkata')->format('Y-m-d H:i:s');

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
        
        if (!$dynamicDB->getSchemaBuilder()->hasTable('allowances')) {
            $dynamicDB->getSchemaBuilder()->create('allowances', function (Blueprint $table) {
                $table->id();
                $table->boolean('employee_pf')->default(false);
                $table->boolean('exit_amount')->default(false);
                $table->boolean('food_allowance')->default(false);
                $table->boolean('insentive_hf')->default(false);
                $table->boolean('insurance_amount')->default(false);
                $table->boolean('medical_reimbursement')->default(false);
                $table->boolean('night_shift')->default(false);
                $table->boolean('NIslubwise')->default(false);
                $table->boolean('NPF')->default(false);
                $table->boolean('option_petronet')->default(false);
                $table->boolean('optional_allowance_subsidy')->default(false);
                $table->boolean('shiftwise')->default(false);
                $table->boolean('subsidy')->default(false);
                $table->boolean('insentive 1')->default(false);
                $table->boolean('sodexd')->default(false);
                $table->timestamps();
            });
        }
        $dynamicDB->table('allowances')->insert([
          'employee_pf' => true,
          'exit_amount' => true,
          'food_allowance' => true,
          'insentive_hf' => true,
          'insurance_amount' => true,
          'medical_reimbursement' => true,
          'night_shift' => true,
          'NIslubwise' => true,
          'NPF'        => true,
          'option_petronet' => true,
          'optional_allowance_subsidy' => true,
          'shiftwise' => true,
          'subsidy' => false,
          'insentive 1' => true,
          'sodexd'   => false,
          'created_at' => $date,
          'updated_at' => $date,



        ]);
        return response()->json(['message' => 'created']);

    }
    return response()->json(['message' => 'session out,pls login']);
}




public function allEmpAttend(Request $request)
{
    session_start();
    if(isset($_SESSION["username"]) && isset($_SESSION["password"]) && isset($_SESSION["dbName"]))
    {
        $username = $_SESSION["username"];
        $password = $_SESSION["password"];
        $dbName =   $_SESSION["dbName"];

        $date = Carbon::now()->timezone('Asia/Kolkata')->format('Y-m-d H:i:s');
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
        // Get today's date
    $today = Carbon::now()->toDateString();

       
    // $totalAttendanceToday = $dynamicDB->table('attendences')->whereDate('created_at', $today)->count();

        $todayAttend = $dynamicDB->table('attendences')
        ->whereDate('created_at', $today)
        ->count();
        return response()->json(['message' => $todayAttend]);
    }
    return response()->json(['message' => 'session out,pls login']);
}

public function allEmpAbcent(Request $request)
{
    session_start();
    if(isset($_SESSION["username"]) && isset($_SESSION["password"]) && isset($_SESSION["dbName"]))
    {
        $username = $_SESSION["username"];
        $password = $_SESSION["password"];
        $dbName = $_SESSION["dbName"];
        $date = Carbon::now()->timezone('Asia/Kolkata')->format('Y=m-d H:i:s');

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
     $today = Carbon::now()->toDateString();
         //total employee attend today
     $todayAttend = $dynamicDB->table('attendences')
     ->whereDate('created_at', $today)
     ->count();
       // Query the "employees" table to count the total number of employees
    $totalEmployees = DB::connection('dynamic')
    ->table('employees')
    ->count();
    
      // Calculate the total absent employees by subtracting total present from total employees
    $totalAbsentToday = $totalEmployees - $todayAttend;
    return response()->json(['message' => 'total abcent ' . $totalAbsentToday]);

    }
    return response()->json(['message' => 'session out,pls login']);
}



public function latest_missAttend(Request $request)
{
    session_start();
    if(isset($_SESSION["username"]) && isset($_SESSION["password"]) && isset($_SESSION["dbName"]))
    {
        $username = $_SESSION["username"];
        $password = $_SESSION["password"];
        $dbName = $_SESSION["dbName"];
        $date = Carbon::now()->timezone('Asia/Kolkata')->format('Y=m-d H:i:s');

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
     $pendingAttend = $dynamicDB->table('miss_attendences')->where('status','pending')->get();
     if(count($pendingAttend) > 0 )
     {
        return response()->json(['message' => $pendingAttend]);
     }
     else
     {
       return response()->json(['message' => 'no pending attendence request']);
     }
     
     
    }
    return response()->json(['message' => 'session out,pls login']);
}



public function salaryStuct(Request $request)
{
    $validatedData = Validator::make($request->all(), [
        'ctc' => 'required',
        'basic%' => 'required',
         'da%'   => 'nullable', //allowances
        'hra%'   => 'required',
        
        'leave travel allowance%' => 'nullable|default:0',
        'conveyance_allowance' => ['nullable','in:yes,no'],
        'medical'  =>  ['nullable','in:yes,no'],
        
        
      

    ]);
 
        ///  Net Salary = Basic Salary + Allowances – (Provident fund + Gratuity + TDS + Professional Tax)

   
  

    
    session_start();
    if(isset($_SESSION["username"]) && isset($_SESSION["password"]) && isset($_SESSION["dbName"]))
    {
        $username = $_SESSION["username"];
        $password = $_SESSION["password"];
        $dbName = $_SESSION["dbName"];
        $date = Carbon::now()->timezone('Asia/Kolkata')->format('Y-m-d H:i:s');
        $ctc = $request->input('ctc');
        $basicInput = $request->input('basic%');
        $basic = $ctc*$basicInput/100;
      
        $basicSalary = $ctc *$request->input('basic%') / 100;
        $da = $request->input('da%',0); //allowances
        $hra = $basicSalary * $request->input('hra%')/100; 
        $lta = $request->input('leave travel allowance%');
        $ca = $request->input('conveyance_allowance');
        $ca = ($ca == 'yes') ? 1600 : 0;   // 1600 per month
        $medical = $request->input('medical');
        $medical = ($medical == 'yes') ? 1250 : 0; // 1250 per month
        $allowances = $da + $ca + $medical;

        
        $grossSalary = $basicSalary + $hra +  $allowances;

        //$netSalary = $grossSalary - ($professionalTax + $ppf + $incomeTax); 

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

     if (!$dynamicDB->getSchemaBuilder()->hasTable('salary_structure')) {
        $dynamicDB->getSchemaBuilder()->create('salary_structure', function (Blueprint $table) {
            $table->id();
            $table->string('ctc');
            $table->decimal('basic', 8, 2);
            $table->decimal('hra',8, 2);
            $table->decimal('conveyance_allowance', 8, 2);
            $table->decimal('medical_allowance', 8, 2);
            $table->decimal('basic_salary', 8, 2);
            $table->decimal('gross_salary', 8, 2);
            $table->decimal('allowances', 8, 2);
            $table->decimal('net_salary', 8, 2)->default(0);
            
            $table->timestamps();
        });
    }
    $dynamicDB->table('salary_structure')->insert([
        'ctc' => $ctc,
        'basic' => $basic,
        'hra'  => $hra,
        'conveyance_allowance' => $ca,
        'medical_allowance' => $medical,
        'basic_salary' => $basicSalary,
        'gross_salary' => $grossSalary,
        'allowances' => $allowances,
        'created_at' => $date,
        'updated_at' => $date,



    ]);
    return response()->json(['message' => 'successfully salary structure are calculated and stored ']);

    }
    return response()->json(['message' => 'session logout,pls login']);
}






public function createIndianStates(Request $request)
{
    session_start();
    if(isset($_SESSION["username"]) && isset($_SESSION["password"]) && isset($_SESSION["dbName"]))    
    {
       $username = $_SESSION['username'];
       $password = $_SESSION['password'];
       $dbName = $_SESSION['dbName'];
    }
    

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

   if (!$dynamicDB->getSchemaBuilder()->hasTable('indian_states')) {
    // If the table doesn't exist, create it
    $dynamicDB->getSchemaBuilder()->create('indian_states', function ($table) {
        $table->id();
        $table->string('name');
        $table->decimal('first_slub', 8, 2); 
        $table->decimal('second_slub', 8, 2);
        $table->decimal('third_slub', 8, 2);
        $table->decimal('fourth_slub', 8, 2);
        $table->timestamps();
    });


    $states = [
        'Andhra Pradesh',
        'Arunachal Pradesh',
        'Assam',
        'Bihar',
        'Chhattisgarh',
        'Goa',
        'Gujarat',
        'Haryana',
        'Himachal Pradesh',
        'Jharkhand',
        'Karnataka',
        'Kerala',
        'Madhya Pradesh',
        'Maharashtra',
        'Manipur',
        'Meghalaya',
        'Mizoram',
        'Nagaland',
        'Odisha',
        'Punjab',
        'Rajasthan',
        'Sikkim',
        'Tamil Nadu',
        'Telangana',
        'Tripura',
        'Uttar Pradesh',
        'Uttarakhand',
        'West Bengal'
    ];


     // Insert the list of states into the 'indian_states' table
     foreach ($states as $state) {
        $dynamicDB->table('indian_states')->insert([
            'name' => $state,
            'first_slub' => 0.00, 
            'second_slub' => 0.00,
            'third_slub' => 0.00,
            'fourth_slub' => 0.00
        ]);
    }


   return response()->json(['message' => 'Table create successfully']);

}
   return response()->json(['message' => 'sorry session out!']);


}










public function assetPurchase(Request $request)
{

    $validatedData = $request->validate([
        'asset_name' => 'required',
        'type_of_asset' => 'required',
        'asset_category' => 'required',
        'description' => 'required',
        'brand_name' => 'required',
        'model' => 'required',
        'serial_no' => 'required',
        'purchase_no' => 'required',
        'purchase_date' => 'required',
        'purchase_amount' => 'required',
        'invoice_no'  => 'required',
        'invoice_date' => 'required',
        'warranty_end_date' => 'required',

    ]);


    session_start();
    if(isset($_SESSION["username"]) && isset($_SESSION["password"]) && isset($_SESSION["dbName"]))
    {
        $username = $_SESSION["username"];
        $password = $_SESSION["password"];
        $dbName = $_SESSION["dbName"];
        $date = Carbon::now()->timezone('Asia/Kolkata')->format('Y-m-d H:i:s');

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
     if (!$dynamicDB->getSchemaBuilder()->hasTable('asset_purchase')) {
        $dynamicDB->getSchemaBuilder()->create('asset_purchase', function (Blueprint $table) {
            $table->id();
            $table->string('asset_code');
            $table->string('asset_name');
            $table->string('type_of_asset');
            $table->string('asset_category');
            $table->text('description');
            $table->string('brand_name');
            $table->string('model');
            $table->string('serial_no');
            $table->string('purchase_no');
            $table->string('purchase_date');
            $table->decimal('purchase_amount',8,2);
            $table->string('invoice_no');
            $table->string('invoice_date');
            $table->date('warranty_end_date');
            $table->enum('isAlloc',['yes','no'])->default('no');
            $table->string('allocation_date')->nullable();
            $table->enum('status',['active','inactive'])->default('inactive');
            
            $table->timestamps();
        });
    }

    $assetCode = strval(rand(10000000, 99999999));

    $dynamicDB->table('asset_purchase')->insert([
        'asset_code' => $assetCode,
        'asset_name' => $request->input('asset_name'),
        'type_of_asset' => $request->input('type_of_asset'),
        'asset_category'  => $request->input('asset_category'),
        'description'  =>  $request->input('description'),
        'brand_name' => $request->input('brand_name'),
        'model' => $request->input('model'),
        'serial_no' => $request->input('serial_no'),
        'purchase_no' => $request->input('purchase_no'),
        'purchase_date' => $request->input('purchase_date'),
        'purchase_amount' => $request->input('purchase_amount'),
        'invoice_no' => $request->input('invoice_no'),
        'invoice_date' => $request->input('invoice_date'),
        'warranty_end_date' => $request->input('warranty_end_date'),
        // 'allocation_date'  => 
        'created_at' =>        $date,
        'updated_at' =>        $date,


    ]);


     return response()->json(['message' => 'table create successfully']);

    }
    return response()->json(['message' => 'sorry session out,pls login']);
}





public function assetAlloc(Request $request)
{
    session_start();
    if(isset($_SESSION["username"]) && isset($_SESSION["password"]) && isset($_SESSION["dbName"]))
    {
        $username = $_SESSION["username"];
        $password = $_SESSION["password"];
        $dbName = $_SESSION["dbName"];
        
        $date = Carbon::now()->timezone('Asia/Kolkata')->format('Y-m-d H:i:s');

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

     if (!$dynamicDB->getSchemaBuilder()->hasTable('asset_allocation')) {
        $dynamicDB->getSchemaBuilder()->create('asset_allocation', function (Blueprint $table) {
            $table->id();
            $table->id('emp_id');
            $table->string('asset_code');
            $table->enum('isAlloc',['yes','no'])->default('yes');
            $table->string('allocation_by');
            $table->id('allocate_by_emp_id');
            $table->string('allocation_to_date');
            $table->string('allocation_from_date')->nullable();
            
            $table->enum('status',['active','inactive'])->default('inactive');
            
            $table->timestamps();
        });
    }



    }
}





public function allAssetRequest(Request $request)
{
    session_start();
    if(isset($_SESSION['username']) &&isset($_SESSION['password']) && isset($_SESSION['dbName']))
    {
        $username = $_SESSION['username'];
        $password = $_SESSION['password'];
        $dbName   = $_SESSION['dbName'];
        $date = Carbon::now()->timezone('Asia/Kolkata')->format('Y-m-d H:i:s');

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
        $totalRequest = $dynamicDB->table('asset_request')->where('status', 'pending')->count();
        $allRequest = $dynamicDB->table('asset_request')->where('status','pending')->get();

        return response()->json(['success' => true,'message' => $allRequest]);
        


    }
    return response()->json(['success' => false,'message' => 'session out! pls login']);

}













}
