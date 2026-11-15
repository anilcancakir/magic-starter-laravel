<?php

namespace AnilcanCakir\MagicStarter\Actions;

use AnilcanCakir\MagicStarter\Contracts\ExchangesHandoffCodes;
use AnilcanCakir\MagicStarter\Handoff\HandoffStore;
use AnilcanCakir\MagicStarter\MagicStarter;
use Illuminate\Database\Eloquent\Model;

/**
 * Default action for redeeming a sign-in handoff code.
 */
class ExchangeHandoffCode implements ExchangesHandoffCodes
{
    public function __construct(
        protected HandoffStore $store,
    ) {}

    /**
     * Redeem a code, once, for the user who minted it.
     *
     * @return array{user: Model, next: string|null}|null
     */
    public function exchange(string $code): ?array
    {
        // 1. Spend the code before anything is compared, so a refusal further on still burns it.
        $handoff = $this->store->pull($code);

        if ($handoff === null) {
            return null;
        }

        // 2. The minting user may have been deleted since; that refusal reads like every other.
        $user = MagicStarter::userModel()::query()->find($handoff['user_id']);

        if (! $user instanceof Model) {
            return null;
        }

        return [
            'user' => $user,
            'next' => $handoff['next'],
        ];
    }
}
