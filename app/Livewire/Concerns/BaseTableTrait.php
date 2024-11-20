<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use App\Livewire\Concerns\Columns\HasActionColumn;
use Rappasoft\LaravelLivewireTables\Views\Column;

trait BaseTableTrait
{
    use HasActionColumn;

    /** @var array<string, bool> */
    protected array $actionLinksToDisplay = ['view' => true, 'edit' => true, 'delete' => true];

    protected bool $showActionColumn = true;

    public function configuringBaseTableTrait(): void
    {
        $this->setPrimaryKey('id')
            ->setColumnSelectDisabled()
            ->setSearchPlaceholder('Search '.$this->resourceName)
            ->setPaginationEnabled()
            ->addAdditionalSelects([$this->databaseTableName.'.id as id'])
            ->setPerPageAccepted([5, 10, 25, 50, 100])
            ->setLoadingPlaceholderContent('Loading')
            ->setLoadingPlaceholderEnabled()
            ->setFiltersStatus(false);

        $this->setupTableStructure();
    }

    /** @return array<Column> */
    public function appendColumns(): array
    {
        return $this->showActionColumn ? [
            $this->getDefaultActionColumn(),
        ] : [];
    }

    public function setupTableStructure()
    {
        return $this->setPerPageFieldAttributes([
            'default' => false,
            'default-styling' => false,
            'default-colors' => false,
            'class' => 'flex appearance-none shadow-none outline-none bg-no-repeat bg-[right_.675rem_center] bg-[#fcfcfc] rounded-md border border-solid border-gray-300 font-medium text-xs h-8 ps-2.5 pe-2.5 bg-[length:14px_10px] w-16',
        ])
            ->setSearchFieldAttributes([
                'default' => false,
                'default-styling' => false,
                'default-colors' => false,
                'class' => 'grow bg-transparent border border-transparent text-inherit appearance-none outline-none opacity-100 active:shadow-none active:text-gray-700 focus:border-primary text-xs',
            ])
            ->setTableWrapperAttributes([
                'default' => false,
                'default-styling' => false,
                'default-colors' => false,
                'class' => 'scrollable-x-auto',
            ])
            ->setTableAttributes([
                'default' => false,
                'default-styling' => false,
                'default-colors' => false,
                'class' => 'table table-auto w-full caption-bottom border-collapse text-left text-gray-700 font-medium text-sm border-0',
            ])
            ->setTheadAttributes([
                'default' => false,
                'default-styling' => false,
                'default-colors' => false,
                'class' => '',
            ])
            ->setThAttributes(function (Column $column) {
                // dump($column->getTitle('Actions'));
                if ($column->getTitle() == 'Actions') {
                    return [
                        'default' => false,
                        'default-styling' => false,
                        'default-colors' => false,
                        'class' => 'bg-[#fcfcfc] text-gray-600 font-medium text-[.8125rem] leading-[1.125rem] align-middle py-2.5 ps-4 pe-4 border-b border-gray-200 border-e border-e-solid border-e-gray-200 w-[60px]',
                    ];
                }

                return [
                    'default' => false,
                    'default-styling' => false,
                    'default-colors' => false,
                    'class' => 'bg-[#fcfcfc] text-gray-600 font-medium text-[.8125rem] leading-[1.125rem] align-middle py-2.5 ps-4 pe-4 border-b border-gray-200 border-e border-e-solid border-e-gray-200',
                ];
            })
            ->setThSortButtonAttributes(function () {
                return [
                    'default' => false,
                    'default-styling' => false,
                    'default-colors' => false,
                    'class' => '',
                ];
            })
            ->setTbodyAttributes([
                'default' => false,
                'default-styling' => false,
                'default-colors' => false,
                'class' => '',
            ])
            ->setTrAttributes(function () {
                return [
                    'default' => false,
                    'default-styling' => false,
                    'default-colors' => false,
                    'class' => '',
                ];
            })
            ->setTdAttributes(function (Column $column, $row, $columnIndex, $rowIndex) {
                dump($columnIndex);
                if ($column->getTitle() == 'Actions') {
                    return [
                        'default' => false,
                        'default-styling' => false,
                        'default-colors' => false,
                        'class' => 'py-3 ps-4 border border-solid border-gray-200 border-e-solid border-e-gray-200 pe-5 b-e-0',
                    ];
                }

                return [
                    'default' => false,
                    'default-styling' => false,
                    'default-colors' => false,
                    'class' => 'py-3 ps-4 pe-4 border border-solid border-gray-200 border-e-solid border-e-gray-200 pe-4',
                ];
            })
            ->setPaginationWrapperAttributes([
                'default' => false,
                'default-styling' => false,
                'default-colors' => false,
                'class' => 'inline-flex items-center gap-1',
            ]);
    }
}
