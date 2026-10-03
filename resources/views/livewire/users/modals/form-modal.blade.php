@use('App\Enums\Users\Role')

<div>
    <x-form-modal>
        <x-form-modal.person-name-fields
            :first-name-label="__('users.first_name')"
            :last-name-label="__('users.last_name')"
        />

        <x-form-modal.modal-input>
            <x-form.input type="email" :label="__('users.email')" wire:model="form.email" required />
        </x-form-modal.modal-input>

        <x-form-modal.modal-input>
            <x-form.inputs.select
                :label="__('users.role')"
                wire:model="form.role"
                :options="[Role::Basic->value => Role::Basic->label(), Role::Administrator->value => Role::Administrator->label()]"
                :selected="$form->role ?? Role::Basic->value"
                required
            />
        </x-form-modal.modal-input>

        {{-- A new account needs a password; when editing, leaving both password fields empty keeps the current one. --}}
        <x-form-modal.modal-input>
            <x-form.input
                type="password"
                :label="__('users.password')"
                wire:model="form.password"
                autocomplete="new-password"
                :required="$form->isCreating()"
            />
        </x-form-modal.modal-input>

        <x-form-modal.modal-input>
            <x-form.input
                type="password"
                :label="__('users.password_confirmation')"
                wire:model="form.password_confirmation"
                autocomplete="new-password"
                :required="$form->isCreating()"
            />
        </x-form-modal.modal-input>
    </x-form-modal>
</div>
