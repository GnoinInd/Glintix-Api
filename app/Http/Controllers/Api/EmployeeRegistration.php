<?php

namespace App\Http\Controllers\Api;
use App\Models\User;


use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

// use Illuminate\Mail\Mailable;
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
//use Illuminate\Http\Response;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Session\Middleware\StartSession;

use App\Exports\UsersExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\MonthsData;
use App\Exports\ThreeMonthWiseData;
use App\Exports\SixMonthData;
use App\Exports\YearwiseData;





class EmployeeRegistration extends Controller
{

        //in web.php
    
    public function companyLoginForm()
    {
           return view('companyLogin');
    }
 


      //in web.php
    public function companyLogin(Request $request)
    {
        $username = $request->username;
        $password = $request->password;
        $user = User::where('username', $username)->first();
    
        if ($user && Hash::check($password, $user->password)) {
            // If authentication is successful, fetch the dbName from the users table
            $dbName = $user->dbName;
    
            // Store the user's credentials and dbName in the session
            session([
                'username' => $request->input('username'),
                'password' => $request->input('password'),
                'dbName' => $dbName,
            ]);
    
            // Retrieve the value of 'dbName' from the session
            $dbNameFromSession = session('dbName');
    
           // echo "Successfully logged in. dbName from session: $dbNameFromSession";
           return redirect()->back()->with('success', "Successfully logged in. dbName from session: $dbNameFromSession");
        } else {
            return redirect()->back()->with('error', 'Login failed');
        }
    }
    






// Function to remove all sessions in (web.php)
public function clearSessions()
{
    session()->flush();
}






public function exportData(Request $request)
{
   
// Session::put('sum', 'this is sum');
    	
//     Session::all();die;
//     if(isset($_SESSION['username']) && isset($_SESSION['password']) && isset($_SESSION['dbName']))
//     {
//         echo "test";die;
//     }
//     echo "fail";die;

// session_start();
// if(isset($_SESSION['username']) && isset($_SESSION['password']) && isset($_SESSION['dbName']))
//      {
//         $username = $_SESSION['username'];
//         $password = $_SESSION['password'];
//         $dbName   = $_SESSION['dbName']; 
//          return Excel::download(new MonthsData($username,$password,$dbName), 'exported_data.xlsx');
//      }
//      echo "failed";die;


         //   for web.php

if(Session::has('username') && Session::has('password') && Session::has('dbName'))
{
    $username = session('username');
    $password = session('password');
    $dbName = session('dbName');
    
    $monthsDataExport = new ThreeMonthWiseData($username, $password, $dbName);

    // $startDate = Carbon::now()->subYear()->startOfDay();echo $startDate;die;
    
    return response()->Excel::download($monthsDataExport, 'exported_data.xlsx');

}
else
{
    echo "no session";
}


     

   




}















    public function sessionShow(Request $request)
{      session_start();
    //   // Retrieve all session data
       //$sessionData = session()->all();

  
    
       $session = var_dump($_SESSION);
    //   // Or, you can log the session data
            //Log::info($sessionData);
  
    //   // You can also return the session data as a JSON response
       return response()->json($session);

 
}











public function employeeBasic(Request $request)
{
        
    try
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
    
         
         if (!$dynamicDB->getSchemaBuilder()->hasTable('user')) {
            $dynamicDB->getSchemaBuilder()->create('user', function (Blueprint $table) {
                $table->id();
                $table->string('title')->nullable();
                $table->string('first_name')->nullable();
                $table->string('middle_name')->nullable();
                $table->string('last_name')->nullable();
                $table->string('preferred_name')->nullable();
                $table->date('DOB')->nullable();
                $table->enum('gender',['male','female','other'])->nullable();
                $table->string('blood_group')->nullable();
                $table->string('marital_status')->nullable();
                $table->string('marital_doc')->nullable();
                $table->string('nationality_one')->nullable();
                $table->string('nationality_two')->nullable();
                $table->string('nationality_doc')->nullable();
                $table->string('country_code')->nullable();
                $table->bigInteger('mobile_no')->nullable();
                $table->string('home_phone_no')->nullable();
                $table->string('work_phone_no')->nullable();
                $table->string('whatsapp_no')->nullable();
                //$table->string('emergency_phone_number')->nullable();
                $table->string('email')->nullable();
                $table->string('voter_id')->nullable();
                $table->string('pan_no')->nullable();
                $table->string('driving_licence')->nullable();
                $table->string('passport_no')->nullable();
                $table->date('passport_to')->nullable();
                $table->date('passport_from')->nullable();
                $table->string('eme_country_code')->nullable();
                $table->string('eme_mobile')->nullable();
                $table->string('eme_whatsapp_no')->nullable();
                $table->string('eme_email')->nullable();
                $table->enum('status',['pending','rejected'])->default('pending');
              
                $table->timestamps();
            });
        }
    
    
        
        $dynamicDB->table('user')->insert([
            'title' =>     $request->input('title'),
            'first_name' => $request->input('first_name'),
            'middle_name' => $request->input('middle_name'),
            'last_name'  => $request->input('last_name'),
            'preferred_name'  =>  $request->input('preferred_name'),
            'DOB' => $request->input('DOB'),
            'blood_group' => $request->input('blood_group'),
           
    
            'created_at' =>        $date,
            'updated_at' =>        $date,
    
    
        ]);
        return response()->json(['message' => 'employee basic details stored successfully']);
     
    
        }
        return response()->json(['success' => false,'message' => 'session out! pls login']);
    

    }
    catch(\Exception $e)
    {
        return response()->json(['success' => false, 'error'=> $e->getMessage()],500);
    }
   


}





