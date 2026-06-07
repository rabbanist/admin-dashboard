<?php

declare(strict_types=1);

namespace Yourvendor\AdminDashboard\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $userModelClass = config('admin-dashboard.user_model', \App\Models\User::class);
        $userTable = (new $userModelClass)->getTable();
        
        // Retrieve the user identifier from route parameter 'user'
        $userParam = $this->route('user');
        $idValue = is_object($userParam) ? $userParam->getKey() : $userParam;

        return [
            'name'          => ['required', 'string', 'max:255'],
            'email'         => ['required', 'string', 'email', 'max:255', "unique:{$userTable},email,{$idValue}"],
            'password'      => ['nullable', 'string', 'min:8', 'confirmed'],
            'profile_photo' => ['nullable', 'image', 'max:2048', 'mimes:jpeg,png,jpg,gif,webp'],
            'roles'         => ['nullable', 'array'],
            'roles.*'       => ['exists:admin_roles,id'],
            'bio'           => ['nullable', 'string', 'max:1000'],
            'phone'         => ['nullable', 'string', 'max:50'],
            'two_factor_enabled' => ['nullable', 'boolean'],
        ];
    }
}
