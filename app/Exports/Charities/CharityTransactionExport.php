<?php

namespace App\Exports\Charities;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class CharityTransactionExport implements FromView, ShouldAutoSize
{
    public function __construct(
        protected Collection $models
    ) {}

    public function view(): View
    {
        return view('charities.exports.excel.index', [
            'models' => $this->models,
        ]);
    }
}