public function employeeMarital(Request $request)
{
    $validatedData = $request->validate([
        'user_id' => 'required',
        'marital_status' => 'required',
        'marital_doc' => 'required|file', 
    ]);

    session_start();

    if (isset($_SESSION['username']) && isset($_SESSION['password']) && isset($_SESSION['dbName'])) {
        $username = $_SESSION['username'];
        $password = $_SESSION['password'];
        $dbName = $_SESSION['dbName'];
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
        $user_id = $request->input('user_id');

        $maritalDocPath = ''; 

        if ($request->hasFile('marital_doc')) {
            $file = $request->file('marital_doc');

            $uniqueFolderName = $user_id . '_' . time();

            $filePath = $file->store('marital_docs/' . $uniqueFolderName);

            $maritalDocPath = $filePath;
        }

        $dynamicDB->table('user')->where('id', $user_id)->update([
            'marital_status' => $request->input('marital_status'),
            'marital_doc' => $maritalDocPath, // Store the file path
            'updated_at' => $date,
        ]);

        return response()->json(['message' => 'Employee marital details stored successfully']);
    }

    return response()->json(['message' => 'Session out! Login, please.']);
}




public function employeeNationality(Request $request)
{
    $validatedData = $request->validate([
        'user_id'  =>  'required',
        'nationality_one' => 'required',
        'nationality_two'  =>  'nullable',
        'nationality_doc' => 'required|file',
    ]);
    session_start();
    if(isset($_SESSION['username']) && isset($_SESSION['password']) && isset($_SESSION['dbName']))
    {
        $username = $_SESSION['username'];
        $password = $_SESSION['password'];
        $dbName = $_SESSION['dbName'];
       
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
        $user_id = $request->input('user_id');
        
        $nationalityDocPath ='';

        if($request->hasFile('nationality_doc'))
        {
            $file = $request->file('nationality_doc');
            $uniqueFolderName = $user_id . '_' . time();
            //$fileName = $file->getClientOriginalName();
            $filePath = $file->store('nationality_docs/' . $uniqueFolderName);
            $nationalityDocPath = $filePath;     

        }

        $dynamicDB->table('user')->where('id',$user_id)->update([
            'nationality_one' => $request->input('nationality_one'),
            'nationality_two' => $request->input('nationality_two'),
            'nationality_doc' => $nationalityDocPath,
            'updated_at'   =>   $date,
          ]);
          return response()->json(['success'=> true,'message' => 'nationality data stored successfully']);

    }

    return response()->json(['success'=> false,'message' => 'session out! pls login']);
}





public function employeeCommunication(Request $request)
{

    $validatedData = $request->validate([
        'user_id'   =>  'required',
        'country_code' => 'required',
        'mobile_number' => 'required',
        'home_phone_number' => 'nullable',
        'work_phone_number' => 'nullable',
        'whatsapp_number'   =>  'nullable',
       // 'emergency_phone_number'  => 'nullable',
        'email'       =>   'required',
        'void'        =>  'nullable',

    ]);

    session_start();
    if(isset($_SESSION['username']) && isset($_SESSION['password']) && isset($_SESSION['dbName']))
    {
        $username = $_SESSION['username'];
        $password = $_SESSION['password'];
        $dbName = $_SESSION['dbName'];

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
        $userId = $request->input('user_id');

        $dynamicDB->table('user')->where('id',$userId)->update([
           'country_code' => $request->input('country_code'),
           'mobile_no'   =>  $request->input('mobile_number'),
           'home_phone_no'  => $request->input('home_phone_number'),
           'work_phone_no'   =>  $request->input('work_phone_number'),
           'whatsapp_no'     =>   $request->input('whatsapp_number'),
           //'emergency_phone_number' => Request->input('emergency_phone_number'),
           'email'       =>   $request->input('email'),
           'voter_id'     =>  $request->input('void'),
        ]);
        return response()->json(['success'=>true,'message' => 'emergency details stored successfully']);
    }
    return response()->json(['success' => false,'message' => 'session out! login pls']);
}



