<?php
namespace App\Http\Controllers\Auth;

use App\Http\Connector\Auth\AuthSocialServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Utils\JwtUtils;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;


/**
 * Class SocialAuthController
 * Handles social login requests from clients (Flutter).
 */
class SocialAuthController extends Controller
{
    public function __construct(private readonly AuthSocialServiceInterface $service) {}
    

    /**
     * POST /api/auth/social-login
     * Body: { provider: 'google'|'facebook', token?: string, id_token?: string }
     */
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'provider' => 'required|in:google,facebook',
            'token'    => 'nullable|string',   // Google/FB access_token
            'id_token' => 'nullable|string',   // Google id_token (optional)
        ]);

        [$jwt, $user] = $this->service->loginWithProvider(
            $data['provider'],
            $data['token'] ?? null,
            $data['id_token'] ?? null
        );

         return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data'    => [
                'user'  => $user,
                'token' => $jwt,
                'expires_at' => $jwt['expires_at'],
            ]
        ]);
    }
        public function facebook(Request $req): JsonResponse
    {
        $accessToken = (string)$req->input('access_token', '');
        if ($accessToken === '') return response()->json([
            'success' => false, 'message' => 'access_token required'
        ], 422);

        $appId  = env('1945764356371445');
        $secret = env('c057f5c275bc3645072f564858b2356c');

        // 1) Verify token belongs to your app
        $debug = Http::get('https://graph.facebook.com/debug_token', [
            'input_token'  => $accessToken,
            'access_token' => $appId.'|'.$secret,
        ])->json();
        $isValid = $debug['data']['is_valid'] ?? false;
        if (!$isValid || ($debug['data']['app_id'] ?? null) != $appId) {
            return response()->json(['success' => false, 'message' => 'Invalid Facebook token'], 401);
        }

        // 2) Fetch profile
        $profile = Http::get('https://graph.facebook.com/me', [
            'fields' => 'id,name,email,picture.width(200)',
            'access_token' => $accessToken
        ])->json();

        $fbId  = (string)($profile['id'] ?? '');
        $name  = (string)($profile['name'] ?? 'FB User');
        $email = (string)($profile['email'] ?? '');

        // 3) Upsert user
        $userId = DB::table('users')->where('provider','facebook')
                                    ->where('provider_id', $fbId)->value('id');
        if (!$userId && $email) {
            $userId = DB::table('users')->where('email',$email)->value('id');
            if ($userId) {
                DB::table('users')->where('id',$userId)->update([
                    'provider'=>'facebook','provider_id'=>$fbId,'name'=>$name,'updated_at'=>now()
                ]);
            }
        }
        if (!$userId) {
            $userId = DB::table('users')->insertGetId([
                'name'=>$name,'email'=>$email ?: null,
                'provider'=>'facebook','provider_id'=>$fbId,
                'password'=>bcrypt(str()->random(40)),
                'created_at'=>now(),'updated_at'=>now()
            ]);
        }
        $user = DB::table('users')->where('id',$userId)->first();
        $jwt = JwtUtils::issue($user);

        return response()->json(['success'=>true,'data'=>['token'=>$jwt,'user'=>[
            'id'=>$user->id,'name'=>$user->name,'email'=>$user->email
        ]]], 200);
    }
}
