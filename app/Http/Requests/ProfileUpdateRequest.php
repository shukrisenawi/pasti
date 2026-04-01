<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'nama_samaran' => ['nullable', 'string', 'max:255'],
            'tarikh_lahir' => ['nullable', 'date'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_avatar' => ['nullable', 'boolean'],
            'pasti_id' => ['nullable', 'integer', 'exists:pastis,id'],
            'phone' => ['nullable', 'string', 'max:30'],
            'marital_status' => ['nullable', 'string', 'in:single,married,widowed,divorced'],
            'joined_at' => ['nullable', 'date'],
        ];
    }
}
