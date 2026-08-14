<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Concerns;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Tests\Unit\Models\Concerns\Support\FakeManageableModel;
use Tests\Unit\Models\Concerns\Support\FakeManagerPivotModel;

describe('CanBeManaged manager relationships', function () {
    beforeEach(function () {
        $this->model = new FakeManageableModel();
    });

    test('provides all manager relationships', function () {
        expect($this->model->managers())->toBeInstanceOf(BelongsToMany::class)
            ->and($this->model->currentManagers())->toBeInstanceOf(BelongsToMany::class)
            ->and($this->model->previousManagers())->toBeInstanceOf(BelongsToMany::class);
    });

    test('uses the explicitly configured table and pivot model', function () {
        $relationship = $this->model->managers();

        expect($relationship->getTable())->toBe((new FakeManagerPivotModel())->getTable())
            ->and($relationship->getPivotClass())->toBe(FakeManagerPivotModel::class);
    });

    test('includes assignment history columns and timestamps', function () {
        expect($this->model->managers()->getPivotColumns())
            ->toContain('hired_at', 'fired_at', 'created_at', 'updated_at');
    });

    test('filters current assignments', function () {
        expect($this->model->currentManagers()->toRawSql())
            ->toContain('"fired_at" is null');
    });

    test('filters previous assignments', function () {
        expect($this->model->previousManagers()->toRawSql())
            ->toContain('"fired_at" is not null');
    });
});
