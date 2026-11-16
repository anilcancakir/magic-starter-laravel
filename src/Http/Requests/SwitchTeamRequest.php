<?php

namespace AnilcanCakir\MagicStarter\Http\Requests;

use AnilcanCakir\MagicStarter\MagicStarter;
use AnilcanCakir\MagicStarter\Support\MigrationHelper;
use AnilcanCakir\MagicStarter\Support\TeamKey;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class SwitchTeamRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to switch to the given team.
     */
    public function authorize(): bool
    {
        $teamId = $this->input('team_id');

        if (! (\is_string($teamId) || \is_int($teamId)) || ! TeamKey::looksLikeOne((string) $teamId)) {
            return false;
        }

        $teamModel = MagicStarter::teamModel();
        $team = $teamModel::find($teamId);

        if (! $team) {
            return false;
        }

        return Gate::allows('switchTo', $team);
    }

    /**
     * Get the validation rules that apply to the team switch request.
     *
     * The team_id format rule mirrors the package's UUID-optional contract:
     * when use_uuids is true (the default) the ID must be a UUID string;
     * when use_uuids is false (integer primary keys) it must be an integer.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $formatRule = MigrationHelper::usesUuids() ? 'uuid' : 'integer';

        return [
            'team_id' => [
                'required',
                $formatRule,
                Rule::exists(MagicStarter::teamModel(), 'id'),
            ],
        ];
    }

    /**
     * Get the custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'team_id.exists' => __('magic-starter::teams.not_found'),
        ];
    }
}
