<?php

namespace App\Http\Resources\Charities;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CharityTransactionSummaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return is_array($this->resource) ? $this->resource : [];
    }
}
