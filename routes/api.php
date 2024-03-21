<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\EmployeeController;

use App\Http\Controllers\Api\EmployeeRegistration;
use App\Http\Controllers\Api\LeaveController;
use App\Http\Controllers\Api\AssetController;
use App\Http\Controllers\Api\DeptController;
use App\Http\Controllers\Api\BranchController;

 use App\Http\Controllers\Api\LoanController;

 use Illuminate\Support\Facades\Session;





Route::middleware('api')->group(function () {
  
});


Route::middleware('auth:sanctum')->group(function () {

Route::post('root_profile', [AdminController::class, 'rootProfile']);
Route::post('root/logout', [AdminController::class, 'rootLogout']);
Route::post('get-token',[AdminController::class,'getRootToken']);
Route::post('root/set-password', [AdminController::class, 'setNewPassword']);
Route::post('registercompany',[AdminController::class,'registerCompany']);
Route::post('root/all-companies',[AdminController::class,'allCompanies']); 
Route::post('company_profile',[AdminController::class,'companyProfile']);
Route::post('company-set-password', [AdminController::class, 'companysetNewPassword']);
Route::post('create-user',[AdminController::class,'newUser']);
Route::post('edit-user/{id}',[AdminController::class,'editUser']);
Route::post('delete-user/{id}',[AdminController::class,'delUser']);
Route::post('logout_company', [AdminController::class, 'logoutCompany']);
Route::post('company-set-new-password', [AdminController::class, 'setNewPasswordCompany']); 
Route::post('access-user/set-new-password', [AdminController::class, 'setNewPasswordUserAccess']); 
Route::post('access-user/logout', [AdminController::class, 'logoutAccess']);
Route::post('addemployee',[AdminController::class,'addEmployee']);
Route::post('editemployee/{employeeId}',[AdminController::class,'editEmployee']);
Route::post('allemployee',[AdminController::class,'allEmployee']);
Route::post('singleemployee/{id}',[AdminController::class,'singleEmployee']);
Route::delete('deleteemployee/{employeeId}',[AdminController::class,'destroyEmployee']);
Route::post('delete-multiple-employee',[AdminController::class,'multiDelEmp']);     
Route::post('test',[AdminController::class,'test']);
Route::post('employee-basic-details',[EmployeeRegistration::class,'employeeBasic']); 
Route::post('employee-marital',[EmployeeRegistration::class,'employeeMarital']);
Route::post('employee-nationality',[EmployeeRegistration::class,'employeeNationality']); 
Route::post('employee-communication-details',[EmployeeRegistration::class,'employeeCommunication']);
Route::post('employee-education-details',[EmployeeRegistration::class,'employeeEducation']);
Route::post('employee-education-attachment',[EmployeeRegistration::class,'employeeEducationDoc']);
Route::post('employee-experience-details',[EmployeeRegistration::class,'employeeExperience']);
Route::post('employee-experience-time',[EmployeeRegistration::class,'employeeExpTime']);
Route::post('employee-experience-doc',[EmployeeRegistration::class,'employeeExpDoc']);
Route::post('employee-bank-details',[EmployeeRegistration::class,'employeeBank']);
Route::post('employee-bank-cheque',[EmployeeRegistration::class,'employeeCheque']);
Route::post('employee-family-details',[EmployeeRegistration::class,'employeeFamily']);
Route::post('employee-document-deatils',[EmployeeRegistration::class,'employeeDocument']);
Route::post('employee-address-proof',[EmployeeRegistration::class,'employeeAddressProof']);
//Route::post('employee-driving-licence',[EmployeeRegistration::class,'employeeDrivingLicenec']);
Route::post('employee-skill',[EmployeeRegistration::class,'employeeSkill']);
Route::post('employee-emergency-details',[EmployeeRegistration::class,'employeeEmergencyDetails']);
Route::post('employee-address-details',[EmployeeRegistration::class,'employeeAddressDetails']);
Route::post('single-api-registration',[EmployeeRegistration::class,'createEmployeeAndDetails']);
Route::post('add-leave',[LeaveController::class,'addLeaveCount']);
Route::post('add-leave-type',[LeaveController::class,'addLeaveType']);
Route::post('leave-application',[LeaveController::class,'leaveApplication']);
Route::post('all-leave-application',[LeaveController::class,'allLeaveApplication']);
Route::post('leave-application-approve/{id}',[LeaveController::class,'leaveApplicationApprove']);
Route::post('empCheckin',[EmployeeController::class,'empCheckIns']);
Route::post('empCheckout',[EmployeeController::class,'empCheckOuts']);
Route::post('dept_iddata/{id}',[DeptController::class,'deptIdData']);
Route::post('dept_alldata',[DeptController::class,'deptAllData']);
Route::post('dept_create',[DeptController::class,'deptCreate']);
Route::post('dept_edit/{id}',[DeptController::class,'editDept']);
Route::post('dept_delete/{id}',[DeptController::class,'destroyDept']);
Route::post('branch_id_data/{id}',[BranchController::class,'branchIdData']);
Route::post('branch_alldata',[BranchController::class,'branchAllData']);
Route::post('branch_create',[BranchController::class,'branchCreate']);
Route::post('branch_edit/{id}',[BranchController::class,'editBranch']);
Route::post('branch_delete/{id}',[BranchController::class,'destroyBranch']);
Route::post('inactive-emp',[AdminController::class,'allInactiveEmp']);    
Route::post('approve-emp',[AdminController::class,'empApproveByAdmin']);
Route::post('branch-by-company',[BranchController::class,'branchDetailsByCode']);
Route::post('dept-by-company-id',[DeptController::class,'getDeptbyBranchandCompanyCode']);
Route::post('assign-branch-dept-to-users',[AdminController::class,'AssignBranchDeptToUsers']);
Route::post('user-role-del/{id}',[AdminController::class,'edituserRoledel']);
Route::post('create-role',[AdminController::class,'assignRole']);
Route::post('all-role',[AdminController::class,'allRoleData']);
Route::post('edit-role/{id}',[AdminController::class,'editRole']);
Route::post('user-role/{id}',[AdminController::class,'edituserRole']);
Route::post('delete-role/{id}',[AdminController::class,'delRole']);
Route::post('module-permission',[AdminController::class,'modulePermission']);
Route::post('add-permission',[AdminController::class,'addPermission']);
Route::post('all-permission-list',[AdminController::class,'allPermission']);
Route::post('create-user-role',[AdminController::class,'userRole']);
Route::post('edit-permission/{RoleId}',[AdminController::class,'editPermission']);
Route::post('delete-permission/{RoleId}',[AdminController::class,'deletePermission']);
Route::post('is-emp-permission',[AdminController::class,'isEmpPermission']);
 
});


   Route::post('root_register',[AdminController::class,'registerRoot']);    
   Route::post('root/login', [AdminController::class, 'adminLogin']);
   Route::post('verify_otp', [AdminController::class, 'verifyOtp']);
   Route::post('root/forget-password', [AdminController::class, 'rootForgetPass']);
   Route::post('root/verify-forget-pass', [AdminController::class, 'verifyRootForgetPass']);
   Route::post('logincompany',[AdminController::class,'loginCompany']);
   Route::post('company_verify_otp', [AdminController::class, 'verifyOtpCompany']); 
   Route::post('company-forget-password', [AdminController::class, 'companyForgetPass']);  
   Route::post('company-verify-forget-password', [AdminController::class, 'verifyForgetPassCompany']);    
   Route::post('login-user',[AdminController::class,'logUser']);  
   Route::post('access-user/forget-pass',[AdminController::class,'accessFogetPass']);
   Route::post('access-user/verify-forget-pass',[AdminController::class,'verifyAccessFogetPass']);

   Route::post('logoutuser', [AdminController::class, 'logoutSession']);
   
   Route::post('forgetpass',[AdminController::class,'forgetpassword']);
  Route::post('employee-login',[EmployeeRegistration::class,'employeeLogin']);
  Route::post('datewiseAttendence',[AdminController::class,'datewiseAttend']);
  Route::post('monthwiseAttendence',[AdminController::class,'monthwiseAttend']);
  Route::post('idwiseAttendence',[AdminController::class,'idWise']);
  Route::post('empLeaveApply',[AdminController::class,'applyLeave']); 
  Route::post('approve-leave',[AdminController::class,'approveLeave']);   
  Route::post('leave/allEmployee',[AdminController::class,'allEmpLeave']);
  Route::post('leave/create',[AdminController::class,'createLeave']);
  Route::post('leave/{id}',[AdminController::class,'singleEmpLeave']);
  Route::post('leave/edit/{id}',[AdminController::class,'editEmpLeave']);
  Route::post('leave/delete/{id}',[AdminController::class,'destroyEmpLeave']);
  Route::post('search_employee',[AdminController::class,'searchEmpByValue']);
  Route::post('latestMember',[AdminController::class,'latestMember']); 
  Route::post('add_project',[AdminController::class,'addProject']);  


