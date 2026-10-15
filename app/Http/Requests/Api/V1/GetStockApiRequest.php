<?php

namespace App\Http\Requests\Api\V1;

class GetStockApiRequest extends BaseApiRequest
{
    public function rules(): array
    {
        return [
            'page_size' => ['nullable', 'integer', 'min:1', 'max:500'],
            'page_number' => ['nullable', 'integer', 'min:1'],
            'product_gtin' => ['nullable', 'string'],
            'material_code' => ['nullable', 'string'],
        ];
    }
}
