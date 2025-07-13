<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use Throwable;

/**
 * Trait for generating dummy data in Livewire forms and modals.
 *
 * This trait provides a standardized approach to populating form fields with
 * realistic fake data for development and testing purposes. It automatically
 * detects the form structure and populates fields accordingly.
 *
 * The trait works with both direct property assignment and form object patterns,
 * making it flexible for different form architectures.
 *
 * @author Your Name
 *
 * @since 1.0.0
 *
 * @example
 * ```php
 * class EventMatchForm extends LivewireBaseForm
 * {
 *     use GeneratesDummyData;
 *
 *     public string $preview = '';
 *     public int $matchTypeId = 0;
 *
 *     protected function getDummyDataFields(): array
 *     {
 *         return [
 *             'preview' => fn() => fake()->paragraph() . ' Epic match!',
 *             'matchTypeId' => fn() => fake()->numberBetween(1, 10),
 *         ];
 *     }
 *
 *     public function fillWithDummyData(): void
 *     {
 *         $this->fillDummyFields();
 *     }
 * }
 * ```
 */
trait GeneratesDummyData
{
    /**
     * Fill form fields with dummy data based on field definitions.
     *
     * This method automatically detects whether the form uses direct properties
     * or a form object pattern and populates fields accordingly. It supports
     * both callable generators and static values.
     *
     *
     * @example
     * ```php
     * // Call this method to populate all defined dummy fields
     * $this->fillDummyFields();
     * ```
     */
    public function fillDummyFields(): void
    {
        $fields = $this->getDummyDataFields();

        foreach ($fields as $field => $generator) {
            $value = is_callable($generator) ? $generator() : $generator;

            // Try to populate fields using available patterns
            $this->populateField($field, $value);
        }
    }

    /**
     * Populate a single field with a value using available patterns.
     *
     * Attempts different field population strategies based on the class context.
     *
     * @param  string  $field  Field name to populate
     * @param  mixed  $value  Value to set
     */
    private function populateField(string $field, mixed $value): void
    {
        // Strategy 1: Try modelForm property (for some form patterns)
        if ($this->tryPopulateModelForm($field, $value)) {
            return;
        }

        // Strategy 2: Try direct property assignment
        if ($this->tryPopulateDirectProperty($field, $value)) {
            return;
        }

        // Strategy 3: Try form property (for BaseFormModal pattern)
        if ($this->tryPopulateFormProperty($field, $value)) {
            return;
        }

        // If none work, silently skip (graceful degradation)
    }

    /**
     * Try to populate via modelForm property.
     *
     * @param  string  $field  Field name
     * @param  mixed  $value  Value to set
     * @return bool True if successful
     */
    private function tryPopulateModelForm(string $field, mixed $value): bool
    {
        try {
            if (isset($this->modelForm)) {
                $this->modelForm->{$field} = $value;

                return true;
            }
        } catch (Throwable) {
            // Ignore and try next strategy
        }

        return false;
    }

    /**
     * Try to populate via direct property.
     *
     * @param  string  $field  Field name
     * @param  mixed  $value  Value to set
     * @return bool True if successful
     */
    private function tryPopulateDirectProperty(string $field, mixed $value): bool
    {
        try {
            if (property_exists($this, $field)) {
                $this->{$field} = $value;

                return true;
            }
        } catch (Throwable) {
            // Ignore and try next strategy
        }

        return false;
    }

    /**
     * Try to populate via form property (BaseFormModal pattern).
     *
     * @param  string  $field  Field name
     * @param  mixed  $value  Value to set
     * @return bool True if successful
     */
    private function tryPopulateFormProperty(string $field, mixed $value): bool
    {
        try {
            if (isset($this->form)) {
                $this->form->{$field} = $value;

                return true;
            }
        } catch (Throwable) {
            // Ignore - no more strategies
        }

        return false;
    }

    /**
     * Get the dummy data field definitions for this form.
     *
     * This abstract method must be implemented by classes using this trait
     * to define which fields should be populated and how they should be generated.
     *
     * @return array<string, callable|mixed> Array mapping field names to generators
     *
     * @example
     * ```php
     * protected function getDummyDataFields(): array
     * {
     *     return [
     *         'name' => fn() => $this->generateWrestlingName(),
     *         'signature_move' => fn() => $this->generateSignatureMove(),
     *         'weight' => fn() => fake()->numberBetween(150, 300),
     *         'active' => true, // Static value
     *     ];
     * }
     * ```
     */
    abstract protected function getDummyDataFields(): array;

    /**
     * Generate a realistic wrestling-style name.
     *
     * Creates wrestling persona names using various patterns including
     * real names, stage names with epithets, and single-word personas.
     * Perfect for wrestler, manager, or character name generation.
     *
     * @return string A realistic wrestling name
     *
     * @example
     * Possible outputs:
     * - "John Smith"
     * - "Thunder Johnson"
     * - "The Destroyer"
     * - "Mike 'Steel' Rodriguez"
     */
    protected function generateWrestlingName(): string
    {
        $patterns = [
            fn () => fake()->firstName().' '.fake()->lastName(),
            fn () => fake()->word().' '.fake()->lastName(),
            fn () => 'The '.fake()->word(),
            fn () => fake()->firstName().' "'.fake()->word().'" '.fake()->lastName(),
        ];

        $pattern = fake()->randomElement($patterns);

        return ucwords($pattern());
    }

