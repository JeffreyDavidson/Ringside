<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use Closure;
use DateTime;
use LogicException;

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
 * @author Ringside
 *
 * @since 1.0.0
 */
trait GeneratesDummyData
{
    /**
     * Fill form fields with dummy data based on field definitions.
     *
     * This method automatically detects whether the form uses direct properties
     * or a form object pattern and populates fields accordingly. It supports
     * both callable generators and static values.
     */
    public function fillDummyFields(): void
    {
        abort_unless(app()->environment(['local', 'testing']), 404);

        $fields = $this->getDummyDataFields();

        foreach ($fields as $field => $generator) {
            $value = $generator;

            if (is_callable($generator)) {
                $value = $generator();
            }

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
        if (isset($this->form) && property_exists($this->form, $field)) {
            $this->form->{$field} = $value;

            return;
        }

        if (property_exists($this, $field)) {
            $this->{$field} = $value;

            return;
        }

        throw new LogicException("Dummy data field [{$field}] is not defined on the form or component.");
    }

    /**
     * Get the dummy data field definitions for this form.
     *
     * This abstract method must be implemented by classes using this trait
     * to define which fields should be populated and how they should be generated.
     *
     * @return array<string, callable|mixed> Array mapping field names to generators
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
     */
    protected function generateWrestlingName(): string
    {
        $patterns = [
            fn (): string => fake()->firstName().' '.fake()->lastName(),
            fn (): string => fake()->word().' '.fake()->lastName(),
            fn (): string => 'The '.fake()->word(),
            fn (): string => fake()->firstName().' "'.fake()->word().'" '.fake()->lastName(),
        ];

        $pattern = $this->randomGenerator($patterns);

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
            return $this->randomString($modifiers).' '.$this->randomString($moveTypes);
        }

        return $this->randomString($moveTypes);
    }

    /**
     * Generate a realistic venue name with proper suffix.
     *
     * Creates venue names that sound like real wrestling arenas, combining
     * either corporate sponsors with venue types or city names with
     * appropriate venue suffixes.
     *
     * @return string A realistic venue name
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
            return $this->randomString($prefixes).' '.$this->randomString($suffixes);
        }

        return fake()->city().' '.$this->randomString($suffixes);
    }

    /**
     * Generate a realistic championship title name.
     *
     * Creates wrestling championship names that mirror real-world title
     * structures, combining divisions/categories with appropriate title
     * nomenclature used in professional wrestling.
     *
     * @return string A realistic championship title name
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

        return $this->randomString($categories).' '.$this->randomString($titleTypes);
    }

    /**
     * Generate realistic address components for US venues.
     *
     * Creates complete US address information suitable for venue locations,
     * including proper state abbreviations and valid ZIP code formats.
     *
     * @return array<string, mixed> Address components with proper typing
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
            'state' => $this->randomString($stateAbbreviations),
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
     */
    protected function generateFutureDate(float $probability = 0.8, string $maxPeriod = '+3 months'): ?string
    {
        if (! fake()->boolean($probability * 100)) {
            return null;
        }

        $dateTime = fake()->dateTimeBetween('now', $maxPeriod);

        return $dateTime->format('Y-m-d');
    }

    /**
     * Generate an optional start date with proper DateTime handling.
     *
     * Returns a formatted date string with specified probability, handling
     * PHPStan nullsafe operator issues with DateTime objects by properly
     * checking for null values before calling format().
     *
     * @param  string  $format  The date format to use (default: 'Y-m-d H:i:s')
     * @param  float  $probability  The probability of returning a date (default: 0.8)
     * @param  string  $maxPeriod  Maximum future period (default: '+3 month')
     * @return string|null The formatted date string or null
     */
    protected function generateOptionalStartDate(
        string $format = 'Y-m-d H:i:s',
        float $probability = 0.8,
        string $minPeriod = 'now',
        string $maxPeriod = '+3 month'
    ): ?string {
        // Directly check probability to avoid PHPStan confusion with fake()->optional()
        if (fake()->boolean((int) ($probability * 100))) {
            return fake()->dateTimeBetween($minPeriod, $maxPeriod)->format($format);
        }

        return null;
    }

    /**
     * Generate an optional date for an employment field.
     */
    protected function generateOptionalEmploymentDate(float $probability = 0.8): ?string
    {
        return $this->generateOptionalStartDate('Y-m-d', $probability);
    }

    /**
     * @param  non-empty-list<string>  $values
     */
    private function randomString(array $values): string
    {
        $value = fake()->randomElement($values);

        if (! is_string($value)) {
            throw new LogicException('Expected the random value to be a string.');
        }

        return $value;
    }

    /**
     * @param  non-empty-list<Closure(): string>  $generators
     * @return Closure(): string
     */
    private function randomGenerator(array $generators): Closure
    {
        $generator = fake()->randomElement($generators);

        if (! $generator instanceof Closure) {
            throw new LogicException('Expected the random value to be a generator.');
        }

        return $generator;
    }
}
