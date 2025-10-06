<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MagicLinkStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Authorization handled by middleware and controller
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'client_id' => 'required|exists:clients,id',
            'scopes' => 'required|array|min:1',
            'scopes.*' => 'required|string|in:view,comment,approve',
            'expires_in_hours' => 'sometimes|integer|min:1|max:720', // Max 30 days
            'pin' => 'nullable|string|size:4|regex:/^\d{4}$/',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'client_id.required' => 'Client ID is required',
            'client_id.exists' => 'The selected client does not exist',
            'scopes.required' => 'At least one scope is required',
            'scopes.array' => 'Scopes must be an array',
            'scopes.*.in' => 'Invalid scope. Must be one of: view, comment, approve',
            'expires_in_hours.integer' => 'Expiration must be an integer',
            'expires_in_hours.min' => 'Expiration must be at least 1 hour',
            'expires_in_hours.max' => 'Expiration cannot exceed 720 hours (30 days)',
            'pin.size' => 'PIN must be exactly 4 digits',
            'pin.regex' => 'PIN must contain only digits',
        ];
    }
}