public function employeeEducation(Request $request)
{

    $validatedData = $request->validate([
        'user_id'   =>  'required',
        'board/university' => 'required',
        'specification' => 'required',
        'course_type' => 'nullable',
        'quali_start_date' => 'nullable',
        'quali_end_date' => 'nullable',
        'grade_type'   =>  'nullable',
        'total_marks'  => 'nullable',
        'grade'       =>   'required',

    ]);

    session_start();
    if(isset($_SESSION['username']) && isset($_SESSION['password']) && isset($_SESSION['dbName']))
    { 
        $username = $_SESSION['username'];
        $password = $_SESSION['password'];
        $dbName = $_SESSION['dbName'];

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
        $userId = $request->input('user_id');


        if (!$dynamicDB->getSchemaBuilder()->hasTable('qualification')) {
            $dynamicDB->getSchemaBuilder()->create('qualification', function (Blueprint $table) {
                $table->id();
                $table->bigInteger('user_id')->unsigned()->nullable();
                $table->string('course')->nullable();
                $table->string('board')->nullable();
                $table->string('specialization')->nullable();
                $table->string('course_type')->nullable();
                $table->string('qua_st_date')->nullable();
                $table->string('qua_end_date')->nullable();
                $table->string('grade_type')->nullable();
                $table->string('grade')->nullable();
                $table->string('total_marks')->nullable(); 
                $table->string('document_type')->nullable();
                $table->string('document_path')->nullable();
                $table->timestamps();

                $table->foreign('user_id')->references('id')->on('user');

            });
        }

        
        $dynamicDB->table('qualification')->insert([
            'user_id' => $userId,
            'course' => $request->input('course'),
            'board' => $request->input('board'),
            'specialization' => $request->input('specialization'),
            'course_type' => $request->input('course_type'),
            'qua_st_date' => $request->input('quali_start_date'),
            'qua_end_date' => $request->input('quali_end_date'),
            'grade_type' => $request->input('grade_type'),
            'grade' => $request->input('grade'),
            'total_marks' => $request->input('total_marks'),
            'created_at' => $date,
            'updated_at' => $date,
        ]);
        
        return response()->json(['success'=>true,'message' => 'qualification details stored successfully']);
    }
    return response()->json(['success' => false,'message' => 'session out! login pls']);
}




public function employeeEducationDoc(Request $request)
{

    $validatedData = $request->validate([
        'user_id'   =>  'required',
        'document_type' => 'required',
        'document_path' => 'file',
    ]);

    session_start();
    if(isset($_SESSION['username']) && isset($_SESSION['password']) && isset($_SESSION['dbName']))
    { 
        $username = $_SESSION['username'];
        $password = $_SESSION['password'];
        $dbName = $_SESSION['dbName'];

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
        $userId = $request->input('user_id');
       
        $documentPath = '';
        if($request->hasFile('document_path'))
        {
            $file = $request->file('document_path');
            //$fileName = $file->getClientOriginalName();
            $uniqueFolder = $userId . '_' . time();
            $filePath = $file->store('qualification_docs/' . $uniqueFolder);
            $documentPath = $filePath;
        }

        
        $dynamicDB->table('qualification')->where('user_id',$userId)->update([
            'document_type' => $request->input('document_type'),
            'document_path' => $documentPath,
            'updated_at' => $date,
        ]);
        
        return response()->json(['success'=>true,'message' => 'qualification document stored successfully']);
    }
    return response()->json(['success' => false,'message' => 'session out! login pls']);
}












public function employeeExperience(Request $request)
{

    $validatedData = $request->validate([
        'user_id'        =>  'required',
        'is_current_emp' =>  'required',
        'company_name'   =>  'required',
        'project_name'   =>  'required',
        'designation'    =>  'required',
        'start_date'     =>  'required',
        'end_date'       =>  'required',
    ]);

    session_start();
    if(isset($_SESSION['username']) && isset($_SESSION['password']) && isset($_SESSION['dbName']))
    { 
        $username = $_SESSION['username'];
        $password = $_SESSION['password'];
        $dbName = $_SESSION['dbName'];

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
        $userId = $request->input('user_id');


        if (!$dynamicDB->getSchemaBuilder()->hasTable('experience')) {
            $dynamicDB->getSchemaBuilder()->create('experience', function (Blueprint $table) {
                $table->id();
                $table->bigInteger('user_id')->unsigned()->nullable();
                $table->enum('is_current_emp', ['yes', 'no'])->nullable();
                $table->string('comp_name')->nullable();
                $table->string('proj_name')->nullable();
                $table->string('designation')->nullable();
                $table->string('start_date')->nullable();
                $table->string('end_date')->nullable();
                $table->string('total_year_exp')->nullable();
                $table->string('total_month_exp')->nullable();
                $table->string('document_type')->nullable();
                $table->string('exp_doc')->nullable();
                $table->timestamps();

                $table->foreign('user_id')->references('id')->on('user');

            });
        }
       
        
        $dynamicDB->table('experience')->insert([
            'user_id'   =>  $userId,
            'is_current_emp' => $request->input('is_current_emp'),
            'comp_name' => $request->input('company_name'),
            'proj_name' => $request->input('project_name'),
            'designation' => $request->input('designation'),
            'start_date'   =>$request->input('start_date'),
            'end_date'   =>  $request->input('end_date'),
            'created_at'  => $date,
            'updated_at' => $date,
        ]);
        
        return response()->json(['success'=>true,'message' => 'Experience details stored successfully']);
    }
    return response()->json(['success' => false,'message' => 'session out! login pls']);
}







