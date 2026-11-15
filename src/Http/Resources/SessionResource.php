<?php

namespace AnilcanCakir\MagicStarter\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SessionResource extends JsonResource
{
    /**
     * Transform the session token into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ip_address' => $this->ip_address,
            'user_agent' => $this->user_agent,
            'agent' => \AnilcanCakir\MagicStarter\Support\SessionAgent::parse((string) ($this->user_agent ?? '')),
            'location' => \AnilcanCakir\MagicStarter\Support\SessionLocation::resolve($this->ip_address),
            'is_current_device' => $this->id === $request->user()->currentAccessToken()->id,
            'last_used_at' => $this->last_used_at,
            'created_at' => $this->created_at,
        ];
    }
}
