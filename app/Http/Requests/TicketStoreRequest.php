<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TicketStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ticket_image' => [
                'required',
                'image',
                'mimes:jpeg,png',
                'max:5120', // 5MB max
            ]
        ];
    }

    public function messages(): array
    {
        return [
            'ticket_image.required' => 'Please upload a ticket image.',
            'ticket_image.image' => 'The file must be an image.',
            'ticket_image.mimes' => 'The image must be a JPEG or PNG file.',
            'ticket_image.max' => 'The image size must not exceed 5MB.',
        ];
    }
}