public function employeeExpTime(Request $request)
{

    $validatedData = $request->validate([
        'user_id'        =>  'required',
        'total_year' =>  'required',
        'total_month'   =>  'required',
        //'exp_certificate' => 'file',
    ]);

    session_start();
    if(isset($_SESSION['username']) && isset($_SESSION['password']) && isset($_SESSION['dbName']))
    { 
        $username = $_SESSION['username'];
        $password = $_SESSION['password'];
        $dbName = $_SESSION['dbName'];

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
        $userId = $request->input('user_id');

     
   
       
        
        $dynamicDB->table('experience')->where('user_id',$userId)->update([
            'total_year_exp' => $request->input('total_year'),
            'total_month_exp' => $request->input('total_month'),
            //'exp_doc' => $filePath,
            'updated_at' => $date,
        ]);
        
        return response()->json(['success'=>true,'message' => 'experience document stored successfully']);
    }
    return response()->json(['success' => false,'message' => 'session out! login pls']);
}








public function employeeExpDoc(Request $request)
{

    $validatedData = $request->validate([
        'user_id'        =>  'required',
        'document_type' =>  'required',
        'exp_certificate' => 'file',
    ]);

    session_start();
    if(isset($_SESSION['username']) && isset($_SESSION['password']) && isset($_SESSION['dbName']))
    { 
        $username = $_SESSION['username'];
        $password = $_SESSION['password'];
        $dbName = $_SESSION['dbName'];

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
        $userId = $request->input('user_id');

     
        $experiencePath = '';
        if($request->hasFile('exp_certificate'))
        {
            $file = $request->file('exp_certificate');
            //$fileName = $file->getClientOriginalName();
            $uniqueFolder = $userId . '_' . time();
            $filePath = $file->store('experience_docs/' . $uniqueFolder);
            $experiencePath = $filePath;

        }


       
        
        $dynamicDB->table('experience')->where('user_id',$userId)->update([
            'document_type' => $request->input('document_type'),
            //'exp_doc' => $filePath,
            'exp_doc'  =>  $experiencePath,
            'updated_at' => $date,
        ]);
        
        return response()->json(['success'=>true,'message' => 'experience document stored successfully']);
    }
    return response()->json(['success' => false,'message' => 'session out! login pls']);
}








public function employeeBank(Request $request)
{

    $validatedData = $request->validate([
        'user_id'        =>  'required',
        'acc_holder_name' =>  'required',
        'acc_type'        =>  'required',
        //'acc_nature'   =>  'nullable',
        'acc_number'      => 'required',
        'bank_name'      =>  'required',
        'ifsc'           =>  'required',
        'branch'         =>  'required',
        'location'       =>  'required',
        'city'           =>  'required',
    ]);

    session_start();
    if(isset($_SESSION['username']) && isset($_SESSION['password']) && isset($_SESSION['dbName']))
    { 
        $username = $_SESSION['username'];
        $password = $_SESSION['password'];
        $dbName = $_SESSION['dbName'];

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
        $userId = $request->input('user_id');


        if (!$dynamicDB->getSchemaBuilder()->hasTable('bank')) {
            $dynamicDB->getSchemaBuilder()->create('bank', function (Blueprint $table) {
                $table->id();
                $table->bigInteger('user_id')->unsigned()->nullable();
                $table->string('ac_holder_name')->nullable();
                $table->string('ac_type')->nullable();
              //  $table->string('ac_nature')->nullable();
                $table->string('ac_number')->nullable();
                $table->string('bank_name')->nullable();
                $table->string('ifsc')->nullable();
                $table->string('branch')->nullable();
                $table->string('location')->nullable();
                $table->string('city')->nullable();
                $table->string('document_type')->nullable();
                $table->string('doc_path')->nullable();
                $table->timestamps();

                $table->foreign('user_id')->references('id')->on('user');

            });
        }
       
        
        $dynamicDB->table('bank')->insert([
            'user_id'   =>  $userId,
            'ac_holder_name' => $request->input('acc_holder_name'),
            'ac_type' => $request->input('acc_type'),
           // 'ac_nature' => $request->input('acc_nature'),
            'ac_number' => $request->input('acc_number'),
            'bank_name'   =>$request->input('bank_name'),
            'ifsc'       =>  $request->input('ifsc'),
            'branch'     =>  $request->input('branch'),
            'location'   =>  $request->input('location'),
            'city'       =>  $request->input('city'),
            'created_at'   =>  $date,
            'updated_at' => $date,
        ]);
        
        return response()->json(['success'=>true,'message' => 'bank details stored successfully']);
    }
    return response()->json(['success' => false,'message' => 'session out! login pls']);
}







