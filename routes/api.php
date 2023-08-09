<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AdminController;

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
      //Eployee
   Route::post('addemployee',[AdminController::class,'addEmployee']);
   Route::post('editemployee/{employeeId}',[AdminController::class,'editEmployee']);
   Route::post('allemployee',[AdminController::class,'allEmployee']);
   Route::post('singleemployee/{id}',[AdminController::class,'singleEmployee']);
    Route::delete('deleteemployee/{employeeId}',[AdminController::class,'destroyEmployee']);

   Route::post('logoutuser', [AdminController::class, 'logoutSession']);
   Route::post('profile',[AdminController::class,'profile']);

  // Route::middleware('web')->post('logincompany',[AdminController::class,'loginCompany']);





Route::post('register',[AdminController::class,'register']);
Route::post('login',[AdminController::class,'login']);


Route::post('createEmployee', [AdminController::class, 'createEmployee']);

Route::post('addemployee',[AdminController::class,'addEmployee']);
Route::post('getemployee',[AdminController::class,'getEmployee']);
Route::post('getemployee/{id}',[AdminController::class,'specificEmployee']);




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
