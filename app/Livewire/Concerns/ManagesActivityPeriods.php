<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use Illuminate\Database\QueryException;

/**
 * Trait for managing activity period operations in Livewire forms.
 *
 * This trait provides functionality for handling activity period creation and management
 * within Livewire form components. It's designed to work with models that implement
 * the HasActivityPeriods trait and provides automated activity period lifecycle
 * management during form operations.
 *
 * The trait automatically handles activity period creation when new models are created
 * with a specified start date, ensuring proper initialization of the activity period
 * system without requiring manual intervention in each form class.
 *
 * Key Responsibilities:
 * - Automatic activity period creation for new models
 * - Integration with form model lifecycle events
 * - Start date validation and processing
 * - Activity period relationship management
 *
 * @requires HasActivityPeriods The target model must use HasActivityPeriods trait
 *
 * @author Your Name
 *
 * @since 1.0.0
 * @see HasActivityPeriods For model activity period functionality
 *
 * @example
 * ```php
 * class TitleForm extends LivewireBaseForm
 * {
 *     use ManagesActivityPeriods;
 *
 *     public string $start_date = '';
 *
 *     public function store(): bool
 *     {
 *         $this->validate();
 *         $result = $this->storeModel();
 *
 *         // Automatically creates activity period if start_date is provided
 *         $this->handleActivityPeriodCreation();
 *
 *         return $result;
 *     }
 * }
 * ```
 */
trait ManagesActivityPeriods
{
    /**
     * Handle activity period creation when creating a new model.
     *
     * Creates an initial activity period for the model if it's being created
     * and has a start date specified. This method should be called after the
     * main model has been saved to establish the initial activity period record.
     *
     * The method performs the following operations:
     * 1. Checks if the form is in creation mode (not editing)
     * 2. Validates that a start_date property exists and is not empty
     * 3. Creates a new activity period record with the specified start date
     *
     * Integration Points:
     * - Requires the formModel to use the HasActivityPeriods trait
     * - Expects a $start_date property to be available on the form
     * - Should be called after successful model creation
     *
     *
     * @throws QueryException If activity period creation fails
     *
     * @example
     * ```php
     * public function store(): bool
     * {
     *     $this->validate();
     *
     *     // Create the main model
     *     $result = $this->storeModel();
     *
     *     // Create activity period if applicable
     *     $this->handleActivityPeriodCreation();
     *
     *     return $result;
     * }
     * ```
     *
     * @see HasActivityPeriods::activityPeriods() For the relationship method
     */
    protected function handleActivityPeriodCreation(): void
    {
        if (! empty($this->start_date)) {
            $this->formModel->activityPeriods()->create([
                'started_at' => $this->start_date,
            ]);
        }
    }
}
