<?php

declare(strict_types=1);

namespace App\Repositories\Support;

use Illuminate\Database\Eloquent\Model;

/**
 * Base repository providing common CRUD operations for all domain repositories.
 *
 * This abstract class serves as the foundation for all repository implementations
 * in the wrestling promotion management system, providing shared functionality
 * and establishing consistent patterns across the repository layer.
 *
 * DESIGN PRINCIPLES:
 * - Consistent CRUD operation patterns across all repositories
 * - Thin abstraction layer over Eloquent models
 * - Domain-agnostic functionality only
 * - Extensible foundation for repository-specific operations
 *
 * USAGE:
 * All domain repositories should extend this class and implement their
 * specific repository interface contracts. The base class provides common
 * operations while derived classes handle domain-specific logic.
 */
abstract class BaseRepository
{
    /**
     * Delete a model instance.
     *
     * Performs a standard delete operation on the provided model instance.
     * If the model uses soft deletes, this will perform a soft delete.
     * Otherwise, it will permanently remove the record from the database.
     *
     * @param  Model  $model  The model instance to delete
     */
    public function delete(Model $model): void
    {
        $model->delete();
    }
}
