<?php

namespace App\Http\Livewire\Datatable;

trait WithBulkActions
{
    private $selectPage = false;

    private $selectAll = false;

    private $selected = [];

    public function renderingWithBulkActions()
    {
        if ($this->selectAll) {
            $this->selectPageRows();
        }
    }

    public function updatedSelected()
    {
        $this->selectAll = false;
        $this->selectPage = false;
    }

    public function updatedSelectPage($value)
    {
        if ($value) {
            return $this->selectPageRows();
        }

        $this->selectAll = false;
        $this->selected = [];
    }

    public function selectPageRows()
    {
        $this->selected = $this->rows->pluck('id')->map(fn ($id) => (string) $id);
    }

    public function selectAll()
    {
        $this->selectAll = true;
    }

    public function getSelectedRowsQueryProperty()
    {
        return (clone $this->rowsQuery)
            ->unless($this->selectAll, fn ($query) => $query->whereKey($this->selected));
    }
}
