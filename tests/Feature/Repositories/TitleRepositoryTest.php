<?php

use App\Data\TitleData;
use App\Models\Title;
use App\Repositories\TitleRepository;

test('it can create a title', function () {
    $data = new TitleData('Example Name Title', null);

    (new TitleRepository())->create($data);

    expect(Title::latest()->first())
        ->name->toEqual('Example Name Title');
});
