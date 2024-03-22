<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Mail;
use App\Mail\LeaveMail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Route;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Carbon\Carbon;

use Illuminate\Database\Schema\Blueprint;


class LoanController extends Controller
{
    public function loanRequest(Request $request)
    {
      try
      {

         
        $validatedData = $request->validate([
            'emp_id'        =>  'required',
            'emp_name'       => 'required',
            'loan_type'      => 'required',
            'currency_type'  => 'nullable',
            'principle'      => 'required',
            'is_interest'    => 'nullable',
            'interest_mode'  => 'required',
            'installment'    => 'required',
            'duration_months'  => 'required',
            'proof_type'     => 'required',
            'proof_doc'      => 'nullable',
            'disburse_method' => 'required', 
            'cheque_doc'     =>  'nullable',
            'notes'          =>  'nullable',
            'interest_rate'  =>  'required',
            ]);
            session_start();
            if(isset($_SESSION['username']) && isset($_SESSION['password']) && isset($_SESSION['dbName']))
            {
                $username = $_SESSION['username'];
                $password = $_SESSION['password'];
                $dbName   = $_SESSION['dbName'];
    
                $date = Carbon::now()->timezone('Asia/Kolkata')->format('Y-m-d H:i:s');
              
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
    
                if (!$dynamicDB->getSchemaBuilder()->hasTable('Loan_request')) {
                    $dynamicDB->getSchemaBuilder()->create('Loan_request', function (Blueprint $table) {
                        $table->id();
                        $table->bigInteger('emp_id')->unsigned()->nullable();
                        $table->string('emp_name');
                        $table->string('loan_type');
                        $table->string('currency_type')->nullable();
                        $table->decimal('principle_amount',10,2);
                        $table->enum('is_interest',['yes','no'])->default('yes');
                        $table->string('interest_mode')->nullable();
                        $table->decimal('interest_rate');
                        $table->string('installment_amount');
                        $table->integer('duration_months');
                        $table->string('proof_type');
                        $table->string('proof_doc')->nullable();
                        $table->enum('disburse_method',['deduct_payroll','direct_cash','cheque'])->default('deduct_payroll');
                        $table->string('cheque_doc')->nullable();
                        $table->enum('status',['pending','approved','rejected'])->default('pending');
                        $table->string('approved_by')->nullable();
                        $table->text('notes')->nullable();
                        $table->timestamps();
        
                        $table->foreign('emp_id')->references('id')->on('employees');
        
                    });
                }
    
                $dynamicDB->table('Loan_request')->insert([
                 'emp_id'   => $request->input('emp_id'),
                 'emp_name' => $request->input('emp_name'),
                 'loan_type' => $request->input('loan_type'),
                 'currency_type' => $request->input('currency_type'),
                 'principle_amount' => $request->input('principle'),
                //  'is_interest'      =>  $request->input('is_interest'),
                 'interest_mode'   => $request->input('interest_mode'), 
                 'interest_rate'   => $request->input('interest_rate'),  
                 'installment_amount'  => $request->input('installment'),
                 'duration_months'    =>  $request->input('duration_months'),
                 'proof_type'        =>  $request->input('proof_type'),
                 'proof_doc'       =>   $request->input('proof_doc'),
                 'disburse_method' =>  $request->input('disburse_method'),
                 'cheque_doc'     =>  $request->input('cheque_doc'),
                 'notes'          =>  $request->input('notes'),
                 'created_at'     =>  $date,
                 'updated_at'     =>  $date,
    
                ]);
                return response()->json(['success' => true,'message'=>'Loan request stored successfully']);
    
            }
            return response()->json(['success' => false,'message'=>'session out! pls login']);


      }
      catch(\Exception $e)
      {
        return response()->json(['error' => $e->getMessage()]);
      }


    }












}
