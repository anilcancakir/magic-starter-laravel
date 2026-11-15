<?php

namespace AnilcanCakir\MagicStarter\Http\Controllers;

use AnilcanCakir\MagicStarter\Contracts\SendsOtpCodes;
use AnilcanCakir\MagicStarter\Contracts\VerifiesOtpCodes;
use AnilcanCakir\MagicStarter\Http\Controllers\Concerns\AuthenticatesUsers;
use AnilcanCakir\MagicStarter\Http\Requests\SendOtpRequest;
use AnilcanCakir\MagicStarter\Http\Requests\VerifyOtpRequest;
use AnilcanCakir\MagicStarter\MagicStarter;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

/**
 * Handle OTP send and verify operations.
 */
class OtpController
{
    use AuthenticatesUsers;

    /**
     * Send an OTP code to the provided phone number.
     *
     * @param  SendOtpRequest  $request  The validated OTP send request.
     * @return JsonResponse 200 with success message.
     */
    public function send(SendOtpRequest $request): JsonResponse
    {
        $phone = $request->validated('phone');
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        Cache::put('otp_' . $phone, $code, 300); // 5 minutes TTL

        app(SendsOtpCodes::class)->send($phone, $code);

        return response()->json([
            'message' => __('magic-starter::auth.otp.sent'),
        ]);
    }

    /**
     * Verify the provided OTP code and authenticate the user.
     *
     * @param  VerifyOtpRequest  $request  The validated OTP verify request.
     * @return JsonResponse 200 with user and token, 401 on invalid OTP, 404 if user not found.
     */
    public function verify(VerifyOtpRequest $request): JsonResponse
    {
        $phone = $request->validated('phone');
        $code = $request->validated('code');

        $isValid = app(VerifiesOtpCodes::class)->verify($phone, $code);

        if (! $isValid) {
            return response()->json([
                'message' => __('magic-starter::auth.otp.invalid'),
            ], 401);
        }

        $userModel = MagicStarter::userModel();
        $user = $userModel::query()->where('phone', $phone)->first();

        if (! $user) {
            return response()->json([
                'message' => __('magic-starter::auth.otp.user_not_found'),
            ], 404);
        }

        return $this->authenticatedResponse($user, $request, $this->createAuthToken($user, $request, storeDeviceInfo: true));
    }
}
