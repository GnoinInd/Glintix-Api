<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\EmployeeController;

use App\Http\Controllers\Api\EmployeeRegistration;

 use App\Http\Controllers\Api\LoanController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});





   
     


       // Register Company

  Route::post('registercompany',[AdminController::class,'registerCompany']);

        //login

   Route::post('logincompany',[AdminController::class,'loginCompany']);


   Route::post('logoutuser', [AdminController::class, 'logoutSession']);
   Route::post('profile',[AdminController::class,'profile']);
   Route::post('forgetpass',[AdminController::class,'forgetpassword']);

  // Route::middleware('web')->post('logincompany',[AdminController::class,'loginCompany']);


  //employee login
  Route::post('loginEmployee',[EmployeeController::class,'loginEmp']);
    //logout employee
    Route::post('empLogout',[EmployeeController::class,'emplogout']);


  // Route::post('createEmployee', [EmployeeController::class, 'createEmployee']);

  //attendence
  
  Route::post('empCheckin',[EmployeeController::class,'empCheckIn']);
  Route::post('empCheckout',[EmployeeController::class,'empCheckOut']);
  // Route::post('getAttendence',[AdminController::class,'getAttend']);
  

  //admin can see through dashboard
  Route::post('datewiseAttendence',[AdminController::class,'datewiseAttend']);
  Route::post('monthwiseAttendence',[AdminController::class,'monthwiseAttend']);
  Route::post('idwiseAttendence',[AdminController::class,'idWise']);


  //leave apply 
  Route::post('empLeaveApply',[AdminController::class,'applyLeave']); 
    //leave approve by admin
  Route::post('approve-leave',[AdminController::class,'approveLeave']);  


   //working day & shift
   Route::post('workingdays',[AdminController::class,'workingDay']);
   Route::post('calculate-workingdays',[AdminController::class,'calculateAndStoreWorkingDays']);
   Route::post('calculate-workingdayss',[AdminController::class,'calculateAndStoreWorkingDayss']);
   
   //  add holidays

   Route::post('add-holidays',[AdminController::class,'addHoliday']);

     // annoucement
   Route::post('AddAnnouncement',[AdminController::class,'addAnnouncement']);  



   //Employee controlled by admin
   Route::post('addemployee',[AdminController::class,'addEmployee']);
   Route::post('editemployee/{employeeId}',[AdminController::class,'editEmployee']);
   Route::post('allemployee',[AdminController::class,'allEmployee']);
   Route::post('singleemployee/{id}',[AdminController::class,'singleEmployee']);
   Route::delete('deleteemployee/{employeeId}',[AdminController::class,'destroyEmployee']);





      // employee route
  // Route::post('employee/allEmployee',[AdminController::class,'allEmp']);  
  // Route::post('employee/create',[AdminController::class,'createEmp']);  
  // Route::post('employee/{id}',[AdminController::class,'singleEmp']);
  // Route::post('employee/edit/{id}',[AdminController::class,'editEmp']);
  // Route::post('employee/delete/{id}',[AdminController::class,'destroyEmp']);

  


  //admin attendences route
  Route::post('attendence/allEmployee',[AdminController::class,'allEmpAttend']); // today all employee attend count
  Route::post('attendence/allAbcent',[AdminController::class,'allEmpAbcent']);

  Route::post('apply-attendence',[EmployeeController::class,'applyAttend']); // employee apply for attendence due to not swipe

  Route::post('latest-miss_attendences',[AdminController::class,'latest_missAttend']);
  Route::post('attendence/create',[AdminController::class,'createAttend']);
  Route::post('attendence/{id}',[AdminController::class,'singleEmpAttend']);
  Route::post('attendence/edit/{id}',[AdminController::class,'editEmpAttend']);
  Route::post('attendence/delete/{id}',[AdminController::class,'destroyEmpAttend']);

     //admin leave route
  Route::post('leave/allEmployee',[AdminController::class,'allEmpLeave']);
  Route::post('leave/create',[AdminController::class,'createLeave']);
  Route::post('leave/{id}',[AdminController::class,'singleEmpLeave']);
  Route::post('leave/edit/{id}',[AdminController::class,'editEmpLeave']);
  Route::post('leave/delete/{id}',[AdminController::class,'destroyEmpLeave']);

  //admin search employee(email,name,designation,address)
  Route::post('search_employee',[AdminController::class,'searchEmpByValue']);
   // latest employee
  Route::post('latestMember',[AdminController::class,'latestMember']); 

    //project
  Route::post('add_project',[AdminController::class,'addProject']);  