Route::post('register',[AdminController::class,'register']);
Route::post('login',[AdminController::class,'login']);

Route::post('month_weekend',[AdminController::class,'calculateWeekend']);


Route::post('getemployee',[EmployeeController::class,'getEmployee']);
Route::post('getemployee/{id}',[EmployeeController::class,'specificEmployee']);



Route::post('add_professionaltax', [AdminController::class, 'addProfessionalTax']);
Route::post('add_salary_structure',[AdminController::class,'salaryStructure']);  
Route::post('salary-structure',[AdminController::class,'salaryStuct']);   
Route::post('add_tax_information',[AdminController::class,'taxInformation']);
Route::post('add-salary-component',[AdminController::class,'salaryComponent']); 
Route::post('add-salary-components',[AdminController::class,'salaryComponents']);  
Route::post('calculate-salary',[AdminController::class,'calculateSalary']); 
Route::post('employee-allowance',[AdminController::class,'empAllowance']);  
Route::post('comapny-allowance',[AdminController::class,'companyAllowance']);  
Route::post('asset-purchase',[AdminController::class,'assetPurchase']);   

Route::post('asset-allocation',[AdminController::class,'assetAlloc']);

Route::post('create-states',[AdminController::class,'createIndianStates']);
Route::post('session-check',[EmployeeRegistration::class,'sessionShow']);
Route::post('all-session',[AdminController::class,'allSession']);
Route::post('emp_search_name',[EmployeeRegistration::class,'empSearchByName']);
Route::post('emp_search_id',[EmployeeRegistration::class,'empSearchById']);
Route::post('emp_search_location',[EmployeeRegistration::class,'empSearchByLoc']);
Route::post('emp_search_date',[EmployeeRegistration::class,'empSearchByDate']);
Route::post('emp_search_halfyear',[EmployeeRegistration::class,'empSearchByhalfYear']);
Route::post('emp_search_yearly',[EmployeeRegistration::class,'empSearchByYear']);
  Route::post('add-asset',[AssetController::class,'addAsset']);
  Route::post('emp-asset-request',[AssetController::class,'requestAsset']);
  Route::post('all-asset-request',[AdminController::class,'allAssetRequest']);
  Route::post('asset-request-approve',[AdminController::class,'assetApprove']);
  Route::post('emp-asset-req/{$id}',[AdminController::class,'']);





