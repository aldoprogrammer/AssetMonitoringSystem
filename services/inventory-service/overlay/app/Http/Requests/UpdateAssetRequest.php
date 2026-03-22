<?php

namespace App\Http\Requests;

use App\Models\Asset;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $assetId = \App\Models\Asset::query()
            ->where('uuid', (string) $this->route('asset'))
            ->value('id');

        return [
            'serial_number' => ['sometimes', 'string', 'max:100', Rule::unique('assets', 'serial_number')->ignore($assetId)],
            'asset_tag' => ['sometimes', 'string', 'max:100', Rule::unique('assets', 'asset_tag')->ignore($assetId)],
            'specs' => ['sometimes', 'array'],
            'status' => ['sometimes', Rule::in([
                Asset::STATUS_AVAILABLE,
                Asset::STATUS_ASSIGNED,
                Asset::STATUS_MAINTENANCE,
                Asset::STATUS_RETIRED,
            ])],
        ];
    }
}
