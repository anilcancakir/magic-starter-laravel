<?php

namespace AnilcanCakir\MagicStarter\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Manages the analytics opt-out of the authenticated user.
 *
 * The preference is a boolean on the user's own row, so it follows the account across devices.
 * It only records the choice: the client decides what to stop sending.
 */
class AnalyticsPreferenceController
{
    /**
     * Show whether the authenticated user has opted out of analytics.
     */
    public function show(Request $request): JsonResponse
    {
        return $this->status((bool) $request->user()->analytics_opt_out);
    }

    /**
     * Update the analytics opt-out of the authenticated user.
     */
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'opted_out' => [
                'required',
                'boolean',
            ],
        ]);

        // 1. The `boolean` rule also passes 1, '1', 0 and '0', so the value is read as a boolean here too.
        $optedOut = filter_var($validated['opted_out'], FILTER_VALIDATE_BOOLEAN);

        // 2. Forced, because the host's user model may not list the column in `$fillable`.
        $user = $request->user();
        $user->forceFill(['analytics_opt_out' => $optedOut])->save();

        // 3. Return the stored value in the same shape as show().
        return $this->status($optedOut);
    }

    private function status(bool $optedOut): JsonResponse
    {
        return response()->json(['opted_out' => $optedOut]);
    }
}
