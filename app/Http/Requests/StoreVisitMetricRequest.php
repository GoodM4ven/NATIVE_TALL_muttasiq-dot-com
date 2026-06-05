<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Services\Support\Enums\ViewName;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\In;

class StoreVisitMetricRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string|In>>
     */
    public function rules(): array
    {
        return [
            'view' => [
                'required',
                'string',
                Rule::in(array_map(
                    static fn (ViewName $viewName): string => $viewName->value,
                    ViewName::cases(),
                )),
            ],
        ];
    }
}
