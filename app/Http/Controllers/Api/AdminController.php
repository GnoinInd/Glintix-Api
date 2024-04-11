<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Exports\ThreeMonthsRecordexport;
//use App\Models\Client;
 use Illuminate\Support\Facades\Mail;
 use App\Mail\LeaveMail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\URL;
use App\Http\Controllers\Api\AdminController;
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
use App\Models\PasswordReset;
use App\Models\SuperUser;
use App\Models\UserOtp;
use Exception;
use Twilio\Rest\Client;
use App\Mail\SuccessfulLoginNotification;
use App\Mail\OtpMail;
use App\Mail\WelcomeMail;
use Laravel\Sanctum\PersonalAccessTokenFactory;
use Cache;
use Illuminate\Validation\ValidationException;
use App\Models\CompanyModuleAccess;
use App\Mail\RootUserMail;
use App\Models\CompanyOtp;
use Laravel\Sanctum\PersonalAccessToken;
use App\Models\CompanyUserAccess;
use App\Models\UserAccessOtp;
use App\Models\Branch;
use App\Models\Dept;
use App\Models\Role;
use App\Models\Permission;
use App\Models\Module;
use App\Models\RoleMaster; 
use App\Models\RoleUserAssign;
use App\Models\ProjectMaster;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ProjectMasterData;

use App\Imports\ProjectDataImport;




class AdminController extends Controller
{



public function register(Request $request)
    {
        $validatedData = Validator::make($request->all(),[
        'name'=>'required|min:3',
        'email' => 'required|email|unique:users',
        'password' => 'required|min:5'
        ]);
        if ($validatedData->fails()) {
            return response()->json(['message' => 'Validation failed'], 422);
        }
    

        $data = $request->all();
        $data['password'] = Hash::make($request->password);
        $user = User::create($data);

        if($user)
        {
            return response()->json(['success'=>'success','message'=>'User registration successfully','data'=>$data]);
        }
        else{
            return response()->json(['success'=>'Fail','message'=>'User registration Fail']);
        }
 
    }

    public function login(Request $request)
{
    $data = [
        'email' => $request->email,
        'password' => $request->password
    ];
    if (auth()->attempt($data)) {
        $user = auth()->user(); 
        $token = $user->createToken('token_key')->accessToken;

       // $request->session()->put('access_token', $token);

        return response()->json(['token' => $token,'data'=>$data], 200);
    } 
    else
     {
        return response()->json(['error' => 'Unauthorised'], 401);
    }
    
}







public function logout()
    {
        $user = Auth::user();

        if ($user) {
            
            $user->tokens->each(function ($token, $key) {
                $token->delete();
            });
           // session()->forget('access_token');

            return response()->json(['message' => 'Logged out successfully'], 200);
        } else {
        
            return response()->json(['message' => 'User not authenticated','user' => $user], 401);
        }
    }


    public function registerRoot(Request $request)
    {
        try
        {

            $validatedData = $request->validate([
                'name' =>'required',
                'username' => 'required',
                'password' => 'required',
                'email'   => 'required|email|unique:super_users,email',
                'phone'   => 'required', 
            ]);
            $superUser = new SuperUser;
            $superUser->name = $validatedData['name'];
            $superUser->username = $validatedData['username'];
            $superUser->password = bcrypt($validatedData['password']);
            $superUser->email = $validatedData['email'];
            $superUser->phone = $validatedData['phone'];
            $superUser->save();
            return response()->json(['success'=>true,'message' => 'root registration done successfully'],200);

        }
        catch(\Exception $e)
        {
            return response()->json(['success' => false,'error' => $e->getMessage()],500);
        }
     
    }

 


    public function adminLogin(Request $request)
    {
      try
      {  
        $validatedData = $request->validate([
            'username' => 'required',
            'password' => 'required|string|min:6',
        ]);

        $root = SuperUser::where('username', $validatedData['username'])->first();

        if ($root && Hash::check($validatedData['password'], $root->password)) {
            $otpResult = $this->generateAndSendOtp($root->id, $root->phone,$root->email);

            if ($otpResult['success']) {
                return response()->json(['success' => true,'userId' => $root->id,'message' => 'otp send successfully'],200);
            } else {
                return response()->json(['success' => false, 'message' => $otpResult['error']],401);
            }
        }

      }
      catch(\Exception $e)
      {
        return response()->json(['success' => false, 'message' => 'An error occurred. Please try again.'], 500);
      }
    }