public function employeeCheque(Request $request)
{

    $validatedData = $request->validate([
        'user_id'        =>  'required',
        'document_type' =>  'required',
        'doc_path' => 'file',
    ]);

    session_start();
    if(isset($_SESSION['username']) && isset($_SESSION['password']) && isset($_SESSION['dbName']))
    { 
        $username = $_SESSION['username'];
        $password = $_SESSION['password'];
        $dbName = $_SESSION['dbName'];

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
        $userId = $request->input('user_id');

     
        $chequePath = '';
        if($request->hasFile('doc_path'))
        {
            $file = $request->file('doc_path');
            //$fileName = $file->getClientOriginalName();
            $uniqueFolder = $userId . '_' . time();
            $filePath = $file->store('cheque_docs/' . $uniqueFolder);
            $chequePath = $filePath;

        }


       
        
        $dynamicDB->table('bank')->where('user_id',$userId)->update([
            'document_type' => $request->input('document_type'),
            'doc_path' => $chequePath,
            'updated_at' => $date,
        ]);
        
        return response()->json(['success'=>true,'message' => 'bank cheque stored successfully']);
    }
    return response()->json(['success' => false,'message' => 'session out! login pls']);
}









public function employeeFamily(Request $request)
{

    $validatedData = $request->validate([
        'user_id'        =>  'required',
        'relation_type' =>  'required',
        'name'        =>  'required',
        'qualification'   =>  'nullable',
        'country_code'      => 'required',
        'contact'      =>  'required',
        'occupation'           =>  'required',
        'uid'         =>  'required',
        'company_employee'       =>  'required',
       
    ]);

    session_start();
    if(isset($_SESSION['username']) && isset($_SESSION['password']) && isset($_SESSION['dbName']))
    { 
        $username = $_SESSION['username'];
        $password = $_SESSION['password'];
        $dbName = $_SESSION['dbName'];

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
        $userId = $request->input('user_id');


        if (!$dynamicDB->getSchemaBuilder()->hasTable('family')) {
            $dynamicDB->getSchemaBuilder()->create('family', function (Blueprint $table) {
                $table->id();
                $table->bigInteger('user_id')->unsigned()->nullable();
                $table->string('relation_type')->nullable();
                $table->string('name')->nullable();
                $table->string('qualification')->nullable();
                $table->string('country_code')->nullable();
                $table->string('contact')->nullable();
                $table->string('occupation')->nullable();
                $table->string('uid')->nullable();
                $table->string('comp_emp')->nullable();
                $table->timestamps();

                $table->foreign('user_id')->references('id')->on('user');

            });
        }
       
        
        $dynamicDB->table('family')->insert([
            'user_id'   =>    $userId,
            'relation_type'=> $request->input('relation_type' ),
            'name'         => $request->input('name'),
            'qualification'=> $request->input('qualification'),
            'country_code' => $request->input('country_code'),
            'contact'      => $request->input('contact'),
            'occupation'   => $request->input('occupation'),
            'uid'          => $request->input('uid'),
            'comp_emp'     => $request->input('company_employee'),
            'created_at'   => $date,
            'updated_at'   => $date,
        ]);
        
        return response()->json(['success'=>true,'message' => 'Family details stored successfully']);
    }
    return response()->json(['success' => false,'message' => 'session out! login pls']);
}










public function employeeDocument(Request $request)
{

    $validatedData = $request->validate([
        'user_id'             =>  'required',
        'pan_number'           =>  'required',
       // 'adhar_number'         =>  'required',
        'voter_id'             =>  'required',
        'driving_licence'      =>  'nullable',
 
        'passport_number'      =>  'nullable',
        'passport_to'          =>  'nullable|date',
        'passport_from'        =>  'nullable|date',
       
    ]);

    session_start();
    if(isset($_SESSION['username']) && isset($_SESSION['password']) && isset($_SESSION['dbName']))
    { 
        $username = $_SESSION['username'];
        $password = $_SESSION['password'];
        $dbName = $_SESSION['dbName'];

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
        $userId = $request->input('user_id');


       
        
        $dynamicDB->table('user')->where('id', $userId)->update([
            'pan_no' => $request->input('pan_number'),
            //'adhar_no' => $request->input('adhar_number'),
            'voter_id' => $request->input('voter_id'),
            'driving_licence' => $request->input('driving_licence'),
            'passport_no'   =>$request->input('passport_number'),
            'passport_to'       =>  $request->input('passport_to'),
            'passport_from'     =>  $request->input('passport_from'),
            'created_at'   =>  $date,
            'updated_at' => $date,
        ]);
        
        return response()->json(['success'=>true,'message' => 'Family details stored successfully']);
    }
    return response()->json(['success' => false,'message' => 'session out! login pls']);
}







