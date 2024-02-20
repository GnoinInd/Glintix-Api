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
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Session\Middleware\StartSession;
use App\Models\SuperUser;

use Exception;
use Twilio\Rest\Client;
use Laravel\Sanctum\PersonalAccessTokenFactory;
use Cache;
use Illuminate\Validation\ValidationException;
use App\Models\CompanyModuleAccess;
use Laravel\Sanctum\PersonalAccessToken;
use App\Models\CompanyUserAccess;

class DeptController extends Controller
{

    public function deptCreate(Request $request)
    {
      try{
         $token = $request->user()->currentAccessToken();
         $tokenRole = $token['tokenable']['role'];
         $status = $token['tokenable']['status'];
         $code = $token['tokenable']['company_code'];
         $company = User::where('company_code', $code)->where('status','active')->first();
         if (!$company) {
             return response()->json(['success' => false,'message' => 'Company not found.'], 404);
         }
         if ($tokenRole == 'admin' || $tokenRole == 'Super Admin') {
             $validatedData = $request->validate([
                 'dept_name' => 'required',
                 'branch_id' => 'required',
                 'description' => 'required',
             ]);
             $dept = new Dept;
             $dept->company_code = $code;
             $dept->dept_name = $request->dept_name;
             $dept->branch_id = $request->branch_id;
             $dept->description = $request->description;
             $dept->save();    
             $deptData = Dept::orderBy('id','desc')->first();
             return response()->json(['success'=>true,'message' => 'Depertment data stored successfully',$deptData],200);    
         }
         return response()->json(['success'=>false,'message' => 'You do not have permission.'], 403);
      }
      catch (\Exception $e) {
         // Log::error('Error creating company employee: ' . $e->getMessage());
         return response()->json(['success'=>false,'message' => 'An error occurred while storing deptartment data.', 'error' => $e->getMessage()], 500);
        }
        
    } 
 
 
 
    public function deptIdData(Request $request,$deptId)
    {
      try{
         $token = $request->user()->currentAccessToken();
         $tokenRole = $token['tokenable']['role'];
         $status = $token['tokenable']['status'];
         $code = $token['tokenable']['company_code'];
         $company = User::where('company_code', $code)->where('status','active')->first();
         if (!$company) {
             return response()->json(['success' => false,'message' => 'Company not found.'], 404);
         }
         if ($tokenRole == 'admin' || $tokenRole == 'Super Admin') {
             $deptData = Dept::where('id',$deptId)->where('company_code',$code)->first();  
             if (!$deptData) {
                 return response()->json(['success' => false, 'message' => 'Department not found for the provided department id.'], 404);
             }
             return response()->json(['success'=>true,'message' => $deptData],200);    
         }
         return response()->json(['success'=>false,'message' => 'You do not have permission.'], 403);
      }
      catch (\Exception $e) {
         // Log::error('Error creating company employee: ' . $e->getMessage());
         return response()->json(['success'=>false,'message' => 'An error occurred while fetching depertment data.', 'error' => $e->getMessage()], 500);
        }
        
    } 




    
    public function deptAllData(Request $request)
    {
      try{
         $token = $request->user()->currentAccessToken();
         $tokenRole = $token['tokenable']['role'];
         $status = $token['tokenable']['status'];
         $code = $token['tokenable']['company_code'];
         $company = User::where('company_code', $code)->where('status','active')->first();
         if (!$company) {
             return response()->json(['success' => false,'message' => 'Company not found.'], 404);
         }
         if ($tokenRole == 'admin' || $tokenRole == 'Super Admin') {
             $deptData = Dept::all();
             if (!$deptData) {
                 return response()->json(['success' => false, 'message' => 'Department not found for the provided branch id.'], 404);
             }
             return response()->json(['success'=>true,'message' => $deptData],200);    
         }
         return response()->json(['success'=>false,'message' => 'You do not have permission.'], 403);
      }
      catch (\Exception $e) {
         // Log::error('Error creating company employee: ' . $e->getMessage());
         return response()->json(['success'=>false,'message' => 'An error occurred while fetching depertment data.', 'error' => $e->getMessage()], 500);
        }
        
    } 

 
 
 
 
