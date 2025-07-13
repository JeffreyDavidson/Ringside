<?php

declare(strict_types=1);

namespace App\Livewire\Users\Forms;

use App\Livewire\Base\LivewireBaseForm;
use App\Models\Users\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

/**
 * Livewire form component for managing user creation and editing.
 *
 * This form handles complete user account management including authentication
 * credentials, profile information, and role assignments. Provides secure
 * password handling, email validation, and user identification management
 * for the wrestling management system's access control.
 *
 * Key Responsibilities:
 * - User account creation and profile management
 * - Secure password handling with hashing and confirmation
 * - Email uniqueness validation for authentication
 * - User identification and contact information
 * - Role-based access control preparation
 *
 * @extends LivewireBaseForm<UserForm, User>
 *
 * @author Your Name
 *
 * @since 1.0.0
 * @see LivewireBaseForm For base form functionality and patterns
 *
 * @property string $name Full name for user identification and display
 * @property string $email Email address for authentication and communication
 * @property string $password Secure password for account access
 * @property string $password_confirmation Password confirmation for security
 */
class UserForm extends LivewireBaseForm
{
    /**
     * The model instance being edited, or null for new user creation.
     *
     * @var User|null Current user model or null for creation
     */
    protected ?Model $formModel = null;

    /**
     * User's full name for identification and display purposes.
     *
     * Used throughout the system for user identification, display in
     * interfaces, and communication purposes. Should be the user's
     * real name for administrative and operational clarity.
     *
     * @var string User's full name for identification
     */
    public string $name = '';

    /**
     * User's email address for authentication and communication.
     *
     * Primary identifier for user authentication and system communications.
     * Must be unique across all users and follow valid email format.
     * Used for login, password resets, and operational notifications.
     *
     * @var string Email address for authentication
     */
    public string $email = '';

    /**
     * User's password for secure account access.
     *
     * Plain text password input that will be hashed before storage.
     * Must meet security requirements for length and complexity.
     * Only used during creation and password change operations.
     *
     * @var string Plain text password for hashing
     */
    public string $password = '';

    /**
     * Password confirmation for security validation.
     *
     * Must match the password field exactly to prevent typos and
     * ensure user knows their chosen password. Required for both
     * account creation and password change operations.
     *
     * @var string Password confirmation for validation
     */
    public string $password_confirmation = '';

    /**
     * Load additional data when editing existing user records.
     *
     * Handles user-specific data loading for edit operations while
     * maintaining security by never loading password information.
     * Can be extended to load role assignments and other relationships.
     *
     * Security Note:
     * - Password fields are never populated during edit operations
     * - Sensitive data loading follows security best practices
     */
    public function loadExtraData(): void
    {
        // Password fields are intentionally not loaded for security
        // Additional user data can be loaded here as needed:
        // $this->roles = $this->formModel?->roles->pluck('name')->toArray() ?? [];
    }

    /**
     * Prepare user data for model storage with secure password handling.
     *
     * Transforms form fields into model-compatible data structure with
     * secure password hashing. Handles both creation and update scenarios
     * appropriately for password management.
     *
     * Security Features:
     * - Passwords are hashed using Laravel's secure Hash facade
     * - Password is only included when provided (allows updates without password change)
     * - Uses bcrypt hashing for maximum security
     *
     * @return array<string, mixed> Model data ready for persistence
     *
     * @see Hash::make() For secure password hashing
     */
    protected function getModelData(): array
    {
        $data = [
            'name' => $this->name,
            'email' => $this->email,
        ];

        // Only include password if provided (allows profile updates without password change)
        if (! empty($this->password)) {
            $data['password'] = Hash::make($this->password);
        }

        return $data;
    }

    /**
     * Get the model class for user form operations.
     *
     * Specifies the User model class for type-safe model operations
     * including creation, updates, and relationship management.
     *
     * @return class-string<User> The User model class
     */
    protected function getModelClass(): string
    {
        return User::class;
    }

    /**
     * Define validation rules for user form fields.
     *
     * Provides comprehensive validation for all user account data including
     * uniqueness constraints, security requirements, and format validation
     * to ensure secure and reliable user management.
     *
     * Validation Requirements:
     * - Name: Required, max 255 characters for database compatibility
     * - Email: Required, valid format, unique across users
     * - Password: Required for creation, minimum 8 characters, confirmed
     * - Password updates: Optional for existing users, same requirements when provided
     *
     * @return array<string, array<int, mixed>> Laravel validation rules array
     *
     * @see Rule::unique() For email uniqueness validation
     */
    protected function rules(): array
    {
        $rules = [
            'name' => $this->getRequiredStringRules(),
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->formModel),
            ],
        ];

        // Password rules - required for creation, optional for updates
        if ($this->isCreating() || ! empty($this->password)) {
            $rules['password'] = ['required', 'string', 'min:8', 'confirmed'];
            $rules['password_confirmation'] = ['required'];
        }

        return $rules;
    }

    /**
     * Get user-specific validation attributes.
     *
     * All standard attributes are provided by HasStandardValidationAttributes trait.
     * This method handles user-specific field naming.
     *
     * @return array<string, string> Custom validation attributes for this form
     */
    protected function getCustomValidationAttributes(): array
    {
        return [
            'password_confirmation' => 'password confirmation',
        ];
    }
}
