<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ContentItemStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Authorization handled by middleware
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $platforms = config('platforms.available', ['facebook', 'instagram', 'linkedin', 'twitter', 'blog']);

        return [
            'client_id' => 'required|exists:clients,id',
            'title' => 'required|string|max:255',
            'copy' => 'nullable|string|max:5000',
            'platform' => 'required|string|in:' . implode(',', array_map('strtolower', $platforms)),
            'scheduled_at' => 'required|date',
            'image' => 'nullable|image|max:2048', // 2MB max
            'status' => 'nullable|string|in:draft,review,approved,scheduled',
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
            'title.required' => 'Content title is required',
            'title.max' => 'Title cannot exceed 255 characters',
            'copy.max' => 'Content copy cannot exceed 5000 characters',
            'platform.required' => 'Platform is required',
            'platform.in' => 'Invalid platform selected',
            'scheduled_at.required' => 'Schedule date is required',
            'scheduled_at.date' => 'Schedule date must be a valid date',
            'image.image' => 'File must be an image',
            'image.max' => 'Image size cannot exceed 2MB',
            'status.in' => 'Invalid status value',
        ];
    }
}
