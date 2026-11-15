<?php

namespace AnilcanCakir\MagicStarter\Http\Controllers;

use AnilcanCakir\MagicStarter\Contracts\ConfirmsTwoFactorAuthentication;
use AnilcanCakir\MagicStarter\Contracts\DisablesTwoFactorAuthentication;
use AnilcanCakir\MagicStarter\Contracts\EnablesTwoFactorAuthentication;
use AnilcanCakir\MagicStarter\Http\Requests\ConfirmTwoFactorRequest;
use AnilcanCakir\MagicStarter\Http\Requests\DisableTwoFactorRequest;
use AnilcanCakir\MagicStarter\Http\Requests\EnableTwoFactorRequest;
use Illuminate\Http\JsonResponse;

class TwoFactorAuthenticationController
{
    /**
     * Enable two factor authentication for the user.
     */
    public function store(EnableTwoFactorRequest $request): JsonResponse
    {
        $user = $request->user();

        $data = app(EnablesTwoFactorAuthentication::class)->enable($user);

        return response()->json([
            'data' => [
                'secret' => $data['secret'],
                'qr_url' => $data['qr_url'],
                'qr_svg' => $user->twoFactorQrCodeSvg(),
                'recovery_codes' => $data['recovery_codes'],
            ],
            'message' => __('magic-starter::auth.two_factor.enabled'),
        ], 200);
    }

    /**
     * Confirm two factor authentication for the user.
     */
    public function confirm(ConfirmTwoFactorRequest $request): JsonResponse
    {
        $user = $request->user();

        app(ConfirmsTwoFactorAuthentication::class)->confirm(
            $user,
            (string) $request->validated('code'),
        );

        return response()->json([
            'data' => null,
            'message' => __('magic-starter::auth.two_factor.confirmed'),
        ], 200);
    }

    /**
     * Disable two factor authentication for the user.
     */
    public function destroy(DisableTwoFactorRequest $request): JsonResponse
    {
        app(DisablesTwoFactorAuthentication::class)->disable($request->user());

        return response()->json([
            'data' => null,
            'message' => __('magic-starter::auth.two_factor.disabled'),
        ], 200);
    }
}
