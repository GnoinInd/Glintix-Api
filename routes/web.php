<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\EmployeeRegistration;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('reset-password',[AdminController::class,'resetpasswordLoad']);
Route::post('reset-password',[AdminController::class,'resetPassword'])->name('password.update');

Route::get('export-project-excel/{month}/{year}',[AdminController::class,'testProject']);



// Route::get('/car', [AdminController::class, 'showForm']);
// Route::post('/car', [AdminController::class, 'processForm']);

Route::get('emp-record',[EmployeeRegistration::class,'ThreeMonthsData']);
      //login for company
Route::get('company-login-form',[EmployeeRegistration::class,'companyLoginForm'])->name('login.form');
Route::post('company-login',[EmployeeRegistration::class,'companyLogin'])->name('company.login');
Route::get('print-sessions', function () {
    $sessions = session()->all();
    dd($sessions); 
});
Route::get('clear-sessions', [EmployeeRegistration::class, 'clearSessions']);   //clear all sessions




  Route::get('export-record',[EmployeeRegistration::class,'MonthsData']);
Route::middleware(['web'])->group(function () {
  Route::get('export-data',[EmployeeRegistration::class,'exportData']);


});
  