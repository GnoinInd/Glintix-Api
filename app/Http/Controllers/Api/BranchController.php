<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Dept;
 use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Response;
use Exception;
use Cache;
use App\Models\CompanyModuleAccess;
use App\Models\CompanyUserAccess;
use App\Models\Branch;

class BranchController extends Controller
{
    
    public function branchCreate(Request $request)
    {
         $token = $request->user()->currentAccessToken();
        
         $name = $token['tokenable']['name'];
         $code = $token['tokenable']['company_code'];
         $company = User::where('company_code', $code)->first();
         if (!$company) {
             return response()->json(['success' => false,'message' => 'Company not found.'], 404);
         }
             $validatedData = $request->validate([
                 'name' => 'required',
                 'address' => 'required',
                 'country' => 'required',
                 'state'   => 'required',
                 'city'   => 'required',
                 'phone'          => 'required',
                 'legal_info'    => 'required',
                 'description'    => 'required',
             ]);
          
         
             return response()->json(['success'=>true,'message' => 'Branch data stored successfully'],200);    
         
   
        
    } 
 
 
 
    public function branchIdData(Request $request,$Id)
    {
   
         $token = $request->user()->currentAccessToken();
     
         $code = $token['tokenable']['company_code'];
         $company = User::where('company_code', $code)->where('status','active')->first();
         if (!$company) {
             return response()->json(['success' => false,'message' => 'Company not found.'], 404);
         }
        }
       

    public function branchAllData(Request $request)
    {
    
         $token = $request->user()->currentAccessToken();
         $code = $token['tokenable']['company_code'];
         $company = User::where('company_code', $code)->where('status','active')->first();
         if (!$company) {
             return response()->json(['success' => false,'message' => 'Company not found.'], 404);
         }
     
         return response()->json(['success'=>false,'message' => 'You do not have permission.'], 403);
 
        
    } 


 
 
 
 
    public function editBranch(Request $request,$Id)
    {
   
         $token = $request->user()->currentAccessToken();
         $company = User::where('company_code', $code)->first();
         if (!$company) {
             return response()->json(['success' => false,'message' => 'Company not found.'], 404);
         }
             $validatedData = $request->validate([
                'name' => 'required',
                'address' => 'required',
                'phone'          => 'required',
                'legal_info'    => 'required',
                'country'     => 'required',
                'state'   => 'required',
                'city'   => 'required',
                'description'   => 'required',
                
             ]);
              
             $branch = Branch::find($branchId);
             if(!$branch)
             {
                return response()->json(['success' => false,'message' => 'branch not found.'], 404);
             }
             $branch->name = $request->name;
             $branch->location = $request->address;
             $branch->phone = $request->phone; 
             $branch->country = $request->country;
             $branch->state = $request->state;
             $branch->city = $request->city;
             $branch->legal_info = $request->legal_info;
             $branch->description = $request->description;
             $branch->save();
             return response()->json(['success'=>true,'message' => 'branch data update successfully'],200);    
         
 
        
    }
 
 
 
 
 
 
    public function destroyBranch(Request $request, $id)
    {
            $token = $request->user()->currentAccessToken(); 
            $company = User::where('company_code', $code)->where('status','active')->first();
            if (!$company) {
                return response()->json(['success' => false,'message' => 'Company not found.'], 404);
            }
  
 
                return response()->json(['success'=>true,'message' => 'Branch deleted successfully'],200);          
    }

    
    public function branchDetailsByCode(Request $request)
    {
     
         $token = $request->user()->currentAccessToken();
         $company = User::where('company_code', $code)->get();
             $branchData = Branch::where('company_code', $code)->get();  
             if (!$branchData) {
                 return response()->json(['success' => false, 'message' => 'Branch not found for the provided Company.'], 404);
             }
             return response()->json(['success'=>true,'message' => $branchData],200);    
        
      
     
        
    } 








}
