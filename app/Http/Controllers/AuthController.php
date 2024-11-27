<?php

namespace App\Http\Controllers;

use App\Jobs\SendEmailWelcome;
use App\Models\User;
use App\Jobs\SendEmailForgotPassword;
use App\Jobs\SendEmailVerification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Facades\JWTAuth;
use Exception;
use function Termwind\render;


class AuthController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api',
            [
                'except' => [
                    'login',
                    'register',
                    'refresh',
                    'verifyEmail',
                    'forgotPassword',
                    'resetPassword',
                    'getGoogleSignInUrl',
                    'loginGoogleCallback',
                    'updateProfile',
                    'checkTokenResetPassword'
                ]
            ]);
    }

    public function register()
    {
        $validator = Validator::make(request()->all(), [
            'first_name' => 'required|string|max:50',
            'last_name' => 'required|string|max:50',
            'email' => 'required|string|email|max:100|unique:users',
            'password' => 'required|string|min:8',
            'role' => 'required|in:admin,instructor,student',
        ], [
            'first_name.required' => __('messages.first_name_required'),
            'first_name.string' => __('messages.first_name_string'),
            'first_name.max' => __('messages.first_name_max'),
            'last_name.required' => __('messages.last_name_required'),
            'last_name.string' => __('messages.last_name_string'),
            'last_name.max' => __('messages.last_name_max'),

            'email.required' => __('messages.email_required'),
            'email.string' => __('messages.email_string'),
            'email.email' => __('messages.email_email'),
            'email.max' => __('messages.email_max'),
            'email.unique' => __('messages.email_unique'),

            'password.required' => __('messages.password_required'),
            'password.string' => __('messages.password_string'),
            'password.min' => __('messages.password_min'),

            'role.required' => __('messages.role_required'),
            'role.in' => __('messages.role_in'),

        ]);
        if ($validator->fails()) {
            return formatResponse(STATUS_FAIL, '', $validator->errors(), __('messages.validation_error'));
        }
        $currentUser = auth()->user();
        $role = request()->input('role');

        if ($currentUser) {
            if ($currentUser->role !== 'admin') {
                return formatResponse(STATUS_FAIL, '', '', __('messages.validation_error_role'));
            }
        } else {
            if (!in_array($role, ['instructor', 'student'])) {
                return formatResponse(STATUS_FAIL, '', '', __('messages.validation_error_role'));
            }
        }
        $user = User::create([
            'first_name' => request()->input('first_name'),
            'last_name' => request()->input('last_name'),
            'email' => request()->input('email'),
            'password' => Hash::make(request()->input('password')),
            'role' => $role,
            'verification_token' => Str::random(60),
        ]);

        SendEmailVerification::dispatch($user);
        return formatResponse(STATUS_OK, $user, '', __('messages.user_signup_success'));
    }

    public function getGoogleSignInUrl(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'role' => 'required|in:instructor,student,admin',
            ], [
                'role.required' => __('messages.role_required'),
                'role.in' => __('messages.role_in'),
            ]);

            if ($validator->fails()) {
                return formatResponse(STATUS_FAIL, '', $validator->errors(), __('messages.validation_error'));
            }
            $role = $request->input('role');

            $url = Socialite::driver('google')->stateless()->with(['state' => http_build_query(['role' => $role])])
                ->redirect()->getTargetUrl();
            return formatResponse(STATUS_OK, ['url' => $url], '', __('messages.get_url_ok'), CODE_OK);
        } catch (\Exception $exception) {
            return $exception;
        }
    }

    public function loginGoogleCallback(Request $request)
    {
        try {
            $state = $request->input('state');
            parse_str($state, $result);
            $googleUser = Socialite::driver('google')->stateless()->user();

            $role = $result['role'] ?? User::ROLE_STUDENT;

            $user = User::where('email', $googleUser->email)->first();
            if ($user) {
                if (!$token = auth('api')->login($user)) {
                    return formatResponse(STATUS_FAIL, '', '', __('messages.create_token_failed'), CODE_FAIL);
                }
                $redirectUrl = env('URL_DOMAIN') . "/google/call-back/{$token}";
                return redirect($redirectUrl);
//                $refreshToken = $this->createRefreshToken();
//                return formatResponse(STATUS_OK, $user, '', __('messages.user_login_success'), CODE_OK, $token, $refreshToken);
            }

            $user = User::create(
                [
                    'avatar' => $googleUser->avatar,
                    'email' => $googleUser->email,
                    'last_name' => $googleUser->name,
                    'role' => $role,
                    'status' => User::USER_ACTIVE,
                    'email_verified' => $googleUser->user['email_verified'],
                    'provider' => 'google',
                    'provider_id' => $googleUser->id,
                    'password' => Hash::make(Str::random(12)),
                ]
            );

            if (!$token = auth('api')->login($user)) {
                return formatResponse(STATUS_FAIL, '', '', __('messages.create_token_failed'), CODE_FAIL);
            }
//            $refreshToken = $this->createRefreshToken();
            SendEmailWelcome::dispatch($user);
            $redirectUrl = env('URL_DOMAIN') . "/google/call-back/{$token}";
            return redirect($redirectUrl);
//            return formatResponse(STATUS_OK, $user, '', __('messages.user_login_success'), CODE_OK, $token, $refreshToken);
        } catch (\Exception $exception) {
            return formatResponse(STATUS_FAIL, '', $exception, __('messages.login_google_success'), CODE_BAD);
        }
    }

    public function verifyEmail($token)
    {
        $user = User::where('verification_token', $token)->first();
        if (!$user) {
            return formatResponse(STATUS_FAIL, '', '', __('messages.url_not_found'), CODE_NOT_FOUND);
        }
        $user->email_verified = true;
        $user->status = USER::USER_ACTIVE;
        $user->verification_token = null;
        $user->save();
        return formatResponse(STATUS_OK, $user, '', __('messages.verify_email_ok'));
    }


    public function forgotPassword()
    {
        $validator = Validator::make(request()->all(), [
            'email' => 'required|string|email|max:100',
        ], [
            'email.required' => __('messages.email_required'),
            'email.string' => __('messages.email_string'),
            'email.email' => __('messages.email_email'),
            'email.max' => __('messages.email_max'),
            'email.unique' => __('messages.email_unique'),
        ]);

        if ($validator->fails()) {
            return formatResponse(STATUS_FAIL, '', $validator->errors(), __('messages.validation_error'));
        }

        $user = User::where('email', request()->input('email'))->first();
        if (!$user) {
            return formatResponse(STATUS_FAIL, '', '', __('messages.email_exist'));
        }
        $user->reset_token = Str::random(60);

        if (!$user->save()) {
            return formatResponse(STATUS_FAIL, '', '', __('messages.error_save'));
        }
        SendEmailForgotPassword::dispatch($user);
        return formatResponse(STATUS_OK, '', '', __('messages.email_send_ok'), CODE_OK);
    }

    public function checkTokenResetPassword($token)
    {
        $user = User::where('reset_token', $token)->first();
        if (!$user) {
            return formatResponse(STATUS_FAIL, '', '', 'Đường dẫn đổi mật khẩu sai.', CODE_NOT_FOUND);
        }
        return formatResponse(STATUS_OK, '', '', 'Đường dẫn đổi mật khẩu đúng.');
    }

    public function resetPassword($token)
    {
        $user = User::where('reset_token', $token)->first();
        if (!$user) {
            return formatResponse(STATUS_FAIL, '', '', __('messages.url_not_found'), CODE_NOT_FOUND);
        }

        $validator = Validator::make(request()->all(), [
            'password' => 'required|string|min:8',
        ], [
            'password.required' => __('messages.password_required'),
            'password.string' => __('messages.password_string'),
            'password.min' => __('messages.password_min'),
        ]);

        if ($validator->fails()) {
            return formatResponse(STATUS_FAIL, '', $validator->errors(), __('messages.validation_error'));
        }

        $user->reset_token = null;
        $user->password = Hash::make(request()->input('password'));
        $user->save();
        return formatResponse(STATUS_OK, $user, '', 'Thay đổi mật khẩu thành công', CODE_OK);
    }

    public function login()
    {
        $validator = Validator::make(request()->all(), [
            'email' => 'required|string|email|max:100',
            'password' => 'required|string|min:8',
        ], [
            'email.required' => __('messages.email_required'),
            'email.string' => __('messages.email_string'),
            'email.email' => __('messages.email_email'),
            'email.max' => __('messages.email_max'),
            'email.unique' => __('messages.email_unique'),

            'password.required' => __('messages.password_required'),
            'password.string' => __('messages.password_string'),
            'password.min' => __('messages.password_min'),
        ]);

        if ($validator->fails()) {
            return formatResponse(STATUS_FAIL, '', $validator->errors(), __('messages.validation_error'));
        }

        $user = User::where(['email' => request()->input('email')])->first();
        if (!$user) {
            return formatResponse(STATUS_FAIL, '', '', __('messages.email_exist'));
        }
        if (!$user->email_verified) {
            return formatResponse(STATUS_FAIL, '', '', __('messages.email_not_verified'));
        }
        $credentials = request(['email', 'password']);
        if (!$token = auth('api')->attempt($credentials)) {
            return formatResponse(STATUS_FAIL, '', '', __('messages.password_incorrect'));
        }
        $refreshToken = $this->createRefreshToken();
        return formatResponse(STATUS_OK, $user, '', __('messages.user_login_success'), CODE_OK, $token, $refreshToken);
    }

    public function logout()
    {
        auth('api')->logout();
        return formatResponse(STATUS_OK, '', '', __('messages.user_logout_success'));
    }

    public function profile()
    {
        $user = auth()->user();
        return formatResponse(STATUS_OK, $user, '', 'Lấy thông tin thành công');
    }


    public function refresh()
    {
        try {
            $refresh_token = request()->input('refresh_token');
            if (!$refresh_token) {
                return formatResponse(STATUS_FAIL, '', '', 'Vui lòng nhập refresh token');
            }

            $decode = JWTAuth::getJWTProvider()->decode($refresh_token);

            // Invalidate current access token
//            auth('api')->invalidate();

            $user = User::find($decode['user_id']);
            if (!$user) {
                return formatResponse(STATUS_FAIL, '', '', 'Tài khoản không tồn tại');
            }
            // Generate new tokens
            $token = auth('api')->login($user);
            $refreshToken = $this->createRefreshToken();

            return formatResponse(STATUS_OK, $user, '', 'Refresh access token thành công', CODE_OK, $token,
                $refreshToken);

        } catch (TokenExpiredException $e) {
            return formatResponse(STATUS_FAIL, '', '', 'Refresh token đã hết hạn');
        } catch (TokenInvalidException $e) {
            return formatResponse(STATUS_FAIL, '', '', 'Refresh token không hợp lệ');
        } catch (Exception $e) {
            return formatResponse(STATUS_FAIL, '', '', 'Lỗi xảy ra trong quá trình làm mới token');
        }
    }

    public function updateProfile()
    {
        $user = auth()->user();
        if (!$user) {
            return formatResponse(STATUS_FAIL, '', '', __('messages.user_not_found'));
        }
        $validator = Validator::make(request()->all(), [
            'first_name' => 'string|max:50',
            'last_name' => 'string|max:50',
            'email' => 'string|email|max:100|unique:users,email,' . $user->id,
            'phone_number' => 'regex:/^[0-9]+$/',
            'address' => 'string',
            'contact_info' => 'string',
            'gender' => 'nullable|string|in:male,female,unknown',
            'date_of_birth' => 'nullable|date',
            'password' => 'string|min:8',
        ], [
            'first_name.required' => __('messages.first_name_required'),
            'first_name.string' => __('messages.first_name_string'),
            'first_name.max' => __('messages.first_name_max'),
            'last_name.required' => __('messages.last_name_required'),
            'last_name.string' => __('messages.last_name_string'),
            'last_name.max' => __('messages.last_name_max'),

            'phone_number.regex' => __('messages.phone_number_update'),
            'address.string' => __('messages.address_update'),
            'contact_info.string' => __('messages.contactInfo_update'),

            'email.required' => __('messages.email_required'),
            'email.string' => __('messages.email_string'),
            'email.email' => __('messages.email_email'),
            'email.max' => __('messages.email_max'),
            'email.unique' => __('messages.email_unique'),

            'password.required' => __('messages.password_required'),
            'password.string' => __('messages.password_string'),
            'password.min' => __('messages.password_min'),

            'gender.in' => __('messages.gender_invalid'),
            'date_of_birth.date' => __('messages.date_of_birth_invalid'),
        ]);

        if ($validator->fails()) {
            return formatResponse(STATUS_FAIL, '', $validator->errors(), __('messages.validation_error'));
        }
        $data = request()->except(['role', 'email_verified', 'reset_token', 'status']);
        if (isset($data['password'])) {
            $data['password'] = Hash::make(request()->input('password'));
        }
        if (!$user->update($data)) {
            return formatResponse(STATUS_FAIL, '', '', __('messages.update_fail'));
        }
        return formatResponse(STATUS_OK, $user, '', __('messages.update_success'));
    }

    public function adminUpdateUser()
    {
        $validator = Validator::make(request()->all(), [
            'user_id' => 'required|integer',
            'username' => 'string|max:50|unique:users',
            'email' => 'string|email|max:100|unique:users',
            'password' => 'string|min:8',
            'role' => 'in:admin,instructor,student',
            'status' => 'in:active,inactive',
        ]);

        if ($validator->fails()) {
            return formatResponse(STATUS_FAIL, '', $validator->errors(), 'Xác thực thất bại');
        }
        $data = request()->all();

        $user = User::where('id', $data['user_id'])->first();
        if (!$user) {
            return formatResponse(STATUS_FAIL, '', '', 'Tài khoản không tồn tại');
        }

        if (isset($data['password'])) {
            $data['password'] = Hash::make(request()->input('password'));
        }

        if (!$user->update($data)) {
            return formatResponse(STATUS_FAIL, '', '', 'Cập nhật thông tin thất bại');
        }
        return formatResponse(STATUS_OK, $user, '', 'Cập nhật thông tin thành công');
    }


    public function deleteUser($id)
    {
        $user = User::where('id', $id)->first();

        if (!$user) {
            return formatResponse(STATUS_FAIL, '', '', __('messages.user_not_found'));
        }

        if ($user->delete()) {
            $user->is_deleted = User::STATUS_DELETED;
            $user->save();
            return formatResponse(STATUS_OK, '', '', 'Xóa tài khoản thành công');
        }
        return formatResponse(STATUS_FAIL, '', '', 'Xóa tài khoản thất bại');

    }

    public function restoreUser($id)
    {
        $user = User::withTrashed()->find($id);
        if (!$user) {
            return formatResponse(STATUS_FAIL, '', '', 'Tài khoản không tồn tại');
        }
        if ($user->trashed()) {
            $user->restore();
            $user->is_deleted = User::STATUS_DEFAULT;
            $user->save();
            return formatResponse(STATUS_OK, $user, '', 'Khôi phục thành công');
        }
        return formatResponse(STATUS_FAIL, '', '', 'Khôi phục thất bại');
    }

    public function forceDeleteUser($id)
    {
        $user = User::withTrashed()->find($id);

        if (!$user) {
            return formatResponse(STATUS_FAIL, '', '', 'Tài khoản không tồn tại');
        }

        // Xóa hoàn toàn khỏi DB
        $user->forceDelete();
        return formatResponse(STATUS_OK, $user, '', 'Xóa hoàn toàn thành công');
    }


    private function createRefreshToken()
    {
        $data = [
            'user_id' => auth('api')->user()->id,
            'random' => rand() . time(),
            'exp' => time() + config('jwt.refresh_ttl') * 60,
        ];
        $refreshToken = JWTAuth::getJWTProvider()->encode($data);
        return $refreshToken;
    }

    public function uploadImage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);
        if ($validator->fails()) {
            return formatResponse(STATUS_FAIL, '', $validator->errors(), 'xác thực thất bại');
        }
        $user = JWTAuth::parseToken()->authenticate();
        if ($user->avatar) {
            $currentFilePath = str_replace(env('URL_IMAGE_S3'), '', $user->avatar);
            if (Storage::disk('s3')->exists($currentFilePath)) {
                Storage::disk('s3')->delete($currentFilePath);
            }
        }
        $path = $request->file('image')->storePublicly('image-user');
        if ($path) {
            $user->avatar = env('URL_IMAGE_S3') . $path;
            $user->save();
            return formatResponse(STATUS_OK, $user, '', 'Cập nhật hình ảnh thành công', CODE_OK);
        }
        return formatResponse(STATUS_FAIL, '', '', 'Cập nhật hình ảnh thất bại', CODE_BAD);
    }


    protected function respondWithToken($token, $refreshToken)
    {
        return response()->json([
            'access_token' => $token,
            'refresh_token' => $refreshToken,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60
        ]);
    }


}