Route::post('register',[AdminController::class,'register']);
Route::post('login',[AdminController::class,'login']);

Route::post('month_weekend',[AdminController::class,'calculateWeekend']);


Route::post('addemployee',[AdminController::class,'addEmployee']);
Route::post('getemployee',[EmployeeController::class,'getEmployee']);
Route::post('getemployee/{id}',[EmployeeController::class,'specificEmployee']);



Route::post('add_professionaltax', [AdminController::class, 'addProfessionalTax']);

  //add salary
Route::post('add_salary_structure',[AdminController::class,'salaryStructure']);  

   // salary structure
Route::post('salary-structure',[AdminController::class,'salaryStuct']);   

   // add tax information
Route::post('add_tax_information',[AdminController::class,'taxInformation']);

  //salary component
Route::post('add-salary-component',[AdminController::class,'salaryComponent']); 

Route::post('add-salary-components',[AdminController::class,'salaryComponents']);  

    //salary calculate
Route::post('calculate-salary',[AdminController::class,'calculateSalary']); 

  //allowance form employee
Route::post('employee-allowance',[AdminController::class,'empAllowance']);  
 
  // allowances in company
Route::post('comapny-allowance',[AdminController::class,'companyAllowance']);  

    //asset management
Route::post('asset-purchase',[AdminController::class,'assetPurchase']);   

Route::post('asset-allocation',[AdminController::class,'assetAlloc']);

Route::post('create-states',[AdminController::class,'createIndianStates']);




     //testing employee registration
//  Route::post('employee-registration',[EmployeeRegistration::class,'employeeRegistration']);    
//  Route::post('employee-qualification',[EmployeeRegistration::class,'employeeQualification']);
//  Route::post('employee-experience',[EmployeeRegistration::class,'employeeExperience']);
//  Route::post('employee-bank',[EmployeeRegistration::class,'employeeBank']);
//  Route::post('employee-family',[EmployeeRegistration::class,'employeeFamily']);
//  Route::post('employee-address',[EmployeeRegistration::class,'employeeAddress']);
//  Route::post('employee-skill',[EmployeeRegistration::class,'employeeSkill']);
//  Route::post('employee-document_upload',[EmployeeRegistration::class,'employeeDocUpload']);

    //to check session variable
Route::post('session-check',[EmployeeRegistration::class,'sessionShow']);


         
// Route::post('employee-details-save',[EmployeeRegistration::class,'employeeRegistration']);
// Route::post('employee-details-show',[EmployeeRegistration::class,'employeeRegistrationShow']);
// Route::post('employee-details-forget',[EmployeeRegistration::class,'employeeRegistrationForget']);

// Route::post('employee-qualification-save',[EmployeeRegistration::class,'employeeQualification']);
// Route::post('employee-qualification-show',[EmployeeRegistration::class,'employeeQualificationShow']);
// Route::post('employee-qualification-forget',[EmployeeRegistration::class,'employeeQualificationForget']);

// Route::post('employee-experience-save',[EmployeeRegistration::class,'employeeExperience']);
// Route::post('employee-experience-show',[EmployeeRegistration::class,'employeeExperienceShow']);
// Route::post('employee-experience-forget',[EmployeeRegistration::class,'employeeExperienceForget']);


// Route::post('employee-bank-save',[EmployeeRegistration::class,'employeeBank']);
// Route::post('employee-bank-show',[EmployeeRegistration::class,'employeeBankShow']);
// Route::post('employee-bank-forget',[EmployeeRegistration::class,'employeeBankForget']);


