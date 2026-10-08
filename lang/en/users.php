<?php

return [
    'add' => 'Add User',
    'index_description' => 'Manage global accounts and platform access.',
    'index_title' => 'Users',
    'name' => 'User Name',
    'email' => 'Email Address',
    'phone' => 'Phone Number',
    'role' => 'Role',
    'first_name' => 'First Name',
    'last_name' => 'Last Name',
    'password' => 'Password',
    'password_confirmation' => 'Confirm Password',
    'activation_confirmation' => 'Activate :name? This email has pending invitations: :invitations.',
    'invalid_status' => 'Select a valid user status.',
    'last_administrator' => 'The platform must keep at least one active administrator. Make another user an active administrator first.',
    'status_changed' => 'User account status changed to :status.',
    'email_change_invitations_warning' => ':name now uses an email with pending invitations: :invitations. They can accept them once signed in.',

    'validation' => [
        'attributes' => [
            'password_confirmation' => 'password confirmation',
        ],
    ],
];
