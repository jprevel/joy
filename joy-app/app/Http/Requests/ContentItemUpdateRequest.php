<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ContentItemUpdateRequest extends FormRequest
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
        $platforms = config('platforms.available', ['facebook', 'instagram', 'linkedin', 'twitter', 'blog']);

        return [
            'title' => 'sometimes|required|string|max:255',
            'copy' => 'nullable|string|max:5000',
            'platform' => 'sometimes|required|string|in:' . implode(',', array_map('strtolower', $platforms)),
            'scheduled_at' => 'sometimes|required|date',
            'image' => 'nullable|image|max:2048', // 2MB max
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Content title is required',
            'title.max' => 'Title cannot exceed 255 characters',
            'copy.max' => 'Content copy cannot exceed 5000 characters',
            'platform.required' => 'Platform is required',
            'platform.in' => 'Invalid platform selected',
            'scheduled_at.required' => 'Schedule date is required',
            'scheduled_at.date' => 'Schedule date must be a valid date',
            'image.image' => 'File must be an image',
            'image.max' => 'Image size cannot exceed 2MB',
        ];
    }
}
