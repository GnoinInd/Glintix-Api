<?php

namespace App\Http\Controllers\Api;

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


class AssetController extends Controller
{
 


    
public function assetPurchase(Request $request)
{

    $validatedData = $request->validate([
        'asset_name' => 'required',
        'type_of_asset' => 'required',
        'asset_category' => 'required',
        'description' => 'required',
        'brand_name' => 'required',
        'model' => 'required',
        'serial_no' => 'required',
        'purchase_no' => 'required',
        'purchase_date' => 'required',
        'purchase_amount' => 'required',
        'invoice_no'  => 'required',
        'invoice_date' => 'required',
        'warranty_end_date' => 'required',

    ]);


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
     if (!$dynamicDB->getSchemaBuilder()->hasTable('asset_purchase')) {
        $dynamicDB->getSchemaBuilder()->create('asset_purchase', function (Blueprint $table) {
            $table->id();
            $table->string('asset_code');
            $table->string('asset_name');
            $table->string('type_of_asset');
            $table->string('asset_category');
            $table->text('description');
            $table->string('brand_name');
            $table->string('model');
            $table->string('serial_no');
            $table->string('purchase_no');
            $table->string('purchase_date');
            $table->decimal('purchase_amount',8,2);
            $table->string('invoice_no');
            $table->string('invoice_date');
            $table->date('warranty_end_date');
            $table->enum('isAlloc',['yes','no'])->default('no');
            $table->string('allocation_date')->nullable();
            $table->enum('status',['active','inactive'])->default('inactive');
            
            $table->timestamps();
        });
    }

    $assetCode = strval(rand(10000000, 99999999));

    $dynamicDB->table('asset_purchase')->insert([
        'asset_code' => $assetCode,
        'asset_name' => $request->input('asset_name'),
        'type_of_asset' => $request->input('type_of_asset'),
        'asset_category'  => $request->input('asset_category'),
        'description'  =>  $request->input('description'),
        'brand_name' => $request->input('brand_name'),
        'model' => $request->input('model'),
        'serial_no' => $request->input('serial_no'),
        'purchase_no' => $request->input('purchase_no'),
        'purchase_date' => $request->input('purchase_date'),
        'purchase_amount' => $request->input('purchase_amount'),
        'invoice_no' => $request->input('invoice_no'),
        'invoice_date' => $request->input('invoice_date'),
        'warranty_end_date' => $request->input('warranty_end_date'),
        // 'allocation_date'  => 
        'created_at' =>        $date,
        'updated_at' =>        $date,


    ]);


     return response()->json(['message' => 'table create successfully']);

    }
    return response()->json(['message' => 'sorry session out,pls login']);
}





public function requestAsset(Request $request)
{
    $validator = Validator::make($request->all(), [
        'allocation_for' => 'required',
        'is_return' => 'nullable',
        'asset_name' => 'required',
        'remarks' => 'required',
    ]);

    if ($validator->fails()) 
    {
        return response()->json(['status' => false, 'message' => $validator->errors()], 400);
    }

    session_start();
    if (isset($_SESSION['username']) && isset($_SESSION['password']) && isset($_SESSION['dbName']) && isset($_SESSION['empEmail']) 
    && isset($_SESSION['empPass'])) 
 
    {
        $username = $_SESSION['username'];
        $password = $_SESSION['password'];
        $dbName = $_SESSION['dbName'];
        $empEmail = $_SESSION['empEmail'];
        $empPassword = $_SESSION['empPass'];


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

        try {
            // $dynamicDB->beginTransaction();

            if (!$dynamicDB->getSchemaBuilder()->hasTable('asset_request')) {
                $dynamicDB->getSchemaBuilder()->create('asset_request', function (Blueprint $table) {
                    $table->id();
                    $table->unsignedBigInteger('emp_id')->unsigned();
                    $table->date('application_date');
                    $table->enum('alloc_for',['emp','branch','dept']);
                    $table->string('assign_emp');
                    $table->enum('is_return',['yes','no'])->default('no')->nullable();
                    $table->string('asset_name');
                    $table->text('remarks');
                    $table->enum('status',['pending','approved','rejected'])->default('pending');
                    $table->timestamps();
                    
                    $table->foreign('emp_id')->references('id')->on('employees');
                    // $table->foreign('emp_id')->references('id')->on('employees')->onDelete('cascade');
                });
            }

            $empId = $dynamicDB->table('employees')->where('email', $empEmail)->value('id');
            $empName = $dynamicDB->table('employees')->where('email', $empEmail)->value('name');

            

            $dynamicDB->table('asset_request')->insert([
                'emp_id' => $empId,
                'application_date' => $date,
                'alloc_for' => $request->input('allocation_for'),
                'assign_emp' => $empName,
                'is_return' => $request->input('is_return'),
                'asset_name' => $request->input('asset_name'),
                'remarks' => $request->input('remarks'),
                //'status' => 'pending',
                'created_at' => $date,
                'updated_at' => $date,
            ]);

         

            return response()->json(['status' => true, 'message' => 'Asset request sent successfully']);
            // $dynamicDB->commit();
        } catch (\Exception $e) {
            // $dynamicDB->rollback();
            return response()->json(['status'=> false,'message'=>'Error processing the request','error'=>$e->getMessage()], 500);
        }
    }

    return response()->json(['status' => false, 'message' => 'Session out! Please login']);
}



















}
