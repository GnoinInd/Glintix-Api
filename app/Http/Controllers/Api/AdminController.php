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



public function getuser($id)
{
   $user = User::find($id);

//    $user = DB::table('users')
//                 ->where('id', $id)
//                 ->first();
   if(is_null($user))
   {
    return response()->json(['success' =>'fail','message' =>'User not Found'],403);
   }
   else{
        return response()->json([
            'user'=>$user,
            'message' => 'User Found',
            'success' =>1
        ]);
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
            // session(['last_activity' => now()]);    
            $otpResult = $this->generateAndSendOtp($root->id, $root->phone,$root->email);

            if ($otpResult['success']) {
                // $role = $root->role;
                // $email = $root->email;
                return response()->json(['success' => true,'userId' => $root->id,'message' => 'otp send successfully'],200);
            } else {
                return response()->json(['success' => false, 'message' => $otpResult['error']],401);
            }
        }

        return response()->json(['success' => false, 'message' => 'Username or password is incorrect'],401); 
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
          //  \Log::error("Twilio Exception: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        } catch (\Exception $e) {
          //  \Log::error("Exception: " . $e->getMessage());
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
        // $userId = $validatedData['user_id'];
        // $otp = $validatedData['otp'];
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
             // Issue a SANCTUM token
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
            // session_start();
            // $_SESSION['setTime'] = time() + (10*60); 
            // $_SESSION['phone'] = $phone;
            // Session::start();
          
            $user->time_expire = now()->addMinutes(1440);
            $user->save();
            // $token = $user->createToken('auth-token', ['custom-scope'])->plainTextToken;  
            // $token->expires_at = now()->addDay(1);
            // $token->save();
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





    public function rootLogout(Request $request)
    {
     // Revoke the Sanctum token
     $token = $request->user()->currentAccessToken();

     if ($token) {
         $token->delete();
     }
 
     // Logout the user from the web guard
     Auth::guard('web')->logout();
 
     // Clear user activity cache
     Cache::forget('user-activity-' . $request->user()->id);
 
     return response()->json(['success' => true, 'message' => 'Successfully logged out'], 200);
    }
    






    public function registerCompany(Request $request)
    {
        // print_r(explode(',',$request->modules));die;
        // $request->modules = explode(',',$request->modules);

        // print_r($request->modules);die;
        $token = $request->user()->currentAccessToken();
        // $token = $request->bearerToken();
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
            //  print_r($modulesId);die;
            $generatedDbName = $this->generateUniqueDbName($request->input('name'));
            // $userpassword = $validatedData['password'];
            $role = $token['tokenable']['role']; 
            // $user = PersonalAccessToken::findToken($token)->tokenable;
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
                // print_r($generatedPassword);die;
                $role = 'admin';
                $total = 25;
                        
                $companyCode = $this->generateUniqueCompanyCode();
                //  print_r($companyCode);die;

                // $modules = $request->input('modules',[]);
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
                // $user->password = $validatedData['password'];
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
              
    
                // return response()->json(['success'=>true,'companyCode'=>$companyCode,'message' => 'Company registered successfully'], 201);
                
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

        // $defaultDB = DB::getDefaultConnection();
        // DB::statement("CREATE DATABASE IF NOT EXISTS $dbName");
        // DB::statement("CREATE USER '$dbUsername'@'localhost' IDENTIFIED BY '$dbPassword'");
        // DB::statement("GRANT ALL ON $dbName.* TO '$dbUsername'@'localhost' IDENTIFIED BY '$dbPassword'");
        // DB::statement("FLUSH PRIVILEGES");
        // DB::setDefaultConnection($defaultDB);

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


//     private function generateUniqueCompanyCode()
// {
//     $code = strtoupper(Str::random(8));
//     while (User::where('company_code', $code)->exists()) {
//         $code = strtoupper(Str::random(8));
//     }
//     return $code;
// }



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







// public function selectModules(Request $request)
// {
//     $validatedData = $request->validate([
//         'modules' => 'required|json', 
//         // 'companyCode' => 'required',
//     ]);
//     // $companyCode = $validatedData['companyCode'];
//     $companyCode = $request->companyCode;

//     if (!$companyCode) {
//         return response()->json(['success' => false, 'message' => 'Company code not found']);
//     }
//     CompanyModuleAccess::where('company_code', $companyCode)->delete();

//     try {
//         $modulesArray = json_decode($validatedData['modules'], true);
//         if (is_array($modulesArray)) {
//             foreach ($modulesArray as $moduleId) {
//                 CompanyModuleAccess::create([
//                     'company_code' => $companyCode,
//                     'module_id' => $moduleId,
//                 ]);
//             }
//         } else {
//             throw new \Exception('Invalid JSON format for the "modules" field.');
//         }

//         return response()->json(['success' => true, 'message' => 'Modules selected successfully']);
//     } catch (\Exception $e) {
//         return response()->json(['success' => false, 'message' => $e->getMessage()]);
//     }
// }





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





public function logoutSession(Request $request)
{
    session_start();
    session_unset();
    session_destroy();

   
    return response()->json(['message' => 'Logged out successfully'],200);
}







public function loginCompany(Request $request)
{
    try{

    
    $validatedData = $request->validate([
        'username' => 'required|string|max:255',
        'password' => 'required|string|min:6',
    ]);
    
    $user = User::where('username', $validatedData['username'])->first();
//    echo $user;die;
    if ($user && Hash::check($validatedData['password'], $user->password)) {
       
        // $dbName = $user->dbName;
        // $token = $user->createToken('access_token')->plainTextToken;

        $otpResult = $this->generateAndSendOtpCompany($user->id, $user->mobile_number,$user->email);

        if ($otpResult['success']) {
            // $role = $user->role;
            // $email = $user->email;

            return response()->json(['success' => true,'userId' => $user->id,'message' => 'otp send successfully'],200);
        } else {
            return response()->json(['success' => false, 'message' => $otpResult['error']],401);
        }

        // $token_array = $request->user()->currentAccessToken();
        // $token_array = $token_array['tokenable'];

        // Add custom values to the token payload
    //  $tokenPayload = $user->tokens()->latest()->first(); // Retrieve the created token
    //  $tokenPayload->forceFill([
    //     'dbName' => 'value1',
    //     'username' => 'value2',
    //     // Add more custom values as needed
    // ])->save();
     

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
             // Issue a SANCTUM token
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
                // session_start();
                // $_SESSION['setTime'] = time() + (10*60); 
                // $_SESSION['phone'] = $phone;
                // Session::start();
              
                $user->expire_at = now()->addMinutes(1440);
                $user->save();
                // $token = $user->createToken('auth-token', ['custom-scope'])->plainTextToken;  
                // $token->expires_at = now()->addDay(1);
                // $token->save();
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
    // print_r($token_array->tokenable);die;
    // if ($token_array && is_object($token_array) && property_exists($token_array->tokenable, 'username') && property_exists($token_array->tokenable, 'dbName')) 
    if ($token_array && isset($token_array['tokenable']['email']))
    {
        // $username = $token_array->tokenable->username;
        // $dbName = $token_array->tokenable->dbName;
         $email = $token_array['tokenable']['email'];
         $dbName = $token_array['tokenable']['dbName'];
        //  $profile = User::where('email',$email)->first(); 

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




private function checkSession()
{
    session_start();
    $now = time();

    if (isset($_SESSION['expire']) && $now > $_SESSION['expire']) {
        session_destroy();
        return false;
    } elseif (isset($_SESSION['expire']) && $now <= $_SESSION['expire']) {
        return true;
    } else {
        return false;
    }
}

private function checkSessionAndSetupConnection()
{
    if (!$this->checkSession()) {
        return false;
    }
   

    if (isset($_SESSION["username"]) && isset($_SESSION["password"]) && isset($_SESSION["dbName"])) {
        $username = $_SESSION["username"];
        $password = $_SESSION["password"];
        $dbName = $_SESSION["dbName"];

        $passcode = Hash::make($password);

        $maxEmp = User::where('username', $username)->where('dbName', $dbName)->value('total');

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

        return compact('maxEmp');
    }

    return false; // Session data not set
}




public function addEmployee(Request $request)
{
    try{
        $token = $request->user()->currentAccessToken();
    //  $username = $token['tokenable']['company_code'];    
    //  print_r($token);die;
    
    if (!$token) {
        return response()->json(['success' => false, 'message' => 'Token not found!'],404);
    }
    $companyCode = $token['tokenable']['company_code'];
    $moduleId = 3;
    $empModule = CompanyModuleAccess::where('company_code', $companyCode)
    ->where('module_id', $moduleId)
    ->where('status', 'active')
    ->first();  
    
    //  echo $empModule;die; 
    if(!$empModule)
    {
      return response()->json(['success'=>false,'message'=>'you can not access Employee module'],403);
    }
    $company= User::where('company_code',$companyCode)->first();
    // print_r($company);die;
    $username = $company->username;
    $password = $company->dbPass;
    $dbName = $company->dbName;
    $maxEmp = $company->total;
    $create = $token['tokenable']['create'];
    $role = $token['tokenable']['role'];
    // print_r($role);die;

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
//   print_r($token);die;
$companyCode = $token['tokenable']['company_code'];
$moduleId = 3;
$empModule = CompanyModuleAccess::where('company_code', $companyCode)
->where('module_id', $moduleId)
->where('status', 'active')
->first(); 
// print_r($empModule);die;
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
// print_r($username);die;
if($role && $role == 'admin' && $read && $read == 1 || $role && $role == 'Super Admin')
{
    Config::set('database.connections.dynamic',[
        'driver' => 'mysql',
        'host' => 'localhost',
        'database' => $dbName,
        'username' => $username, 
        'password' => $password,
        'charset' => 'utf8mb4',
        'collection' => 'utf8mb4_unicode_ci',
        'prefix' => '',
        'strict' => true,
        'engine' => null,
     ]);
     $dynamicDB = DB::connection('dynamic');
     if(!Schema::connection('dynamic')->hasTable('employees'))
     {
       return response()->json(['success'=>false,'message'=>'Employee table not found'],404);
     }
   //   $allEmp = $dynamicDb->table('employees')->get();
      $allEmp = $dynamicDB->table('employees')->select('id','name','email','designation','address','created_at')->get();
   //   print_r($allEmp);die;
   if(!$allEmp)
   {
       return response()->json(['success'=>false,'message'=>'data not found'],404);
   }
   return response()->json(['success'=>true,'message'=>'Employee found','allEmployee'=>$allEmp],200);
}

return response()->json(['success'=>false,'message'=>'Access Denied!'],403);

    }
    catch(\Exception $e)
    {
        return response()->json(['success' => false, 'message' => 'An error occurred. Please try again.','error'=>$e->getMessage()], 500);
    }
}







// public function singleEmployee(Request $request, $employeeId)
// {

//    session_start();
   
//    if (isset($_SESSION["username"]) && isset($_SESSION["password"]) && isset($_SESSION["dbName"]))
//    {
      
//    $username = $_SESSION["username"];
//    $password = $_SESSION["password"];
//    $dbName = $_SESSION["dbName"];

//    Config::set('database.connections.dynamic', [
//     'driver' => 'mysql',
//     'host' => 'localhost', 
//     'database' => $dbName,
//     'username' => $username,
//     'password' => $password,
//     'charset' => 'utf8mb4',
//     'collation' => 'utf8mb4_unicode_ci',
//     'prefix' => '',
//     'strict' => true,
//     'engine' => null,
// ]);
 
//  if (!Schema::connection('dynamic')->hasTable('employees')) {
    
//     return response()->json(['message' => 'Dynamic database table not found'], 404);
// }
// $employee = DB::connection('dynamic')->table('employees')->find($employeeId);

// if (!$employee) {
//     return response()->json(['message' => 'Employee not found'], 404);
// }
// return response()->json(['message' => 'Employee found', 'data' => $employee]);
  
//  }
//   else
//   {
//     return response()->json(['message' => 'sorry session out,pls login']);
//   }

  
// }


// public function singleEmployee(Request $request, $employeeId)
// {
//     $sessionCheckResult = $this->checkSessionAndSetupConnection();
//     if(!$sessionCheckResult)
//     {
//       return response()->json(['message' => 'Sorry, session expired or invalid. Please login.'], 400);
//     }
   
//     if(isset($_SESSION['read']) && $_SESSION['read'] == 1)
//     {
//         if(!Schema::connection('dynamic')->hasTable('employees'))
//         {
//           return response()->json(['message' => 'no table found'],404);
//         }
//        $employee = DB::connection('dynamic')->table('employees')->find($employeeId);
//        if(!$employee)
//        {
//         return response()->json(['message' => 'data not found']);
//        }
//      return response()->json(['message' => $employee]);
//     }
//     else
//     {
//         return response()->json(['message' => 'you have no permission'],403);
//     }
  
// }




public function singleEmployee(Request $request, $employeeId)
{
    $token = $request->user()->currentAccessToken();
   
    if(!$token)
    {
        return response()->json(['success'=>false,'message'=>'token not found'],404);
    }
    $companyCode = $token['tokenable']['company_code'];
    $moduleId = 3;
    $empModule = CompanyModuleAccess::where('company_code',$companyCode)->where('module_id',$moduleId)->where('status','active')->first();
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
    if($read && $read == 1 && $role && $role == 'admin' || $role && $role == 'Super Admin')
    {
        Config::set('database.connections.dynamic',[
            'driver' => 'mysql',
            'host' => 'localhost',
            'database' => $dbName,
            'username' => $username,
            'password' => $password,
            'charset' => 'utf8mb4',
            'collection' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
            'engine' => null,
        ]);
        $dynamicDB = DB::connection('dynamic');
        $singleEmp = $dynamicDB->table('employees')->where('id',$employeeId)->first();
        if(!$singleEmp)
        {
            return response()->json(['success'=>false,'message'=>'Employee not found!'],404);
        }
        return response()->json(['success'=>true,'message'=>'Employee found',
        'empDetails' => [
            'id'  => $singleEmp->id,
            'name' => $singleEmp->name,
            'email' => $singleEmp->email,
            'address' => $singleEmp->address,
            'designation' => $singleEmp->designation,
            'created_at' => $singleEmp->created_at,
        ]
    ],200);

    }
    return response()->json(['success'=>false,'message'=>'Access Denied!'],403);
    
}






// public function editEmployee(Request $request, $employeeId)
// {
    
//     session_start();

//     if (isset($_SESSION["username"]) && isset($_SESSION["password"]) && isset($_SESSION["dbName"]))
//     {
//         $username = $_SESSION["username"];
//         $password = $_SESSION["password"];
//         $dbName = $_SESSION["dbName"];
     
//         Config::set('database.connections.dynamic', [
//             'driver' => 'mysql',
//             'host' => 'localhost', 
//             'database' => $dbName,
//             'username' => $username,
//             'password' => $password,
//             'charset' => 'utf8mb4',
//             'collation' => 'utf8mb4_unicode_ci',
//             'prefix' => '',
//             'strict' => true,
//             'engine' => null,
//         ]);
    
      
//         if (!Schema::connection('dynamic')->hasTable('employees')) {
          
//             return response()->json(['message' => 'Dynamic database table not found'], 404);
//         }  
//         $employee = DB::connection('dynamic')->table('employees')->find($employeeId);
//         if (!$employee) {
//             return response()->json(['message' => 'Employee not found'], 404);
//         }
//         $date = Carbon::now()->timezone('Asia/kolkata')->format('Y-m-d H:i:s');
//         DB::connection('dynamic')->table('employees')->where('id', $employeeId)->update([
//             'name' => $request->input('name'),
//             'email' => $request->input('email'),
//             'designation' => $request->input('designation'),
//             'address' => $request->input('address'),
//             'updated_at' => $date,
//         ]);
    
//         return response()->json(['message' => 'Employee updated successfully through session'], 200);
//     }
//     else
//     {
//         return response()->json(['message' => 'sorry session out,pls login']);
//     }
   
// }


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
    ->where('status', 'active')
    ->first();  
    
    //   echo $empModule;die;
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
  
    // $companyCode = $token['tokenable']['company_code'];
    $dbName = $token['tokenable']['dbName'];
    $user = User::where('company_code',$companyCode)->first();
    $dbUsername = $user->username;
    $dbPass = $user->dbPass;
    $edit = $token['tokenable']['edit'];
    $role = $token['tokenable']['role'];
    //  print_r($role);die;

    Config::set('database.connections.dynamic', [
        'driver' => 'mysql',
        'host' => 'localhost',
        'database' => $dbName,
        'username' => $dbUsername,
        'password' => $dbPass,
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
        'strict' => true,
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




// public function destroyEmployee(Request $request, $employeeId)
// {
//     session_start();
//     if (isset($_SESSION["username"]) && isset($_SESSION["password"]) && isset($_SESSION["dbName"]))
//     {
//         $username = $_SESSION["username"];
//         $password = $_SESSION["password"];
//         $dbName = $_SESSION["dbName"];

      
//     Config::set('database.connections.dynamic', [
//         'driver' => 'mysql',
//         'host' => 'localhost',
//         'database' => $dbName,
//         'username' => $username,
//         'password' => $password,
//         'charset' => 'utf8mb4',
//         'collation' => 'utf8mb4_unicode_ci',
//         'prefix' => '',
//         'strict' => true,
//         'engine' => null,
//     ]);

//     if (!Schema::connection('dynamic')->hasTable('employees')) {
//         return response()->json(['message' => 'Dynamic database table not found'], 404);
//     }

//     $employee = DB::connection('dynamic')->table('employees')->find($employeeId);
//     if (!$employee) {
//         return response()->json(['message' => 'Employee not found'], 404);
//     }
//     DB::connection('dynamic')->table('employees')->where('id', $employeeId)->delete();

//     return response()->json(['message' => 'Employee deleted successfully through session']);
    
//     }
//     else
//     {
//         return response()->json(['message' => 'Sorry session out,pls login']);
//     }
    
    
// }




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
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
        'strict' => true,
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
            'charset' => 'utf8mb4',
            'collection'=> 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
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





// public function checkId(Request $request)
// {
//     $token = $request->user()->currentAccessToken();
//     $companyCode = $token['tokenable']['company_code'];
//     // print_r($companyCode);
//     $moduleId = 3;
//     $empModule = CompanyModuleAccess::where('company_code',$companyCode)->where('module_id',$moduleId)->where('status','active')->first();
//     // print_r($empModule);die;
//     if(!$empModule)
//     {
//         return response()->json(['success'=>false,'message'=>'can not access']);
//     }
//     $company = User::where('company_code',$companyCode)->first();
//     $username = $company->username;
//     $password = $company->password;
//     $dbName = $company->dbPass;
//     Config::set('database.connections.dynamic',[
//         'driver' => 'mysql',
//         'host' => 'localhost',
//         'database' => $dbName,
//         'username' => $username,
//         'password' => $password,
//         'charset' => 'utf8mb4',
//         'collection'=> 'utf8mb4_unicode_ci',
//         'prefix' => '',
//         'strict' => true,
//         'engine' => null,
//         ]);

// }









// public function searchEmpByValue(Request $request)
// {
//    try
//    {   
//     $validatedData = $request->validate([
//         'option' => 'required',
//         'value' => 'required',
        
//     ]);
//     session_start();
//     if(isset($_SESSION["username"]) && isset($_SESSION["password"]) && isset($_SESSION["dbName"]))
//     {
//        $username = $_SESSION["username"];
//        $password = $_SESSION["password"];
//        $dbName = $_SESSION["dbName"];

//        $option = $validatedData['option'];
//        $value = $validatedData['value'];
       
//        Config::set('database.connections.dynamic', [
//         'driver' => 'mysql',
//         'host' => 'localhost', 
//         'database' => $dbName,
//         'username' => $username,
//         'password' => $password,
//         'charset' => 'utf8mb4',
//         'collation' => 'utf8mb4_unicode_ci',
//         'prefix' => '',
//         'strict' => true,
//         'engine' => null,
//     ]);
    
//      $dynamicDB = DB::connection('dynamic');
//      $results = $dynamicDB->table('employees')->where($option,'like', '%' . $value . '%')->get();
//     if( count($results) > 0)
//     {
//         return response()->json(['data' => $results]);
//     }
//     return response()->json(['message' => 'Data not Found']);

//     }

//     return response()-json(['message' => 'pls login first']);
//    }

//    catch(\Exception $e)
//    {
//     return response()->json(['error' => $e->getMessage()]);
//    }
  

    
// }




public function searchEmpByValue(Request $request)
{
    $validatedData = $request->validate
    ([
        'option' => 'required',
        'value' => 'required',      
    ]);

    $sessionCheckResult = $this->checkSessionAndSetupConnection();
    if (!$sessionCheckResult) {
        return response()->json(['message' => 'Sorry, session expired or invalid. Please login.'], 401);
    }

     if(isset($_SESSION['read']) && $_SESSION['read'] == 1)
     {
        if(!Schema::connection('dynamic')->hasTable('employees'))
        {
            return response()->json(['message' => 'table not found'], 404);
        }
        $option = $request->input('option');
        $value = $request->input('value');
        $results = DB::connection('dynamic')->table('employees')->where($option, 'like', '%' . $value .'%')->get();
        if(count($results) > 0)
        {
            return response()->json(['message' => $results]);
        }
        return response()->json(['message' => 'data not found'],404);
     }

     return response()->json(['message' => 'you have no permission'],403);

}






// public function latestMember(Request $request)
// {
//     session_start();
//     if(isset($_SESSION["username"]) && isset($_SESSION["password"]) && isset($_SESSION["dbName"]))
//     {
//        $username = $_SESSION["username"];
//        $password = $_SESSION["password"];
//        $dbName = $_SESSION["dbName"];

//        Config::set('database.connections.dynamic', [
//         'driver' => 'mysql',
//         'host' => 'localhost', 
//         'database' => $dbName,
//         'username' => $username,
//         'password' => $password,
//         'charset' => 'utf8mb4',
//         'collation' => 'utf8mb4_unicode_ci',
//         'prefix' => '',
//         'strict' => true,
//         'engine' => null,
//     ]);
    
//      $dynamicDB = DB::connection('dynamic');

//     $recentEmp = $dynamicDB->table('employees')->orderBy('created_at','desc')->limit(10)->get();
//     return response()->json(['data' => $recentEmp]);

//     }
//     return response()->json(['message' => 'pls login']);
// }


public function latestMember(Request $request)
{
    $sessionCheckResult = $this->checkSessionAndSetupConnection();
    if(!$sessionCheckResult)
    {
      return response()->json(['message' => 'Sorry, session expired or invalid. Please login.'], 400);    
    }
   if(isset($_SESSION['read']) && $_SESSION['read'] == 1)
   {
     if(!Schema::connection('dynamic')->hasTable('employees'))
     {
        return response()->json(['message' => 'table not found']);
     }
     $dynamicDB = DB::connection('dynamic');
     $latestEmp = $dynamicDB->table('employees')->orderBy('created_at','desc')->limit(10)->get();
     return response()->json(['message' => $latestEmp]);

   }
   return response()->json(['message' => 'you have no permission']);

}



public function applyLeave(Request $request)
{
    $validatedData = $request->validate([
        'leavetype' => 'required',
        'startdate' => 'required|date', 
        'enddate' => 'required|date', 
        'reason' => 'required',  
    ]);
     session_start();
    if(isset($_SESSION['username']) && 
    isset($_SESSION['password']) && 
    isset($_SESSION['dbName']) && 
    isset($_SESSION['empEmail']) &&
    isset($_SESSION['empPass']))
    {
        $username = $_SESSION["username"];
        $password = $_SESSION["password"];
        $dbName = $_SESSION["dbName"];
        $email = $_SESSION['empEmail'];
        $empPass = $_SESSION['empPass'];

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
        $currentEmp = $dynamicDB->table('employees')->where('email',$email)->first();
        
        if(!$currentEmp)
        {
            return response()->json(['message' => 'Employee not found'],404);
        }
        $id = $currentEmp->id;
        $name = $currentEmp->name;
        
            
          if (!$dynamicDB->getSchemaBuilder()->hasTable('leaves')) {
            $dynamicDB->getSchemaBuilder()->create('leaves', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('employee_id');
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();
                $table->string('leave_type')->nullable();
                $table->integer('duration')->nullable();
                $table->string('reason')->nullable();
                $table->enum('status', ['pending', 'approved','reject'])->default('pending');
                $table->string('approved_by')->nullable();
                $table->date('approval_date')->nullable();
                $table->string('attachment')->nullable();
                $table->date('date');
                $table->timestamps();
                $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            });
        }
          $currentTime = Carbon::now()->timezone('Asia/Kolkata')->format('H:i:s');

          $start_date = Carbon::parse($validatedData['startdate']);
          $end_date = Carbon::parse($validatedData['enddate']);
          $duration = $end_date->diffInDays($start_date);
          $updateDuration = $duration + 1 ;

          try{
            $data = [
                'employee_id' => $id,
                'start_date' => $validatedData['startdate'],
                'end_date' => $validatedData['enddate'],
                'leave_type' => $validatedData['leavetype'],
                'reason' => $validatedData['reason'],
                'duration' => $updateDuration,
                'created_at' => $date, 
                'updated_at' => $date,
                'date' => now()->toDateString(),
            ];
            $dynamicDB->table('leaves')->insert($data);
            $details = ['title' => "Leave Application",'applicantName' => $name,'designation' => $currentEmp->designation,
            'leave_type'=>$validatedData['leavetype'],'startdate' =>$validatedData['startdate'],'enddate' =>$validatedData['enddate'],
            'days' => $updateDuration,'reason' => $validatedData['reason'],'date' =>now()->toDateString()];
            
            Mail::to("gemsfiem@gmail.com")->send(new LeaveMail($details)); 
            
            return response()->json(['message' => 'employee leave request created successfully']);
          }
          catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }  
       

    }
     return response()->json(['message' => 'Access Denied']);

}

public function approveLeave(Request $request)
{
    $validatedData = $request->validate([
        'employee_id' => 'required|integer',
        'status' => 'required|string']);
    session_start();
    if(isset($_SESSION["username"]) && isset($_SESSION["password"]) && isset($_SESSION["dbName"]))
    {
        $username = $_SESSION["username"];
        $password = $_SESSION["password"];
        $dbName = $_SESSION["dbName"];

        $date = Carbon::now()->timezone('Asia/Kolkata')->format('Y-m-d H:i:s');
        $empId = $request->input('employee_id');
        $status = $request->input('status');
        

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
        
            
            $user = User::where('username',$username)->first();
            $role = $user->role;
            if($user)
            {
                $employee = $dynamicDB->table('employees')->where('id', $empId)->first();
                if ($employee) {
               $dynamicDB->table('leaves')
                  ->where('employee_id', $empId) 
                  ->update([
                      'status' => $status,
                      'approved_by' => $role,
                      'updated_at' => $date,
                        ]);
                        return response()->json(['message' => 'leave status updated']);
                }
                return response()->json(['message' => 'employee not found']);

            }
            return response()->json(['message' => 'user not found']);
            
            
        
           
       

    }
}
public function datewiseAttend(Request $request, $date)
{
    session_start();
    if (isset($_SESSION["username"]) && isset($_SESSION["password"]) && isset($_SESSION["dbName"])) {
        $username = $_SESSION["username"];
        $dbName = $_SESSION["dbName"];
        $user = User::where('username', $username)
            ->where('dbName', $dbName)->first();

        if ($user) {
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
            $allAttend = $dynamicDB->table('attendences')->whereDate('date', $date)->get();
            if ($allAttend->count() > 0) {
                return response()->json(['message' => 'Datewise all Employee Attendence', 'data' => $allAttend]);
            } else {
                return response()->json(['message' => 'No data found']);
            }
        } else {
            return response()->json(['message' => 'Profile details not found']);
        }
    } else {
        return response()->json(['message' => 'Session out, please login']);
    }
}



public function monthwiseAttend(Request $request, $year, $month)
{
    session_start();
    if (isset($_SESSION["username"]) && isset($_SESSION["password"]) && isset($_SESSION["dbName"])) {
        $username = $_SESSION["username"];
        $dbName = $_SESSION["dbName"];
        $user = User::where('username', $username)
            ->where('dbName', $dbName)->first();

        if ($user) {

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
            $startOfMonth = Carbon::createFromDate($year, $month, 1)->startOfMonth();
            $endOfMonth = Carbon::createFromDate($year, $month, 1)->endOfMonth();
            
            $monthwiseAttend = $dynamicDB->table('attendences')
                ->whereBetween('date', [$startOfMonth, $endOfMonth])
                ->get();
            
            if ($monthwiseAttend->count() > 0) {
                return response()->json(['message' => 'Monthwise Employee Attendance', 'data' => $monthwiseAttend]);
            } else {
                return response()->json(['message' => 'No data found']);
            }
        } else {
            return response()->json(['message' => 'Profile details not found']);
        }
    } else {
        return response()->json(['message' => 'Session out, please login']);
    }
}

public function idWiseMonthwiseAttend(Request $request, $year, $month, $employeeId)
{
    session_start();
    if (isset($_SESSION["username"]) && isset($_SESSION["password"]) && isset($_SESSION["dbName"])) {
        $username = $_SESSION["username"];
        $dbName = $_SESSION["dbName"];
        $user = User::where('username', $username)
            ->where('dbName', $dbName)->first();

        if ($user) {
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
            $startOfMonth = Carbon::createFromDate($year, $month, 1)->startOfMonth();
            $endOfMonth = Carbon::createFromDate($year, $month, 1)->endOfMonth();
            
            $idWiseMonthwiseAttend = $dynamicDB->table('attendences')
                ->where('employee_id', $employeeId)
                ->whereBetween('date', [$startOfMonth, $endOfMonth])
                ->get();
            
            if ($idWiseMonthwiseAttend->count() > 0) {
                return response()->json(['message' => 'ID Wise Monthwise Employee Attendance', 'data' => $idWiseMonthwiseAttend]);
            } else {
                return response()->json(['message' => 'No data found']);
            }
        } else {
            return response()->json(['message' => 'Profile details not found']);
        }
    } else {
        return response()->json(['message' => 'Session out, please login']);
    }
}


public function addHoliday(Request $request)
{
    session_start();
    if (isset($_SESSION["username"]) && isset($_SESSION["password"]) && isset($_SESSION["dbName"])) {
        $username = $_SESSION["username"];
        $password = $_SESSION["password"];
        $dbName = $_SESSION["dbName"];

        $user = User::where('username', $username)->first();
        $email = $user->email;

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
            $table = 'holidays';
            if (!$dynamicDB->getSchemaBuilder()->hasTable($table)) {
                $dynamicDB->getSchemaBuilder()->create($table, function (Blueprint $table) {
                    $table->id();
                    $table->date('holiday');
                    $table->timestamps();
                });
            }

            $holidayDates = $request->input('holidays'); 
            foreach ($holidayDates as $date) {
                $dynamicDB->table($table)->insert([
                    'holiday' => $date
                ]);
            }
            return response()->json(['message' => 'Holidays added successfully']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    return response()->json(['message' => 'Please login']);
}




public function workingDay(Request $request)
{
    $validatedData = $request->validate([
        'working-days' => 'required',  
        'shift' => 'required',        
    ]);     

    session_start();
    if(isset($_SESSION["username"]) && isset($_SESSION["password"]) && isset($_SESSION["dbName"]))
    {
        
        $username = $_SESSION["username"];
        $password = $_SESSION["password"];
        $dbName = $_SESSION["dbName"];
        $user = User::where('username',$username)
        ->where('dbName',$dbName)->first();
        
        
 if($user)
 {
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
 
         if (!$dynamicDB->getSchemaBuilder()->hasTable('workings')) {
            $dynamicDB->getSchemaBuilder()->create('workings', function (Blueprint $table) {
                $table->id();
                
                $table->time('working_days')->nullable();
                $table->time('weekoff')->nullable();
                $table->timestamps();
              
            });
        }
        return response()->json(['message' => 'working table data will create from here']);
      
}

     return response()->json(['message' => 'user not found']);

    }
    return response()->json(['message' => 'pls login']);


   
}



public function calculateAndStoreWorkingDays(Request $request)
{
    try {
        $start = '2023-06-10';
        $end = '2023-11-30';
        $workingDaysOption = 'mon-fri';
        $startDate = Carbon::parse($start);
        $endDate = Carbon::parse($end);
       

        session_start();
        if (
            isset($_SESSION["username"]) &&
            isset($_SESSION["password"]) &&
            isset($_SESSION["dbName"])
        ) {
            $username = $_SESSION["username"];
            $password = $_SESSION["password"];
            $dbName = $_SESSION["dbName"];

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

            if (!$dynamicDB->getSchemaBuilder()->hasTable('workings')) {
                $dynamicDB->getSchemaBuilder()->create('workings', function (Blueprint $table) {
                    $table->id();
                    $table->integer('year');
                    $table->integer('month');
                    $table->string('monthName');
                    $table->integer('working_days');
                    $table->integer('weekends');
                    $table->integer('holidays');
                    $table->timestamps();
                });
            }


            $processingDate = $startDate->copy();
           $workingCount = $dynamicDB->table('workings')->count();
           if (!($workingCount > 0)) 
           {
            while ($processingDate <= $endDate) {
                $year = $processingDate->year;
                $month = $processingDate->month;
                $monthName = $processingDate->format('F');
                $weekendDays = $this->calculateWeekendDays($workingDaysOption);
                $holidays = $this->getHolidaysForMonth($dynamicDB, $year, $month);
                $workingDaysCount = $this->calculateWorkingDays($processingDate, $weekendDays, $holidays,$workingDaysOption);
                $dynamicDB->table('workings')->insert([
                    'year' => $year,
                    'month' => $month,
                    'monthName' => $monthName,
                    'working_days' => $workingDaysCount,
                    'weekends' => $weekendDays,
                    'holidays' => count($holidays),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $processingDate->startOfMonth()->addMonth(); 
               
            }   
            return response()->json(['message' => 'Working days calculated and stored successfully']);

           }
           return response()->json(['message' => 'Data already updated']);
            
            

           
        } else {
            return response()->json(['message' => 'Please login'], 401);
        }
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
}

private function calculateWeekendDays($workingDaysOption)
{
    
    if ($workingDaysOption === 'mon-fri') {
        return 2; 
    } elseif ($workingDaysOption === 'mon-sat') {
        return 1; 
    } elseif ($workingDaysOption === 'mon-sun') {
        return 0;
    } else {
        return 0; 
    }
}


private function getHolidaysForMonth($dynamicDB, $year, $month)
{
    $holidays = $dynamicDB->table('holidays') 
        ->whereYear('holiday', $year)
        ->whereMonth('holiday', $month)
        ->pluck('holiday')
        ->toArray();

    return $holidays;
}





private function calculateWorkingDays($processingDate, $weekendDays, $holidays, $workingDaysOption) 
{
    $workingDays = 0;
    $currentDate = $processingDate->copy();

    while ($currentDate->month === $processingDate->month) {   
        if (
            (!$currentDate->isWeekend() && $workingDaysOption === 'mon-fri') ||
            (!$currentDate->isSunday() && $workingDaysOption === 'mon-sat') ||
            $workingDaysOption === 'mon-sun'
        ) {
            $formattedDate = $currentDate->toDateString();
            if (!in_array($formattedDate, $holidays)) {
                $workingDays++; 
                
            }
        }
       
        $currentDate->addDay();
        
    }

    return $workingDays;
    
    
}


public function calculateAndStoreWorkingDayss(Request $request)
{


$start = '2023-08-20';
$end = '2023-11-20';

$startDate = Carbon::parse($start);
$endDate = Carbon::parse($end);

$currentDate = $startDate->copy();
$abc = $currentDate->month;
$bcd = $endDate->month;

$totalSaturdays = 0;
$totalSundays = 0;

while ($currentDate <= $endDate) {
    $dayOfMonth = $currentDate->day; // 20
    $monthDays = $currentDate->daysInMonth; //31
   
  
    for ($day = $dayOfMonth; $day <= $monthDays; $day++) {
        $dateString = "{$currentDate->year}-{$currentDate->month}-$day";
        $currentDay = Carbon::parse($dateString);

        if ($currentDay->month ===  $abc) {
            if ($currentDay->dayOfWeek === Carbon::SATURDAY || $currentDay->dayOfWeek === Carbon::SUNDAY) {
                $totalWeekends++;
            } 
        }
        elseif(!$currentDay->month === $bcd)
        {
            if ($currentDate->dayOfWeek === Carbon::SATURDAY || $currentDate->dayOfWeek === Carbon::SUNDAY) {
                $totalWeekends++;
            }
        }
        elseif($currentDay->month === $bcd)
        {
            $dayofendmonth = $endDate->day;
            if ($currentDate->dayOfWeek === Carbon::SATURDAY || $currentDate->dayOfWeek === Carbon::SUNDAY) {
                $totalWeekends++;
        }
          
    }
    $currentDate->addMonth();
}

echo "Total Saturdays: $totalSaturdays\n";
echo "Total Sundays: $totalSundays\n";
die();





    try {
        $start = '2023-08-20';
        $end = '2023-11-20';
        $workingDaysOption = 'mon-fri';
        $startDate = Carbon::parse($start);
        $endDate = Carbon::parse($end);
        $againDate = $startDate->copy();
        
        $processDate = Carbon::parse($againDate);
        
        $MonthInNumber = $processDate->month;
       
        $yearInNumber = $processDate->year;
         $dayOfMonth = $processDate->day;
        $daysInMonth = $processDate->daysInMonth;
        $daysTotal = $daysInMonth - $dayOfMonth + 1; 
      
       
        if($processDate->month === $MonthInNumber)
        {
            for($day = $dayOfMonth;$day <= $daysInMonth;$day++)
            {
                $dateString = "$yearInNumber-$MonthInNumber-$day";
                $currentDate = Carbon::parse($dateString);

              if ($currentDate->dayOfWeek === Carbon::SATURDAY) 
                {
                 $saturdaysCount++;
                }
             if ($currentDate->dayOfWeek === Carbon::SUNDAY)
                {
                 $sundaysCount++;
                }
             }

        }
        

        session_start();
        if (
            isset($_SESSION["username"]) &&
            isset($_SESSION["password"]) &&
            isset($_SESSION["dbName"])
        ) {
            $username = $_SESSION["username"];
            $password = $_SESSION["password"];
            $dbName = $_SESSION["dbName"];

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

            if (!$dynamicDB->getSchemaBuilder()->hasTable('workings')) {
                $dynamicDB->getSchemaBuilder()->create('workings', function (Blueprint $table) {
                    $table->id();
                    $table->integer('year');
                    $table->integer('month');
                    $table->integer('working_days');
                    $table->integer('weekends');
                    $table->integer('holidays');
                    $table->timestamps();
                });
            }

            $processingDate = $startDate->copy();
            while ($processingDate <= $endDate) {
                $year = $processingDate->year;
                $month = $processingDate->month;

                $weekendDays = $this->calculateWeekendDayss($workingDaysOption);
                $holidays = $this->getHolidaysForMonths($dynamicDB, $year, $month);

                $workingDaysCount = $this->calculateWorkingDayss($processingDate, $endDate, $workingDaysOption, $holidays);
                $dynamicDB->table('workings')->insert([
                    'year' => $year,
                    'month' => $month,
                    'working_days' => $workingDaysCount,
                    'weekends' => $weekendDays,
                    'holidays' => count($holidays),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $processingDate->addMonth(); 
            }

            return response()->json(['message' => 'Working days calculated and stored successfully']);
        } else {
            return response()->json(['message' => 'Please login'], 401);
        }
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
}

}
private function calculateWeekendDayss($workingDaysOption)
{
    if ($workingDaysOption === 'mon-fri') {
        return 2; 
    } elseif ($workingDaysOption === 'mon-sat') {
        return 1;
    } else {
        return 0; 
    }
}

private function getHolidaysForMonths($dynamicDB, $year, $month)
{
    $holidays = $dynamicDB->table('holidays') 
        ->whereYear('holiday', $year)
        ->whereMonth('holiday', $month)
        ->pluck('holiday')
        ->toArray();

    return $holidays;
}

private function calculateWorkingDayss($startDate, $endDate, $workingDaysOption, $holidays)
{
    $workingDays = 0;
    $currentDate = $startDate->copy();

    while ($currentDate <= $endDate) {
        $dayOfWeek = $currentDate->dayOfWeek;
        echo $dayOfWeek;die();

        if (
            ($workingDaysOption === 'mon-fri' && $dayOfWeek >= Carbon::MONDAY && $dayOfWeek <= Carbon::FRIDAY) ||
            ($workingDaysOption === 'mon-sat' && $dayOfWeek >= Carbon::MONDAY && $dayOfWeek <= Carbon::SATURDAY) ||
            $workingDaysOption === 'mon-sun'
        ) {
            $formattedDate = $currentDate->toDateString();
            if (!in_array($formattedDate, $holidays)) {
                $workingDays++;
            }
        }

        $currentDate->addDay();
    }

    return $workingDays;
}




public function addAnnouncement(Request $request)
{
    try{
        session_start();
        if (
            isset($_SESSION["username"]) &&
            isset($_SESSION["password"]) &&
            isset($_SESSION["dbName"])
        ) {
            $username = $_SESSION["username"];
            $password = $_SESSION["password"];
            $dbName = $_SESSION["dbName"];
    
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
            if (!$dynamicDB->getSchemaBuilder()->hasTable('announcements')) {
                $dynamicDB->getSchemaBuilder()->create('announcements', function (Blueprint $table) {
                    $table->id();
                    $table->date('date');
                    $table->text('announcement');
                    $table->timestamps();
                });
            }
        $announcement ="this is for test";
             $dynamicDB->table('announcements')->insert([
                'announcement' => $announcement,
                'date' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        return response()->json(['message' => 'announcement create sucessfully']);
    
        }
        return response()->json(['message' => 'pls login']);
    }
    catch(\Exception $e)
    {
        return response()->json(['error' => $e->getMessage()], 500);
    }
  
}

public function addProject(Request $request)
{
    $validatedData = $request->validate([
        'project_name' => 'required',
        'project_date' => 'required|date',
        'project_endDate' => 'required|date',
        'team_number' => 'required',
        'project_leader' => 'required'
    ]);  
    session_start();
    if(isset($_SESSION["username"]) && isset($_SESSION["password"]) && isset($_SESSION["dbName"]))
    {
        $username = $_SESSION["username"];
        $password = $_SESSION["password"];
        $dbName = $_SESSION["dbName"];

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
        
        if (!Schema::connection('dynamic')->hasTable('projects')) {
            Schema::connection('dynamic')->create('projects', function (Blueprint $table) {
                $table->id();
                $table->string('project_name');
                $table->date('asign_date');
                $table->date('end_date');
                $table->unsignedBigInteger('project_leader');
                $table->integer('team_member')->nullable();
                $table->timestamps(); 
           
                $table->foreign('project_leader')->references('id')->on('employees');
            });
           
        }
        
         $projectName = $validatedData['project_name'];
         
         $projectLeader =  $validatedData['project_leader']; 
         $projectDate = $validatedData['project_date'];
         $endDate = $validatedData['project_endDate'];
         $teamNumber = $validatedData['team_number'];
         $date = Carbon::now()->timezone('Asia/Kolkata')->format('Y-m-d H:i:s');
         
         $dynamicDB->table('projects')->insert([
         'project_name' => $projectName,
         'asign_date' => $projectDate,
         'end_date' => $endDate,
         'project_leader' => $projectLeader,
         'team_member' => $teamNumber,
         'created_at' =>$date,
         'updated_at' => $date,
        ]);
        return response()->json(['message' => 'Projects details stored']);
    

    }
    return response()->json(['message' => 'pls login']);
    
}



public function addProfessionalTax(Request $request)
{
    $validatedData = $request->validate([
        'month' => 'required',
        'state' => 'required',
        'year' => 'required',

    ]); 
    session_start();
    if(isset($_SESSION["username"]) && isset($_SESSION["password"]) && isset($_SESSION["dbName"]))
    {
        $username = $_SESSION["username"];
        $password = $_SESSION["password"];
        $dbName = $_SESSION["dbName"];

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

           if (!Schema::connection('dynamic')->hasTable('professionalTaxes')) {
            Schema::connection('dynamic')->create('projects', function (Blueprint $table) {
                $table->id();
                $table->string('month');
                $table->year('year');
                $table->string('state');
                $table->string('brunch')->nullable();
                $table->timestamps(); 
                  
            });
           
        }
        $month = $validatedDate['month'];
        $year = $validatedData['year'];
        $state = $validatedData['state'];
        $date = Carbon::now()->timezone('Asia/Kolkata')->format('Y-m-d H:i:s');

        $dynamicDB->table('projects')->insert([
            'month' => $month,
            'year' => $year,
            'state' => $state,
            'created_at' =>$date,
            'updated_at' => $date,
           ]);
           return response()->json(['message' => 'Professional tax added successfully']);
       



    }
    return response()->json(['message' => 'pls login']);
}

public function calculateWeekend(Request $request)
{
    $year = 2023;
    $month = 9;  
    $firstDay = Carbon::create($year, $month)->startOfMonth();
    $lastDay = Carbon::create($year, $month)->endOfMonth();
    $weekendDays = 0;
    $currentDay = clone $firstDay;
    while ($currentDay <= $lastDay) {
        if ($currentDay->dayOfWeek === 6 || $currentDay->dayOfWeek === 0) {
            $weekendDays++;
        }
        $currentDay->addDay();
    }
    return response()->json(['weekendDays' => $weekendDays]);
}


public function taxInformation(Request $request)
{
    session_start();
}



public function salaryStructure(Request $request)
{
    $validatedData = $request->validate([

           'date'    => 'required|date'
    ]);
    session_start();
    if(isset($_SESSION["username"]) && isset($_SESSION["password"]) && isset($_SESSION["dbName"]))
    {
     $username = $_SESSION["username"];
     $password = $_SESSION["password"];
     $dbName = $_SESSION["dbName"];

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

    if (!Schema::connection('dynamic')->hasTable('tax_information')) {
        Schema::connection('dynamic')->create('tax_information', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id'); 
            $table->string('tax_code'); 
            $table->decimal('tax_rate', 5, 2); 
            $table->timestamps(); 

            $table->foreign('employee_id')->references('id')->on('employees');
              
        });
       
    }  
        if (!Schema::connection('dynamic')->hasTable('deductions')) {
            Schema::connection('dynamic')->create('deductions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->string('deduction_name'); 
            $table->decimal('deduction_amount', 10, 2);
            $table->timestamps(); 

            $table->foreign('employee_id')->references('id')->on('employees');
                  
            });
           
        }

             
             if (!Schema::connection('dynamic')->hasTable('bonuses')) {
            Schema::connection('dynamic')->create('bonuses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->string('bonus_name'); 
            $table->decimal('bonus_amount', 10, 2);
            $table->timestamps();

            $table->foreign('employee_id')->references('id')->on('employees');

                      
                });
               
            }





     
      if (!Schema::connection('dynamic')->hasTable('salaries')) {
        
        Schema::connection('dynamic')->create('salaries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->decimal('amount', 10, 2); 
            $table->enum('frequency', ['monthly', 'bi-weekly']); 
            $table->string('payment_method');
            $table->string('bank_account_number')->nullable(); 
            $table->string('bank_name')->nullable(); 
            $table->unsignedBigInteger('tax_information_id')->nullable();
            $table->unsignedBigInteger('deductions_id')->nullable();
            $table->unsignedBigInteger('bonuses_id')->nullable();
            $table->date('payroll_period'); 
            $table->date('date_of_payment'); 
            $table->decimal('net_salary', 10, 2);
            $table->timestamps(); 
            $table->foreign('employee_id')->references('id')->on('employees');
            $table->foreign('tax_information_id')->references('id')->on('tax_information');
            $table->foreign('deductions_id')->references('id')->on('deductions');
            $table->foreign('bonuses_id')->references('id')->on('bonuses');
              
        });
       
    }
    $date = $validatedData['date'];
    $start = Carbon::parse($date);
    $year = $start->year;
    $weekends = $this->calculateWeekends($start,$year);
    return response()->json(['message' => 'created']);


    }
    return response()->json(['message' => 'pls login']);
}




private function calculateWeekends($start,$year)
{ 
    $month = $start->format('n'); 
    $firstDay = Carbon::create($year, $month)->startOfMonth();
    $lastDay = Carbon::create($year, $month)->endOfMonth();
    $weekendDays = 0;

    $currentDay = clone $firstDay;
    while ($currentDay <= $lastDay) {
        if ($currentDay->dayOfWeek === 6 || $currentDay->dayOfWeek === 0) {
            $weekendDays++;
        }
        $currentDay->addDay();
    }
    return response()->json(['weekendDays' => $weekendDays]);
}



public function salaryComponent(Request $request)
{

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
        if (!$dynamicDB->getSchemaBuilder()->hasTable('salary_components')) {
            $dynamicDB->getSchemaBuilder()->create('salary_components', function (Blueprint $table) {
                $table->id();
                $table->enum('dearness_allowance',['yes','no'])->nullable();
                $table->enum('houserent_allowance',['yes','no'])->nullable();
                $table->enum('medical_allowance',['yes','no'])->nullable();
                $table->enum('bonus',['yes','no'])->nullable();
                $table->enum('Performance_linked_incentive',['yes','no'])->nullable();
                $table->enum('salary_arrears',['yes','no'])->nullable();
                $table->enum('travel_and_food_reimbursements',['yes','no'])->nullable();
                $table->enum('gratuity',['yes','no'])->nullable();
                $table->enum('professional_tax',['yes','no'])->nullable();
                $table->enum('tax_deduction_at_source',['yes','no'])->nullable();
                $table->enum('esic',['yes','no'])->nullable();
                $table->timestamps();
            });
        }

        $dynamicDB->table('salary_components')->insert([
            'dearness_allowance' => 'yes',
            'houserent_allowance' => 'yes',
            'medical_allowance' => 'no',
            'bonus' => 'yes',
            'Performance_linked_incentive' => 'no',
            'salary_arrears' => 'no',
            'travel_and_food_reimbursements' => 'yes',
            'gratuity' => 'yes',
            'professional_tax' =>'yes',
            'tax_deduction_at_source' => 'yes',
            'esic' => 'yes',
            'created_at' => $date,
            'updated_at' => $date,
        ]);
           
   return response()->json(['message' => 'successfully stored']);


    }
    return response()->json(['message' => 'session out,pls login']);

}

public function salaryComponents(Request $request)
{
      $validatedData = $request->validate([
         //Allowances
        'dearness_allowance' => 'nullable|boolean',
        'houserent_allowance' => 'nullable|boolean',
        'leave_travel_allowance' => 'nullable|boolean',
        'conveyance_allowance' => 'nullable|boolean',
        'medical_allowance' => 'nullable|boolean',
        'overtime_payment' => 'nullable|boolean',
        'bonus'  =>  'nullable|boolean',
        'performance_linked_incentive' => 'nullable|boolean',
        'salary_arrears' =>  'nullable|boolean',
        'travel_and_food_reimbursements' => 'nullable|boolean',
        'gratuity' => 'nullable|boolean',
        'professional_tax' => 'nullable|boolean',
        'tax_deduction_at_source' => 'nullable|boolean',  //
        'esic' => 'nullable|boolean',

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
        if (!$dynamicDB->getSchemaBuilder()->hasTable('salary_components')) {
            $dynamicDB->getSchemaBuilder()->create('salary_components', function (Blueprint $table) {
                $table->id();
                $table->boolean('dearness_allowance')->nullable();
                $table->boolean('houserent_allowance')->nullable();
                $table->boolean('leave_travel_allowance')->nullable();
                $table->boolean('conveyance_allowance')->nullable();
                $table->boolean('medical_allowance')->nullable();
                $table->boolean('overtime_payment')->nullable();
                $table->boolean('bonus')->nullable();
                $table->boolean('performance_linked_incentive')->nullable();
                $table->boolean('salary_arrears')->nullable();
                $table->boolean('travel_and_food_reimbursements')->nullable();
                $table->boolean('gratuity')->nullable();
                $table->boolean('professional_tax')->nullable();
                $table->boolean('tax_deduction_at_source')->nullable();
                $table->boolean('esic')->nullable();
                $table->timestamps();
            });
        }

        $dynamicDB->table('salary_components')->insert([
            'dearness_allowance' => $validatedData['dearness_allowance'],
            'houserent_allowance' => $validatedData['houserent_allowance'],
            'leave_travel_allowance' => $validatedData['leave_travel_allowance'],
            'conveyance_allowance' => $validatedData['conveyance_allowance'],
            'medical_allowance' => $validatedData['medical_allowance'],

            'overtime_payment' =>$validatedData['overtime_payment'],
            'bonus' => $validatedData['bonus'],
            'performance_linked_incentive'  => $validatedData['performance_linked_incentive'],
            'salary_arrears' => $validatedData['salary_arrears'],
            'travel_and_food_reimbursements' => $validatedData['travel_and_food_reimbursements'],
            'gratuity' => $validatedData['gratuity'],
            'professional_tax' => $validatedData['professional_tax'],
            'tax_deduction_at_source' => $validatedData['tax_deduction_at_source'],
            'esic' => $validatedData['esic'],
            'created_at' => $date,
            'updated_at' => $date,
        ]);
           
   return response()->json(['message' => 'successfully components are stored']);


    }
    return response()->json(['message' => 'session out,pls login']);

}
public function calculateSalary(Request $request)
{
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
        $employee_id = $validatedData['id'];
        $amount = $dynamicDB->table('salary')->where('id',$employee_id)->value('amount');

  } 
  return response()->json(['message' => 'session logout,pls login']);

}

public function empAllowance(Request $request)
{
  
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
        if (!$dynamicDB->getSchemaBuilder()->hasTable('emp_allowances')) {
            $dynamicDB->getSchemaBuilder()->create('emp_allowances', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('employee_id');
                $table->string('allowance_type');
                $table->decimal('amount', 10, 2); 
                $table->string('currency')->nullable();
                $table->date('effective_date');
                $table->date('end_date')->nullable();
                $table->string('frequency')->nullable();
                $table->boolean('taxable')->default(false);
                $table->text('comments')->nullable();
                $table->timestamps();
                $table->foreign('employee_id')->references('id')->on('employees');
               
            });
        }

        return response()->json(['message' => 'successfully created']);

    }
    return response()->json(['message' => 'session out,pls login']);

}

public function companyAllowance(Request $request)
{ 
    session_start();
    if(isset($_SESSION["username"]) && isset($_SESSION["password"]) && isset($_SESSION["dbName"]))
    {
        $username = $_SESSION["username"];
        $password = $_SESSION["password"];
        $dbName  =  $_SESSION["dbName"];
        
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
        
        if (!$dynamicDB->getSchemaBuilder()->hasTable('allowances')) {
            $dynamicDB->getSchemaBuilder()->create('allowances', function (Blueprint $table) {
                $table->id();
                $table->boolean('employee_pf')->default(false);
                $table->boolean('exit_amount')->default(false);
                $table->boolean('food_allowance')->default(false);
                $table->boolean('insentive_hf')->default(false);
                $table->boolean('insurance_amount')->default(false);
                $table->boolean('medical_reimbursement')->default(false);
                $table->boolean('night_shift')->default(false);
                $table->boolean('NIslubwise')->default(false);
                $table->boolean('NPF')->default(false);
                $table->boolean('option_petronet')->default(false);
                $table->boolean('optional_allowance_subsidy')->default(false);
                $table->boolean('shiftwise')->default(false);
                $table->boolean('subsidy')->default(false);
                $table->boolean('insentive 1')->default(false);
                $table->boolean('sodexd')->default(false);
                $table->timestamps();
            });
        }
        $dynamicDB->table('allowances')->insert([
          'employee_pf' => true,
          'exit_amount' => true,
          'food_allowance' => true,
          'insentive_hf' => true,
          'insurance_amount' => true,
          'medical_reimbursement' => true,
          'night_shift' => true,
          'NIslubwise' => true,
          'NPF'        => true,
          'option_petronet' => true,
          'optional_allowance_subsidy' => true,
          'shiftwise' => true,
          'subsidy' => false,
          'insentive 1' => true,
          'sodexd'   => false,
          'created_at' => $date,
          'updated_at' => $date,



        ]);
        return response()->json(['message' => 'created']);

    }
    return response()->json(['message' => 'session out,pls login']);
}




public function allEmpAttend(Request $request)
{
    session_start();
    if(isset($_SESSION["username"]) && isset($_SESSION["password"]) && isset($_SESSION["dbName"]))
    {
        $username = $_SESSION["username"];
        $password = $_SESSION["password"];
        $dbName =   $_SESSION["dbName"];

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
    $today = Carbon::now()->toDateString();
        $todayAttend = $dynamicDB->table('attendences')
        ->whereDate('created_at', $today)
        ->count();
        return response()->json(['message' => $todayAttend]);
    }
    return response()->json(['message' => 'session out,pls login']);
}

public function allEmpAbcent(Request $request)
{
    session_start();
    if(isset($_SESSION["username"]) && isset($_SESSION["password"]) && isset($_SESSION["dbName"]))
    {
        $username = $_SESSION["username"];
        $password = $_SESSION["password"];
        $dbName = $_SESSION["dbName"];
        $date = Carbon::now()->timezone('Asia/Kolkata')->format('Y=m-d H:i:s');

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
     $today = Carbon::now()->toDateString();
     $todayAttend = $dynamicDB->table('attendences')
     ->whereDate('created_at', $today)
     ->count();
    $totalEmployees = DB::connection('dynamic')
    ->table('employees')
    ->count();
    $totalAbsentToday = $totalEmployees - $todayAttend;
    return response()->json(['message' => 'total abcent ' . $totalAbsentToday]);

    }
    return response()->json(['message' => 'session out,pls login']);
}



public function latest_missAttend(Request $request)
{
    session_start();
    if(isset($_SESSION["username"]) && isset($_SESSION["password"]) && isset($_SESSION["dbName"]))
    {
        $username = $_SESSION["username"];
        $password = $_SESSION["password"];
        $dbName = $_SESSION["dbName"];
        $date = Carbon::now()->timezone('Asia/Kolkata')->format('Y=m-d H:i:s');

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
     $pendingAttend = $dynamicDB->table('miss_attendences')->where('status','pending')->get();
     if(count($pendingAttend) > 0 )
     {
        return response()->json(['message' => $pendingAttend]);
     }
     else
     {
       return response()->json(['message' => 'no pending attendence request']);
     }
     
     
    }
    return response()->json(['message' => 'session out,pls login']);
}



public function salaryStuct(Request $request)
{
    $validatedData = Validator::make($request->all(), [
        'ctc' => 'required',
        'basic%' => 'required',
         'da%'   => 'nullable', 
        'hra%'   => 'required',
        
        'leave travel allowance%' => 'nullable|default:0',
        'conveyance_allowance' => ['nullable','in:yes,no'],
        'medical'  =>  ['nullable','in:yes,no'],
       
    ]);
   
  

    
    session_start();
    if(isset($_SESSION["username"]) && isset($_SESSION["password"]) && isset($_SESSION["dbName"]))
    {
        $username = $_SESSION["username"];
        $password = $_SESSION["password"];
        $dbName = $_SESSION["dbName"];
        $date = Carbon::now()->timezone('Asia/Kolkata')->format('Y-m-d H:i:s');
        $ctc = $request->input('ctc');
        $basicInput = $request->input('basic%');
        $basic = $ctc*$basicInput/100;
      
        $basicSalary = $ctc *$request->input('basic%') / 100;
        $da = $request->input('da%',0); 
        $hra = $basicSalary * $request->input('hra%')/100; 
        $lta = $request->input('leave travel allowance%');
        $ca = $request->input('conveyance_allowance');
        $ca = ($ca == 'yes') ? 1600 : 0;  
        $medical = $request->input('medical');
        $medical = ($medical == 'yes') ? 1250 : 0; 
        $allowances = $da + $ca + $medical;

        
        $grossSalary = $basicSalary + $hra +  $allowances;

        //$netSalary = $grossSalary - ($professionalTax + $ppf + $incomeTax); 

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

     if (!$dynamicDB->getSchemaBuilder()->hasTable('salary_structure')) {
        $dynamicDB->getSchemaBuilder()->create('salary_structure', function (Blueprint $table) {
            $table->id();
            $table->string('ctc');
            $table->decimal('basic', 8, 2);
            $table->decimal('hra',8, 2);
            $table->decimal('conveyance_allowance', 8, 2);
            $table->decimal('medical_allowance', 8, 2);
            $table->decimal('basic_salary', 8, 2);
            $table->decimal('gross_salary', 8, 2);
            $table->decimal('allowances', 8, 2);
            $table->decimal('net_salary', 8, 2)->default(0);
            
            $table->timestamps();
        });
    }
    $dynamicDB->table('salary_structure')->insert([
        'ctc' => $ctc,
        'basic' => $basic,
        'hra'  => $hra,
        'conveyance_allowance' => $ca,
        'medical_allowance' => $medical,
        'basic_salary' => $basicSalary,
        'gross_salary' => $grossSalary,
        'allowances' => $allowances,
        'created_at' => $date,
        'updated_at' => $date,



    ]);
    return response()->json(['message' => 'successfully salary structure are calculated and stored ']);

    }
    return response()->json(['message' => 'session logout,pls login']);
}






public function createIndianStates(Request $request)
{
    session_start();
    if(isset($_SESSION["username"]) && isset($_SESSION["password"]) && isset($_SESSION["dbName"]))    
    {
       $username = $_SESSION['username'];
       $password = $_SESSION['password'];
       $dbName = $_SESSION['dbName'];
    }
    

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

   if (!$dynamicDB->getSchemaBuilder()->hasTable('indian_states')) {
    // If the table doesn't exist, create it
    $dynamicDB->getSchemaBuilder()->create('indian_states', function ($table) {
        $table->id();
        $table->string('name');
        $table->decimal('first_slub', 8, 2); 
        $table->decimal('second_slub', 8, 2);
        $table->decimal('third_slub', 8, 2);
        $table->decimal('fourth_slub', 8, 2);
        $table->timestamps();
    });


    $states = [
        'Andhra Pradesh',
        'Arunachal Pradesh',
        'Assam',
        'Bihar',
        'Chhattisgarh',
        'Goa',
        'Gujarat',
        'Haryana',
        'Himachal Pradesh',
        'Jharkhand',
        'Karnataka',
        'Kerala',
        'Madhya Pradesh',
        'Maharashtra',
        'Manipur',
        'Meghalaya',
        'Mizoram',
        'Nagaland',
        'Odisha',
        'Punjab',
        'Rajasthan',
        'Sikkim',
        'Tamil Nadu',
        'Telangana',
        'Tripura',
        'Uttar Pradesh',
        'Uttarakhand',
        'West Bengal'
    ];
     foreach ($states as $state) {
        $dynamicDB->table('indian_states')->insert([
            'name' => $state,
            'first_slub' => 0.00, 
            'second_slub' => 0.00,
            'third_slub' => 0.00,
            'fourth_slub' => 0.00
        ]);
    }


   return response()->json(['message' => 'Table create successfully']);

}
   return response()->json(['message' => 'sorry session out!']);


}


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
        
        'created_at' =>        $date,
        'updated_at' =>        $date,


    ]);


     return response()->json(['message' => 'table create successfully']);

    }
    return response()->json(['message' => 'sorry session out,pls login']);
}





public function assetAlloc(Request $request)
{
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

     if (!$dynamicDB->getSchemaBuilder()->hasTable('asset_allocation')) {
        $dynamicDB->getSchemaBuilder()->create('asset_allocation', function (Blueprint $table) {
            $table->id();
            $table->id('emp_id');
            $table->string('asset_code');
            $table->enum('isAlloc',['yes','no'])->default('yes');
            $table->string('allocation_by');
            $table->id('allocate_by_emp_id');
            $table->string('allocation_to_date');
            $table->string('allocation_from_date')->nullable();
            
            $table->enum('status',['active','inactive'])->default('inactive');
            
            $table->timestamps();
        });
    }



    }
}





public function allAssetRequest(Request $request)
{
    session_start();
    if(isset($_SESSION['username']) &&isset($_SESSION['password']) && isset($_SESSION['dbName']))
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
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
            'engine' => null,
        ]);

        $dynamicDB = DB::connection('dynamic');
        $totalRequest = $dynamicDB->table('asset_request')->where('status', 'pending')->count();
        $allRequest = $dynamicDB->table('asset_request')->where('status','pending')->get();

        return response()->json(['success' => true,'message' => $allRequest]);
        


    }
    return response()->json(['success' => false,'message' => 'session out! pls login']);

}





public function assetApprove(Request $request)
{
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
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
            'engine' => null,
        ]);

        $dynamicDB = DB::connection('dynamic');
      
      

    }
}