    private function generateAndSendOtp($userId, $phone,$email)
    {
        try {
            $otp = rand(100000, 999999);

            $expireAt = now()->addMinutes(1);
            UserOtp::create([
                'user_id' => $userId,
                'otp' => $otp,
                'expire_at' => $expireAt,
            ]);

            $this->sendOtpViaTwilio($phone, $otp);
            $this->sendOtpViaEmail($email,$otp);

            return ['success' => true];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function sendOtpViaTwilio($phone, $otp)
    {
        try {
            $accountSid = getenv("TWILIO_SID");
            $authToken = getenv("TWILIO_TOKEN");
            $twilioNumber = getenv("TWILIO_FROM");

            $client = new Client($accountSid, $authToken);
            $message = $client->messages->create($phone, [
                'from' => $twilioNumber,
                'body' => "Your OTP: $otp",
            ]);

            if ($message->sid) {
                return ['success' => true];
            } else {
                return ['success' => false, 'error' => 'Failed to send OTP'];
            }
        } catch (\Twilio\Exceptions\RestException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }



    private function sendOtpViaEmail($email,$otp)
    {
        try{
            Mail::to($email)->send(new OtpMail($otp));
            return ['success'=>true];
        }
        catch(\Exception $e)
        {
           return ['success'=>false,'error'=>$e->getMessage()];
        }
    }




    
    public function verifyOtp(Request $request)
    {
      try
       {
 
        $validatedData = $request->validate([
             'user_id' => 'required',
             'otp' => 'required|string|digits:6',
        ]);

        
         $userId = $request->user_id;
        $otp = $request->otp;
      
        $userOtp = UserOtp::where('user_id', $userId)
            ->where('otp', $otp)
            ->where('expire_at', '>', now())
            ->first();
        if ($userOtp) {
            $userOtp->delete();
            $root = SuperUser::find($userId);
            $role = $root->role;
            $email = $root->email;

             Mail::to($email)->send(new SuccessfulLoginNotification($root));
            $token = $root->createToken('access_token')->plainTextToken;
        

            return response()->json(['success' => true, 'data' => $root, 'access_token' => $token,
             'message' => 'OTP verification successful'], 200);
        

        } else {
            $deleted = UserOtp::where('user_id', $userId)
            ->where('otp', $otp)
            ->delete();
          
            if ($deleted) {
                return response()->json(['success' => false, 'message' => 'Invalid OTP'],422);
            } else {
                return response()->json(['success' => false, 'message' => 'Invalid OTP and no expired OTP found'],422);
            }
            
           
        } 
      }
      catch(\Exception $e)
      {
     return response()->json(['success' => false, 'message' => 'An error occurred. Please try again.',$e->getMessage()], 500);      }
    }




 
    
    



          





    public function rootForgetPass(Request $request)
    {
      try
      { 
        $validatedData = $request->validate([
            'mobile_number' => 'required|exists:super_users,phone',
        ]);
        $root = SuperUser::where('phone', $validatedData['mobile_number'])->first();
        if ($root) {
            $otpResult = $this->generateAndSendOtp($root->id, $root->phone,$root->email);
            if ($otpResult['success'])
             {
                return response()->json(['success' => true,'phone'=>$validatedData['mobile_number'], 'message' => 'OTP sent successfully'],200);
             } 
            else 
            {
                return response()->json(['success' => false, 'message' => $otpResult['error']],500);
            }
        } 
        else
         {
            return response()->json(['success' => false, 'message' => 'User not found'],404);
         }
      }
      catch(\Exception $e)
      {
        return response()->json(['success' => false, 'message' => 'An error occurred. Please try again.'], 500);
      }

    }



    public function verifyRootForgetPass(Request $request)
    {
        $validatedData = $request->validate([
            // 'mobile_number' => 'required',
            'otp'  => 'required'
        ]);
        $otp = $validatedData['otp'];
        // $phone = $validatedData['mobile_number'];
        $phone = $request->mobile_number;
        $user = SuperUser::where('phone',$phone)->first();
        if($user)
        {
            $userId = $user->id; 
            $userOtp = UserOtp::where('user_id',$userId)->where('otp',$otp)->where('expire_at', '>', now())->first();
                
         if($userOtp)
         { 
            $userOtp->delete();
            $user->time_expire = now()->addMinutes(1440);
            $user->save();
            $token = $user->createToken('access-token')->plainTextToken;
            return response()->json(['success' => true,'token'=>$token,'message' => 'OTP verification successful'],200);

         }
         else
         {
          return response()->json(['success' => false,'success'=>false,'message' => 'Invalid OTP or mobile number'],422);
         }

       }
       return response()->json(['success' => false,'success'=>false,'message' => 'user not found'],404);


    }



  public function setNewPassword(Request $request)
  {
    try{
    $validatedData = $request->validate([
        'new_password' => 'required|string|min:6',
        'confirm_new_password' => 'required|string|same:new_password',
    ]);
    
    $token = $request->user()->currentAccessToken();
    if($token)
    {
        $mobile = $token['tokenable']['phone']; 
        $userId = $token['tokenable']['id'];    
      
    if ($user = $this->validateSuperUserTimeExpire($mobile, $userId)) 
     {
        $newPassword = $validatedData['new_password'];
        $user->password = Hash::make($newPassword);
        $user->save();
        $token->delete();  

        return response()->json(['success' => true, 'message' => 'Password updated successfully'], 200);
     }
    else 
     {
        return response()->json(['success' => false, 'message' => 'time expired!'], 422);
     }
    }
    return response()->json(['success'=> false,'message'=>'Invalid Token'],422);
   }
   catch(\Exception $e)
   {
    return response()->json(['success' => false, 'message' => 'An error occurred. Please try again.'], 500);
   }
  }


   protected function validateSuperUserTimeExpire($mobile, $userId)
   {
      $user = SuperUser::where('phone', $mobile)->where('id', $userId)->first();

      if ($user && Carbon::now()->lt($user->time_expire)) {
          return $user;
      }
      elseif ($user)
      {
        $token = $user->currentAccessToken();
        if($token)
        {
          $token->delete();
        }
        return null;
      }

      return null;
   }







    public function registerCompany(Request $request)
    {
        $token = $request->user()->currentAccessToken();
        if(!$token)
        {
            return response()->json(['success'=>false,'message'=>'invalid token'],401);
        }
        try {
           
            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'contact_person' =>'required',
                'country'  => 'required',
                'state'  =>  'required',
                'postal_code'  => 'required',
                'address' => 'required|string',
                'email' => [
                    'required',
                    'email',
                    'unique:users,email',
                    function ($attribute, $value, $fail) {
                        if (strpos($value, '@') !== false) {
                            list($username, $domain) = explode('@', $value);
                            if (strpos($domain, '.com') === false) {
                                $fail($attribute.' must have ".com" after the @ symbol.');
                            }
                        } else {
                            $fail($attribute.' is not a valid email address.');
                        }
                    },
                ],
                'fax'  =>  'nullable',
                'mobile_number' => 'required',
                'website_url' => 'nullable',
                'company_logo' => 'file|nullable',
                'modules' => 'required',
                          
            ]);
            $requestModules = $request->modules;

            if (strpos($requestModules, '"') !== false) {
                $modulesId = array_map('intval', explode(',', str_replace('"', '', $requestModules)));
            } else {
                $modulesId = explode(',', $requestModules);
            }
            $generatedDbName = $this->generateUniqueDbName($request->input('name'));
            $role = $token['tokenable']['role']; 
            if(isset($role) && $role == 'root')
            {


                if ($request->hasFile('company_logo')) {
                    $file = $request->file('company_logo');
                    $uniqueFolder = 'logo' . '_' . time();
                    $filePath = $file->store('companyLogo/' . $uniqueFolder);
                    $logoPath = $filePath;
                } else {
                    $logoPath = null;
                }
                $usernameAlpha = Str::random(5, 'abcdefghijklmnopqrstuvwxyz');
                $usernameNum = Str::random(5, '0123456789');
                $generatedUsername = $usernameAlpha . $usernameNum;  
                $passAlpha = str_shuffle(Str::random(4, 'abcdefghijklmnopqrstuvwxyz'));
                $passSpecial = '!@#$%^&*()_?';
                $passSpecial = $passSpecial[rand(0, strlen($passSpecial) - 1)];
                $passNum = str_shuffle(Str::random(5, '0123456789'));
                $generatedPassword = $passAlpha . $passSpecial . $passNum;
                $role = 'admin';
                $total = 25;
                        
                $companyCode = $this->generateUniqueCompanyCode();
                if (!isset($modulesId) || !is_array($modulesId)) {
                    return response()->json(['success' => false, 'message' => 'Modules parameter is required and must be an array'],404);
                }

                $moduleSelectionResult = $this->selectModules($companyCode, $modulesId);
                if(!$moduleSelectionResult['success'])
                {
                    return response()->json(['success'=>false,'message'=>'some issue to select modules'],401);
                }
        

                $user = new User;
                $user->name = $validatedData['name'];
                $user->email = $validatedData['email'];
                $user->password = bcrypt($generatedPassword);
                $user->dbName = $generatedDbName;
                $user->username = $generatedUsername;
                $user->total   = $total;
                $user->company_code = $companyCode;
                $user->contact_person = $validatedData['contact_person'];
                $user->address  = $validatedData['address'];
                $user->country  = $validatedData['country'];
                $user->state    = $validatedData['state'];
                $user->postal_code = $validatedData['postal_code'];
                $user->mobile_number = $validatedData['mobile_number'];
                $user->fax     = isset($validatedData['fax']) ? $validatedData['fax'] : null;
                $user->website_url = isset($validatedData['website_url']) ? $validatedData['website_url'] : null;
                $user->company_logo = $logoPath;
                $user->dbPass = $generatedPassword;
                $user->save();
                
                $dbName = $generatedDbName;
                $dbUsername = $generatedUsername;
                $dbPassword = $generatedPassword;
    
                $this->createDynamicDatabase($dbName, $dbUsername, $dbPassword);
    
                Config::set('database.connections.dynamic', [
                    'driver' => 'mysql',
                    'host' => 'localhost',
                    'database' => $dbName,
                    'username' => $dbUsername,
                    'password' => $dbPassword,
                    'charset' => 'utf8mb4',
                    'collation' => 'utf8mb4_unicode_ci',
                    'prefix' => '',
                    'strict' => true,
                    'engine' => null,
                ]);
    
                $dynamicDB = DB::connection('dynamic');
                if (!$this->tableExists($dynamicDB, 'clients')) {
                    $this->createClientsTable($dynamicDB);
                }
                $date = Carbon::now()->timezone('Asia/kolkata')->format('Y-m-d H:i:s');
                $clientData = [
                    'name'    => $request->input('name'),
                    'email'   => $request->input('email'),
                    'username'=> $generatedUsername,
                    'password'=> $generatedPassword,
                    // 'phone'   => $request->input('phone'),
                    'dbName'  => $generatedDbName,
                    'company_code' => $companyCode, 
                    'role'       =>  $role,
                    'contact_person' =>$request->input('contact_person'),
                    'address'      => $request->input('address'),
                    'country'   => $request->input('country'),
                    'state'   => $request->input('state'),
                    'postal_code' => $request->input('postal_code'),
                    'mobile_number' => $request->input('mobile_number'),
                    'fax'      =>  $request->input('fax'),
                    'website_url'  =>  $request->input('website_url'),
                    'company_logo' => $logoPath, 
                    'created_at' => $date,
                    'updated_at' => $date,
                ];
                $dynamicDB->table('clients')->insert($clientData);

                if (!$this->tableExists($dynamicDB, 'personal_access_tokens')) {
                    $this->createTokenTable($dynamicDB);
                }
            
                
                $email = $request->input('email');
                $details = User::where('email',$email)->first();
                if (!$details) {
                    Log::error('User not found for email: ' . $email);
                }
                try {
                    Mail::to($email)->send(new WelcomeMail($details,$generatedPassword));
                } catch (\Exception $e) {
                    Log::error('Error sending welcome email: ' . $e->getMessage());
                } 
                $rootEmail = $token['tokenable']['email'] ?? null;
                if(!$rootEmail)
                {
                    Log::error('root email not found: ' . $rootEmail);
                }
                try {
                    Mail::to($rootEmail)->send(new RootUserMail($details,$generatedPassword));
                } catch (\Exception $e) {
                    Log::error('Error sending welcome email: ' . $e->getMessage());
                } 
                              
                return response()->json([
                    'success' => true,
                    'message' => 'Company registered successfully and modules selected successfully',
                    'companyDetails' => [
                        'name' => $details->name,
                        'comtact person' => $details->contact_person,
                        'address' => $details->address,
                        'country' => $details->country,
                        'state' => $details->state,
                        'postal code' => $details-> postal_code,
                        'dbName' => $generatedDbName,
                        'companyCode' => $companyCode,
                        'email' => $details->email,
                        'fax' => $details->fax,
                        'phone' => $details->mobile_number,
                        'website url' => $details->website_url,
                    ],
                ], 201);

            }
            else
            {
                return response()->json(['success'=>false,'message'=>'access denied!'],403);
            }


        } catch (Exception $e) {
            Log::error('Company registration failed: ' . $e->getMessage());
            return response()->json(['message' => 'Company registration failed. Please try again.',$e->getMessage()], 500);
        }
    }


    


    private function createDynamicDatabase($dbName, $dbUsername, $dbPassword)
    { 
        $defaultDB = DB::getDefaultConnection();
        DB::statement("CREATE DATABASE IF NOT EXISTS $dbName");
        DB::statement("CREATE USER '$dbUsername'@'localhost' IDENTIFIED BY '$dbPassword'");
        DB::statement("GRANT ALL ON $dbName.* TO '$dbUsername'@'localhost'");
        DB::statement("FLUSH PRIVILEGES");
        DB::setDefaultConnection($defaultDB);
    

    }

    private function tableExists($connection, $table)
    {
        return Schema::connection($connection->getConfig('name'))->hasTable($table);
    }
 
    private function createClientsTable($connection)
    {
    
        if (!$connection->getSchemaBuilder()->hasTable('clients')) {
            $connection->getSchemaBuilder()->create('clients', function (Blueprint $table) {
                $table->id();
                $table->string('Name', 255);
                $table->string('contact_person' ,255)->nullable();
                $table->string('address' ,255)->nullable();
                $table->string('country' ,255)->nullable();
                $table->string('state' ,255)->nullable();
                $table->string('postal_code' ,255)->nullable();
                $table->string('mobile_number' ,255)->nullable();
                $table->string('fax' ,255)->nullable();
                $table->string('website_url' ,255)->nullable();
                $table->string('company_logo' ,255)->nullable();
                $table->string('email', 255);
                $table->string('username', 255);
                $table->string('password', 255);
                // $table->string('phone', 20);
                $table->string('dbName', 255);
                $table->string('company_code',255);
                $table->enum('role',['admin','subadmin'])->default('admin');
                $table->timestamps();
            });
        }


    }



    private function createTokenTable($connection)
    {
        if (!$connection->getSchemaBuilder()->hasTable('personal_access_tokens')) {
            $connection->getSchemaBuilder()->create('personal_access_tokens', function (Blueprint $table) {
                $table->id();
                // $table->foreignId('user_id');
                $table->text('tokenable_type');
                $table->unsignedBigInteger('tokenable_id');
                $table->string('name',255);
                $table->string('token', 64)->unique();
                $table->text('abilities')->nullable();
                $table->timestamp('last_used_at')->nullable();
                $table->timestamps();
    
                // $table->index(['tokenable_id', 'tokenable_type']);
            });
        }
    }



    private function generateUniqueCompanyCode()
{
    $timestamp = time();
    $randomString = substr(uniqid('',true),0,3);
    $code = $randomString .'_' . $timestamp;
    return $code;
}


private function generateUniqueDbName($name)
{
    $randomString = substr(str_shuffle('0123456789'), 0, 3);
    $code = substr($name, 0, 4) . '_' . $randomString;
    return strtolower($code);
}





private function selectModules($companyCode, $modulesId)
{
    if (!is_array($modulesId)) {
        return ['success' => false, 'message' => 'Modules must be an array'];
    }

    if (!$companyCode) {
        return ['success' => false, 'message' => 'Company code not found'];
    }

    CompanyModuleAccess::where('company_code', $companyCode)->delete();

    try {
        foreach ($modulesId as $moduleId) {
            CompanyModuleAccess::create([
                'company_code' => $companyCode,
                'module_id' => $moduleId,
            ]);
        }

        return ['success' => true, 'message' => 'Modules selected successfully'];
    } catch (\Exception $e) {
        Log::error('Module selection error: ' . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}





public function allCompanies(Request $request)
{
    try{
        $token = $request->user()->currentAccessToken();
        $tokenRole = $token['tokenable']['role'];
        if($token && $tokenRole && $tokenRole == 'root')
        {
          $userData = User::all(); 
          return response()->json(['success'=>true,  'message' => 'company data found', 'data'=> $userData],200);
        }
       return response()->json(['success'=>false,'message' => 'token not found'],404);
      
    }
    catch(\Exception $e)
    {
        return response()->json(['success' => false, 'message' => 'An error occurred. Please try again.'], 500);
    }

}

public function rootProfile(request $request)
{
    try{
     $token = $request->user()->currentAccessToken();

     if ($token && isset($token['tokenable']['email']))
    {
         $email = $token['tokenable']['email'];
         $profile = SuperUser::where('email',$email)->first(); 
        if($profile)
        {
            return response()->json(['success' => true,'message' => 'data found','data' => $profile],200);

        }
        return response()->json(['success'=>false,'message'=>'data not found'],404);
    }
    return response()->json(['success'=>false,'message'=>'token not found!'],404);
 }
 catch(\Exception $e)
 {
    return response()->json(['success' => false, 'message' => 'An error occurred. Please try again.'], 500);
 }

}


public function loginCompany(Request $request)
{
    try{

    
    $validatedData = $request->validate([
        'username' => 'required|string|max:255',
        'password' => 'required|string|min:6',
    ]);
    
    $user = User::where('username', $validatedData['username'])->first();
    if ($user && Hash::check($validatedData['password'], $user->password)) {
        $otpResult = $this->generateAndSendOtpCompany($user->id, $user->mobile_number,$user->email);

        if ($otpResult['success']) {
            return response()->json(['success' => true,'userId' => $user->id,'message' => 'otp send successfully'],200);
        } else {
            return response()->json(['success' => false, 'message' => $otpResult['error']],401);
        }
     

        return response()->json(['success' => true, 'message' => 'Login Successfully'], 200);
    } else {
        return response()->json(['success' => false, 'message' => 'Username or password is incorrect'],401);
    }
  }
  catch(\Exception $e)
  {
    return response()->json(['success' => false, 'message' => 'An error occurred. Please try again.'], 500);
  }
}


   private function generateAndSendOtpCompany($userId, $phone,$email)
    {
        try {
            $otp = rand(100000, 999999);

            $expireAt = now()->addMinutes(1);
            CompanyOtp::create([
                'user_id' => $userId,
                'otp' => $otp,
                'expire_at' => $expireAt,
            ]);

            $this->sendOtpViaTwilioCompany($phone, $otp);
            $this->sendOtpViaEmailCompany($email,$otp);

            return ['success' => true];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function sendOtpViaTwilioCompany($phone, $otp)
    {
        try {
            $accountSid = getenv("TWILIO_SID");
            $authToken = getenv("TWILIO_TOKEN");
            $twilioNumber = getenv("TWILIO_FROM");

            $client = new Client($accountSid, $authToken);
            $message = $client->messages->create($phone, [
                'from' => $twilioNumber,
                'body' => "Your OTP: $otp",
            ]);

            if ($message->sid) {
                return ['success' => true];
            } else {
                return ['success' => false, 'error' => 'Failed to send OTP'];
            }
        } catch (\Twilio\Exceptions\RestException $e) {
          //  \Log::error("Twilio Exception: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        } catch (\Exception $e) {
          //  \Log::error("Exception: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }



    private function sendOtpViaEmailCompany($email,$otp)
    {
        try{
            Mail::to($email)->send(new OtpMail($otp));
            return ['success'=>true];
        }
        catch(\Exception $e)
        {
           return ['success'=>false,'error'=>$e->getMessage()];
        }
    }



     
    public function verifyOtpCompany(Request $request)
    {
        try{    
        $validatedData = $request->validate([
               'user_id' => 'required',
             'otp' => 'required|string|digits:6',
        ]);
         $userId = $request->user_id;
        
        // $userId = $validatedData['user_id'];
        $otp = $request->otp;
        $companyOtp = CompanyOtp::where('user_id', $userId)
            ->where('otp', $otp)
            ->where('expire_at', '>', now())
            ->first();
        if ($companyOtp) {
            $companyOtp->delete();
            $root = User::find($userId);
            $role = $root->role;
            $email = $root->email;

             Mail::to($email)->send(new SuccessfulLoginNotification($root));
             $token = $root->createToken('access_token')->plainTextToken;        
            return response()->json(['success' => true, 'data' => $root, 'access_token' => $token,
             'message' => 'OTP verification successful'], 200);
        

        } else {
            $deleted = CompanyOtp::where('user_id', $userId)
            ->where('otp', $otp)
            ->delete();
          
            if ($deleted) {
                return response()->json(['success' => false, 'message' => 'Invalid OTP'],422);
            } else {
                return response()->json(['success' => false, 'message' => 'Invalid OTP and no expired OTP found'],422);
            }
            
           
        }
      }
      catch(\Exception $e)
      {
        return response()->json(['success' => false, 'message' => 'An error occurred. Please try again.',$e->getMessage()], 500);
      }
    }





    public function companyForgetPass(Request $request)
    {
        try{

       
        $validatedData = $request->validate([
            'mobile_number' => 'required|exists:users,mobile_number',
        ]);
        $root = User::where('mobile_number', $validatedData['mobile_number'])->first();
        if ($root) {
            $otpResult = $this->generateAndSendOtpCompany($root->id, $root->mobile_number,$root->email);
            if ($otpResult['success'])
             {
                return response()->json(['success' => true,'phone'=>$validatedData['mobile_number'], 'message' => 'OTP sent successfully'],200);
             } 
            else 
            {
                return response()->json(['success' => false, 'message' => $otpResult['error']],500);
            }
        } 
        else
         {
            return response()->json(['success' => false, 'message' => 'User not found'],404);
         }

      }
      catch(\Exception $e)
      {
        return response()->json(['success' => false, 'message' => 'An error occurred. Please try again.'], 500);
      }
    }



    public function verifyForgetPassCompany(Request $request)
    {
        try{
            $validatedData = $request->validate([
                'mobile_number' => 'required',
                'otp'  => 'required'
            ]);
            $otp = $validatedData['otp'];
            // $phone = $validatedData['mobile_number'];
            $phone = $request->mobile_number;
            $user = User::where('mobile_number',$phone)->first();
            if($user)
            {
                $userId = $user->id; 
                $userOtp = CompanyOtp::where('user_id',$userId)->where('otp',$otp)->where('expire_at', '>', now())->first();
                    
             if($userOtp)
             {
                $userOtp->delete();
                $user->expire_at = now()->addMinutes(1440);
                $user->save();
                $token = $user->createToken('access-token')->plainTextToken;
                return response()->json(['success' => true,'token'=>$token,'message' => 'OTP verification successful'],200);
    
             }
             else
             {
              return response()->json(['success' => false,'success'=>false,'message' => 'Invalid OTP or mobile number'],422);
             }
    
           }
           return response()->json(['success' => false,'success'=>false,'message' => 'user not found'],404);
    
        }
        catch(\Exception $e)
        {
            return response()->json(['success' => false, 'message' => 'An error occurred. Please try again.'], 500);
        }
     

    }



  public function setNewPasswordCompany(Request $request)
  {
    try{
    
    $validatedData = $request->validate([
        'new_password' => 'required|string|min:6',
        'confirm_new_password' => 'required|string|same:new_password',
    ]);
    
    $token = $request->user()->currentAccessToken();
    if($token)
    {
        $mobile = $token['tokenable']['phone']; 
        $userId = $token['tokenable']['id'];    
      
    if ($user = $this->validateUserTimeExpire($mobile, $userId)) 
     {
        $newPassword = $validatedData['new_password'];
        $user->password = Hash::make($newPassword);
        $user->save();
        $token->delete();  

        return response()->json(['success' => true, 'message' => 'Password updated successfully'], 200);
     }
    else 
     {
        return response()->json(['success' => false, 'message' => 'time expired!'], 422);
     }
    }
    return response()->json(['success'=> false,'message'=>'Invalid Token'],422);
   }
   catch(\Exception $e)
   {
    return response()->json(['success' => false, 'message' => 'An error occurred. Please try again.'], 500);
   }
  }


   protected function validateUserTimeExpire($mobile, $userId)
   {
      $user = SuperUser::where('phone', $mobile)->where('id', $userId)->first();

      if ($user && Carbon::now()->lt($user->time_expire)) {
          return $user;
      }
      elseif ($user)
      {
        $token = $user->currentAccessToken();
        if($token)
        {
          $token->delete();
        }
        return null;
      }

      return null;
   }





public function logoutCompany(Request $request)
{
    $token = $request->user()->currentAccessToken();
    if ($token) {
        $token->delete();
    }
    Auth::guard('web')->logout();
    return response()->json(['success' => true, 'message' => 'Successfully logged out'], 200);
}







public function companyProfile(Request $request)
{
    $token_array = $request->user()->currentAccessToken();
    if ($token_array && isset($token_array['tokenable']['email']))
    {
         $email = $token_array['tokenable']['email'];
         $dbName = $token_array['tokenable']['dbName'];
        $user = User::where('email', $email)
            ->where('dbName', $dbName)
            ->first();

        if ($user) {
            return response()->json(['status' => true, 'success' => true, 'message' => 'Data found', 'data' => $user], 200);
        } else {
            return response()->json(['status' => false, 'success' => false, 'message' => 'Profile details not found'], 404);
        }
    } else
     {
        return response()->json(['status' => false, 'success' => false, 'message' => 'token not found!'], 404);
     }
}




public function forgetpassword(Request $request)
{
        
         $validatedData = $request->validate([
            'email' => 'required|email',
                     
        ]);
  try
  {
    $user = User::where('email',$request->email)->get();
    
    if(count($user) > 0)
    
   {
        
      $token = str::random(30);
      $domain = URL::to('/');
      $url = $domain.'/reset-password?token='.$token;
      $data['url'] = $url;
      $data['email'] = $request->email;
      $data['title'] = 'Password Reset';
      $data['body'] = 'Please click on below link to reset your password';


      
      Mail::send('forget-password-mail', ['data' => $data],function($message) use ($data) {
        
         $message->to($data['email'])->subject($data['title']);

    });



      $datetime = Carbon::now()->timezone('Asia/Kolkata')->format('Y-m-d H:i:s');
      PasswordReset::updateOrCreate(
        ['email' => $request->email],
        ['email' => $request->email,
        'token' => $token,
        'created_at' => $datetime
        ] 
      );
      
      return response()->json(['success' => 'pls check your mail to reset your password'],200);

    }
    else
    {
        return response()->json(['success' => false, 'msg' => 'User not found'],404);
    }
  

  }
  catch(\Exception $e)
  {
    Log::error('Email sending error: ' . $e->getMessage());
    
   return response()->json(['success' => false,'msg'=>$e->getMessage()],500);
  }



}



public function resetpasswordLoad(Request $request)   
{
    $token = $request->token;
    $userData = DB::table('password_resets')->where('token',$token)->get();
    
    if(isset($request->token) && count($userData) > 0)
    {
    $usersData = DB::table('password_resets')->where('token',$token)->value('email');
     
      return view('resetPassword',compact('usersData'));
    }
    else
    {
    return view('404');
    }
}

public function resetPassword(Request $request)
{
    $request->validate([
   'password' => 'required|string|min:6|confirmed'
    ]);
   $email = $request->email;
    $userUpdate = DB::table('users')->where('email',$email)->value('id');
    $user = User::find($userUpdate);
   $user->password = Hash::make($request->password);
   $user->save();
   PasswordReset::where('email',$email)->delete();
   return "<h1> Your Password has been reset Successfully </h1>";
    
}
public function addEmployee(Request $request)
{
    try{
        $token = $request->user()->currentAccessToken();
    
    if (!$token) {
        return response()->json(['success' => false, 'message' => 'Token not found!'],404);
    }
    $companyCode = $token['tokenable']['company_code'];
    $moduleId = 3;
    $empModule = CompanyModuleAccess::where('company_code', $companyCode)
    ->where('module_id', $moduleId)
    ->where('status', 'active')
    ->first();  
    
    if(!$empModule)
    {
      return response()->json(['success'=>false,'message'=>'you can not access Employee module'],403);
    }
    $company= User::where('company_code',$companyCode)->first();
    $username = $company->username;
    $password = $company->dbPass;
    $dbName = $company->dbName;
    $maxEmp = $company->total;
    $create = $token['tokenable']['create'];
    $role = $token['tokenable']['role'];

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
    $email = $request->email;
    if($dynamicDB->getSchemaBuilder()->hasTable('employees'))
    {
        $uniqueEmail = $dynamicDB->table('employees')->where('email',$email)->first();
        if($uniqueEmail)
        {
            return response()->json(['success'=>false,'message'=>'email exists'],409);
        }
    }
    $validatedData = $request->validate([
        'name' => 'required',
        'email' => 'required|email',
        'designation' => 'required|string',
        'address' => 'required',
        'username' => 'required|string',
        'password' => 'required',
    ]);
    if (!$dynamicDB->getSchemaBuilder()->hasTable('employees')) {
        $dynamicDB->getSchemaBuilder()->create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique()->index();
            $table->string('username');
            $table->string('password');
            $table->string('designation');
            $table->string('address')->nullable();
            $table->timestamps();
        });
    }

    $empCount = $dynamicDB->table('employees')->count() ?? 0;

    if ($create && $create == 1 && $role == 'admin'|| $role && $role == 'Super Admin') 
    { 

    if ($empCount < $maxEmp) {
        $username = $request->username;
        $passcode = Hash::make($request->password);

        $employee = $dynamicDB->table('employees')->insert([
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'username' => $username,
            'password' => $passcode,
            'designation' => $validatedData['designation'],
            'address' => $validatedData['address'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $lastInsertedRecord = $dynamicDB->table('employees')->orderBy('id','desc')->first();

        return response()->json(['success'=>true,'message' => 'Employee added successfully','empData'=> $lastInsertedRecord, 'data' => $employee], 201);
    } else {
        return response()->json(['success'=>false,'message' => 'Maximum employee limit reached. Cannot add more.'],422);
    }
  }
   return response()->json(['success'=>false,'message' => 'Access Denied!'],422);


    }
    catch(\Exception $e)
    {
        return response()->json(['success' => false, 'message' => 'An error occurred. Please try again.',$e->getMessage()], 500);
    }
    
}



public function allEmployee(Request $request)
{
    try{
        $token = $request->user()->CurrentAccessToken();
$companyCode = $token['tokenable']['company_code'];
$moduleId = 3;
$empModule = CompanyModuleAccess::where('company_code', $companyCode)
->where('module_id', $moduleId)
->where('status', 'active')
->first(); 
 if(!$empModule)
 {
    return response()->json(['success'=>false,'message'=>'you can not access Employee module'],403);
 }
 $company = User::where('company_code',$companyCode)->first();
$username = $company->username;
$password = $company->dbPass;
$dbName = $company->dbName;
$role = $token['tokenable']['role'];
$read = $token['tokenable']['read'];
if($role && $role == 'admin' && $read && $read == 1 || $role && $role == 'Super Admin')
{
    Config::set('database.connections.dynamic',[
        'driver' => 'mysql',
        'host' => 'localhost',
        'database' => $dbName,
        'username' => $username, 
        'password' => $password,
        'collection' => 'utf8mb4_unicode_ci',
        'prefix' => '',
        'engine' => null,
     ]);
     $dynamicDB = DB::connection('dynamic');
     if(!Schema::connection('dynamic')->hasTable('employees'))
     {
       return response()->json(['success'=>false,'message'=>'Employee table not found'],404);
     }
      $allEmp = $dynamicDB->table('employees')->select('id','name','email','designation','address','created_at')->get();
   if(!$allEmp)
   {
       return response()->json(['success'=>false,'message'=>'data not found'],404);
   }
}

return response()->json(['success'=>false,'message'=>'Access Denied!'],403);

    }
    catch(\Exception $e)
    {
        return response()->json(['success' => false, 'message' => 'An error occurred. Please try again.','error'=>$e->getMessage()], 500);
    }
}

public function editEmployee(Request $request, $employeeId)
{
    $token = $request->user()->currentAccessToken();
    if (!$token) {
        return response()->json(['success' => false, 'message' => 'Token not found!'],404);
    }

    $companyCode = $token['tokenable']['company_code']; 
    $moduleId = 3;
    $empModule = CompanyModuleAccess::where('company_code', $companyCode)
    ->where('module_id', $moduleId)
    ->first();  
    
    if(!$empModule)
    {
      return response()->json(['success'=>false,'message'=>'you can not access Employee module'],403);
    }



    $validatedData = $request->validate([
        'name' => 'required',
        'email' => 'required',
        'designation' => 'required',
        'address' => 'required',
                
        ]);
      $dbName = $token['tokenable']['dbName'];
    $user = User::where('company_code',$companyCode)->first();
    $dbUsername = $user->username;
    $dbPass = $user->dbPass;
    $edit = $token['tokenable']['edit'];
    $role = $token['tokenable']['role'];
    Config::set('database.connections.dynamic', [
        'driver' => 'mysql',
        'host' => 'localhost',
        'database' => $dbName,
        'username' => $dbUsername,
        'password' => $dbPass,
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
        'engine' => null,
    ]);

    $dynamicDB = DB::connection('dynamic');
    if ($edit && $edit == 1 && $role && $role == 'admin'|| $role && $role == 'Super Admin') 
    {
        
        if (!Schema::connection('dynamic')->hasTable('employees')) {
            return response()->json(['success'=>false,'message' => 'Table not found'], 404);
        }

        $employee = $dynamicDB->table('employees')->find($employeeId);

        if (!$employee) {
            return response()->json(['success'=>false,'message' => 'Employee not found'], 404);
        }

        $date = Carbon::now()->timezone('Asia/Kolkata')->format('Y-m-d H:i:s');

        DB::connection('dynamic')->table('employees')->where('id', $employeeId)->update([
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'designation' => $request->input('designation'),
            'address' => $request->input('address'),
            'updated_at' => $date,
        ]);

        return response()->json(['success'=>true,'message' => 'Employee updated successfully'], 200);
    }

    return response()->json(['success'=>false,'message' => 'You have no permission to edit'], 403);
}



public function destroyEmployee(Request $request, $employeeId)
{ 
    $token = $request->user()->currentAccessToken();
    if (!$token) {
        return response()->json(['success' => false, 'message' => 'Token not found!'],404);
    }
    $companyCode = $token['tokenable']['company_code'];
    $moduleId = 3;
    $empModule = CompanyModuleAccess::where('company_code', $companyCode)
    ->where('module_id', $moduleId)
    ->where('status', 1)
    ->first();  
    
    if(!$empModule)
    {
      return response()->json(['success'=>false,'message'=>'you can not access Employee module'],403);
    }
    $dbName = $token['tokenable']['dbName'];
    $user = User::where('company_code',$companyCode)->first();
    $dbUsername = $user->username;
    $dbPass = $user->dbPass;
    $delete = $token['tokenable']['delete'];
    $role = $token['tokenable']['role'];
    // print_r($role);die;

    Config::set('database.connections.dynamic', [
        'driver' => 'mysql',
        'host' => 'localhost',
        'database' => $dbName,
        'username' => $dbUsername,
        'password' => $dbPass,
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
        'engine' => null,
    ]);

    $dynamicDB = DB::connection('dynamic');
    if(!Schema::connection('dynamic')->hasTable('employees'))
    {
        return response()->json(['success'=>false,'message' => 'table not found'],404);
    }
    if($delete && $delete == 1 && $role && $role == 'admin'|| $role && $role == 'Super Admin')
    {
      $employee = DB::connection('dynamic')->table('employees')->find($employeeId);
      if(!$employee)
      {
        return response()->json(['success'=>false,'message' => 'no record found'],404);
      }
      DB::connection('dynamic')->table('employees')->where('id',$employeeId)->delete(); 
      return response()->json(['success'=>true,'message' => 'record deleted successfully'],200);
    }
   
    return response()->json(['success'=>false,'message' => 'Access Denied!'],403);


}



public function multiDelEmp(Request $request)
{
    $token = $request->user()->currentAccessToken();
    $companyCode = $token['tokenable']['company_code'];
    $moduleId = 3;
    $empModule = CompanyModuleAccess::where('company_code',$companyCode)->where('module_id',$moduleId)->where('status','active')->first();
    if(!$empModule)
    {
        return response()->json(['success'=>false,'message'=>'you can not access employee module'],403);
    }
    $company = User::where('company_code',$companyCode)->first();
    $username = $company->username;
    $password = $company->dbPass;
    $dbName = $company->dbName;  
    $role = $token['tokenable']['role'];
    $delete = $token['tokenable']['delete'];
    
    $validatedData = $request->validate([
        'employee_ids' => 'required',
    ]);
    if($role && $role == 'admin' && $delete && $delete == 1 || $role && $role == 'Super Admin')
    {
        Config::set('database.connections.dynamic',[
            'driver' => 'mysql',
            'host' => 'localhost',
            'database' => $dbName,
            'username' => $username,
            'password' => $password,
            'collection'=> 'utf8mb4_unicode_ci',
            'prefix' => '',
            'engine' => null,
            ]);
            $dynamicDB = DB::connection('dynamic');    
            if(!$request->has('employee_ids'))
            {
                return response()->json(['success'=>false,'message'=>'no employee ids provided'],400);
            }
            $employeeIds = $request->employee_ids;
            $deletedRow = $dynamicDB->table('employees')->whereIn('id', explode(',', $employeeIds))->delete();
            if($deletedRow > 0) 
            {
                return response()->json(['success' => true, 'message' => 'Employees deleted successfully'], 200);
         
            }
            else
            {
                return response()->json(['success' => false, 'message' => 'No employees found for delete'], 404);
        
            }
    }
 
    return response()->json(['success' => false, 'message' => 'Access Denied!'], 403);

  
}


public function newUser(Request $request)
{
    $token = $request->user()->currentAccessToken();
    if (!$token)
    {
        return response()->json(['success'=>false,'message'=>'token not found!'],404);
    }
    
    $validatedData = $request->validate([
        'emp_id' => 'required',
        'name' => 'required',
        'email' => 'required',
        'username' => 'required',
        'password' => 'required',
        'read' => 'nullable|boolean',
        'create' => 'nullable|boolean',
        'edit' => 'nullable|boolean',
        'delete' => 'nullable|boolean',
        'role' => 'required',
         'mobile_number' => 'required',

        
    ]);
        $username = $token['tokenable']['username'];
        $password = $token['tokenable']['dbPass'];
        $dbName = $token['tokenable']['dbName'];
        $companyCode = $token['tokenable']['company_code'];
        $date = now()->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s');

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
        $empId = $request->emp_id;
        $empIdCheck = $dynamicDB->table('employees')->where('id',$empId)->first();
        if(!$empIdCheck)
        {
            return response()->json(['success' => false, 'message' => 'employee id not found'],404);

        }
        if (!$dynamicDB->getSchemaBuilder()->hasTable('permission')) {
            $dynamicDB->getSchemaBuilder()->create('permission', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->unsignedBigInteger('emp_id');
                $table->string('username');
                $table->string('password');
                $table->string('email');
                $table->string('mobile_number')->nullable();
                $table->boolean('read');
                $table->boolean('create');
                $table->boolean('edit');
                $table->boolean('delete');
                $table->enum('role', ['admin', 'subadmin']);
                $table->timestamps();
                $table->foreign('emp_id')->references('id')->on('employees');
            });
        }

        $dynamicDB->table('permission')->insert([
            'name' => $request->name,
            'emp_id' => $request->emp_id,
            'username' => $request->username,
            'password' => bcrypt($request->password),
            'email'   => $request->email,
            'read' => $request->input('read', false),
            'create' => $request->input('create', false),
            'edit' => $request->input('edit', false),
            'delete' => $request->input('delete', false),
            'role' => $request->role,
            'created_at' => $date,
            'updated_at' => $date,
        ]);

        $company_access = new CompanyUserAccess;
        $company_access->name = $request->name;
        $company_access->emp_id = $request->emp_id;
        $company_access->email = $request->email;
        $company_access->username = $request->username;
        $company_access->password = Hash::make($request->password);
        $company_access->dbName = $dbName;
        $company_access->company_code = $companyCode;
        $company_access->mobile_number = $request->mobile_number;
        $company_access->read = $request->read;
        $company_access->create = $request->create;
        $company_access->edit = $request->edit;
        $company_access->delete = $request->delete;
        $company_access->save();

        return response()->json(['success' => true, 'message' => 'user role created successfully'],201);
    
}







public function editUser(Request $request,$id)
{
   session_start();
   if(isset($_SESSION['username']) && isset($_SESSION['password']) && isset($_SESSION['dbName']))
    {
        $username = $_SESSION['username'];
        $password = $_SESSION['password'];
        $dbName   = $_SESSION['dbName'];
        $date = now()->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s');

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

        $permission = $dynamicDB->table('permission')->find($id);
        if(!$permission)
        {
            return response()->json(['message'=> 'record not found']);
        }
        else
        {
            $dynamicDB->table('permission')->where('id', $id)->update([

                'name' => $request->name,
                'emp_id' => $request->emp_id,
                'username' => $request->username,
                'password' => bcrypt($request->password),
                'read' => $request->input('read'),
                'create' => $request->input('create'),
                'edit' => $request->input('edit'),
                'delete' => $request->input('delete'),
                'role' => $request->role,
                'updated_at' => $date,
              
    
            ]);
    
                
    
                return response()->json(['success' => true, 'message' => 'User updated successfully']);
        }
        
   

        



    }

    return response()->json(['success' => false,'message' => 'session out! pls login']);
}




public function delUser(Request $request,$id)
{
    session_start();
    if(isset($_SESSION['username']) && isset($_SESSION['password']) && isset($_SESSION['dbName']))
    {
        $username = $_SESSION['username'];
        $password = $_SESSION['password'];
        $dbName   = $_SESSION['dbName'];
        $date = now()->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s');

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
       $delUser =  $dynamicDB->table('permission')->find($id);
       if(!$delUser)
       {
        return response()->json(['success' => false, 'message'=> 'record not found']);

       }
       else
       {
        $dynamicDB->table('permission')->where('id',$id)->delete();
        return response()->json(['success' => true, 'message' => 'User deleted successfully']);

       }
       

    }

    return response()->json(['success' =>false,'message'=>'session out! login again']);
    
}

public function logUser(Request $request)
{
    try {
        $validatedData = $request->validate([
            'company_code' => 'required',
            'username' => 'required',
            'password' => 'required',
        ]);

        $user = User::where('company_code', $request->company_code)->first();
        if ($user) {
            $dbName = $user->dbName;
            $username = $user->username;
            $password = $user->dbPass;
      


            $access = CompanyUserAccess::where('username', $request->username)->
            where('company_code', $request->company_code)->first();
            if ($access  && Hash::check($request->password, $access->password)) {
                $token = $access->createToken('company-user-accessToken')->plainTextToken;
                return response()->json([
                    'success' => true,
                    'message' => 'Login successful',
                    'token' => $token,
                ]);
            } else {
                return response()->json(['success' => false, 'message' => 'Access denied']);
            }
        }

        return response()->json(['success' => false, 'message' => 'Invalid credentials']);
    } catch (Exception $e) {
        return response()->json(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
    }
}


public function accessFogetPass(Request $request)
{
    $validatedData = $request->validate([
      'mobile_number' => 'required',
    ]);
    $phone = $validatedData['mobile_number'];
    $accessUser = CompanyUserAccess::where('mobile_number',$phone)->first();
    if($accessUser)
    {


        $otpResult = $this->generateAndSendOtpUserAccess($accessUser->emp_id, $accessUser->phone,$accessUser->email);
        if($otpResult['success'])
        {
            return response()->json(['success' => true,'phone'=>$validatedData['mobile_number'], 'message' => 'OTP sent successfully'],200);
        }
        else 
        {
            return response()->json(['success' => false, 'message' => $otpResult['error']],500);
        }

    }
    else
    {
       return response()->json(['success'=>'error','message'=>'user not found!'],404); 

    }


}


private function generateAndSendOtpUserAccess($empId, $phone,$email)
{
    try {
        $otp = rand(100000, 999999);

        $expireAt = now()->addMinutes(1);
        UserAccessOtp::create([
            'emp_id' => $userId,
            'otp' => $otp,
            'expire_at' => $expireAt,
        ]);

        $this->sendOtpViaTwilioUserAccess($phone, $otp);
        $this->sendOtpViaEmailUserAccess($email,$otp);

        return ['success' => true];
    } catch (\Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

private function sendOtpViaTwilioUserAccess($phone, $otp)
{
    try {
        $accountSid = getenv("TWILIO_SID");
        $authToken = getenv("TWILIO_TOKEN");
        $twilioNumber = getenv("TWILIO_FROM");

        $client = new Client($accountSid, $authToken);
        $message = $client->messages->create($phone, [
            'from' => $twilioNumber,
            'body' => "Your OTP: $otp",
        ]);

        if ($message->sid) {
            return ['success' => true];
        } else {
            return ['success' => false, 'error' => 'Failed to send OTP'];
        }
    } catch (\Twilio\Exceptions\RestException $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    } catch (\Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}



private function sendOtpViaEmailUserAccess($email,$otp)
{
    try{
        Mail::to($email)->send(new OtpMail($otp));
        return ['success'=>true];
    }
    catch(\Exception $e)
    {
       return ['success'=>false,'error'=>$e->getMessage()];
    }
}



public function verifyAccessFogetPass(Request $request)
{
    $validatedData = $request->validate([
        'otp'  => 'required',
    ]);
    $otp = $validatedData['otp'];
    $phone = $request->mobile_number;
    $accessUser = CompanyUserAccess::where('mobile_number',$phone)->first();
    if($accessUser)
    {
      $empId = $accessUser->emp_id;
      $userOtp = UserAccessOtp::where('emp_id',$empId)->where('otp',$otp)->where('expire_at', '>', now())->first();
      if($userOtp)
      {
          $userOtp->delete();
          $accessUser->time_expire = now()->addMinutes(1440);
          $accessUser->save();
          $token = $accessUser->createToken('access-token')->plainTextToken;
          return response()->json(['success' => true,'token'=>$token,'message' => 'OTP verification successful'],200);
      }
      else
           {
            return response()->json(['success' => false,'success'=>false,'message' => 'Invalid OTP or mobile number'],422);
           }
    }
    return response()->json(['success' => false,'success'=>false,'message' => 'user not found'],404);
  

}



public function setNewPasswordUserAccess(Request $request)
{
    $validatedData = $request->validate([
        'new_password' => 'required|string|min:6',
        'confirm_new_password' => 'required|string|same:new_password',
    ]);

    $token = $request->user()->currentAccessToken();
    if ($token) {
        $phone = $token['tokenable']['mobile_number'];
        $empId = $token['tokenable']['emp_id'];

        $user = $this->validateCompanyUserTimeExpire($phone, $empId);

        if ($user) {
            $newPassword = $validatedData['new_password'];
            $user->password = Hash::make($newPassword);
            $user->save();
            $token->delete();

            return response()->json(['success' => true, 'message' => 'Password updated successfully'], 200);
        } else {
            return response()->json(['success' => false, 'message' => 'Invalid user or token or token has expired'], 422);
        }
    }

    return response()->json(['success' => false, 'message' => 'Invalid Token'], 422);
}



protected function validateCompanyUserTimeExpire($phone, $empId)
{
    $user = CompanyUserAccess::where('mobile_number', $phone)->where('emp_id', $empId)->first();

    if ($user && Carbon::now()->lt($user->time_expire)) {
        return $user;
    } elseif ($user) {
        $token = $user->currentAccessToken();
        if ($token) {
            $token->delete();
        }
        return null;
    }

    return null;
}





public function logoutAccess(Request $request)
{
    $token = $request->user()->currentAccessToken();
    if ($token) {
        $token->delete();
    }
    Auth::guard('web')->logout();
    return response()->json(['success' => true, 'message' => 'Successfully logged out'], 200);
}

public function empApproveByAdmin(Request $request)
{
    try{
        $token = $request->user()->currentAccessToken();
        if(!$token)
        {
            return response()->json(['success'=>false,'message'=>'invalid token'],401);
        }
        $tokenRole = $token['tokenable']['role'];
        $status = $token['tokenable']['status'];
        $code = $token['tokenable']['company_code'];
        $company = User::where('company_code', $code)->first();
        $moduleId = 3;
        $empModule = CompanyModuleAccess::where('company_code',$code)->where('module_id',$moduleId)->first();
        $accessEmp = $token['tokenable']['create'];
        $username = $company->username;
        $password = $company->dbPass;
        $dbName   = $company->dbName;
        
        if (!$company) {
            return response()->json(['success' => false,'message' => 'Company not found.'], 404);
        }
        if($tokenRole == 'admin' && $accessEmp != 1)
        {
           return response()->json(['success' => false,'message' => 'you can not access employee module.'], 403);
        }
        if(!$accessEmp && $tokenRole == 'admin')
        {
          return response()->json(['success' => false,'message' => 'you can not access employee module.'], 403);
        }
        if (($accessEmp == 1 && $tokenRole == 'admin') || $tokenRole == 'Super Admin') 
        {   
            $validatedData = $request->validate([
                'emp_id' => 'required',
                'branch_id' => 'required',
                'dept_id'   => 'required',
                'status' => 'required',
               ]); 
               $emp_id = $request->emp_id; 
               $branchId = $request->branch_id;
               $deptId = $request->dept_id;
               $status = $request->status;
               $date = Carbon::now()->timezone('Asia/kolkata')->format('Y-m-d H:i:s');

       $employee = CompanyUserAccess::find($emp_id);
       if($employee)
       {
        $dept = Dept::where('id',$deptId)->where('branch_id',$branchId)->where('company_code',$code)->first();
        if($dept)
        {
         $employee->status = $status;
         $employee->branch_id = $branchId;
         $employee->dept_id = $deptId;
         $employee->updated_at = $date;
         $employee->save();
       return response()->json(['success' => true, 'message' => 'Employee status updated successfully.'],200);
        }
        return response()->json(['success' => false, 'message' => 'deptid or branchid not found.'],404);
       
       }
       return response()->json(['success'=>false,'message'=>'employee not found'],404);
     }
     return response()->json(['success' => false,'message' => 'you have no permission'],403);
    }
    catch(\Exception $e)
    {
        return response()->json(['message' => 'An error occurred. Please try again.',$e->getMessage()], 500);
    }

}



public function allInactiveEmp(Request $request)
{
    try{
    $token = $request->user()->currentAccessToken();
    if(!$token)
    {
        return response()->json(['success'=>false,'message'=>'invalid token'],401);
    }
    $tokenRole = $token['tokenable']['role'];
    $status = $token['tokenable']['status'];
    $code = $token['tokenable']['company_code'];
    $company = User::where('company_code', $code)->first();
    $moduleId = 3;
    $empModule = CompanyModuleAccess::where('company_code',$code)->where('module_id',$moduleId)->first();
    $accessEmp = $token['tokenable']['create'];
    
     if (!$company) {
         return response()->json(['success' => false,'message' => 'Company not found.'], 404);
     }
     if($tokenRole == 'admin' && $accessEmp != 1)
     {
       return response()->json(['success' => false,'message' => 'you can not access employee module.'], 403);
     }
     if(!$accessEmp && $tokenRole == 'admin')
     {
       return response()->json(['success' => false,'message' => 'you can not access employee module.'], 403);
     }
     if (($accessEmp == 1 && $tokenRole == 'admin') || $tokenRole == 'Super Admin') 
     { 
      $inactiveEmp = CompanyUserAccess::where('company_code',$code)->where('status','inactive')->get();
      if(!$inactiveEmp)
      {
        return response()->json(['success' => false,'message' => 'no data found'],404);
      }
      return response()->json(['success' => false,'message' => 'data found',$inactiveEmp],200);

     }
     return response()->json(['success' => false,'message' => 'you have no permission'],403);
     
    }
   catch(\Exception $e)
   {   
     return response()->json(['message' => 'An error occurred. Please try again.',$e->getMessage()], 500);
   }



}






public function assignRole(Request $request)
{
     $token = $request->user()->currentAccessToken();
     if(!$token)
     {
         return response()->json(['success'=>false,'message'=>'invalid token'],401);
     }
     $code = $token['tokenable']['company_code'];
     $empData = CompanyUserAccess::where('company_code',$code)->get(); 
     $modulesData = Module::all();
     $roleName = RoleMaster::where('company_code',$code)->where('name',$request->name)->first();
     if($roleName)
     {
        return response()->json(['success'=>false,'message'=>'Role Name already present']);
     }
     $date = Carbon::now()->timezone('Asia/Kolkata')->format('Y-m-d H:i:s');
     $validatedData = $request->validate([
        'name' => 'required', 
        'modules'     =>  'required',
        'permission'   =>  'required|string',
          
    ]);
    $permissionArray = explode(',',$request->permission);
    $permission = json_encode($permissionArray);
    $role = RoleMaster::create([
        'name' => $request->name,
        'company_code' => $code,
        'permission' => $permission,
        'modules'  => $request->modules,
        'created_at' => $date,
        'updated_at' => $date,
    ]);

    return response()->json(['success'=>true,'message' => 'Role assigned successfully','modules'=>$modulesData], 200);
    
}




public function allRoleData(Request $request)
{
  
     $token = $request->user()->currentAccessToken();
     $code = $token['tokenable']['company_code'];
     $allData = RoleMaster::where('company_code',$code)->get();
     if($allData->isEmpty())
     {
        return response()->json(['success'=>false,'message'=> 'no data found'],404);
     }
     return response()->json(['success'=>true,'data'=>$allData]);
   
}




public function addPermission(Request $request)
{
    $token = $request->user()->currentAccessToken();
    if(!$token)
    {
        return response()->json(['success'=>false,'message'=>'invalid token'],401);
    }
    $tokenRole = $token['tokenable']['role'];
    $code = $token->tokenable->company_code;
    $validatedData = $request->validate([
        'emp_id' => 'required',
        'modules_name' => 'required',
    ]);
    $empId = $request->emp_id;
    $roleData = Role::where('company_code',$code)->where('emp_id',$empId)->first();
    if(!$roleData)
    {
        return response()->json(['success'=>false,'message'=>'role not found'],404);
    }
    $roleId = $roleData->id;
    $roleName = $roleData->name;
    $date = Carbon::now()->timezone('Asia/kolkata')->format('Y-m-d H:i:s');
    $permission = Permission::create([
     'name' => $roleName,
     'guard_name' => 'api',
     'role_id'   => $roleId,
     'modules_name' => $request->modules_name,
     'created_at' => $date,
     'updated_at' => $date,
    ]);
    return response()->json(['success'=>true,'message' => 'Permission added successfully',$permission], 200);
    
     return response()->json(['success' => false,'message' => 'you have no permission'],403);

}




public function allPermission(Request $request)
{
   try{
    $token = $request->user()->currentAccessToken();
    if(!$token)
    {
        return response()->json(['success'=>false,'message'=>'invalid token'],401);
    }
    $code = $token->tokenable->company_code; 
    $id = $token->tokenable->id;
    $roleData = Role::where('company_code',$code)->where('emp_id',$id)->first();
    if (!$roleData) {
        return response()->json(['success' => false, 'message' => 'Role not found'], 404);
     }
    $roleId = $roleData->id;
    $permission = Permission::where('role_id',$roleId)->first();
    if (!$permission) {
        return response()->json(['success' => false, 'message' => 'Permission not found for this role'], 404);
     }
    $allModules = $permission->modules_name;
    $moduleIds = explode(',',$allModules);
    $moduleNames = Module::whereIn('id',$moduleIds)->pluck('name')->toArray();   
    return response()->json(['success' => true, 'modules' => $moduleNames], 200);
   }
   catch(\Exception $e)
   {
    return response()->json(['success'=>false,'message' => 'An error occurred. Please try again.',$e->getMessage()],500);
   }


}

public function isEmpPermission(Request $request)
{
     $token = $request->user()->currentAccessToken();
     if(!$token)
     {
         return response()->json(['success'=>false,'message'=>'invalid token'],401);
     }
     $code = $token->tokenable->company_code;
     $empId = $token->tokenable->id;
     $roleData = Role::where('company_code',$code)->where('emp_id',$empId)->first();
     if(!$roleData)
     {
        return response()->json(['success'=>false,'message'=>'invalid token'],401);
     }
     $roleId = $roleData->id;
     $permission = Permission::where('role_id',$roleId)->first();
     if(!$permission)
     { 
        return response()->json(['success'=>false,'message'=>'permission not found'],404);
     }
    $moduleId = 1;
    $modulesArray = explode(',',$permission->modules_name); 
     if(in_array($moduleId,$modulesArray))
     {
        return response()->json(['success' => true, 'message' => 'Permission granted'], 200);
     }
     else {
        return response()->json(['success' => false, 'message' => 'Permission denied'], 403);
     }
}


public function edituserRoledel(Request $request,$id)
{
    $token = $request->user()->currentAccessToken();
    if(!$token)
    {
        return response()->json(['success'=>false,'message'=>'invalid token'],401);
    }
    $code = $token->tokenable->company_code;    
        $role = RoleUserAssign::where('company_code',$code)->where('id',$id)->first();
        if(!$role)
        {
           return response()->json(['success' => false,'message' => 'role not found.'], 404);
        }
        $role->delete();
        return response()->json(['success' => true,'message' => 'role deleted successfully'], 200);

}





public function editRole(Request $request,$id)
{
    $token = $request->user()->currentAccessToken();
    if(!$token)
    {
        return response()->json(['success'=>false,'message'=>'invalid token'],401);
    }
  
    $code = $token->tokenable->company_code;
    $validatedData = $request->validate([
        'name' => 'required',
        'permission' => 'required|string', 
        'modules'   => 'required'  
     ]);
     $permissionArray = explode(',',$request->permission);
     $role = RoleMaster::where('company_code',$code)->where('id',$id)->first();
     if(!$role)
     {
        return response()->json(['success' => false,'message' => 'role not found.'], 404);
     }
     $role->name = $request->name;
     $role->permission = json_encode($permissionArray);
     $role->modules = $request->modules;
     $role->save();
     return response()->json(['success' => true,'message' => 'role updated successfully'], 200);
   return response()->json(['success' => false,'message' => 'you have no permission.'], 403);



}





public function delRole(Request $request,$id)
{
    $token = $request->user()->currentAccessToken();
    if(!$token)
    {
        return response()->json(['success'=>false,'message'=>'invalid token'],401);
    }
    $code = $token->tokenable->company_code;
        $role = RoleMaster::where('company_code',$code)->where('id',$id)->first();
        if(!$role)
        {
           return response()->json(['success' => false,'message' => 'role not found.'], 404);
        }
        $role->delete();
        return response()->json(['success' => true,'message' => 'role deleted successfully'], 200);
    return response()->json(['success' => false,'message' => 'you have no permission.'], 403);

}





public function edituserRole(Request $request,$id)
{
    $token = $request->user()->currentAccessToken();
    if(!$token)
    {
        return response()->json(['success'=>false,'message'=>'invalid token'],401);
    }
  
    $code = $token->tokenable->company_code;
    $validatedData = $request->validate([
        'role_id' => 'required',
        'emp_id' => 'required', 
        'branch_id' => 'required',
        'dept_id'  => 'required'
     ]);
     $role = RoleUserAssign::where('company_code',$code)->where('id',$id)->first();
     if(!$role)
     {
        return response()->json(['success' => false,'message' => 'role not found.'], 404);
     }
     $role->emp_id = $request->emp_id;
     $role->role_id = $request->role_id;
     $role->branch_id = $request->branch_id;
     $role->dept_id = $request->dept_id;
     $role->save();
     return response()->json(['success' => true,'message' => 'role updated successfully'], 200);
   
   return response()->json(['success' => false,'message' => 'you have no permission.'], 403);
}



public function editPermission(Request $request,$id)
{
    $token = $request->user()->currentAccessToken();
    if(!$token)
    {
        return response()->json(['success'=>false,'message'=>'invalid token'],401);
    }
    $code = $token->tokenable->company_code;
    $date = Carbon::now()->timezone('Asia/Kolkata')->format('Y-m-d H:i:s');
    $validatedData = $request->validate([
        'modules_name' => 'required|string' ,
        'modules_name.*' => 'integer|distinct',
     ]);
    $permission = Permission::where('role_id',$id)->first();
    if (!$permission) {
        return response()->json(['success' => false, 'message' => 'Permission not found'], 404);
    }
    $permission->modules_name = $request->modules_name;
    $permission->updated_at = $date;
    $permission->save();

    return response()->json(['success' => true, 'message' => 'Permission updated successfully'], 200);

    

}


public function userRole(Request $request)
{
    try{
        $token = $request->user()->currentAccessToken();
        if(!$token)
        {
            return response()->json(['success'=>false,'message'=>'invalid token'],401);
        }
        
        $code = $token['tokenable']['company_code'];
        $date = Carbon::now()->timezone('Asia/Kolkata')->format('Y-m-d H:i:s');
        $validatedData = $request->validate([
           'emp_id' => 'required',
           'role_id'   =>  'required',  
           'branch_id' => 'required',
           'dept_id'   => 'required'   
       ]);
       $role = RoleUserAssign::create([
           'emp_id' => $request->emp_id,
           'role_id'=> $request->role_id,
           'branch_id'    => $request->branch_id,
           'dept_id'      => $request->dept_id,
           'company_code' => $code,
           'created_at' => $date,
           'updated_at' => $date,
       ]);
   
       return response()->json(['success'=>true,'message' => 'Role user assigned successfully'], 200);
       }
       catch(\Exception $e)
       {   
         return response()->json(['success'=>false,'message' => 'An error occurred. Please try again.',$e->getMessage()], 500);
       }
}




public function deletePermission(Request $request,$id)
{
    $token = $request->user()->currentAccessToken();
    if(!$token)
    {
        return response()->json(['success'=>false,'message'=>'invalid token'],401);
    }
    $code = $token->tokenable->company_code;    
     $permission = Permission::where('role_id',$id)->first();
     if (!$permission) {
        return response()->json(['success' => false, 'message' => 'Permission not found'], 404);
     }
     $permission->delete();
     return response()->json(['success' => true,'message' => 'role deleted successfully'], 200);
   return response()->json(['success' => false,'message' => 'you have no permission.'], 403);

}

public function modulePermission(Request $request)
{
    try{
        $token = $request->user()->currentAccessToken();
        if(!$token)
        {
            return response()->json(['success'=>false,'message'=>'invalid token'],401);
        }
        $code = $token['tokenable']['company_code'];
        $id = $token->tokenable->id;
        $modulePermission = RoleMaster::where('company_code',$code)->where('emp_id',$id)->first();
        $moduleId = explode(',',$modulePermission->modules);
        $modulesName = Module::whereIn('id',$moduleId)->pluck('name')->toArray();
        return response()->json(['success' => true, 'modules' => $modulesName], 200);

       }
       catch(\Exception $e)
       {   
         return response()->json(['success'=>false,'message' => 'An error occurred. Please try again.',$e->getMessage()], 500);
       }

}



public function controlPannelCreate(Request $request)
{
    try {
        $token = $request->user()->currentAccessToken();
        if (!$token) {
            return response()->json(['success' => false, 'message' => 'Invalid token'], 401);
        }
        $moduleId = 1;
        $code = $token->tokenable->company_code;
        $empId = $token->tokenable->id;
        $roleData = RoleUserAssign::where('emp_id',$empId)->where('company_code',$code)->first();
        if (!$roleData) {
            return response()->json(['success' => false, 'message' => 'Data not found'], 404);
        }
        $roleId = $roleData->role_id;
        $roleData = RoleMaster::where('id',$roleId)->first();
        // $modules = $roleData->modules;echo $modules;die;
        $module = $roleData->modules;
        if(!$module)
        {
            return response()->json(['success'=>false,'message'=>'you can not access']);
        }
        $modules = $this->modulesCheck($module,$moduleId);
        if($modules)
        {
        $permit = "createControlPanel";
        $permission = $roleData->permission;
        $permission = json_decode($permission,true);
        $isControlPannel = in_array($permit, $permission);
 
        if (!$isControlPannel) {
            return response()->json(['success' => false, 'message' => 'you have no excess'], 403);
        }
        return response()->json(['success' => true, 'message'=>'you can access control pannel','roleData' => $permission], 200);
      }   
         return response()->json(['success'=>false,'message'=>'you have no permission'],403);
    } catch (\Exception $e) {
        return response()->json(['success' => false, 'message' => 'An error occurred. Please try again.', 'error' => $e->getMessage()], 500);
    }
    
    
}


 
private function modulesCheck($module,$moduleId)
{
  $modules = explode(',',$module);
  if(in_array($moduleId,$modules))
  {
    return $modules;
  }
  return null;
}



public function permissionMaster(Request $request)

 {
    $modules = [
        [
            'name' => 'Control Panel',
            'module_id' => 1,
            'permissions' => ['readControlPanel', 'createControlPanel','updateControlPanel','deleteControlPanel'],
        ],
        [
            'name' => 'Masters',
            'module_id' => 2,
            'permissions' => ['readMasters', 'createMasters','updateMasters','deleteMasters'],
        ],
        [
            'name' => 'Employee',
            'module_id' => 3,
            'permissions' => ['readEmployee', 'createEmployee','updateEmployee','deleteEmployee'],
        ],
        [
            'name' => 'Leave',
            'module_id' => 4,
            'permissions' => ['readLeave','createLeave', 'updateLeave','deleteLeave'],
        ],
        [
            'name' => 'Loan',
            'module_id' => 5,
            'permissions' => ['readLoan','createLoan', 'updateLoan','deleteLoan'],
        ],
        [
            'name' => 'Salary Details',
            'module_id' => 6,
            'permissions' => ['readSalaryDetails','createSalaryDetails', 'updateSalaryDetails','deleteSalaryDetails'],
        ],
        [
            'name' => 'Reports',
            'module_id' => 7,
            'permissions' => ['readReports','createReports', 'updateReports','deleteReports'],
        ],
        [
            'name' => 'HR Management',
            'module_id' => 8,
            'permissions' => ['readHRManagement','createHRManagement', 'updateHRManagement','deleteHRManagement'],
        ],
        [
            'name' => 'Timesheet',
            'module_id' => 9,
            'permissions' => ['readTimesheet','createTimesheet', 'updateModuleName','deleteTimesheet'],
        ],
        [
            'name' => 'Task Management',
            'module_id' => 10,
            'permissions' => ['readTaskManagement','createTaskManagement', 'updateTaskManagement','deleteTaskManagement'],
        ],
    ];
    
    return response()->json($modules);
    
    
 } 



 public function companyCretateRole(Request $request)
 {
    try
    {
            $token = $request->user()->currentAccessToken();
    if (!$token) {
        return response()->json(['success' => false, 'message' => 'Invalid token'], 401);
    }
    $code = $token->takenable->company_code;
    $date = Carbon::now()->timezone('Asia/Kolkata')->format('Y-m-d H:i:s');
    $validatedData = $request->validate([
        'role_name'   =>  'required',     
    ]);
    $role = RoleUserAssign::create([
        'role_name' => $request->role_name,
        'company_code' => $code,
        'created_at' => $date,
        'updated_at' => $date,
    ]);

    return response()->json(['success'=>true,'message' => 'Role Name Saved Successfully'], 200);

 }
 catch (Exception $e) {
    return response()->json(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
}
    }




    public function projectMaster(Request $request)
    {
        try
        {
            $token = $request->user()->currentAccessToken();
            if(!$token)
        {
            return response()->json(['success'=>false,'message'=>'Token Not Found'],404);
        }
        $code = $token->tokenable->company_code;
        $validatedData = $request->validate([
            'branch'   =>  'required',     
            'department' => 'required',
            'proj_name'  =>  'required',
            'proj_title'  =>  'required',
            'proj_code'  => 'required',
            'methodology' => 'required',
            'version'   =>  'required',
            'description' => 'required',
            'start_date' => 'required',
            'target_date' => 'required',
            'due_date' => 'required',
            'duration' => 'required',
            'priority' => 'required',
            'risk' => 'required',
        ]);
        $id = $token->tokenable->id;
        $fileName = '';
        if($request->file)
        {
            $file = $request->file;
            $ext = $file->getClientOriginalExtension();
            $fileName = $id.'.'.time().'.'.$ext;
            $file->move(public_path('/project_file'),$fileName);

        }
        $projModule = new ProjectMaster;
        $projModule->branch = $request->branch;
        $projModule->department = $request->department;
        $projModule->proj_name = $request->proj_name;
        $projModule->proj_title = $request->proj_title;
        $projModule->proj_code = $request->proj_code;
        $projModule->methodology = $request->methodology;
        $projModule->version = $request->version;
        $projModule->description = $request->description;
        $projModule->start_date = $request->start_date;
        $projModule->target_date = $request->target_date;
        $projModule->due_date = $request->due_date;
        $projModule->duration = $request->duration;
        $projModule->priority = $request->priority;
        $projModule->risk = $request->risk;
        $projModule->company_code = $code;
        $projModule->resource_id = $request->resource_id;
        $projModule->resource_name = $request->resource_name;
        $projModule->location = $request->location;
        $projModule->serial_no = $request->serial_no;
        $projModule->memory_size = $request->memory_size;
        $projModule->model = $request->model;
        $projModule->comments = $request->comments;
        $projModule->type_of_resource = $request->type_of_resource;
        $projModule->quantity = $request->quantity;
        $projModule->storage_capacity = $request->storage_capacity;
        $projModule->assumption = $request->assumption;
        $projModule->resource_description = $request->resource_description;
        $projModule->mac_address = $request->mac_address;
        $projModule->subnet_mask = $request->subnet_mask;
        $projModule->dns = $request->dns;
        $projModule->ip_address = $request->ip_address;
        $projModule->gateway = $request->gateway;
        $projModule->soft_name = $request->soft_name;
        $projModule->soft_version = $request->soft_version;
        $projModule->year_of_licence = $request->year_of_licence;
        $projModule->soft_serial_no = $request->soft_serial_no;
        $projModule->soft_licence = $request->soft_licence;
        $projModule->title = $request->title;
        $projModule->soft_quantity = $request->soft_quantity;
        $projModule->soft_description = $request->soft_description;
        $projModule->role = $request->role;
        $projModule->no_of_roles = $request->no_of_roles;
        $projModule->human_resource_description = $request->human_resource_description;
        $projModule->cost_type = $request->cost_type;
        $projModule->cost_resource_name = $request->cost_resource_name;
        $projModule->cost_quantity = $request->cost_quantity;
        $projModule->cost = $request->cost;
        $projModule->total_cost = $request->total_cost;
        $projModule->cost_description = $request->cost_description;
        $projModule->client_id = $request->client_id;
        $projModule->client_role = $request->client_role;
        $projModule->client_name = $request->client_name;
        $projModule->client_website = $request->client_website;
        $projModule->client_domain = $request->client_domain;
        $projModule->client_insurance = $request->client_insurance;
        $projModule->document_type = $request->document_type;
        $projModule->file = $fileName;
        $projModule->save();
        return response()->json(['success' => true, 'message' => 'Project Master Data Stored Successfully']);
        }
        catch (Exception $e) {
            return response()->json(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
        }
    }




    public function editProjectMaster(Request $request, $id)
    {
        try{
            $token = $request->user()->currentAccessToken();
            if(!$token)
            {
                return response()->json(['success'=>false,'message'=>'Token Not Found'],404);
            }
            $validatedData = $request->validate([
                'branch'   =>  'required',     
                'department' => 'required',
                'proj_name'  =>  'required',
                'proj_title'  =>  'required',
                'proj_code'  => 'required',
                'methodology' => 'required',
                'version'   =>  'required',
                'description' => 'required',
                'start_date' => 'required',
                'target_date' => 'required',
                'due_date' => 'required',
                'duration' => 'required',
                'priority' => 'required',
                'risk' => 'required',
            ]);
            $projModule = ProjectMaster::find($id);
            if(!$projModule)
            {
                return response()->json(['success'=>false,'message'=>'Data Not Found'],404);
            }
            $id = $token->tokenable->id;
            $projModule->branch = $request->branch;
            $projModule->department = $request->department;
            $projModule->proj_name = $request->proj_name;
            $projModule->proj_title = $request->proj_title;
            $projModule->proj_code = $request->proj_code;
            $projModule->methodology = $request->methodology;
            $projModule->version = $request->version;
            $projModule->description = $request->description;
            $projModule->start_date = $request->start_date;
            $projModule->target_date = $request->target_date;
            $projModule->due_date = $request->due_date;
            $projModule->duration = $request->duration;
            $projModule->priority = $request->priority;
            $projModule->risk = $request->risk;
            $projModule->resource_id = $request->resource_id;
            $projModule->resource_name = $request->resource_name;
            $projModule->location = $request->location;
            $projModule->serial_no = $request->serial_no;
            $projModule->memory_size = $request->memory_size;
            $projModule->model = $request->model;
            $projModule->comments = $request->comments;
            $projModule->type_of_resource = $request->type_of_resource;
            $projModule->quantity = $request->quantity;
            $projModule->storage_capacity = $request->storage_capacity;
            $projModule->assumption = $request->assumption;
            $projModule->resource_description = $request->resource_description;
            $projModule->mac_address = $request->mac_address;
            $projModule->subnet_mask = $request->subnet_mask;
            $projModule->dns = $request->dns;
            $projModule->ip_address = $request->ip_address;
            $projModule->gateway = $request->gateway;
            $projModule->soft_name = $request->soft_name;
            $projModule->soft_version = $request->soft_version;
            $projModule->year_of_licence = $request->year_of_licence;
            $projModule->soft_serial_no = $request->soft_serial_no;
            $projModule->soft_licence = $request->soft_licence;
            $projModule->title = $request->title;
            $projModule->soft_quantity = $request->soft_quantity;
            $projModule->soft_description = $request->soft_description;
            $projModule->role = $request->role;
            $projModule->no_of_roles = $request->no_of_roles;
            $projModule->human_resource_description = $request->human_resource_description;
            $projModule->cost_type = $request->cost_type;
            $projModule->cost_resource_name = $request->cost_resource_name;
            $projModule->cost_quantity = $request->cost_quantity;
            $projModule->cost = $request->cost;
            $projModule->total_cost = $request->total_cost;
            $projModule->cost_description = $request->cost_description;
            $projModule->client_id = $request->client_id;
            $projModule->client_role = $request->client_role;
            $projModule->client_name = $request->client_name;
            $projModule->client_website = $request->client_website;
            $projModule->client_domain = $request->client_domain;
            $projModule->client_insurance = $request->client_insurance;
            $projModule->document_type = $request->document_type;
            if($request->file)
            {
                $file = $request->file;
                $ext = $file->getClientOriginalExtension();
                $fileName = $id.'.'.time().'.'.$ext;
                $file->move(public_path('/project_file'),$fileName);
                $projModule->file = $fileName;
    
            }
            $projModule->save();  
              return response()->json(['success' => true, 'message' => 'Project Master Data Updated Successfully']);

        }

        catch (Exception $e) {
            return response()->json(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
        }

    }




    public function destroyProjectMaster(Request $request,$id)
    {
        $token = $request->user()->currentAccessToken();
        if(!$token)
        {
            return response()->json(['success'=>false,'message'=>'Token Not Found'],404);
        }
        $projModule = ProjectMaster::find($id);
        if(!$projModule)
        {
            return response()->json(['success'=>false,'message'=>'Record Not Found'],404);
        }
        $projModule->delete();
        return response()->json(['success'=>true,'message'=>'Record Deleted Successfully'],200);

    }




    public function allProjectMaster(Request $request)
    {
        $token = $request->user()->currentAccessToken();
        if(!$token)
        {
            return response()->json(['success'=>false,'message'=>'token not found!'],404);
        }
        $code = $token->tokenable->company_code;
        $allProject = ProjectMaster::where('company_code',$code)->get();
        if($allProject->isEmpty())
        {
            return response()->json(['success'=>false,'message'=>'Data Not Found!'],404);
        }
        return response()->json(['success'=>true,'data'=>$allProject]);
    }



    public function currentDateProject(Request $request)
    {
     
        try {
            $token = $request->user()->currentAccessToken();
            if (!$token) {
                return response()->json(['success' => false, 'message' => 'Token not found!'], 404);
            }
        
            $validatedData = $request->validate([
                'from_date' => 'required|date',
                'to_date' => 'required|date|after_or_equal:from_date',
            ]);
        
            $code = $token['tokenable']['company_code'];
            $fromDate = $validatedData['from_date'];
            $toDate = $validatedData['to_date'];
            
            $currentDate = ProjectMaster::where('company_code', $code)
                                        ->whereBetween('start_date', [$fromDate, $toDate])
                                        ->get();
        
            if ($currentDate->isEmpty()) {
                return response()->json(['success' => false, 'message' => 'Data Not Found!'], 404);
            }
        
            return response()->json(['success' => true, 'message' => 'Data Found', 'data' => $currentDate], 200);
        
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()], 500);
        }
        
        

    }




    public function MonthWiseDateProject(Request $request)
    {
        $token = $request->user()->currentAccessToken();
        if(!$token)
        {
            return response()->json(['success'=>false,'message'=>'Token Not Found!'],404);
        }
        $code = $token['tokenable']['company_code'];
        $validatedData = $request->validate([
            'month_year' => 'required|date_format:Y-m',
        ]);
        $month = $request->month_year;
        $monthWiseData = ProjectMaster::where('company_code',$code)->whereYear('start_date','=',date('Y',strtotime($month)))
        ->whereMonth('start_date','=',date('m',strtotime($month)))->get();
        if($monthWiseData->isEmpty())
        {
            return response()->json(['success'=>false,'message'=>'Data Not Found!'],404);
        }
        return response()->json(['success'=>true,'message'=>'Data Found',$monthWiseData],200);
    }




    public function excelProject(Request $request,$month,$year)
    {
        $token = $request->user()->currentAccessToken();
        if(!$token)
        {
            return response()->json(['success'=>false,'message'=>'Token Not Found!'],404);
        }
        $code = $token['tokenable']['company_code'];
      return Excel::download(new ProjectMasterData($month,$year,$code),'project_master_data.xlsx');
    }


    public function testProject(Request $request,$month,$year)
    {
       
        $code = '65d_1708508552';
      return Excel::download(new ProjectMasterData($month,$year,$code),'project_master_data.xlsx');
    }



    public function importProject(Request $request)
    {
        $token = $request->user()->currentAccessToken();
        if (!$token) {
            return response()->json(['success' => false, 'message' => 'Authentication token not found. Please log in.'], 401);
        }
    
        $validatedData = $request->validate([
            'file' => 'required|mimes:xlsx,xls',
        ]);
       $code = $token['tokenable']['company_code'];
        $file = $request->file('file');
    
        try {
             Excel::import(new ProjectDataImport(), $file);
            return response()->json(['success' => true, 'message' => 'File imported successfully']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'An error occurred during import', 'error' => $e->getMessage()], 500);
        }
    }
    






}