// Route::post('employee-family-save',[EmployeeRegistration::class,'employeeFamily']);
// Route::post('employee-family-show',[EmployeeRegistration::class,'employeeFamilyShow']);
// Route::post('employee-family-forget',[EmployeeRegistration::class,'employeeFamilyForget']);


// Route::post('employee-address-save',[EmployeeRegistration::class,'employeeAddress']);
// Route::post('employee-address-show',[EmployeeRegistration::class,'employeeAddressShow']);
// Route::post('employee-address-forget',[EmployeeRegistration::class,'employeeAddressForget']);


// Route::post('employee-skill-save',[EmployeeRegistration::class,'employeeSkill']);
// Route::post('employee-skill-show',[EmployeeRegistration::class,'employeeSkillShow']);
// Route::post('employee-skill-forget',[EmployeeRegistration::class,'employeeSkillForget']);


// Route::post('employee-document_upload-save',[EmployeeRegistration::class,'employeeDocumentUpload']);
// Route::post('employee-document_upload-show',[EmployeeRegistration::class,'employeeDocumentUploadShow']);
// Route::post('employee-document_upload-forget',[EmployeeRegistration::class,'employeeDocumentUploadForget']);
// Route::post('employee-document-session-check',[EmployeeRegistration::class,'checkStoredFile']);





     //Employee registration
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


// Route::post('employee-qualification',[EmployeeRegistration::class,'employeeQualification']);
// Route::post('employee-experience',[EmployeeRegistration::class,'employeeExperience']);
// Route::post('employee-bank',[EmployeeRegistration::class,'employeeBank']);
// Route::post('employee-family',[EmployeeRegistration::class,'employeeFamily']);
// Route::post('employee-address',[EmployeeRegistration::class,'employeeAddress']);
// Route::post('employee-skill',[EmployeeRegistration::class,'employeeSkill']);
// Route::post('employee-document_upload',[EmployeeRegistration::class,'employeeDocUpload']);



          // Admin check employee list by (name,id,location,date)

Route::post('emp_search_name',[EmployeeRegistration::class,'empSearchByName']);
Route::post('emp_search_id',[EmployeeRegistration::class,'empSearchById']);
Route::post('emp_search_location',[EmployeeRegistration::class,'empSearchByLoc']);
Route::post('emp_search_date',[EmployeeRegistration::class,'empSearchByDate']);
Route::post('emp_search_halfyear',[EmployeeRegistration::class,'empSearchByhalfYear']);
Route::post('emp_search_yearly',[EmployeeRegistration::class,'empSearchByYear']);


     //excel data export
  Route::get('emp-3months-record',[EmployeeRegistration::class,'Usersdata']);
   //Route::get('export-record',[EmployeeRegistration::class,'MonthsData']);

  Route::get('emp-monthwise',[EmployeeRegistration::class,'empMonthWiseExcel']);
  Route::get('emp-three-monthwise',[EmployeeRegistration::class,'empThreeMonthWiseExcel']);
  Route::get('emp-six-monthwise',[EmployeeRegistration::class,'empSixMonthWiseExcel']);
  Route::get('emp-yearwise',[EmployeeRegistration::class,'empYearWiseExcel']);















     //loan 

Route::post('request-loan',[LoanController::class,'loanRequest']);










Route::group(['middleware' => 'check.login'], function () {
  // Routes accessible only when logged in
  //Route::get('create/employee', [AdminController::class, 'createEmployee']);
  
});

// Route::middleware('auth:api')->group(function(){
//   Route::get('get-user',[AdminController::class,'userdetail']);
// });

Route::middleware('auth:api')->group(function (){
 Route::get('/user/{id}',[AdminController::class,'getuser']);
 Route::get('/logout', [AdminController::class, 'logout']);
});



// Route::middleware('auth:api')->get('/user/{id)', function (Request $request) {
//     return $request->user();
// });
