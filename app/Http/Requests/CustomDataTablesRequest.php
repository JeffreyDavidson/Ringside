<?php

namespace App\Http\Requests;

use Yajra\DataTables\Utilities\Request;

class CustomDataTablesRequest extends Request
{
    /**
     * Get global search keyword.
     *
     * @return string
     */
    public function keyword()
    {
        $keyword = $this->request->input('query.generalSearch', $this->request->input('search.value'));

        return $this->prepareKeyword($keyword);
    }

    /**
     * Check if DataTables is searchable.
     *
     * @return bool
     */
    public function isSearchable()
    {
        return $this->request->input('query.generalSearch', $this->request->input('search.value')) != '';
    }
}
