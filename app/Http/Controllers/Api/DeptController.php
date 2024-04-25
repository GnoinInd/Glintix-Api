<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\User;
use App\Models\Dept;
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
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Session\Middleware\StartSession;
use App\Models\SuperUser;

use App\Models\CompanyUserAccess;

class DeptController extends Controller
{

    public function deptCreate(Request $request)
    {
         $token = $request->user()->currentAccessToken();
         $company = User::where('company_code', $code)->where('status','active')->first();
             $validatedData = $request->validate([
                 'dept_name' => 'required',
                 'branch_id' => 'required',
                 'description' => 'required',
             ]);
             $dept = new Dept;
             $dept->company_code = $code;
             $dept->dept_name = $request->name;
             $dept->branch_id = $request->id;
             $dept->description = $request->description;
             $dept->save();    
             return response()->json(['success'=>true,'message' => 'Depertment data stored successfully'],200);    
        
    } 
 
 
 
    public function deptIdData(Request $request,$id)
    {
     
         $token = $request->user()->currentAccessToken();
         $company = User::where('company_code', $code)->get();
             $deptData = Dept::where('id',$deptId)->where('company_code',$code)->get();  
           
             return response()->json(['success'=>true,'message' => $deptData],200);    
        
    } 




    
    public function deptAllData(Request $request)
    { 
         $token = $request->user()->currentAccessToken();
         $company = User::where('company_code', $code)->first();
         if (!$company) {
             return response()->json(['success' => false,'message' => 'Company not found.'], 404);
         }
           
             return response()->json(['success'=>true],200);    
        
    } 

 
 
 
 
    public function editDept(Request $request,$id)
    {
         $token = $request->user()->currentAccessToken();
         $company = User::where('company_code', $code)->where('status','active')->first();
         if (!$company) {
             return response()->json(['success' => false,'message' => 'Company not found.'], 404);
         }
             $validatedData = $request->validate([
                 'dept_name' => 'required',
                 'description' => 'required',
                 'branch_id' => 'required',
             ]);
           
             $dept->dept_name = $request->dept_name;
             $dept->description = $request->description;
             $dept->branch_id = $request->id;
             $dept->save();
             return response()->json(['success'=>true,'message' => 'depertment update successfully'],200);    
      
        
    }
 
 
 
 
 
 
    public function destroyDept(Request $request, $id)
    {
      
            $token = $request->user()->currentAccessToken();
            $company = User::where('company_code', $code)->where('status','active')->first();
            if (!$company) {
                return response()->json(['success' => false,'message' => 'Company not found.'], 404);
            }
 
 
            return response()->json(['success'=>false,'message' => 'You do not have permission.'], 403);
     
    }
    



}
