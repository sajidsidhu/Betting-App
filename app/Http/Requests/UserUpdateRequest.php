<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UserUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:255'],
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('users')->ignore($this->user()->id)
            ],
            'phone_number' => [
                'nullable',
                'string',
                Rule::unique('users', 'phone')->ignore($this->user()->id)
            ],
            'profile_picture' => ['nullable', 'image', 'mimes:jpeg,png,jpg'],
        ];
    }

    protected function passedValidation(): void
    {
        if (
            !$this->exists('name') &&
            !$this->exists('email') &&
            !$this->exists('phone_number') &&
            !$this->hasFile('profile_picture')
        ) {
            throw new HttpResponseException(
                response()->json([
                    'message' => 'At least one of name, email, phone_number, or profile_picture must be provided.',
                    'errors' => [
                        'request' => ['No fields provided for update.']
                    ]
                ], 422)
            );
        }
    }
}