public function employeeAddressProof(Request $request)
{

    $validatedData = $request->validate([
        'user_id'        =>  'required', 
        'document_type'  =>  'required',
        'document_path'  =>  'file',  
    ]);

    session_start();
    if(isset($_SESSION['username']) && isset($_SESSION['password']) && isset($_SESSION['dbName']))
    {
        $username = $_SESSION['username'];
        $password = $_SESSION['password'];
        $dbName   =  $_SESSION['dbName'];

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
        $userId = $request->input('user_id');

        $addressPath = '';
        if($request->hasFile('document_path'))
        {
            $file = $request->file('document_path');
            //$filaName = $file->getClientOriginalName();
            $uniqueFolder = $userId . '_' . time();
            $filePath = $file->store('addressProof/' . $uniqueFolder);
            $addressPath = $filePath;
        }

        if (!$dynamicDB->getSchemaBuilder()->hasTable('documents')) {
            $dynamicDB->getSchemaBuilder()->create('documents', function (Blueprint $table) {
                $table->id();
                $table->bigInteger('user_id')->unsigned()->nullable();
                $table->string('doc_type')->nullable();
                $table->string('doc_path')->nullable();
                $table->timestamps();

                $table->foreign('user_id')->references('id')->on('user');

            });
        }
       
        
        $dynamicDB->table('documents')->insert([
            'user_id'   =>  $userId,
            'doc_type' => $request->input('document_type'),
            'doc_path' => $addressPath,
            'created_at'   =>  $date,
            'updated_at' => $date,
        ]);
        return response()->json(['success'=> true, 'message' => 'document uploda successfully']);
        


    }

    return response()->json(['success' => false,'message' => 'session out! login pls']);
}







public function employeeSkill(Request $request)
{
    $validatedData = $request->validate([
        'user_id'      =>  'required',
        'skill_name'   =>  'nullable',
        'passing_year' =>  'nullable|date',   
    ]);

    session_start();
    if(isset($_SESSION['username']) && isset($_SESSION['password']) && isset($_SESSION['dbName']))
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
        $userId = $request->input('user_id');

        
        if (!$dynamicDB->getSchemaBuilder()->hasTable('skill')) {
            $dynamicDB->getSchemaBuilder()->create('skill', function (Blueprint $table) {
                $table->id();
                $table->bigInteger('user_id')->unsigned()->nullable();
                $table->string('skill_name')->nullable();
                $table->date('pass_year')->nullable();
                $table->timestamps();

                $table->foreign('user_id')->references('id')->on('user');

            });
        }
       
        
        $dynamicDB->table('skill')->insert([
            'user_id'     =>   $userId,
            'skill_name'  =>   $request->input('skill_name'),
            'pass_year'   =>   $request->input('passing_year'),
            'created_at'  =>   $date,
            'updated_at'  =>   $date,
        ]);
        return response()->json(['success' => true, 'message' => 'skill details upload successfully']);
        
        
    }
 
    return response()->json(['success' => false, 'message' => 'session out! login pls']);

}





public function employeeEmergencyDetails(Request $request)
{
    $validatedData = $request->validate([
        'user_id'      =>  'required',
        'eme_country_code'   =>  'nullable',
        'eme_mobile_no' =>  'nullable|',
        'eme_whatsapp_no' => 'nullable',
        'eme_email'    =>  'nullable',   
    ]);

    session_start();
    if(isset($_SESSION['username']) && isset($_SESSION['password']) && isset($_SESSION['dbName']))
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
        $userId = $request->input('user_id');

       
        
        $dynamicDB->table('user')->where('id', $userId)->update([
            'eme_country_code'  =>   $request->input('eme_country_code'),
            'eme_mobile'   =>   $request->input('eme_mobile_no'),
            'eme_whatsapp_no' => $request->input('eme_whatsapp_no'),
            'eme_email'     =>   $request->input('eme_email'),
            'created_at'  =>   $date,
            'updated_at'  =>   $date,
        ]);
        return response()->json(['success' => true, 'message' => 'employee emergency details upload successfully']);
        
        
    }
 
    return response()->json(['success' => false, 'message' => 'session out! login pls']);

}