    /**
     * Generate a realistic wrestling signature move name.
     *
     * Creates authentic-sounding wrestling move names by combining
     * move types with optional modifiers, similar to real wrestling
     * signature moves and finishers.
     *
     * @return string A realistic signature move name
     *
     * @example
     * Possible outputs:
     * - "Stone Cold Stunner"
     * - "Tombstone Slam"
     * - "Submission"
     * - "People's Elbow"
     */
    protected function generateSignatureMove(): string
    {
        $moveTypes = [
            'Stunner', 'Slam', 'Drop', 'Splash', 'Driver', 'Cutter', 'Bomb', 'Lock',
            'Submission', 'Suplex', 'Clothesline', 'Elbow', 'Knee', 'Kick', 'Punch',
        ];

        $modifiers = [
            'Stone Cold', 'Five Knuckle', 'Attitude', 'Rock Bottom', 'Sweet Chin',
            'Razor\'s Edge', 'Tombstone', 'People\'s', 'Sharpshooter', 'Figure Four',
        ];

        $useModifier = fake()->boolean(60);

        if ($useModifier) {
            return fake()->randomElement($modifiers).' '.fake()->randomElement($moveTypes);
        }

        return fake()->randomElement($moveTypes);
    }

    /**
     * Generate a realistic venue name with proper suffix.
     *
     * Creates venue names that sound like real wrestling arenas, combining
     * either corporate sponsors with venue types or city names with
     * appropriate venue suffixes.
     *
     * @return string A realistic venue name
     *
     * @example
     * Possible outputs:
     * - "Madison Square Garden"
     * - "American Airlines Center"
     * - "Chicago Stadium"
     * - "Wells Fargo Arena"
     */
    protected function generateVenueName(): string
    {
        $suffixes = ['Arena', 'Center', 'Stadium', 'Coliseum', 'Garden', 'Dome', 'Auditorium'];
        $prefixes = [
            'American Airlines', 'Madison Square', 'Staples', 'Wells Fargo', 'TD Garden',
            'United Center', 'Honda Center', 'Barclays', 'Target', 'Capital One',
        ];

        $usePrefix = fake()->boolean(70);

        if ($usePrefix) {
            return fake()->randomElement($prefixes).' '.fake()->randomElement($suffixes);
        }

        return fake()->city().' '.fake()->randomElement($suffixes);
    }

    /**
     * Generate a realistic championship title name.
     *
     * Creates wrestling championship names that mirror real-world title
     * structures, combining divisions/categories with appropriate title
     * nomenclature used in professional wrestling.
     *
     * @return string A realistic championship title name
     *
     * @example
     * Possible outputs:
     * - "Intercontinental Championship Title"
     * - "Women's Tag Team Titles"
     * - "World Heavyweight Title"
     * - "United States Championship"
     */
    protected function generateChampionshipTitle(): string
    {
        $titleTypes = [
            'Championship Title', 'Title', 'Titles', 'Championship Titles',
        ];

        $categories = [
            'Intercontinental', 'United States', 'European', 'Hardcore', 'Cruiserweight',
            'Women\'s', 'Tag Team', 'World Heavyweight', 'Universal', 'Raw Women\'s',
        ];

        return fake()->randomElement($categories).' '.fake()->randomElement($titleTypes);
    }

    /**
     * Generate realistic address components for US venues.
     *
     * Creates complete US address information suitable for venue locations,
     * including proper state abbreviations and valid ZIP code formats.
     *
     * @return array<string, mixed> Address components with proper typing
     *
     * @example
     * ```php
     * $address = $this->generateUSAddress();
     * // Returns:
     * // [
     * //     'street_address' => '123 Main Street',
     * //     'city' => 'Chicago',
     * //     'state' => 'IL',
     * //     'zipcode' => 60601
     * // ]
     * ```
     */
    protected function generateUSAddress(): array
    {
        $stateAbbreviations = [
            'AL', 'AK', 'AZ', 'AR', 'CA', 'CO', 'CT', 'DE', 'FL', 'GA',
            'HI', 'ID', 'IL', 'IN', 'IA', 'KS', 'KY', 'LA', 'ME', 'MD',
            'MA', 'MI', 'MN', 'MS', 'MO', 'MT', 'NE', 'NV', 'NH', 'NJ',
            'NM', 'NY', 'NC', 'ND', 'OH', 'OK', 'OR', 'PA', 'RI', 'SC',
            'SD', 'TN', 'TX', 'UT', 'VT', 'VA', 'WA', 'WV', 'WI', 'WY',
        ];

        return [
            'street_address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'state' => fake()->randomElement($stateAbbreviations),
            'zipcode' => (int) fake()->numerify('#####'),
        ];
    }

    /**
     * Generate realistic future date for activations or employment.
     *
     * Creates future dates within a specified timeframe, useful for
     * scheduling activations, contract start dates, or event dates.
     * Uses probability to sometimes return null for optional dates.
     *
     * @param  float  $probability  Probability of generating a date (0.0 to 1.0)
     * @param  string  $maxPeriod  Maximum future period (e.g., '+3 months', '+1 year')
     * @return string|null Date string in Y-m-d format, or null
     *
     * @example
     * ```php
     * // 80% chance of a date within 3 months
     * $startDate = $this->generateFutureDate(0.8, '+3 months');
     *
     * // Always generate a date within 1 year
     * $contractDate = $this->generateFutureDate(1.0, '+1 year');
     *
     * // 30% chance of a date within 6 months
     * $optionalDate = $this->generateFutureDate(0.3, '+6 months');
     * ```
     */
    protected function generateFutureDate(float $probability = 0.8, string $maxPeriod = '+3 months'): ?string
    {
        if (! fake()->boolean($probability * 100)) {
            return null;
        }

        $dateTime = fake()->dateTimeBetween('now', $maxPeriod);

        return $dateTime->format('Y-m-d');
    }
}
