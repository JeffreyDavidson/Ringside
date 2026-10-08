<?php

declare(strict_types=1);

namespace App\Livewire\Users\Forms;

use App\Data\Users\UserData;
use App\Enums\Users\Role;
use App\Livewire\Base\BaseForm;
use App\Models\Users\User;
use App\Rules\Users\UniqueEmail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rules\Password;

/** @extends BaseForm<User> */
class CreateEditForm extends BaseForm
{
    public string $first_name = '';

    public string $last_name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public string $role = 'basic';

    protected function loadModelData(Model $model): void
    {
        $this->password = '';
        $this->password_confirmation = '';
    }

    public function toData(): UserData
    {
        return new UserData(
            firstName: $this->first_name,
            lastName: $this->last_name,
            email: $this->email,
            role: Role::from($this->role),
            password: $this->password !== '' ? $this->password : null,
        );
    }

    public function user(): User
    {
        return User::query()->findOrFail($this->modelId);
    }

    protected function rules(): array
    {
        $rules = [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                new UniqueEmail($this->modelId),
            ],
            'role' => ['required', 'string', 'in:administrator,basic'],
        ];

        if ($this->isCreating()) {
            $rules['password'] = ['required', 'string', Password::defaults(), 'confirmed'];
            $rules['password_confirmation'] = ['required'];
        } elseif (! empty($this->password)) {
            $rules['password'] = ['required', 'string', Password::defaults(), 'confirmed'];
            $rules['password_confirmation'] = ['required'];
        }

        return $rules;
    }

    #[\Override]
    protected function validationAttributes(): array
    {
        return [
            'password_confirmation' => __('users.validation.attributes.password_confirmation'),
        ];
    }
}
