<?php

namespace App\Http\Controllers\Api;
use App\Models\User;
use App\Models\CompanyUserAccess;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Session\Middleware\StartSession;
use App\Models\CompanyModuleAccess;
use App\Models\RoleMaster; 
use App\Models\RoleUserAssign;







class EmployeeRegistration extends Controller
{
    

public function employeeBasic(Request $request)
{
    $token = $request->user()->currentAccessToken();
    $tokenRole = $token['tokenable']['role'];
    $status = $token['tokenable']['status'];
    $code = $token['tokenable']['company_code'];
    $company = User::where('company_code',$code)->first();
    $maxEmp = $company->total;
    $username = $company->username;
    $password = $company->dbPass;
    $dbName = $company->dbName;
    $moduleId = 3;
    $empModule = CompanyModuleAccess::where('company_code', $code)
    ->where('module_id', $moduleId)
    ->first();
    $date = Carbon::now()->timezone('Asia/Kolkata')->format('Y-m-d H:i:s');
    $accessEmp = $token['tokenable']['create'];
     
        if(!$empModule)
        {
            return response()->json(['success' => false,'message' => 'you can not access employee module'],403);
        }
        if($tokenRole == 'admin' && $accessEmp != 1)
        {
            return response()->json(['success' => false,'message' => 'you have no permission'],403);
        }
        if($tokenRole == 'admin' && $accessEmp == 1 ||$tokenRole && $tokenRole == 'Super Admin')
        {  
            $validatedData = $request->validate([
                'title' => 'nullable',
                'first_name' => 'required',
                'middle_name' => 'nullable',
                'last_name'   => 'required',
                'preferred_name' => 'required',
                'dob'       =>  'required',
                'blood_group' => 'required',
            ]);
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
            if (!$dynamicDB->getSchemaBuilder()->hasTable('company_employee'))
            {
                $dynamicDB->getSchemaBuilder()->create('company_employee', function (Blueprint $table)
                {
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

            $empCount = $dynamicDB->table('company_employee')->count();
            //  print_r($empCount);die;
             if($maxEmp >= $empCount)
             {
                $dynamicDB->table('company_employee')->insert([
                    'title' =>     $request->input('title'),
                    'first_name' => $request->input('first_name'),
                    'middle_name' => $request->input('middle_name'),
                    'last_name'  => $request->input('last_name'),
                    'preferred_name'  =>  $request->input('preferred_name'),
                    'DOB' => $request->input('DOB'),
                    'blood_group' => $request->input('blood_group'),
                    'created_at' =>      $date,
                    'updated_at' =>      $date,         
           
                ]);
                $lastInsertedRecord = $dynamicDB->table('company_employee')->orderBy('id','desc')->first();

                return response()->json(['success' => true,'empData'=>$lastInsertedRecord,'message' => 'employee basic details stored successfully'],201);

             }
             return response()->json(['message' => 'Maximum employee limit reached. Cannot add more.'],403); 


          
        }
            
      return response()->json(['success' => false,'message' => 'you have no permission'],403);    
   

}






public function employeeMarital(Request $request)
{
   
    $token = $request->user()->currentAccessToken();
    $tokenRole = $token['tokenable']['role'];
    // print_r($tokenRole); die;

    // if($tokenRole == 'admin' || $tokenRole == 'root')
    // {
    //     return response()->json(['success' => true,'token'=>$tokenRole,'message' => 'found']);
    // }  
    $status = $token['tokenable']['status'];
    $code = $token['tokenable']['company_code'];
    // print_r($code); die;
    // $maxEmp = User::where('company_code',$code)->value('total');
    $company = User::where('company_code',$code)->first();
    // print_r($tokenRole); die;
    $maxEmp = $company->total;
    $username = $company->username;
    $password = $company->dbPass;
    $dbName = $company->dbName;
    $moduleId = 5;
    $empModule = CompanyModuleAccess::where('company_code', $code)
    ->where('module_id', $moduleId)
    ->first();
 
    $date = Carbon::now()->timezone('Asia/Kolkata')->format('Y-m-d H:i:s');
    $accessEmp = $token['tokenable']['create'];
        if(!$empModule)
        {
            return response()->json(['success' => false,'message' => 'you can not access employee module'],403);
        }
        if($tokenRole == 'admin' && $accessEmp != 1)
        {
            return response()->json(['success' => false,'message' => 'you have no permission'],403);
        }
        if($tokenRole == 'admin' && $accessEmp == 1 || $tokenRole && $tokenRole == 'Super Admin')
        { 
            $validatedData = $request->validate([
                'emp_id' => 'required',
                'marital_status' => 'required',
                'marital_doc' => 'file|mimes:jpeg,png,pdf',
             ]);
             $emp_id = $request->emp_id;
                Config::set('database.connections.dynamic', [
                 'driver' => 'mysql',
                 'host' => 'localhost',
                 'database' => $dbName,
                 'username' => $username,
                 'password' => $password,
                 'collation' => 'utf8mb4_unicode_ci',
             ]);
             $dynamicDB = DB::connection('dynamic');
             $empCount = $dynamicDB->table('company_employee')->count();
               
        if($maxEmp >= $empCount)
        {
            if ($dynamicDB->table('company_employee')->where('id', $emp_id)->exists())
            {
                if ($request->hasFile('marital_doc')) {
                    $file = $request->file('marital_doc');
                    $filePath = $file->store('marital_docs/' . $uniqueFolderName);
                    $maritalDocPath = $filePath;
                    $dynamicDB->table('company_employee')->where('id', $emp_id)->update([
                        'marital_status' => $request->input('marital_status'),
                        'marital_doc' => $maritalDocPath,
                        'updated_at' => $date,
                    ]);

                    $lastInsertedRecord = $dynamicDB->table('company_employee')->orderBy('id','desc')->first();

                    return response()->json(['success'=>true,'empData'=>$lastInsertedRecord,'message' => 'Employee marital details stored successfully'],201);
                } 
                    return response()->json(['message' => 'Please add a valid image file for marital_doc'], 400);
            }
            return response()->json(['message' => 'Employee with ID ' . $emp_id . ' not found.'], 404);
        }
        return response()->json(['message' => 'Maximum employee limit reached. Cannot add more.'], 400);

        }
       

}





public function employeeNationality(Request $request)
{
    $token = $request->user()->currentAccessToken();
    $tokenRole = $token['tokenable']['role']; 
    $status = $token['tokenable']['status'];
    $code = $token['tokenable']['company_code'];
    $company = User::where('company_code',$code)->first();
    $maxEmp = $company->total;
    $username = $company->username;
    $password = $company->dbPass;
    $dbName = $company->dbName;
    $moduleId = 3;
    $empModule = CompanyModuleAccess::where('company_code', $code)
    ->where('module_id', $moduleId)
    ->first();
    //  print_r($empModule); die;
    $date = Carbon::now()->timezone('Asia/Kolkata')->format('Y-m-d H:i:s');
    $accessEmp = $token['tokenable']['create'];
        if(!$empModule)
        {
            return response()->json(['success' => false,'message' => 'you can not access employee module'],403);
        }
        if($tokenRole == 'admin' && $accessEmp != 1)
        {
            return response()->json(['success' => false,'message' => 'you have no permission'],403);
        }
        // if($tokenRole != 'admin' || $tokenRole != 'Super Admin')
        // {
        //     return response()->json(['success' => false,'message' => 'you have no permission'],403);
        // }
        if($tokenRole == 'admin' && $accessEmp && $accessEmp == 1 || $tokenRole && $tokenRole == 'Super Admin')
        {
            $validatedData = $request->validate([
                'emp_id'  =>  'required',
                'nationality_one' => 'required',
            ]);
            $emp_id = $request->emp_id;
            Config::set('database.connections.dynamic', [
                'driver' => 'mysql',
                'host' => 'localhost',
                'database' => $dbName,
                'username' => $username,
                'password' => $password,
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
                'engine' => null,
            ]);
            $dynamicDB = DB::connection('dynamic');
            $empCount = $dynamicDB->table('company_employee')->count();
                if($maxEmp >= $empCount)
                {
                    if ($dynamicDB->table('company_employee')->where('id', $emp_id)->exists()) 
                    {
                        if($request->hasFile('nationality_doc'))
                     {
                         $file = $request->file('nationality_doc');
                         $filePath = $file->store('nationality_docs/' . $uniqueFolderName);
                         $nationalityDocPath = $filePath;   
                         $dynamicDB->table('company_employee')->where('id',$emp_id)->update([
                          'nationality_one' => $request->input('nationality_one'),
                          'nationality_two' => $request->input('nationality_two'),
                          'nationality_doc' => $nationalityDocPath,
                          'updated_at'   =>   $date,
                        ]);
                        $lastInsertedRecord = $dynamicDB->table('company_employee')->orderBy('id','desc')->first();
                        return response()->json(['success'=> true,'empData'=>$lastInsertedRecord,'message' => 'nationality data stored successfully'],201);                       
                        
                     }
                     else 
                     {
                        return response()->json(['success'=>false,'message' => 'Please add a valid image file for mnationality doc'], 400);
                     }         
                    }
                    else 
                    {
                        return response()->json(['success'=>false,'message' => 'Employee with ID ' . $emp_id . ' not found.'], 404);
                    }
                }
                return response()->json(['message' => 'Maximum employee limit reached. Cannot add more.'], 400);
  
        }       
}




public function employeeCommunication(Request $request)
{
    
    $token = $request->user()->currentAccessToken();
    $tokenRole = $token['tokenable']['role']; 
    $status = $token['tokenable']['status'];
    $code = $token['tokenable']['company_code'];
    $company = User::where('company_code',$code)->first();
    $maxEmp = $company->total;
    $username = $company->username;
    $password = $company->dbPass;
    $dbName = $company->dbName;
    $moduleId = 3;
    $empModule = CompanyModuleAccess::where('company_code', $code)
    ->where('module_id', $moduleId)
    ->where('status', 'active')
    ->first();
    //  print_r($username); die;
    $date = Carbon::now()->timezone('Asia/Kolkata')->format('Y-m-d H:i:s');
    $accessEmp = $token['tokenable']['create'];
        if(!$empModule)
        {
            return response()->json(['success' => false,'message' => 'you can not access employee module'],403);
        }
        if($tokenRole == 'admin' && $accessEmp != 1)
        {
            return response()->json(['success' => false,'message' => 'you have no permission'],403);
        }
        if($tokenRole == 'admin' && $accessEmp && $accessEmp == 1 || $tokenRole && $tokenRole == 'Super Admin')
        {
             
          $validatedData = $request->validate([
           'emp_id'   =>  'required',
           'country_code' => 'required',
           'mobile_number' => 'required',
           'home_phone_number' => 'nullable',
           'work_phone_number' => 'nullable',
           'whatsapp_number'   =>  'nullable',
          // 'emergency_phone_number'  => 'nullable',
           'email'       =>   'required',
           'void'        =>  'nullable',
               
            ]);
         $emp_id = $request->emp_id;
         Config::set('database.connections.dynamic', [
             'driver' => 'mysql',
             'host' => 'localhost',
             'database' => $dbName,
             'username' => $username,
             'password' => $password,
             'collation' => 'utf8mb4_unicode_ci',
             'prefix' => '',
             'strict' => true,
             'engine' => null,
         ]);
          $dynamicDB = DB::connection('dynamic');
          $empCount = $dynamicDB->table('company_employee')->count();
          if($maxEmp >= $empCount)
          {
            if ($dynamicDB->table('company_employee')->where('id', $emp_id)->exists()) 
            {
              $dynamicDB->table('company_employee')->where('id',$emp_id)->update([
                  'country_code' => $request->input('country_code'),
                  'mobile_no'   =>  $request->input('mobile_number'),
                  'home_phone_no'  => $request->input('home_phone_number'),
                  'work_phone_no'   =>  $request->input('work_phone_number'),
                  'whatsapp_no'     =>  $request->input('whatsapp_number'),
                  'email'       =>   $request->input('email'),
                  'voter_id'     =>  $request->input('void'),
               ]);
                   $lastInsertedRecord = $dynamicDB->table('company_employee')->orderBy('id','desc')->first();

                         return response()->json(['success'=>true,'empData' => $lastInsertedRecord,'message' => 'emergency details stored successfully'],201);
             }
                else 
                {
                    return response()->json(['message' => 'Employee with ID ' . $emp_id . ' not found.'], 404);
                }
          }
                    return response()->json(['message' => 'Maximum employee limit reached. Cannot add more.'], 400);
  
        }

}












public function employeeExperience(Request $request)
{
    try{
        $token = $request->user()->currentAccessToken();
        $tokenRole = $token['tokenable']['role'];
        $status = $token['tokenable']['status'];
        $code = $token['tokenable']['company_code'];
        $company = User::where('company_code',$code)->first();
        $maxEmp = $company->total;
        $username = $company->username;
        $password = $company->dbPass;
        $dbName = $company->dbName;
        $moduleId = 3;
        $empModule = CompanyModuleAccess::where('company_code',$code)->where('status','active')
        ->where('module_id',$moduleId)->first();
        // print_r($empModule);die;
        $date = Carbon::now()->timezone('Asia/Kolkata')->format('Y-m-d H:i:s');
        $accessEmp = $token['tokenable']['create'];
        // echo $accessEmp;die;
        if(!$empModule)
        {
            return response()->json(['success'=>false,'message'=>'you can not access employee module'],403);
        }
        if($tokenRole == 'admin' && $accessEmp != 1)
        {
            return response()->json(['success' => false,'message' => 'you have no permission'],403);
        }
        if($accessEmp == 1 && $tokenRole == 'admin' || $tokenRole && $tokenRole == 'Super Admin')
        { 
           $validatedData = $request->validate([
          'emp_id'        =>  'required',
          'is_current_emp' =>  'required',
          'company_name'   =>  'required',
          'project_name'   =>  'required',
          'designation'    =>  'required',
          'start_date'     =>  'required',
          'end_date'       =>  'required',
      ]);
      $emp_id = $request->emp_id;
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
         if (!$dynamicDB->getSchemaBuilder()->hasTable('experience')) {
            $dynamicDB->getSchemaBuilder()->create('experience', function (Blueprint $table) {
                $table->id();
                $table->bigInteger('emp_id')->unsigned()->nullable();
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
           
                $table->foreign('emp_id')->references('id')->on('company_employee');
               
            });
        }

        $empCount = $dynamicDB->table('experience')->count();
        if($maxEmp >= $empCount)
        {
          if ($dynamicDB->table('company_employee')->where('id', $emp_id)->exists()) 
          {
            $dynamicDB->table('experience')->insert([
             'emp_id'   =>  $emp_id,
             'is_current_emp' => $request->input('is_current_emp'),
             'comp_name' => $request->input('company_name'),
             'proj_name' => $request->input('project_name'),
             'designation' => $request->input('designation'),
             'start_date'   =>$request->input('start_date'),
             'end_date'   =>  $request->input('end_date'),
             'created_at'  => $date,
             'updated_at' => $date,
            ]);
           $lastInsertedRecord = $dynamicDB->table('experience')->orderBy('id','desc')->first();      
            return response()->json(['success'=>true,'message' => 'Experience details stored successfully','empData'=>$lastInsertedRecord],201);
          }
         
            return response()->json(['message' => 'Employee with ID ' . $emp_id . ' not found.'], 404);
          
            
        }
        return response()->json(['message' => 'Maximum employee limit reached. Cannot add more.'], 400);

        }
        return response()->json(['success' => false,'message' => 'you have no permission'],403);

    }
    catch(\Exception $e)
    {
        return response()->json(['message' => 'An error occurred. Please try again.'], 500); 
    }
}





public function employeeExpTime(Request $request)
{
    try{
        $token = $request->user()->currentAccessToken();
        $tokenRole = $token['tokenable']['role'];
        $status = $token['tokenable']['status'];
        $code = $token['tokenable']['company_code'];
        $company = User::where('company_code',$code)->first();
        $maxEmp = $company->total;
        $username = $company->username;
        $password = $company->dbPass;
        $dbName = $company->dbName;
        $moduleId = 3;
        $empModule = CompanyModuleAccess::where('company_code',$code)->where('module_id',$moduleId)
        ->where('status',$status)->first();
        $date = Carbon::now()->timezone('Asia/kolkata')->format('Y-m-d H:i:s');
        $accessEmp = $token['tokenable']['create'];
        if(!$empModule)
        {
            return response()->json(['success'=>false,'message'=>'you can not access employee module'],403);

        }
        if($tokenRole == 'admin' && $accessEmp != 1)
        {
            return response()->json(['success'=>false,'message'=>'you have no permission'],403);
        }
        if($accessEmp == 1 && $tokenRole == 'admin' || $tokenRole && $tokenRole == 'Super Admin')
        {
            $validatedData = $request->validate([
             'emp_id'        =>  'required',
             'total_year' =>  'required',
             'total_month'   =>  'required'
              ]);
            $emp_id = $request->emp_id;
            Config::set('database.connections.dynamic', [
                'driver' => 'mysql',
                'host' => 'localhost',
                'database' => $dbName,
                'username' => $username,
                'password' => $password,
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
                'engine' => null,
               ]);
               $dynamicDB = DB::connection('dynamic');

               if ($dynamicDB->getSchemaBuilder()->hasTable('experience')) 
               {
                  $empCount = $dynamicDB->table('experience')->count();
          
                  if ($dynamicDB->table('company_employee')->where('id', $emp_id)->exists()) 
                  {
                    if ($maxEmp >= $empCount)
                    {
                      $documentPath = $filePath;
          
                           $dynamicDB->table('experience')->where('emp_id',$emp_id)->update([
                             'total_year_exp' => $request->input('total_year'),
                             'total_month_exp' => $request->input('total_month'),
                             'updated_at' => $date,
                              ]);
                            $lastInsertedRecord = $dynamicDB->table('experience')->orderBy('id','desc')->first();
                             return response()->json(['success'=>true,'empData'=>$lastInsertedRecord,'message' => 'qualification document stored successfully'],201);
                                              
                     }
                      return response()->json(['message' => 'Maximum employee limit reached. Cannot add more.'], 400);
                  }
                  return response()->json(['message' => 'Employee with ID ' . $emp_id . ' not found.'], 404);
               }
               return response()->json(['message' => 'table not found.'], 404);                               
        }
        return response()->json(['success' => false,'message' => 'you have no permission'],403);
    }
    catch(\Exception $e)
    {
        return response()->json(['message' => 'An error occurred. Please try again.'], 500); 
    }
}




public function employeeExpDoc(Request $request)
{
    try
    {
     $token = $request->user()->currentAccessToken();
     $tokenRole = $token['tokenable']['role'];
     $status = $token['tokenable']['status'];
     $code = $token['tokenable']['company_code'];
     $company = User::where('company_code',$code)->first();
     $maxEmp = $company->total;
     $username = $company->username;
     $password = $company->dbPass;
     $dbName = $company->dbName;
     $moduleId = 3;
     $empModule = CompanyModuleAccess::where('company_code',$code)->where('module_id',$moduleId)
     ->where('status',$status)->first();
     $date = Carbon::now()->timezone('Asia/kolkata')->format('Y-m-d H:i:s');
     $accessEmp = $token['tokenable']['create']; 
     if(!$empModule)
     {
        return response()->json(['success' => false,'message' => 'you can not access employee module'],403);
     }
     if($tokenRole == 'admin' && $accessEmp != 1)
     {
        return response()->json(['success' => false,'message' => 'you have no permission'],403);
     }
    if($accessEmp == 1 && $tokenRole == 'admin' || $tokenRole && $tokenRole == 'Super Admin')
        {
            $validatedData = $request->validate([
                'emp_id'        =>  'required',
                'document_type' =>  'required',
                'exp_certificate' => 'file|mimes:jpeg,png,pdf',
             ]);
            $emp_id = $request->emp_id;
            Config::set('database.connections.dynamic', [
              'driver' => 'mysql',
              'host' => 'localhost',
              'database' => $dbName,
              'username' => $username,
              'password' => $password,
              'collation' => 'utf8mb4_unicode_ci',
              'prefix' => '',
              'engine' => null,
             ]);
               $dynamicDB = DB::connection('dynamic');

               if ($dynamicDB->getSchemaBuilder()->hasTable('experience')) 
               {
                  $empCount = $dynamicDB->table('experience')->count();
          
                  if ($dynamicDB->table('company_employee')->where('id', $emp_id)->exists()) 
                  {
                    if ($maxEmp >= $empCount)
                    {
                       if($request->hasFile('exp_certificate'))
                         {
                            $file = $request->file('exp_certificate');
                            $filePath = $file->store('experience_docs/' . $uniqueFolder);
                            $experiencePath = $filePath;

                            $dynamicDB->table('experience')->where('emp_id',$emp_id)->update([
                                         'document_type' => $request->input('document_type'),
                                         'exp_doc'  =>  $experiencePath,
                                         'updated_at' => $date,
                                     ]);      
                            $lastInsertedRecord = $dynamicDB->table('experience')->orderBy('id','desc')->first();
                             return response()->json(['success'=>true,'message' => 'experience document stored successfully','empData'=>$lastInsertedRecord],201);
                                              
                          }
                            return response()->json(['message' => 'Please add a valid image file for experience doc'], 400);
                     }
                      return response()->json(['message' => 'Maximum employee limit reached. Cannot add more.'], 400);
                  }
                  return response()->json(['message' => 'Employee with ID ' . $emp_id . ' not found.'], 404);
               }
               return response()->json(['message' => 'table not found.'], 404);                               
        }
        return response()->json(['success' => false,'message' => 'you have no permission'],403);
    
   
    }
    catch(\Exception $e)
    {
        return response()->json(['message' => 'An error occurred. Please try again.'], 500);
    }
}




public function employeeBank(Request $request)
{
    try
    {
      $token = $request->user()->currentAccessToken();
      $tokenRole = $token['tokenable']['role'];
      $status = $token['tokenable']['status'];
      $code = $token['tokenable']['company_code'];
      $company = User::where('company_code',$code)->first();
      $maxEmp = $company->total;
      $username = $company->username;
      $password = $company->dbPass;
      $dbName = $company->dbName;
      $moduleId = 3;
      $empModule = CompanyModuleAccess::where('company_code',$code)->where('module_id',$moduleId)
      ->where('status',$status)->first();
      $accessEmp = $token['tokenable']['create'];
      $date = Carbon::now()->timezone('Asia/kolkata')->format('Y-m-d H:i:s');
      if($tokenRole == 'admin' && $accessEmp != 1)
      {
        return response()->json(['success' => false,'message' => 'you have no permission'],403);
      }
      if(!$empModule)
      {
        return response()->json(['success' => false,'message' => 'you can not access employee module'],403);
      }
      if($tokenRole == 'admin' && $accessEmp == 1 || $tokenRole && $tokenRole == 'Super Admin')
      {
        $validatedData = $request->validate([
            'emp_id'        =>  'required',
            'acc_holder_name' =>  'required',
            'acc_type'        =>  'required',
            'acc_number'      => 'required',
            'bank_name'      =>  'required',
            'ifsc'           =>  'required',
            'branch'         =>  'required',
            'location'       =>  'required',
            'city'           =>  'required',
        ]);
        $emp_id = $request->emp_id;
        Config::set('database.connections.dynamic', [
            'driver' => 'mysql',
            'host' => 'localhost',
            'database' => $dbName,
            'username' => $username,
            'password' => $password,
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'engine' => null,
        ]);
      $dynamicDB = DB::connection('dynamic');
      if (!$dynamicDB->getSchemaBuilder()->hasTable('bank'))
      {
        $dynamicDB->getSchemaBuilder()->create('bank', function (Blueprint $table) {
           $table->id();
           $table->bigInteger('emp_id')->unsigned()->nullable();
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
            
           $table->foreign('emp_id')->references('id')->on('company_employee');
            
       });
     }
      $empCount = $dynamicDB->table('bank')->count();
      if ($maxEmp >= $empCount)
      {
        if ($dynamicDB->table('company_employee')->where('id', $emp_id)->exists()) 
        {
            $dynamicDB->table('bank')->insert([
              'emp_id'   =>  $emp_id,
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
           $lastInsertedRecord = $dynamicDB->table('bank')->orderBy('id','desc')->first();
           return response()->json(['success'=>true,'message' => 'bank details stored successfully','empData'=>$lastInsertedRecord],201);                                  
        }
        return response()->json(['message' => 'Employee with ID ' . $emp_id . ' not found.'], 404);
       }
       return response()->json(['message' => 'Maximum employee limit reached. Cannot add more.'], 400);

      }
      return response()->json(['success' => false,'message' => 'you have no permission'],403);

    }
    catch(\Exception $e)
    {
        return response()->json(['message' => 'An error occurred. Please try again.'], 500);
    }
}




public function employeeCheque(Request $request)
{
    try
    {
     $token = $request->user()->currentAccessToken();
     $tokenRole = $token['tokenable']['role'];
     $status = $token['tokenable']['status'];
     $code = $token['tokenable']['company_code'];
     $company = User::where('company_code',$code)->first();
     $maxEmp = $company->total;
     $username = $company->username;
     $password = $company->dbPass;
     $dbName = $company->dbName;
     $moduleId = 3;
     $empModule = CompanyModuleAccess::where('company_code',$code)->where('module_id',$moduleId)
     ->where('status',$status)->first();
     $date = Carbon::now()->timezone('Asia/kolkata')->format('Y-m-d H:i:s');
     $accessEmp = $token['tokenable']['create']; 
     if(!$empModule)
     {
        return response()->json(['success' => false,'message' => 'you can not access employee module'],403);
     }
     if($tokenRole == 'admin' && $accessEmp != 1)
     {
        return response()->json(['success' => false,'message' => 'you have no permission'],403);
     }
    if($accessEmp == 1 && $tokenRole == 'admin' || $tokenRole && $tokenRole == 'Super Admin')
        {
            $validatedData = $request->validate([
                'emp_id'        =>  'required',
                'document_type' =>  'required',
                'doc_path' => 'file|mimes:jpeg,png,pdf',
            ]);
            $emp_id = $request->emp_id;
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

               if ($dynamicDB->getSchemaBuilder()->hasTable('experience')) 
               {
                  $empCount = $dynamicDB->table('bank')->count();
          
                  if ($dynamicDB->table('company_employee')->where('id', $emp_id)->exists()) 
                  {
                    if ($maxEmp >= $empCount)
                    {
                       if($request->hasFile('doc_path'))
                         {
                            $file = $request->file('doc_path');
                            $filePath = $file->store('bank_doc/' . $uniqueFolder);
                            $experiencePath = $filePath;

                            $dynamicDB->table('bank')->where('emp_id',$emp_id)->update([
                              'document_type' => $request->input('document_type'),
                              'doc_path' => $filePath,
                              'updated_at' => $date,  
                            ]);  
                            $lastInsertedRecord = $dynamicDB->table('bank')->orderBy('id','desc')->first();
                             return response()->json(['success'=>true,'message' => 'experience document stored successfully','empData'=>$lastInsertedRecord],201);
                                              
                          }
                            return response()->json(['message' => 'Please add a valid image file for experience doc'], 400);
                     }
                      return response()->json(['message' => 'Maximum employee limit reached. Cannot add more.'], 400);
                  }
                  return response()->json(['message' => 'Employee with ID ' . $emp_id . ' not found.'], 404);
               }
               return response()->json(['message' => 'table not found.'], 404);                               
        }    
   
    }
    catch(\Exception $e)
    {
        return response()->json(['message' => 'An error occurred. Please try again.',$e->getMessage()], 500);
    }
}




public function employeeFamily(Request $request)
{
    try
    {
        $token = $request->user()->currentAccessToken();
        $tokenRole = $token['tokenable']['role'];
        $status = $token['tokenable']['status'];
        $code = $token['tokenable']['company_code'];
        $company = User::where('company_code',$code)->first();
        $maxEmp = $company->total;
        $username = $company->username;
        $password = $company->dbPass;
        $dbName = $company->dbName;
        $moduleId = 3;
        $empModule = CompanyModuleAccess::where('company_code',$code)->where('module_id',$moduleId)
        ->where('status',$status)->first();
        $accessEmp = $token['tokenable']['create'];
        $date = Carbon::now()->timezone('Asia/kolkata')->format('Y-m-d H:i:s');
        if($tokenRole == 'admin' && $accessEmp != 1)
        {
          return response()->json(['success' => false,'message' => 'you have no permission'],403);
        }
        if(!$empModule)
        {
          return response()->json(['success' => false,'message' => 'you can not access employee module'],403);
        }
        if($tokenRole == 'admin' && $accessEmp == 1 || $tokenRole && $tokenRole == 'Super Admin')
        {
            $validatedData = $request->validate([
                'emp_id'        =>  'required',
                'relation_type' =>  'required',
                'name'        =>  'required',
                'qualification'   =>  'nullable',
                'country_code'      => 'required',
                'contact'      =>  'required',
                'occupation'           =>  'required',
                'uid'         =>  'required',
                'company_employee'       =>  'required',
                      
            ]);
          $emp_id = $request->emp_id;
          Config::set('database.connections.dynamic', [
              'driver' => 'mysql',
              'host' => 'localhost',
              'database' => $dbName,
              'username' => $username,
              'password' => $password,
              'collation' => 'utf8mb4_unicode_ci',
              'prefix' => '',
              'engine' => null,
          ]);
        $dynamicDB = DB::connection('dynamic');
        if (!$dynamicDB->getSchemaBuilder()->hasTable('family')) {
            $dynamicDB->getSchemaBuilder()->create('family', function (Blueprint $table) {
                $table->id();
                $table->bigInteger('emp_id')->unsigned()->nullable();
                $table->string('relation_type')->nullable();
                $table->string('name')->nullable();
                $table->string('qualification')->nullable();
                $table->string('country_code')->nullable();
                $table->string('contact')->nullable();
                $table->string('occupation')->nullable();
                $table->string('uid')->nullable();
                $table->string('comp_emp')->nullable();
                $table->timestamps();
           
                $table->foreign('emp_id')->references('id')->on('company_employee');
           
            });
        }
        $empCount = $dynamicDB->table('family')->count();
        if ($maxEmp >= $empCount)
        {
          if ($dynamicDB->table('company_employee')->where('id', $emp_id)->exists()) 
          {
            $dynamicDB->table('family')->insert([
                'emp_id'   =>    $emp_id,
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
             $lastInsertedRecord = $dynamicDB->table('family')->orderBy('id','desc')->first();
             return response()->json(['success'=>true,'message' => 'Family details stored successfully','empData'=>$lastInsertedRecord],201);                                  
          }
          return response()->json(['message' => 'Employee with ID ' . $emp_id . ' not found.'], 404);
         }
         return response()->json(['message' => 'Maximum employee limit reached. Cannot add more.'], 400);
  
        }  
    }
    catch(\Exception $e)
    {
        return response()->json(['message' => 'An error occurred. Please try again.',$e->getMessage()], 500);
    }
}




public function employeeDocument(Request $request)
{
    try
    {
        $token = $request->user()->currentAccessToken();
        $tokenRole = $token['tokenable']['role'];
        $status = $token['tokenable']['status'];
        $code = $token['tokenable']['company_code'];
        $company = User::where('company_code',$code)->first();
        $maxEmp = $company->total;
        $username = $company->username;
        $password = $company->dbPass;
        $dbName = $company->dbName;
        $moduleId = 3;
        $empModule = CompanyModuleAccess::where('company_code',$code)->where('module_id',$moduleId)
        ->where('status',$status)->first();
        $accessEmp = $token['tokenable']['create'];
        $date = Carbon::now()->timezone('Asia/kolkata')->format('Y-m-d H:i:s');
        if($tokenRole == 'admin' && $accessEmp != 1)
        {
          return response()->json(['success' => false,'message' => 'you have no permission'],403);
        } 
        if(!$accessEmp)
         {
           return response()->json(['success'=>false,'message'=>'you have no permission to create'],403);
         }
        if(!$empModule)
        {
          return response()->json(['success' => false,'message' => 'you can not access employee module'],403);
        }
        if($tokenRole == 'admin' && $accessEmp == 1 || $tokenRole && $tokenRole == 'Super Admin')  
        {
            $validatedData = $request->validate([
                'emp_id'             =>  'required',
                'pan_number'           =>  'required',
               // 'adhar_number'         =>  'required',
                'voter_id'             =>  'required',
                'driving_licence'      =>  'nullable',
                
                'passport_number'      =>  'nullable',
                'passport_to'          =>  'nullable|date',
                'passport_from'        =>  'nullable|date',
                      
            ]);
            $emp_id = $request->emp_id;
            Config::set('database.connections.dynamic', [
                'driver' => 'mysql',
                'host' => 'localhost',
                'database' => $dbName,
                'username' => $username,
                'password' => $password,
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
                'engine' => null,
            ]);
          $dynamicDB = DB::connection('dynamic');
          if (!$dynamicDB->getSchemaBuilder()->hasTable('company_employee'))
          {
            return response()->json(['success'=>false,'message'=>'table not found'],404);
          }
          $empCount = $dynamicDB->table('company_employee')->count();
          if($maxEmp >= $empCount)
          {
            if ($dynamicDB->table('company_employee')->where('id', $emp_id)->exists()) 
            {
              $dynamicDB->table('company_employee')->where('id', $emp_id)->update([
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
               $lastInsertedRecord = $dynamicDB->table('company_employee')->orderBy('id','desc')->first();
               return response()->json(['success'=>true,'message' => 'Family details stored successfully','empData'=>$lastInsertedRecord]);
            }
            return response()->json(['message' => 'Employee with ID ' . $emp_id . ' not found.'], 404);          
          }
          return response()->json(['message' => 'Maximum employee limit reached. Cannot add more.'], 400);
        }    
    }
    catch(\Exception $e)
    {
        return response()->json(['message' => 'An error occurred. Please try again.',$e->getMessage()], 500);
    }
}


 



public function employeeAddressProof(Request $request)
{
    $validatedData = $request->validate([
         'emp_id'        =>  'required', 
         'document_type'  =>  'required',
         'document_path'  =>  'file|mimes:jpeg,png,pdf',  
     ]);

     $sessionCheckResult = $this->checkSessionAndSetupConnection();
     if (!$sessionCheckResult) {
         return response()->json(['message' => 'Sorry, session expired or invalid. Please login.'], 401);
     }
     $dynamicDB = DB::connection('dynamic');
     $maxEmp = $sessionCheckResult['maxEmp'];
     $date = now()->timezone('Asia/Kolkata')->toDateTimeString();
     $emp_id = $request->input('emp_id');
 
     if(isset($_SESSION['create']) && $_SESSION['create'] == 1)
     {
        if ($dynamicDB->getSchemaBuilder()->hasTable('company_employee')) 
        {
            if ($dynamicDB->table('company_employee')->where('id', $emp_id)->exists())  
            {
                if (!$dynamicDB->getSchemaBuilder()->hasTable('documents')) {
                     $dynamicDB->getSchemaBuilder()->create('documents', function (Blueprint $table) {
                         $table->id();
                         $table->bigInteger('emp_id')->unsigned()->nullable();
                         $table->string('doc_type')->nullable();
                         $table->string('doc_path')->nullable();
                         $table->timestamps();
                    
                         $table->foreign('emp_id')->references('id')->on('company_employee');
                    
                     });
                 }
                 $empCount = $dynamicDB->table('documents')->count();
                if ($maxEmp > $empCount)
                {
                 if($request->hasFile('document_path'))
                  {
                      $file = $request->file('document_path');
                      //$filaName = $file->getClientOriginalName();
                      $uniqueFolder = $emp_id . '_' . time();
                      $filePath = $file->store('addressProof/' . $uniqueFolder);
                      $addressPath = $filePath;
                      $dynamicDB->table('documents')->insert([
                             'emp_id'   =>  $emp_id,
                             'doc_type' => $request->input('document_type'),
                             'doc_path' => $addressPath,
                             'created_at'   =>  $date,
                             'updated_at' => $date,
                         ]);
                         return response()->json(['success'=> true, 'message' => 'document uplodad successfully']);

                  }
                  return response()->json(['message' => 'Please add a valid image file for qualification doc'], 400);
                }
                return response()->json(['message' => 'Maximum employee limit reached. Cannot add more.'], 400); 
            }
            return response()->json(['message' => 'Employee with ID ' . $emp_id . ' not found.'], 404);
        }
        return response()->json(['message' => 'main table not found.'], 404);
     }
}





public function employeeSkill(Request $request)
{
    $validatedData = $request->validate([
         'emp_id'      =>  'required',
         'skill_name'   =>  'nullable',
         'passing_year' =>  'nullable|date',   
     ]);
     $sessionCheckResult = $this->checkSessionAndSetupConnection();
     if (!$sessionCheckResult) {
         return response()->json(['message' => 'Sorry, session expired or invalid. Please login.'], 401);
     }
     $dynamicDB = DB::connection('dynamic');
     $maxEmp = $sessionCheckResult['maxEmp'];
     $date = now()->timezone('Asia/Kolkata')->toDateTimeString();
     $emp_id = $request->input('emp_id');

     if(isset($_SESSION['create']) && $_SESSION['create'] == 1)
     {
        if ($dynamicDB->getSchemaBuilder()->hasTable('company_employee'))
        {
            if ($dynamicDB->table('company_employee')->where('id', $emp_id)->exists())  
            {
              if (!$dynamicDB->getSchemaBuilder()->hasTable('skill')) {
                  $dynamicDB->getSchemaBuilder()->create('skill', function (Blueprint $table) {
                       $table->id();
                       $table->bigInteger('emp_id')->unsigned()->nullable();
                       $table->string('skill_name')->nullable();
                       $table->date('pass_year')->nullable();
                       $table->timestamps();

                       $table->foreign('emp_id')->references('id')->on('company_employee');

                   });
               } 
               
               $empCount = $dynamicDB->table('skill')->count();
                if ($maxEmp > $empCount)
                {
                   $dynamicDB->table('skill')->insert([
                        'emp_id'     =>   $emp_id,
                        'skill_name'  =>   $request->input('skill_name'),
                        'pass_year'   =>   $request->input('passing_year'),
                        'created_at'  =>   $date,
                        'updated_at'  =>   $date,
                    ]);
                    return response()->json(['success' => true, 'message' => 'skill details upload successfully']);
                }
               return response()->json(['message' => 'Maximum employee limit reached. Cannot add more.'], 400); 
            }
            return response()->json(['message' => 'Employee with ID ' . $emp_id . ' not found.'], 404);
        }
        return response()->json(['message' => 'main table not create yet.'], 404);
     }
     

}


private function modulesCheck($module,$moduleId)
{
  $modules = explode(',',$module);
  if(in_array($moduleId,$modules))
  {
    return $modules;
  }
  return null;
}


private function permitCheck($permit, $permitValue)
{
    $permitValue = json_decode($permitValue, true);
    if (in_array($permit, $permitValue)) {
        return ['success' => true]; 
    }
    return null; 
}



    protected $dynamicDB;

    public function createEmployeeAndDetails(Request $request)
    {
        try {
            $token = $request->user()->currentAccessToken();
            $tokenRole = $token['tokenable']['role'];
            $status = $token['tokenable']['status'];
            $code = $token['tokenable']['company_code'];
            $company = User::where('company_code', $code)->first();
            $moduleId = 3;
            $empModule = CompanyModuleAccess::where('company_code',$code)->where('module_id',$moduleId)
            ->where('status',$status)->first();
            // $accessEmp = $token['tokenable']['create'];
            if (!$company) {
                return response()->json(['success' => false,'message' => 'Company not found.'], 404);
            }
           


            if($tokenRole == 'admin')
            {
                $empId = $token->tokenable->id;
                $roleData = RoleUserAssign::where('emp_id',$empId)->where('company_code',$code)->first();
                if (!$roleData) {
                    return response()->json(['success' => false, 'message' => 'User not found'], 404);
                }
                $roleId = $roleData->role_id;
                $roleData = RoleMaster::where('id',$roleId)->first();
                $module = $roleData->modules;
                if(!$module)
                {
                    return response()->json(['success'=>false,'message'=>'you can not access']);
                }
                $modules = $this->modulesCheck($module,$moduleId);
                if(!$modules)
                {
                    return response()->json(['success'=>false,'message'=>'Access Denied'],403);
                }
                $permit = "createEmployee";
                $permitValue = $roleData->permission;
                $permission = $this->permitCheck($permit, $permitValue);

                if (!$permission) { 
                    return response()->json(['success' => false, 'message' => 'Access Denied!'], 403);
                }
               

            }


            // if($tokenRole == 'admin' && $accessEmp != 1)
            // {
            //    return response()->json(['success' => false,'message' => 'you can not access employee module.'], 403);
            // }
            // if(!$accessEmp && $tokenRole == 'admin')
            // {
            //   return response()->json(['success' => false,'message' => 'you can not access employee module.'], 403);
            // }

            $maxEmp = $company->total;
            // $accessEmp = $token['tokenable']['create'];

            if ($tokenRole == 'Super Admin' && $empModule || $tokenRole == 'admin' && $permission == true ) {
                $validatedData = $request->validate([
                    'title' => 'nullable',
                    'first_name' => 'required',
                    'middle_name' => 'nullable',
                    'last_name' => 'required',
                    'preferred_name' => 'required',
                    'dob' => 'required',
                    'blood_group' => 'required',
         
                    'marital_status' => 'required',
                    'marital_doc' => 'file|mimes:jpeg,png,pdf',
         
                    'nationality_one' => 'required',
                    'nationality_two'  =>  'nullable',
                    'nationality_doc' => 'required|file|mimes:jpeg,png,pdf',
         
                    'country_code' => 'required',
                    'mobile_number' => 'required',
                    'home_phone_number' => 'nullable',
                    'work_phone_number' => 'nullable',
                    'whatsapp_no'   =>  'nullable',
                    'email'       =>   'required',
         
                    'course' => 'required',
                    'board' => 'required',
                    'specialization' => 'required',
                    'course_type' => 'nullable',
                    'quali_start_date' => 'nullable',
                    'quali_end_date' => 'nullable',
                    'grade_type'   =>  'nullable',
                    'total_marks'  => 'nullable',
                    'grade'       =>   'required',
         
                   'course_document_type' => 'required',
                   'course_document_path' => 'file|mimes:jpeg,png,pdf',
         
                   'is_current_emp' =>  'required',
                   'company_name'   =>  'required',
                   'project_name'   =>  'required',
                   'designation'    =>  'required',
                   'start_date'     =>  'required',
                   'end_date'       =>  'required',
         
                   'total_year' =>  'required',
                   'total_month'   =>  'required',
         
                   'exp_document_type' =>  'required',
                   'exp_certificate' => 'file|mimes:jpeg,png,pdf',
         
                   'acc_holder_name' =>  'required',
                   'acc_type'        =>  'required',
                   //'acc_nature'   =>  'nullable',
                   'acc_number'      => 'required',
                   'bank_name'      =>  'required',
                   'ifsc'           =>  'required',
                   'branch'         =>  'required',
                   'location'       =>  'required',
                   'city'           =>  'required',
         
                   'bank_document_type' =>  'required',
                   'bank_doc_path' => 'file|mimes:jpeg,png,pdf',
         
                   'relation_type' =>  'required',
                   'name'        =>  'required',
                   'qualification'   =>  'nullable',
                   'rel_country_code'      => 'required',
                   'contact'      =>  'required',
                   'occupation'           =>  'required',
                   'uid'         =>  'required',
                   'company_employee'       =>  'required',
             
                   'pan_number'           =>  'required',
                   'voter_id'             =>  'required',
                   'driving_licence'      =>  'nullable',
         
                   'passport_number'      =>  'nullable',
                   'passport_to'          =>  'nullable|date',
                   'passport_from'        =>  'nullable|date',
         
                   'document_type'  =>  'required',
                   'document_path'  =>  'file|mimes:jpeg,png,pdf',  
         
                   'skill_name'   =>  'nullable',
                   'passing_year' =>  'nullable|date',
         
                   'eme_country_code'   =>  'nullable',
                   'eme_mobile_no' =>  'nullable|',
                   'eme_whatsapp_no' => 'nullable',
                   'eme_email'    =>  'nullable',  
         
                   'country'   =>  'required',
                   'state' =>  'required',   
                   'emp_city'  =>  'required',
                   'pin_code' => 'required',
                   'house_no'  => 'nullable',
                   'address_line_one' => 'required',
                   ]);

                $this->setDynamicDBConnection($company->dbName, $company->username, $company->dbPass);

                if (!$this->dynamicDB->getSchemaBuilder()->hasTable('company_employee')) {
                    $this->dynamicDB->getSchemaBuilder()->create('company_employee', function ($table) {
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
                     $table->enum('status',['pending','accepted','rejected'])->default('pending');
                     $table->string('username');
                     $table->string('password');
                     $table->timestamps();
                     });
                 }  
                 $empCount =  $this->dynamicDB->table('company_employee')->count();
                 $date = Carbon::now()->timezone('Asia/kolkata')->format('Y-m-d H:i:s');
                if ($maxEmp >= $empCount) {
                    $emp_id = $this->createCompanyEmployee($request);
                    if(!$emp_id)
                    {
                        return response()->json(['success'=>false,'message'=>'emp_id not found'],404);
                    }
                    //  echo $emp_id;die;
                     $qualificationResult = $this->employeeQualificationDetails($request, $emp_id,$date);
                     $experienceResult = $this->employeeExperienceDetails($request,$emp_id,$date);
                     $bankResult = $this->employeeBankDetails($request,$emp_id,$date);
                     $familyResult = $this->employeeFamilyDetails($request,$emp_id,$date);
                     $documentResult = $this->employeeDocumentDetails($request,$emp_id,$date);
                     $addressResult = $this->employeeAddressDetail($request,$emp_id,$date);
                     $skillResult = $this->employeeSkillDetails($request,$emp_id,$date);
                     $company_access = new CompanyUserAccess;
                     $company_access->name = $request->preferred_name;
                     $company_access->emp_id = $emp_id;
                     $company_access->email = $request->email;
                     $company_access->username = $request->username;
                     $company_access->password = Hash::make($request->password);
                     $company_access->dbName = $company->dbName;
                     $company_access->company_code = $code;
                     $company_access->mobile_number = $request->mobile_number;
                     $company_access->role = 'subadmin';
                     $company_access->read = 0;
                     $company_access->create = 0;
                     $company_access->edit = 0;
                     $company_access->delete = 0;
                     $company_access->save();
                    return response()->json(['success'=>true,'message' => 'Employee registration stored successfully',
                    'qualification'=>$qualificationResult,'experience'=>$experienceResult,'bank'=>$bankResult,
                    'family'=>$familyResult,'doc'=>$documentResult,'address'=>$addressResult,'skill'=>$skillResult], 200);
                } else {
                    return response()->json(['success'=>false,'message' => 'Maximum employee limit reached. Cannot add more.'], 403);
                }
            } else {
                return response()->json(['success'=>false,'message' => 'You do not have permission to create employees.'], 403);
            }
        } catch (\Exception $e) {
            return response()->json(['success'=>false,'message' => 'An error occurred. Please try again.', 'error' => $e->getMessage()], 500);
        }
    }

    private function createCompanyEmployee($request)
    {
        try {
            if ($request->hasFile('marital_doc')) {
                $file = $request->file('marital_doc');
                $uniqueFolderName = time();
                $filePath = $file->store('marital_docs/' . $uniqueFolderName);
                $maritalDocPath = $filePath;
            } else {
                $maritalDocPath = null;
            }
            if ($request->hasFile('nationality_doc')) {
                $file = $request->file('nationality_doc');
                $uniqueFolderName = time();
                $filePath = $file->store('nationality_doc/' . $uniqueFolderName);
                $nationalityDocPath = $filePath;
            } else {
                $nationalityDocPath = null;
            }
            $date = Carbon::now()->timezone('Asia/Kolkata')->format('Y-m-d H:i:s');

            $this->dynamicDB->table('company_employee')->insert([
                'title' => $request->input('title'),
                'first_name' => $request->input('first_name'),
                'middle_name' => $request->input('middle_name'),
                'last_name' => $request->input('last_name'),
                'preferred_name' => $request->input('preferred_name'),
                'DOB' => $request->input('dob'),
                'blood_group' => $request->input('blood_group'),
                'marital_status' => $request->input('marital_status'),
                'marital_doc' => $maritalDocPath,
                'nationality_one' => $request->input('nationality_one'),
                'nationality_two' => $request->input('nationality_two'),
                'nationality_doc' => $nationalityDocPath,
                'country_code' => $request->input('country_code'),
                'mobile_no'   =>  $request->input('mobile_number'),
                'home_phone_no'  => $request->input('home_phone_number'),
                'work_phone_no'   =>  $request->input('work_phone_number'),
                'whatsapp_no'     =>  $request->input('whatsapp_no'),
                'email'       =>   $request->input('email'),
                'voter_id'     =>  $request->input('voter_id'),
                'pan_no' => $request->input('pan_number'),
                'driving_licence' => $request->input('driving_licence'),
                'passport_no'   =>$request->input('passport_number'),
                'passport_to'       =>  $request->input('passport_to'),
                'passport_from'     =>  $request->input('passport_from'),
                'eme_country_code'  =>   $request->input('eme_country_code'),
                'eme_mobile'   =>   $request->input('eme_mobile_no'),
                'eme_whatsapp_no' => $request->input('eme_whatsapp_no'),
                'eme_email'     =>   $request->input('eme_email'),
                'username'     =>  $request->input('username'),
                'password'     => Hash::make($request->input('password')),
                'created_at' => $date,
                'updated_at' => $date,
            ]);

            return $this->dynamicDB->table('company_employee')->orderBy('id', 'desc')->value('id');
        } catch (\Exception $e) {
            return response()->json(['success'=>false,'message' => 'An error occurred while creating company employee.', 'error' => $e->getMessage()], 500);
        }
    }


    



        


    private function employeeQualificationDetails($request, $emp_id, $date)
    {
   try{
        if (!$this->dynamicDB->getSchemaBuilder()->hasTable('qualification'))
      {
          $this->dynamicDB->getSchemaBuilder()->create('qualification', function (Blueprint $table)
        {
          $table->id();
          $table->bigInteger('emp_id')->unsigned()->nullable();
          $table->string('course')->nullable();
          $table->string('board')->nullable();
          $table->string('specialization')->nullable();
          $table->string('course_type')->nullable();
          $table->string('qua_st_date')->nullable();
          $table->string('qua_end_date')->nullable();
          $table->string('grade_type')->nullable();
          $table->string('grade')->nullable();
          $table->string('total_marks')->nullable(); 
          $table->string('course_document_type')->nullable();
          $table->string('course_document_path')->nullable();
          $table->timestamps();
         
          $table->foreign('emp_id')->references('id')->on('company_employee')->onDelete('cascade');
        }); 
      }
      if ($request->hasFile('course_document_path')) {
        $file = $request->file('course_document_path');
        $uniqueFolderName = time();
        $filePath = $file->store('course_document/' . $uniqueFolderName);
        $courseDocPath = $filePath;
       }
       else 
       {
        $courseDocPath = null;
       }

        if ($this->dynamicDB->table('company_employee')->where('id', $emp_id)->exists()) {
            $this->dynamicDB->table('qualification')->insert([
                'emp_id' => $emp_id,
                'course' => $request->input('course'),
                'board' => $request->input('board'),
                'specialization' => $request->input('specialization'),
                'course_type' => $request->input('course_type'),
                'qua_st_date' => $request->input('quali_start_date'),
                'qua_end_date' => $request->input('quali_end_date'),
                'grade_type' => $request->input('grade_type'),
                'grade' => $request->input('grade'),
                'total_marks' => $request->input('total_marks'),
                'course_document_type' => $request->input('course_document_type'),
                'course_document_path' => $courseDocPath,
                'created_at' => $date,
                'updated_at' => $date,
            ]);
            $lastInsertedRecord = $this->dynamicDB->table('qualification')->orderBy('id', 'desc')->first();
    
            return $lastInsertedRecord;
        } else {
            return response()->json(['message' => 'Employee with ID ' . $emp_id . ' not found.'], 404);
        }
      }
      catch (\Exception $e) {
        return response()->json(['success'=>false,'message' => 'An error occurred while creating company employee.', 'error' => $e->getMessage()], 500);
       }
      
    }

    private function employeeExperienceDetails($request,$emp_id,$date)
    {
        try{

        
        if (!$this->dynamicDB->getSchemaBuilder()->hasTable('experience')) {
            $this->dynamicDB->getSchemaBuilder()->create('experience', function (Blueprint $table) {
                $table->id();
                $table->bigInteger('emp_id')->unsigned()->nullable();
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
           
                $table->foreign('emp_id')->references('id')->on('company_employee');
               
            });
        }

        if ($request->hasFile('exp_certificate')) {
            $file = $request->file('exp_certificate');
            $uniqueFolderName = time();
            $filePath = $file->store('exp_document/' . $uniqueFolderName);
            $experiencePath = $filePath;
           }
           else 
           {
            $experiencePath = null;
           }

        if ($this->dynamicDB->table('company_employee')->where('id', $emp_id)->exists()) 
        {
          $this->dynamicDB->table('experience')->insert([
           'emp_id'   =>  $emp_id,
           'is_current_emp' => $request->input('is_current_emp'),
           'comp_name' => $request->input('company_name'),
           'proj_name' => $request->input('project_name'),
           'designation' => $request->input('designation'),
           'start_date'   =>$request->input('start_date'),
           'end_date'   =>  $request->input('end_date'),
           'total_year_exp' => $request->input('total_year'),
           'total_month_exp' => $request->input('total_month'),
           'document_type' => $request->input('exp_document_type'),
           'exp_doc'  =>  $experiencePath,
           'created_at'  => $date,
           'updated_at' => $date,
          ]);
         $lastInsertedRecord = $this->dynamicDB->table('experience')->orderBy('id','desc')->first();      
         return $lastInsertedRecord;
        }
       
          return response()->json(['message' => 'Employee with ID ' . $emp_id . ' not found.'], 404);
     }

      catch (\Exception $e) {
        // Log::error('Error creating company employee: ' . $e->getMessage());
        return response()->json(['success'=>false,'message' => 'An error occurred while creating company employee.', 'error' => $e->getMessage()], 500);
       }
    }



    private function employeeBankDetails($request,$emp_id,$date)
    {
        try{

        
        if (!$this->dynamicDB->getSchemaBuilder()->hasTable('bank')) {
            $this->dynamicDB->getSchemaBuilder()->create('bank', function (Blueprint $table) {
                $table->id();
                $table->bigInteger('emp_id')->unsigned()->nullable();
                $table->string('ac_holder_name')->nullable();
                $table->string('ac_type')->nullable();
              //  $table->string('ac_nature')->nullable();
                $table->string('ac_number')->nullable();
                $table->string('bank_name')->nullable();
                $table->string('ifsc')->nullable();
                $table->string('branch')->nullable();
                $table->string('location')->nullable();
                $table->string('city')->nullable();
                $table->string('bank_document_type')->nullable();
                $table->string('bank_doc_path')->nullable();
                $table->timestamps();

                $table->foreign('emp_id')->references('id')->on('company_employee');

            });
        }


        if ($request->hasFile('bank_doc_path')) {
            $file = $request->file('bank_doc_path');
            $uniqueFolderName = time();
            $filePath = $file->store('bank_document/' . $uniqueFolderName);
            $bankDocPath = $filePath;
           }
           else 
           {
            $bankDocPath = null;
           }



      
         if ($this->dynamicDB->table('company_employee')->where('id', $emp_id)->exists())
            {              
               $this->dynamicDB->table('bank')->insert([
                'emp_id'   =>  $emp_id,
                'ac_holder_name' => $request->input('acc_holder_name'),
                'ac_type' => $request->input('acc_type'),
                'ac_number' => $request->input('acc_number'),
                'bank_name'   =>$request->input('bank_name'),
                'ifsc'       =>  $request->input('ifsc'),
                'branch'     =>  $request->input('branch'),
                'location'   =>  $request->input('location'),
                'city'       =>  $request->input('city'),
                'bank_document_type' => $request->input('bank_document_type'),
                'bank_doc_path' => $bankDocPath,
                'created_at'   =>  $date,
                'updated_at' => $date,
            ]);
            $lastInsertedRecord = $this->dynamicDB->table('bank')->orderBy('id','desc')->first();             
            return $lastInsertedRecord;
        
            }
             return response()->json(['message' => 'Employee with ID ' . $emp_id . ' not found.'], 404); 
        }
         catch (\Exception $e) {
            return response()->json(['success'=>false,'message' => 'An error occurred while creating company employee.', 'error' => $e->getMessage()], 500);
           }
    }


    private function employeeFamilyDetails($request,$emp_id,$date)
    {
        try{


        if (!$this->dynamicDB->getSchemaBuilder()->hasTable('family')) {
            $this->dynamicDB->getSchemaBuilder()->create('family', function (Blueprint $table) {
                $table->id();
                $table->bigInteger('emp_id')->unsigned()->nullable();
                $table->string('relation_type')->nullable();
                $table->string('name')->nullable();
                $table->string('qualification')->nullable();
                $table->string('country_code')->nullable();
                $table->string('contact')->nullable();
                $table->string('occupation')->nullable();
                $table->string('uid')->nullable();
                $table->string('comp_emp')->nullable();
                $table->timestamps();
           
                $table->foreign('emp_id')->references('id')->on('company_employee');
           
            });
        }

        if ($this->dynamicDB->table('company_employee')->where('id', $emp_id)->exists()) 
        {
          $this->dynamicDB->table('family')->insert([
              'emp_id'   =>    $emp_id,
              'relation_type'=> $request->input('relation_type' ),
              'name'         => $request->input('name'),
              'qualification'=> $request->input('qualification'),
              'country_code' => $request->input('rel_country_code'),
              'contact'      => $request->input('contact'),
              'occupation'   => $request->input('occupation'),
              'uid'          => $request->input('uid'),
              'comp_emp'     => $request->input('company_employee'),
              'created_at'   => $date,
              'updated_at'   => $date,
          ]); 
           $lastInsertedRecord = $this->dynamicDB->table('family')->orderBy('id','desc')->first();
           return $lastInsertedRecord;
        }
        return response()->json(['message' => 'Employee with ID ' . $emp_id . ' not found.'], 404);

        }
        catch (\Exception $e) {
            return response()->json(['success'=>false,'message' => 'An error occurred while creating company employee.', 'error' => $e->getMessage()], 500);
           }

    }



    private function employeeDocumentDetails($request,$emp_id,$date)
    {
        try{

        if (!$this->dynamicDB->getSchemaBuilder()->hasTable('documents')) {
            $this->dynamicDB->getSchemaBuilder()->create('documents', function (Blueprint $table) {
                $table->id();
                $table->bigInteger('emp_id')->unsigned()->nullable();
                $table->string('doc_type')->nullable();
                $table->string('doc_path')->nullable();
                $table->timestamps();
           
                $table->foreign('emp_id')->references('id')->on('company_employee');
           
            });
        }

        if ($request->hasFile('document_path')) {
            $file = $request->file('document_path');
            $uniqueFolderName = time();
            $filePath = $file->store('document/' . $uniqueFolderName);
            $empDocPath = $filePath;
           }
           else 
           {
            $empDocPath = null;
           }

        if ($this->dynamicDB->table('company_employee')->where('id', $emp_id)->exists()) 
        {
          $this->dynamicDB->table('documents')->insert([
                'emp_id'   =>  $emp_id,
                'doc_type' => $request->input('document_type'),
                'doc_path' => $empDocPath,
                'created_at'   =>  $date,
                'updated_at' => $date,
            ]);
           $lastInsertedRecord = $this->dynamicDB->table('documents')->orderBy('id','desc')->first();
           return $lastInsertedRecord;
        }
        return response()->json(['message' => 'Employee with ID ' . $emp_id . ' not found.'], 404);  
     }
        catch (\Exception $e) {
            // Log::error('Error creating company employee: ' . $e->getMessage());
            return response()->json(['success'=>false,'message' => 'An error occurred while creating company employee.', 'error' => $e->getMessage()], 500);
           }
    }








    private function employeeAddressDetail($request,$emp_id,$date)
    {
     try{
     
        if (!$this->dynamicDB->getSchemaBuilder()->hasTable('address')) {
            $this->dynamicDB->getSchemaBuilder()->create('address', function (Blueprint $table) {
                $table->id();
                $table->bigInteger('emp_id')->unsigned()->nullable();
                $table->string('country');
                $table->string('state' );
                $table->string('city')->nullable();
                $table->integer('pin')->nullable();
                $table->string('house_no')->nullable();
                $table->string('add_line_one')->nullable();
                $table->timestamps();
           
                $table->foreign('emp_id')->references('id')->on('company_employee');
           
            });
        }

        if ($this->dynamicDB->table('company_employee')->where('id', $emp_id)->exists()) 
        {
          $this->dynamicDB->table('address')->insert([
            'emp_id'     =>   $emp_id,
            'country'  =>   $request->input('country'),
            'state'   =>   $request->input('state'),
            'city'    =>  $request->input('emp_city'),
            'pin' => $request->input('pin_code'),
            'house_no' => $request->input('house_no'),
            'add_line_one' => $request->input('address_line_one'),
            'created_at'  =>   $date,
            'updated_at'  =>   $date,
          ]); 
           $lastInsertedRecord = $this->dynamicDB->table('address')->orderBy('id','desc')->first();
           return $lastInsertedRecord;
        }
        return response()->json(['message' => 'Employee with ID ' . $emp_id . ' not found.'], 404);

      }
         catch (\Exception $e) {
            // Log::error('Error creating company employee: ' . $e->getMessage());
            return response()->json(['success'=>false,'message' => 'An error occurred while creating company employee.', 'error' => $e->getMessage()], 500);
           }

    
    }


    private function employeeSkillDetails($request,$emp_id,$date)
    {
        try{

       

        if (!$this->dynamicDB->getSchemaBuilder()->hasTable('skill')) {
            $this->dynamicDB->getSchemaBuilder()->create('skill', function (Blueprint $table) {
                $table->id();
                $table->bigInteger('emp_id')->unsigned()->nullable();
                $table->string('skill_name')->nullable();
                $table->date('pass_year')->nullable();
                $table->timestamps();

                $table->foreign('emp_id')->references('id')->on('company_employee');   
            });
        }

        if ($this->dynamicDB->table('company_employee')->where('id', $emp_id)->exists()) 
        {
          $this->dynamicDB->table('skill')->insert([
            'emp_id'     =>   $emp_id,
            'skill_name'  =>   $request->input('skill_name'),
            'pass_year'   =>   $request->input('passing_year'),
            'created_at'  =>   $date,
            'updated_at'  =>   $date,
          ]); 
           $lastInsertedRecord = $this->dynamicDB->table('skill')->orderBy('id','desc')->first();
           return $lastInsertedRecord;
        }
        return response()->json(['message' => 'Employee with ID ' . $emp_id . ' not found.'], 404);

    }
    catch (\Exception $e) {
        // Log::error('Error creating company employee: ' . $e->getMessage());
        return response()->json(['success'=>false,'message' => 'An error occurred while creating company employee.', 'error' => $e->getMessage()], 500);
       }

    }

    
    

    private function setDynamicDBConnection($dbName, $username, $password)
    {
        $config = [
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
        ];

        config(['database.connections.dynamic' => $config]);
        $this->dynamicDB = DB::connection('dynamic');
    }



    public function employeeLogin(Request $request)
    {
     try{
       $validatedData = $request->validate([
            'company_code' => 'required',
            'username' => 'required', 
            'password' => 'required', 
            'email' => 'required',  
               ]);
         $code = $request->company_code;
         $company = User::where('company_code',$code)->where('status','active')->first();
         $dbName = $company->dbName;
         $username = $company->username;
         $password = $company->dbPass;
         $empRecord = CompanyUserAccess::where('company_code',$code)->where('username',$request->username)
        ->where('email',$request->email)->first();
         if($empRecord && Hash::check($request->password,$empRecord->password))
         {
            $token = $empRecord->createToken('access-token')->plainTextToken;
            return response()->json(['Success' => true,'Message' => 'username and Password are correct','token'=>$token],200);      
         }
         return response()->json(['Success' => false, 'Message' => 'username and Password are incorrect'],403);
        }catch (\Exception $e) {
        return response()->json(['success'=>false,'message' => 'An error occurred while creating company employee.', 'error' => $e->getMessage()], 500);
       }
    }






    public function readEmployeeAndDetails(request $request)
    {
        $token = $request->user()->currentAccessToken();
        if(!$token)
        {
            return response()->json(['success'=>false,'message'=>'Token Not Found!'],404);
        }
        $tokenRole = $token['tokenable']['role'];
        $code = $token['tokenable']['company_code'];
        $moduleId = 3;
        $empModule = CompanyModuleAccess::where('company_code',$code)->where('module_id',$moduleId)
        ->where('status','active')->first();


        
        if($tokenRole == 'admin')
        {
            $empId = $token->tokenable->id;
            $roleData = RoleUserAssign::where('emp_id',$empId)->where('company_code',$code)->first();
            if (!$roleData) {
                return response()->json(['success' => false, 'message' => 'User not found'], 404);
            }
            $roleId = $roleData->role_id;
            $roleData = RoleMaster::where('id',$roleId)->first();
            $module = $roleData->modules;
            if(!$module)
            {
                return response()->json(['success'=>false,'message'=>'you can not access']);
            }
            $modules = $this->modulesCheck($module,$moduleId);
            if(!$modules)
            {
                return response()->json(['success'=>false,'message'=>'Access Denied'],403);
            }
            $permit = "readEmployee";
            $permitValue = $roleData->permission;
            $permission = $this->permitCheck($permit, $permitValue);

            if (!$permission) { 
                return response()->json(['success' => false, 'message' => 'Access Denied!'], 403);
            }
        }

         if ($tokenRole == 'Super Admin' && $empModule || $tokenRole == 'admin' && $permission == true )
             {
                $employeeData = CompanyUserAccess::where('company_code',$code)->get();
                if(!$employeeData)
                {
                    return response()->json(['success'=>false,'message' => 'data not found.'], 404);

                }
                return response()->json(['success'=>true,'message' => 'Data Found',$employeeData], 200);
             }  
             return response()->json(['success'=>false,'message' => 'You do not have permission to read employees.'], 403);
       
       

    }









}
