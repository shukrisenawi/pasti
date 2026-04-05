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
        $isGuru = (bool) $this->user()?->hasRole('guru');
        $requiredOrNullable = fn () => $isGuru ? ['required'] : ['nullable'];

        return [
            'name' => ['required', 'string', 'max:255'],
            'nama_samaran' => [...$requiredOrNullable(), 'string', 'max:255'],
            'tarikh_lahir' => [...$requiredOrNullable(), 'date'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:7168', 'dimensions:min_width=300,min_height=300'],
            'remove_avatar' => ['nullable', 'boolean'],
            'phone' => [...$requiredOrNullable(), 'string', 'max:30'],
            'marital_status' => [...$requiredOrNullable(), 'string', 'in:single,married,widowed,divorced'],
            'joined_at' => [...$requiredOrNullable(), 'date'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $user = $this->user();
            if (! $user || ! $user->hasRole('guru')) {
                return;
            }

            $wantsRemoveAvatar = $this->boolean('remove_avatar');
            $hasNewAvatar = $this->hasFile('avatar');
            $hasExistingAvatar = filled($user->avatar_path) && ! $wantsRemoveAvatar;

            if (! $hasExistingAvatar && ! $hasNewAvatar) {
                $validator->errors()->add('avatar', 'Avatar wajib diisi untuk guru.');
            }
        });
    }
}

