<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;

class DeleteAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'password' => ['required', 'string', function ($attribute, $value, $fail) {
                if (!Hash::check($value, $this->user()->password)) {
                    $fail('The password is incorrect.');
                }
            }],
            'confirm_deletion' => ['required', 'boolean', 'accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'password.required' => 'Please enter your password to confirm deletion.',
            'confirm_deletion.required' => 'You must confirm that you want to delete your account.',
            'confirm_deletion.accepted' => 'You must confirm that you want to delete your account.',
        ];
    }
}
