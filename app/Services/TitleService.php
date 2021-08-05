<?php

namespace App\Services;

use App\Repositories\TitleRepository;

class TitleService
{
    protected $titleRepository;

    public function __construct(TitleRepository $titleRepository)
    {
        $this->titleRepository = $titleRepository;
    }

    /**
     * Undocumented function
     *
     * @param [type] $title
     * @param [type] $startedAt
     * @return void
     */
    public function create($data)
    {
        $title = $this->titleRepository->create($data);

        if ($request->filled('activated_at')) {
            $title->activate($request->input('activated_at'));
        }

        return $title;
    }
}
