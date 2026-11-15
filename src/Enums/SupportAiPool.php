<?php

namespace AnilcanCakir\MagicStarter\Enums;

/**
 * Which daily token pool an AI support turn draws from.
 *
 * The host decides through {@see \AnilcanCakir\MagicStarter\Contracts\RespondsToSupport::pool()},
 * because only the host knows the plan; each pool's size is `support.ai.quotas.pools.<value>`.
 */
enum SupportAiPool: string
{
    /** A free plan or a new account. */
    case FREE = 'free';

    /** A paying customer. */
    case PAID = 'paid';
}
