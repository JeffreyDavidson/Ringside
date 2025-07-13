<?php

declare(strict_types=1);

namespace App\Livewire\Users\Modals;

use App\Livewire\Base\BaseFormModal;
use App\Livewire\Users\Forms\UserForm;
use App\Models\Users\User;
use Livewire\Form;

/**
 * Livewire modal component for user account form management.
 *
 * Handles the creation and editing of user accounts within the wrestling
 * management system. Provides secure user management with password handling,
 * email validation, and account security features through a modal interface.
 *
 * Key Features:
 * - Modal-based user form interface
 * - Automatic data generation for testing and development
 * - Secure password handling and validation
 * - Email uniqueness validation
 * - User account management workflows
 *
 * @extends BaseFormModal<UserForm, User>
 *
 * @author Your Name
 *
 * @since 1.0.0
 * @see BaseFormModal For modal functionality and patterns
 * @see User For the underlying user model structure
 */
class UserFormModal extends BaseFormModal
{
    /**
     * The user form instance for data management.
     *
     * Handles all user-specific validation, data transformation,
     * and persistence operations within the modal interface.
     */
    public Form $form;

    /**
     * Get the form class that handles user data validation and processing.
     *
     * @return class-string<UserForm> The fully qualified class name of UserForm
     */
    protected function getFormClass(): string
    {
        return UserForm::class;
    }

    /**
     * Get the model class that represents user entities.
     *
     * @return class-string<User> The fully qualified class name of User model
     */
    protected function getModelClass(): string
    {
        return User::class;
    }

    /**
     * Get the Blade view path for rendering the user form modal.
     *
     * @return string The view path relative to resources/views
     */
    protected function getModalPath(): string
    {
        return 'users.modals.form-modal';
    }

    /**
     * Generate dummy data fields for user form testing and development.
     *
     * Returns field generators for user account data including names, emails,
     * and secure passwords with proper confirmation handling.
     *
     * @return array<string, callable(): mixed> Array mapping field names to generators
     */
    protected function getDummyDataFields(): array
    {
        // Generate password once for consistency between password and confirmation
        $password = 'password123';

        return [
            'name' => fn () => $this->generateUserName(),
            'email' => fn () => $this->generateUniqueEmail(),
            'password' => fn () => $password,
            'password_confirmation' => fn () => $password,
        ];
    }

    /**
     * Generate random user data for testing and development.
     *
     * Populates the form with realistic user data including names, emails,
     * and secure passwords. Ensures email uniqueness and proper password
     * confirmation for testing authentication workflows.
     */
    public function generateRandomData(): void
    {
        // Generate consistent password for both fields
        $password = 'password123';

        $this->form->fill([
            'name' => $this->generateUserName(),
            'email' => $this->generateUniqueEmail(),
            'password' => $password,
            'password_confirmation' => $password,
        ]);
    }

    /**
     * Generate a realistic user name for testing.
     *
     * Creates full names using faker patterns appropriate for
     * user accounts in a wrestling management system.
     *
     * @return string A generated full name
     *
     * @example
     * Returns names like: "John Smith", "Sarah Johnson", "Michael Brown"
     */
    protected function generateUserName(): string
    {
        return fake()->firstName().' '.fake()->lastName();
    }

    /**
     * Generate a unique email address for testing.
     *
     * Creates email addresses that are unique and follow realistic
     * patterns while avoiding conflicts with existing user accounts.
     * Uses safe email generation to prevent real email issues.
     *
     * @return string A unique email address
     *
     * @example
     * Returns emails like: "john.smith@example.com", "sarah.j@test.org"
     */
    protected function generateUniqueEmail(): string
    {
        // Use faker's unique email generation with safe domains
        return fake()->unique()->safeEmail();
    }
}
