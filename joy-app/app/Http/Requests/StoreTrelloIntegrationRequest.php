<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTrelloIntegrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    public function rules(): array
    {
        return [
            'workspace_id' => [
                'required',
                'integer',
                'exists:client_workspaces,id',
                Rule::unique('trello_integrations', 'workspace_id')
                    ->ignore($this->route('integration')?->id)
            ],
            'api_key' => [
                'required',
                'string',
                'size:32',
                'regex:/^[a-f0-9]{32}$/',
            ],
            'api_token' => [
                'required',
                'string',
                'size:64',
                'regex:/^[a-f0-9]{64}$/',
            ],
            'board_id' => [
                'required',
                'string',
                'size:24',
                'regex:/^[a-f0-9]{24}$/',
            ],
            'list_id' => [
                'nullable',
                'string',
                'size:24',
                'regex:/^[a-f0-9]{24}$/',
            ],
            'is_active' => [
                'sometimes',
                'boolean',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'workspace_id.unique' => 'This workspace already has a Trello integration configured.',
            'api_key.size' => 'The API key must be exactly 32 characters long.',
            'api_key.regex' => 'The API key must be a valid hexadecimal string.',
            'api_token.size' => 'The API token must be exactly 64 characters long.',
            'api_token.regex' => 'The API token must be a valid hexadecimal string.',
            'board_id.size' => 'The board ID must be exactly 24 characters long.',
            'board_id.regex' => 'The board ID must be a valid hexadecimal string.',
            'list_id.size' => 'The list ID must be exactly 24 characters long.',
            'list_id.regex' => 'The list ID must be a valid hexadecimal string.',
        ];
    }

    public function attributes(): array
    {
        return [
            'workspace_id' => 'workspace',
            'api_key' => 'Trello API key',
            'api_token' => 'Trello API token',
            'board_id' => 'board ID',
            'list_id' => 'list ID',
        ];
    }
}
