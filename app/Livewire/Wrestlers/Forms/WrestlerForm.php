<?php

declare(strict_types=1);

namespace App\Livewire\Wrestlers\Forms;

use App\Livewire\Base\LivewireBaseForm;
use App\Livewire\Concerns\ManagesEmployment;
use App\Models\Wrestlers\Wrestler;
use App\Rules\Shared\CanChangeEmploymentDate;
use App\ValueObjects\Height;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

/**
 * Livewire form component for managing wrestler creation and editing.
 *
 * This form handles the complete lifecycle of wrestler data management including
 * personal information, physical characteristics, wrestling persona details,
 * and employment tracking integration. Provides specialized validation for
 * wrestling-specific fields like height measurements and signature moves.
 *
 * Key Responsibilities:
 * - Wrestler profile management (name, hometown, physical stats)
 * - Height value object integration with feet/inches conversion
 * - Employment relationship tracking and validation
 * - Wrestling persona data (signature moves, career information)
 * - Custom validation rules for wrestling industry requirements
 *
 * @extends LivewireBaseForm<WrestlerForm, Wrestler>
 *
 * @author Your Name
 *
 * @since 1.0.0
 * @see LivewireBaseForm For base form functionality and patterns
 * @see ManagesEmployment For employment tracking capabilities
 * @see Height For height value object operations
 * @see CanChangeEmploymentDate For custom validation rules
 *
 * @property string $name Wrestler's ring name or real name
 * @property string $hometown Wrestler's billed hometown for storylines
 * @property int $height_feet Height measurement in feet (5-7 typical range)
 * @property int $height_inches Additional inches for height (0-11 range)
 * @property int $weight Wrestler's weight in pounds
 * @property string|null $signature_move Wrestler's finishing move or signature
 * @property Carbon|string|null $employment_date Employment start date
 */
class WrestlerForm extends LivewireBaseForm
{
    use ManagesEmployment;

    /**
     * The model instance being edited, or null for new wrestler creation.
     *
     * @var Wrestler|null Current wrestler model or null for creation
     */
    protected ?Model $formModel = null;

    /**
     * Wrestler's ring name or legal name for identification.
     *
     * Used for roster management, match cards, and promotional materials.
     * Must be unique across all active wrestlers in the system.
     *
     * @var string Wrestler's primary name identifier
     */
    public string $name = '';

    /**
     * Wrestler's billed hometown for storyline and promotional purposes.
     *
     * Used in match introductions, promotional materials, and character
     * development. Can be real hometown or kayfabe location for storylines.
     *
     * @var string Hometown for promotional billing
     */
    public string $hometown = '';

    /**
     * Height measurement in feet (5-7 typical range for wrestlers).
     *
     * Combined with height_inches to create complete Height value object.
     * Validated to ensure realistic wrestler measurements.
     *
     * @var int Feet component of wrestler height
     */
    public int $height_feet = 0;

    /**
     * Additional inches for height measurement (0-11 valid range).
     *
     * Works with height_feet to provide precise height measurements.
     * Converted to total inches for Height value object storage.
     *
     * @var int Inches component of wrestler height
     */
    public int $height_inches = 0;

    /**
     * Wrestler's weight in pounds for promotional billing.
     *
     * Used for match announcements, weight class determination,
     * and promotional materials. Validated as 3-digit number.
     *
     * @var int Weight in pounds
     */
    public int $weight = 0;

    /**
     * Wrestler's signature finishing move or special technique.
     *
     * Optional field for wrestling persona development. Must be unique
     * if provided to avoid confusion in match commentary and promotion.
     *
     * @var string|null Signature wrestling move name
     */
    public ?string $signature_move = '';

    /**
     * Employment start date for contract and career tracking.
     *
     * Managed through ManagesEmployment trait for consistent employment
     * tracking across all personnel types. Supports Carbon objects or
     * string dates for flexible input handling.
     *
     * @var Carbon|string|null Employment start date
     */
    public Carbon|string|null $employment_date = '';

    /**
     * Load additional data when editing existing wrestler records.
     *
     * Handles complex data loading including employment relationships
     * and Height value object conversion to separate feet/inches fields.
     * Called automatically during form initialization for edit operations.
     *
     * Employment Integration:
     * - Loads start date from employment relationship
     * - Handles null employment for new wrestlers
     *
     * Height Conversion:
     * - Converts stored inches to feet/inches display format
     * - Uses Height value object for accurate calculations
     *
     *
     * @see ManagesEmployment::$employment_date For employment date handling
     * @see Height::toInches() For height conversion calculations
     */
    public function loadExtraData(): void
    {
        // Early return if no model
        if (! $this->formModel) {
            return;
        }

        // Load employment start date from relationship
        $this->employment_date = $this->formModel->firstEmployment?->started_at?->toDateString();

        // Convert Height value object to separate feet/inches fields
        $height = $this->formModel->height;
        $this->height_feet = (int) floor($height->toInches() / 12);
        $this->height_inches = $height->toInches() % 12;
    }

    /**
     * Prepare wrestler-specific data for model storage.
     *
     * Transforms form fields into model-compatible data structure,
     * including Height value object creation from feet/inches inputs.
     * Excludes employment data which is handled separately.
     *
     * Data Transformations:
     * - Combines height_feet and height_inches into Height value object
     * - Converts Height to total inches for database storage
     * - Passes through other fields with appropriate typing
     *
     * @return array<string, mixed> Model data ready for persistence
     *
     * @see Height::__construct() For height object creation
     * @see Height::toInches() For database storage format
     */
    protected function getModelData(): array
    {
        $height = new Height($this->height_feet, $this->height_inches);

        return [
            'name' => $this->name,
            'hometown' => $this->hometown,
            'height' => $height->toInches(),
            'weight' => $this->weight,
            'signature_move' => $this->signature_move,
        ];
    }

    /**
     * Get the model class for wrestler form operations.
     *
     * Specifies the Wrestler model class for type-safe model operations
     * including creation, updates, and relationship management.
     *
     * @return class-string<Wrestler> The Wrestler model class
     */
    protected function getModelClass(): string
    {
        return Wrestler::class;
    }

    /**
     * Define validation rules for wrestler form fields.
     *
     * Provides comprehensive validation for all wrestler data including
     * uniqueness constraints, realistic physical measurements, and
     * employment date validation through custom rules.
     *
     * @return array<string, array<int, mixed>> Laravel validation rules array
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('wrestlers', 'name')->ignore($this->formModel)],
            'hometown' => ['required', 'string', 'max:255'],
            'height_feet' => ['required', 'integer', 'max:7'],
            'height_inches' => ['required', 'integer', 'max:11'],
            'weight' => ['required', 'integer', 'digits:3'],
            'signature_move' => ['nullable', 'string', 'max:255', Rule::unique('wrestlers', 'signature_move')->ignore($this->formModel)],
            'employment_date' => ['nullable', 'date', new CanChangeEmploymentDate($this->formModel)],
        ];
    }

    /**
     * Get wrestler-specific validation attributes.
     *
     * Extends standard attributes with wrestler-specific field names for better
     * user experience in validation messages.
     *
     * @return array<string, string> Custom validation attributes for this form
     */
    protected function validationAttributes(): array
    {
        return [
            'height_feet' => 'first name',
            'height_inches' => 'last name',
            'signature_move' => 'signature move',
            'employment_date' => 'employment date',
        ];
    }
}