    public function editDept(Request $request,$deptId)
    {
      try{
         $token = $request->user()->currentAccessToken();
         $tokenRole = $token['tokenable']['role'];
         $status = $token['tokenable']['status'];
         $code = $token['tokenable']['company_code'];
         $company = User::where('company_code', $code)->where('status','active')->first();
         if (!$company) {
             return response()->json(['success' => false,'message' => 'Company not found.'], 404);
         }
         if ($tokenRole == 'admin' || $tokenRole == 'Super Admin') {
             $validatedData = $request->validate([
                 'dept_name' => 'required',
                 'description' => 'required',
                 // 'branch_id' => 'required',
             ]);
             $dept = Dept::find($deptId);
             if(!$dept)
             {
                 return response()->json(['success' => false,'message' => 'depertment data not found.'], 404);  
             }
             $dept->dept_name = $request->dept_name;
             $dept->description = $request->description;
             // $dept->branch_id = $request->branch_id;
             $dept->save();
             $deptData = Dept::where('id',$deptId)->first();
             return response()->json(['success'=>true,'message' => 'depertment update successfully',$deptData],200);    
         }
         return response()->json(['success'=>false,'message' => 'You do not have permission.'], 403);
      }
      catch (\Exception $e) {
         // Log::error('Error creating company employee: ' . $e->getMessage());
         return response()->json(['success'=>false,'message' => 'An error occurred while fetching depertment data.', 'error' => $e->getMessage()], 500);
        }
        
    }
 
 
 
 
 
 
    public function destroyDept(Request $request, $id)
    {
        try {
            $token = $request->user()->currentAccessToken();
            $tokenRole = $token['tokenable']['role'];
            $status = $token['tokenable']['status'];
            $code = $token['tokenable']['company_code'];
 
            $company = User::where('company_code', $code)->where('status','active')->first();
            if (!$company) {
                return response()->json(['success' => false,'message' => 'Company not found.'], 404);
            }
 
            if ($tokenRole == 'admin' || $tokenRole == 'Super Admin') {
                $dept = Dept::find($id);
                if (!$dept) {
                    return response()->json(['success' => false, 'message' => 'Department data not found.'], 404);
                }
  
                $dept->delete();
 
                return response()->json(['success'=>true,'message' => 'Department deleted successfully'],200);    
            }
 
            return response()->json(['success'=>false,'message' => 'You do not have permission.'], 403);
        } catch (\Exception $e) {
            // Log::error('Error deleting department: ' . $e->getMessage());
            return response()->json(['success'=>false,'message' => 'An error occurred while deleting department.', 'error' => $e->getMessage()], 500);
        }
    }
    
    


    public function getDeptbyBranchandCompanyCode(Request $request)
    {
        try{
            $token = $request->user()->currentAccessToken();
            $tokenRole = $token['tokenable']['role'];
            $status = $token['tokenable']['status'];
            $code = $token['tokenable']['company_code'];
            $branch_id =  $request->branch_id;
            $company = User::where('company_code', $code)->where('status','active')->first();
            if (!$company) {
                return response()->json(['success' => false,'message' => 'Company not found.'], 404);
            }
            if ($tokenRole == 'admin' || $tokenRole == 'Super Admin') {
                $deptData = Dept::where('branch_id',$branch_id)->where('company_code',$code)->all();  
                if (!$deptData) {
                    return response()->json(['success' => false, 'message' => 'Department not found for the provided branch id.'], 404);
                }
                return response()->json(['success'=>true,'message' => $deptData],200);    
            }
            return response()->json(['success'=>false,'message' => 'You do not have permission.'], 403);
         }
         catch (\Exception $e) {
            // Log::error('Error creating company employee: ' . $e->getMessage());
            return response()->json(['success'=>false,'message' => 'An error occurred while fetching depertment data.', 'error' => $e->getMessage()], 500);
           }
    } 









}