public function employeeAddressDetails(Request $request)
{
    $validatedData = $request->validate([
        'user_id'      =>  'required',
        'country'   =>  'required',
        'state' =>  'required',   
        'city'  =>  'required',
        'pin_code' => 'required',
        'house_no'  => 'nullable',
        'address_line_one' => 'required',
    ]);

    session_start();
    if(isset($_SESSION['username']) && isset($_SESSION['password']) && isset($_SESSION['dbName']))
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
        $userId = $request->input('user_id');

        
        if (!$dynamicDB->getSchemaBuilder()->hasTable('address')) {
            $dynamicDB->getSchemaBuilder()->create('address', function (Blueprint $table) {
                $table->id();
                $table->bigInteger('user_id')->unsigned()->nullable();
                $table->string('country');
                $table->string('state' );
                $table->string('city')->nullable();
                $table->integer('pin')->nullable();
                $table->string('house_no')->nullable();
                $table->string('add_line_one')->nullable();
                $table->timestamps();

                $table->foreign('user_id')->references('id')->on('user');

            });
        }
       
        
        $dynamicDB->table('address')->insert([
            'user_id'     =>   $userId,
            'country'  =>   $request->input('country'),
            'state'   =>   $request->input('state'),
            'city'    =>  $request->input('city'),
            'pin' => $request->input('pin_code'),
            'house_no' => $request->input('house_no'),
            'add_line_one' => $request->input('address_line_one'),
            'created_at'  =>   $date,
            'updated_at'  =>   $date,
        ]);
        return response()->json(['success' => true, 'message' => 'address details upload successfully']);
        
        
    }
 
    return response()->json(['success' => false, 'message' => 'session out! login pls']);

}












public function empSearchByName(Request $request)
{

$validatedData = $request->validate(['emp_name' => 'required']);
    session_start();
    if(isset($_SESSION['username']) && isset($_SESSION['password']) && isset($_SESSION['dbName']))
    {
        $username = $_SESSION['username'];
        $password = $_SESSION['password'];
        $dbName   = $_SESSION['dbName'];
        
        $name = $request->input('emp_name');
       

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
   try
    {
        $dynamicDB = DB::connection('dynamic');
        $result = $dynamicDB->table('user')->where('preferred_name','like','%'.$name.'%')->get();
        if(count($result) == 0)
        {
            return response()->json(['message' => 'name not found']);
        }
        return response()->json(['success' => true, 'message' => $result]);
    }
   catch(\Exception $e)
    {
    return response()->json(['success'=>false,'error'=>$e->getMessage()],500);
    }

      

    }
    return response()->json(['success' => false, 'message' => 'session out! pls login']);
}





public function empSearchById(Request $request)
{
    $validatedData = $request->validate(['emp_id' => 'required']);
    
    session_start();
    if(isset($_SESSION['username']) && isset($_SESSION['password']) && $_SESSION['dbName'])
    {
        $username = $_SESSION['username'];
        $password = $_SESSION['password'];
        $dbName   = $_SESSION['dbName'];
        $empId = $request->input('emp_id');

        try{

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
    
            $dynamicDB =DB::connection('dynamic');
            $result = $dynamicDB->table('user')->where('id',$empId)->get();
            if(count($result) == 0)
            {
               return response()->json(['success'=> false, 'message'=> 'id not found']);
            }

            return response()->json(['success' => true, 'message'=> $result],200);
        }
        catch(\Exception $e)
        {
          return response()->json(['error' => $e->getMessage()],500);
       

        }
      
         
    }
    return response()->json(['success' => false, 'message' => 'session out! pls login']);

}






public function empSearchByLoc(Request $request)
{
    $validatedData = $request->validate(['location' => 'required']);
    
    session_start();
    if(isset($_SESSION['username']) && isset($_SESSION['password']) && $_SESSION['dbName'])
    {
        $username = $_SESSION['username'];
        $password = $_SESSION['password'];
        $dbName   = $_SESSION['dbName'];
        $location = $request->input('location');

        try{

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
    
            $dynamicDB =DB::connection('dynamic');
            //  if(empty($location))
            //  {
            //     return response()->json(['message'=> 'pls enter value']);
            //  }
            $result = $dynamicDB->table('address')->where('add_line_one','like','%'.$location.'%')->get();
            if(count($result) == 0)
            {
               return response()->json(['success'=> false, 'message'=> 'location not found']);
            }
           

            return response()->json(['success' => true, 'message'=> $result],200);
        }
        catch(\Exception $e)
        {
          return response()->json(['error' => $e->getMessage()],500);
       

        }
      
         
    }
    return response()->json(['success' => false, 'message' => 'session out! pls login']);

}







public function empSearchByDate(Request $request)
{
    $validatedData = $request->validate(['date' => 'required|date']);
    
    session_start();
    if(isset($_SESSION['username']) && isset($_SESSION['password']) && $_SESSION['dbName'])
    {
        $username = $_SESSION['username'];
        $password = $_SESSION['password'];
        $dbName   = $_SESSION['dbName'];
        $date = $request->input('date');

        try{

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
    
            $dynamicDB =DB::connection('dynamic');
            //  if(empty($location))
            //  {
            //     return response()->json(['message'=> 'pls enter value']);
            //  }
            $result = $dynamicDB->table('user')->where('created_at','like','%'.$date.'%')->get();
            if(count($result) == 0)
            {
               return response()->json(['success'=> false, 'message'=> 'date not found']);
            }
           

            return response()->json(['success' => true, 'message'=> $result],200);
        }
        catch(\Exception $e)
        {
          return response()->json(['error' => $e->getMessage()],500);
       

        }
      
         
    }
    return response()->json(['success' => false, 'message' => 'session out! pls login']);

}