public function newUser(Request $request)
{
    $token = $request->user()->currentAccessToken();
        //  $username = $token['tokenable']['username'];    
        //  print_r($username);die;
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
        // 'company_code' => 'required',

        
    ]);

    // session_start();
    // if(isset($_SESSION['username']) && isset($_SESSION['password']) && isset($_SESSION['dbName']))
    // {}
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
        //  print_r($empIdCheck);die;
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
                // $table->string('db_name');
                $table->string('email');
                $table->string('mobile_number')->nullable();
                $table->boolean('read');
                $table->boolean('create');
                $table->boolean('edit');
                $table->boolean('delete');
                $table->enum('role', ['admin', 'subadmin']);
                // $table->string('mobile_number')->nullable();
                $table->timestamps();

                $table->foreign('emp_id')->references('id')->on('employees');
            });
        }

        $dynamicDB->table('permission')->insert([
            'name' => $request->name,
            'emp_id' => $request->emp_id,
            'username' => $request->username,
            'password' => bcrypt($request->password),
            // 'db_name'  => $dbName,
            'email'   => $request->email,
            'read' => $request->input('read', false),
            'create' => $request->input('create', false),
            'edit' => $request->input('edit', false),
            'delete' => $request->input('delete', false),
            'role' => $request->role,
            //  'mobile_number' => $request->input('mobile_number'),
            'created_at' => $date,
            'updated_at' => $date,
        ]);

        $company_access = new CompanyUserAccess;
        $company_access->name = $request->name;
        $company_access->emp_id = $request->emp_id;
        // $company_access->emp_code = $request->company_code;
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
      
           
        // Config::set('database.connections.dynamic', [
        //     'driver' => 'mysql',
        //     'host' => 'localhost',
        //     'database' => $dbName,
        //     'username' => $username,
        //     'password' => $password,
        //     'charset' => 'utf8mb4',
        //     'collation' => 'utf8mb4_unicode_ci',
        //     'prefix' => '',
        //     'strict' => true,
        //     'engine' => null,
        // ]);


        //    $dynamicDB = DB::connection('dynamic');

        //    $access = $dynamicDB->table('permission')->where('username',$request->username)->first();


        // Config::set('database.connections.dynamic', [
        //     'driver' => 'mysql',
        //     'host' => 'localhost',
        //     'database' => $dbName,
        //     'username' => $username,
        //     'password' => $password,
        //     'charset' => 'utf8mb4',
        //     'collation' => 'utf8mb4_unicode_ci',
        //     'prefix' => '',
        //     'strict' => true,
        //     'engine' => null,
        // ]);
      
        // DB::connection('dynamic');

            $access = CompanyUserAccess::where('username', $request->username)->
            where('company_code', $request->company_code)->first();
            // print_r($access) ;die;
            if ($access  && Hash::check($request->password, $access->password)) {
                // $token = $access->createToken('dynamic-database-permission', ['dbname' => $dbName, 'username' => $username, 'password' => $password])->plainTextToken;
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
        // $companyCode = $accessUser->company_code;
        // $mobile = $accessUser->mobile_number;
        // $user = User::where('company_code',$companyCode)->first();
        // $dbName = $user->dbName;
        // $dbUsername = $user->username;
        // $dbPass = $user->dbPass;
        //  print_r($accessUser->emp_id);die;

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

    // Config::set('database.connections.dynamic', [
    //     'driver' => 'mysql',
    //     'host' => 'localhost',
    //     'database' => $dbName,
    //     'username' => $username,
    //     'password' => $password,
    //     'charset' => 'utf8mb4',
    //     'collation' => 'utf8mb4_unicode_ci',
    //     'prefix' => '',
    //     'strict' => true,
    //     'engine' => null,
    // ]);
    // $dynamicDB = DB::connection('dynamic');
    // $dynamicDB->table('permission')

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
      //  \Log::error("Twilio Exception: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    } catch (\Exception $e) {
      //  \Log::error("Exception: " . $e->getMessage());
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
        // 'mobile_number' => 'required',
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






public function test(Request $request)
{

    // $dynamicDB = DB::connection('dynamic');
   
    $token = $request->user()->currentAccessToken();
    // $token = $request->user()->currentAccessToken();
    // $username = $token['tokenable']['name'];    
    print_r($token);die;
    
    if (!$token) {
        return response()->json(['success' => false, 'message' => 'Token not found!']);
    }

    
    $username = $token['tokenable']['username'];
    $password = $token['tokenable']['dbPass'];
    $dbName = $token['tokenable']['dbName'];
    $maxEmp = $token['tokenable']['total'];
}





public function allSession(Request $request)
{
    $allSessionData = Session::all();
    return response()->json([$allSessionData]); 
}

public function getRootToken(Request $request)
{
    $accessToken = $request->bearerToken();
    if(isset($accessToken) && !empty($accessToken))
    {
        return response()->json(['success' => true,'message' => 'Token Found','data' => $accessToken],200);

    }
    else{
        return response()->json(['success'=>false,'message'=>'Token not found'],404);
    }
}

public function AssignBranchDeptToUsers(Request $request)
{
    $token = $request->user()->currentAccessToken();
    if(!$token)
    {
        return response()->json(['success' => false, 'message' => 'Invalid Token'], 422);
    }
    else{
        $validatedData = $request->validate([
            'branch_id' => 'required',
            'dept_id' => 'required',
            'emp_id' => 'required',
        ]);
        $code = $token['tokenable']['company_code'];

    }

}




public function empApproveByAdmin(Request $request)
{
    try{
        $token = $request->user()->currentAccessToken();
        if(!$token)
        {
            return response()->json(['success'=>false,'message'=>'invalid token'],401);
        }
        //   print_r($token);die; 
        $tokenRole = $token['tokenable']['role'];
        $status = $token['tokenable']['status'];
        $code = $token['tokenable']['company_code'];
        $company = User::where('company_code', $code)->first();
        $moduleId = 3;
        $empModule = CompanyModuleAccess::where('company_code',$code)->where('module_id',$moduleId)
        ->where('status',$status)->first();
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


               
        //     Config::set('database.connections.dynamic', [
        //         'driver' => 'mysql',
        //         'host' => 'localhost',
        //         'database' => $dbName,
        //         'username' => $username,
        //         'password' => $password,
        //         'charset' => 'utf8mb4',
        //         'collation' => 'utf8mb4_unicode_ci',
        //         'prefix' => '',
        //         'strict' => true,
        //         'engine' => null,
        //     ]);
        //   $dynamicDB = DB::connection('dynamic');
        //   if ($dynamicDB->table('company_employee')->where('id', $emp_id)->exists()) 
        //   {
        //       $dynamicDB->table('company_employee')->where('id',$emp_id)->update([
        //     'status' => $status,
        //     'updated_at' => $date,
        //     ]);
        //     return response()->json(['success' => true,'message' => 'employee details accepted by admin'],404);

        //   }
        // return response()->json(['success' => false,'message' => 'emp_id is not found'],404);

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
    $empModule = CompanyModuleAccess::where('company_code',$code)->where('module_id',$moduleId)
    ->where('status',$status)->first();
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
    try{
     $token = $request->user()->currentAccessToken();
     $code = $token['tokenable']['company_code'];
    
     $date = Carbon::now()->timezone('Asia/kolkata')->format('Y-m-d H:i:s');
     $validatedData = $request->validate([
        'role_name' => 'required',
        'emp_id'   =>  'required',
       
    ]);
    $role = Role::create([
        'name' => $request->role_name,
        'company_code' => $code,
        'emp_id' => $request->emp_id,
        'guard_name' => 'api',
        'created_at' => $date,
        'updated_at' => $date,
    ]);

    return response()->json(['success'=>true,'message' => 'Role assigned successfully'], 200);
    }
    catch(\Exception $e)
    {   
      return response()->json(['success'=>false,'message' => 'An error occurred. Please try again.',$e->getMessage()], 500);
    }
    
    
}




public function allRoleData(Request $request)
{
    try{
     $token = $request->user()->currentAccessToken();
     $code = $token['tokenable']['company_code'];
     $allData = Role::where('company_code',$code)->get();
     if($allData->isEmpty())
     {
        return response()->json(['success'=>false,'message'=> 'no data found'],404);
     }
     return response()->json(['success'=>true,'data'=>$allData]);
    }
    catch(\Exception $e)
    {
        return response()->json(['success'=>false,'message' => 'An error occurred. Please try again.',$e->getMessage()],500);
    }
 
}




public function addPermission(Request $request)
{
   try
   {
    $token = $request->user()->currentAccessToken();
    if(!$token)
    {
        return response()->json(['success'=>false,'message'=>'invalid token'],401);
    }
    $tokenRole = $token['tokenable']['role'];
    if($tokenRole == 'admin')
    {
    $code = $token->tokenable->company_code;
    
    //  echo $token->tokenable->id;die;
    $validatedData = $request->validate([
        'emp_id' => 'required',
        'modules_name' => 'required',
    ]);
    // $empId = $token->tokenable->id;
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
    }
     return response()->json(['success' => false,'message' => 'you have no permission'],403);

   }
    catch(\Exception $e)
    {
        return response()->json(['success'=>false,'message' => 'An error occurred. Please try again.',$e->getMessage()],500);
    }
 

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
    try{
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
    catch(\Exception $e)
   {
    return response()->json(['success'=>false,'message' => 'An error occurred. Please try again.',$e->getMessage()],500);
   }


}









}
