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
            'first_name' => ['sometimes', 'min:2', 'max:255'],
            'last_name' => ['sometimes', 'min:1', 'max:255'],
            'email' => [
                'sometimes',
                'email',
                'max:255',
                Rule::unique('users')->ignore($this->user()->id)
            ],
            'phone' => [
                'sometimes',
                'string',
                Rule::unique('users', 'phone')->ignore($this->user()->id)
            ],
            'profile_picture' => ['nullable', 'image', 'mimes:jpeg,png,jpg'],
            'password' => 'sometimes|string|min:8|confirmed|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/',
            'password_confirmation' => 'required_with:password|string|min:8|same:password',
        ];
    }

}