public function empSearchByhalfYear(Request $request)
{
   
    
    session_start();
    if(isset($_SESSION['username']) && isset($_SESSION['password']) && $_SESSION['dbName'])
    {
        $username = $_SESSION['username'];
        $password = $_SESSION['password'];
        $dbName   = $_SESSION['dbName'];
        $currentDate = Carbon::now()->timezone('Asia/Kolkata');
        $halfYearAgo = Carbon::now()->timezone('Asia/Kolkata')->subMonths(6);
        
       

        try{

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
    
            $dynamicDB =DB::connection('dynamic');
            
           
        //    $result = $dynamicDB->table('user')->where('created_at','>=',$halfYearAgo)->where('created_at','<=',$currentDate)->get();
            $result = $dynamicDB->table('user')->where('created_at', '<=', $halfYearAgo)->get();

            if(count($result) == 0)
            {
               return response()->json(['success'=> false, 'message'=> 'date not found']);
            }
           

            return response()->json(['success' => true, 'message'=> $result],200);
        }
        catch(\Exception $e)
        {
          return response()->json(['error' => $e->getMessage()],500);
       

        }
      
         
    }
    return response()->json(['success' => false, 'message' => 'session out! pls login']);

}







public function empSearchByYear(Request $request)
{
    session_start();

    if (isset($_SESSION['username']) && isset($_SESSION['password']) && isset($_SESSION['dbName'])) {
        $username = $_SESSION['username'];
        $password = $_SESSION['password'];
        $dbName = $_SESSION['dbName'];

        try {
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

            
            //$twelveMonthsAgo = Carbon::now()->timezone('Asia/Kolkata')->subMonths(12);
            $oneYearAgo = Carbon::now()->timezone('Asia/Kolkata')->subYear();
            $currentDate = Carbon::now()->timezone('Asia/Kolkata');

            $result = $dynamicDB->table('user')
            ->where('created_at', '<=', $oneYearAgo) 
             //->where('created_at', '>', $currentDate->endOfDay())  
                ->get();

            if (count($result) == 0) {
                return response()->json(['success' => false, 'message' => 'No records found'], 404);
            }

            return response()->json(['success' => true, 'message' => $result], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    return response()->json(['success' => false, 'message' => 'Session expired. Please log in'], 401);
}







public function Usersdata() 
    {

           return Excel::download(new UsersExport, 'users.xlsx');          
      
    }


    
public function MonthsData() 
{
    //session_start();
    
    return Excel::download(new MonthsData(), 'exported_data.xlsx');

}











public function empMonthWiseExcel(Request $request)
{
    session_start();
    if(isset($_SESSION['username']) && isset($_SESSION['password']) && isset($_SESSION['dbName']))
    {
      $username = $_SESSION['username'];
      $password = $_SESSION['password'];
      $dbName   = $_SESSION['dbName'];
      
      $YearwiseDataExport = new YearwiseData($username,$password,$dbName);
    
     return Excel::download($YearwiseDataExport,'monthwise_employee.xlsx');  
      
    }
    else
    {
      return response()->json(['success' => false, 'message' => 'session out! login pls']);
    }
  
}











public function empThreeMonthWiseExcel(Request $request)
{
    session_start();
    if(isset($_SESSION['username']) && isset($_SESSION['password']) && isset($_SESSION['dbName']))
    {
      $username = $_SESSION['username'];
      $password = $_SESSION['password'];
      $dbName   = $_SESSION['dbName'];
      
      $YearwiseDataExport = new YearwiseData($username,$password,$dbName);
    
     return Excel::download($YearwiseDataExport,'3monthwise_employee.xlsx');  
      
    }
    else
    {
      return response()->json(['success' => false, 'message' => 'session out! login pls']);
    }
  
}










public function empSixMonthWiseExcel(Request $request)
{
    session_start();
    if(isset($_SESSION['username']) && isset($_SESSION['password']) && isset($_SESSION['dbName']))
    {
      $username = $_SESSION['username'];
      $password = $_SESSION['password'];
      $dbName   = $_SESSION['dbName'];
      
      $YearwiseDataExport = new YearwiseData($username,$password,$dbName);
    
     return Excel::download($YearwiseDataExport,'6monthwise_employee.xlsx');  
      
    }
    else
    {
      return response()->json(['success' => false, 'message' => 'session out! login pls']);
    }
  
}














public function empYearWiseExcel(Request $request)
{
    session_start();
    if(isset($_SESSION['username']) && isset($_SESSION['password']) && isset($_SESSION['dbName']))
    {
      $username = $_SESSION['username'];
      $password = $_SESSION['password'];
      $dbName   = $_SESSION['dbName'];
      
      $YearwiseDataExport = new YearwiseData($username,$password,$dbName);
   
    
     return Excel::download($YearwiseDataExport,'yearwise_employee.xlsx');  
      
    }
    else
    {
      return response()->json(['success' => false, 'message' => 'session out! login pls']);
    }
  
}


























}
