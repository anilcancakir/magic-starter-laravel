<?php

namespace AnilcanCakir\MagicStarter\Http\Controllers;

use AnilcanCakir\MagicStarter\Http\Requests\ConfirmPasswordRequest;
use AnilcanCakir\MagicStarter\Http\Requests\DestroyOtherSessionsRequest;
use AnilcanCakir\MagicStarter\Http\Resources\SessionResource;
use AnilcanCakir\MagicStarter\Support\TeamKey;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Manages Sanctum personal access tokens as user sessions.
 */
class SessionController
{
    /**
     * Retrieve all active sessions.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        return SessionResource::collection($request->user()->tokens);
    }

    /**
     * Revoke a specific session.
     *
     * Requires password confirmation to prevent unauthorized revocation.
     */
    public function destroy(ConfirmPasswordRequest $request, string $tokenId): JsonResponse
    {
        $token = TeamKey::looksLikeOne($tokenId)
            ? $request->user()->tokens()->where('id', $tokenId)->first()
            : null;

        if (! $token) {
            abort(404, (string) __('magic-starter::auth.sessions.not_found'));
        }

        $token->delete();

        return response()->json([
            'data' => null,
            'message' => __('magic-starter::auth.sessions.revoked'),
        ]);
    }

    /**
     * Revoke all other sessions.
     *
     * Requires password confirmation to prevent unauthorized bulk revocation.
     */
    public function destroyOther(DestroyOtherSessionsRequest $request): JsonResponse
    {
        $user = $request->user();
        $currentId = $user->currentAccessToken()->id;

        $user->tokens()
            ->where('id', '!=', $currentId)
            ->delete();

        return response()->json([
            'data' => null,
            'message' => __('magic-starter::auth.sessions.others_revoked'),
        ]);
    }
}
