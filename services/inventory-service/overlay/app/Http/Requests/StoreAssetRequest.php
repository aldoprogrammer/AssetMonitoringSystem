<?php

namespace App\Http\Requests;

use App\Models\Asset;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'serial_number' => ['required', 'string', 'max:100', 'unique:assets,serial_number'],
            'asset_tag' => ['required', 'string', 'max:100', 'unique:assets,asset_tag'],
            'specs' => ['required', 'array'],
            'status' => ['required', Rule::in([
                Asset::STATUS_AVAILABLE,
                Asset::STATUS_ASSIGNED,
                Asset::STATUS_MAINTENANCE,
                Asset::STATUS_RETIRED,
            ])],
        ];
    }
}
