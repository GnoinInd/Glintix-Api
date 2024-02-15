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
use App\Models\Branch;

class BranchController extends Controller
{
    
    public function branchCreate(Request $request)
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
                 'name' => 'required',
                 'location' => 'required',
             ]);
             $branch = new Branch;
             $branch->name = $request->name;
             $branch->location = $request->location;
             $branch->company_code = $code;
             $branch->save();  
             $branchData = Branch::orderBy('id','desc')->first();  
             return response()->json(['success'=>true,'message' => 'Branch data stored successfully',$branchData],200);    
         }
         return response()->json(['success'=>false,'message' => 'You do not have permission.'], 403);
      }
      catch (\Exception $e) {
         // Log::error('Error creating company employee: ' . $e->getMessage());
         return response()->json(['success'=>false,'message' => 'An error occurred while storing branch data.', 'error' => $e->getMessage()], 500);
        }
        
    } 
 
 
 
    public function branchIdData(Request $request,$branchId)
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
             $branchData = Branch::where('id', $branchId)->get();  
             if (!$branchData) {
                 return response()->json(['success' => false, 'message' => 'Branch not found for the provided branch id.'], 404);
             }
             return response()->json(['success'=>true,'message' => $branchData],200);    
         }
         return response()->json(['success'=>false,'message' => 'You do not have permission.'], 403);
      }
      catch (\Exception $e) {
         // Log::error('Error creating company employee: ' . $e->getMessage());
         return response()->json(['success'=>false,'message' => 'An error occurred while fetching branch data.', 'error' => $e->getMessage()], 500);
        }
        
    } 


    public function branchAllData(Request $request)
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
             $branchData = Branch::all();  
             if (!$branchData) {
                 return response()->json(['success' => false, 'message' => 'Branch not found for the provided branch id.'], 404);
             }
             return response()->json(['success'=>true,'message' => $branchData],200);    
         }
         return response()->json(['success'=>false,'message' => 'You do not have permission.'], 403);
      }
      catch (\Exception $e) {
         // Log::error('Error creating company employee: ' . $e->getMessage());
         return response()->json(['success'=>false,'message' => 'An error occurred while fetching branch data.', 'error' => $e->getMessage()], 500);
        }
        
    } 


 
 
 
 
    public function editBranch(Request $request,$branchId)
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
                'branch_name' => 'required',
                'branch_location' => 'required',
             ]);
              
             $branch = Branch::find($branchId);
             if(!$branch)
             {
                return response()->json(['success' => false,'message' => 'branch not found.'], 404);
             }
             $branch->name = $request->branch_name;
             $branch->location = $request->branch_location;
             $branch->save();
             $branchData = Branch::where('id',$branchId)->first();
             return response()->json(['success'=>true,'message' => 'branch data update successfully',$branchData],200);    
         }
         return response()->json(['success'=>false,'message' => 'You do not have permission.'], 403);
      }
      catch (\Exception $e) {
         // Log::error('Error creating company employee: ' . $e->getMessage());
         return response()->json(['success'=>false,'message' => 'An error occurred while fetching branch data.', 'error' => $e->getMessage()], 500);
        }
        
    }
 
 
 
 
 
 
    public function destroyBranch(Request $request, $id)
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
                $branch = Branch::find($id);
                if (!$branch) {
                    return response()->json(['success' => false, 'message' => 'Branch data not found.'], 404);
                }
  
                $branch->delete();
 
                return response()->json(['success'=>true,'message' => 'Branch deleted successfully'],200);    
            }
 
            return response()->json(['success'=>false,'message' => 'You do not have permission.'], 403);
        } catch (\Exception $e) {
            // Log::error('Error deleting branch ' . $e->getMessage());
            return response()->json(['success'=>false,'message' => 'An error occurred while deleting branch.', 'error' => $e->getMessage()], 500);
        }
    }

    









}
