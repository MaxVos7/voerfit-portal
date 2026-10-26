<?php

namespace App\Http\Requests;

use App\Enums\DayOverviewColumn;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateDayOverviewColumnsRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'columns' => ['required', 'array', 'min:1'],
            'columns.*.key' => ['required', 'distinct', Rule::enum(DayOverviewColumn::class)],
            'columns.*.visible' => ['required', 'boolean'],
        ];
    }

    /**
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $columns = $this->input('columns');

                if (is_array($columns) && ! collect($columns)->contains(fn (mixed $column): bool => is_array($column) && filter_var($column['visible'] ?? false, FILTER_VALIDATE_BOOLEAN))) {
                    $validator->errors()->add('columns', 'Laat minstens één kolom zichtbaar.');
                }
            },
        ];
    }
}
