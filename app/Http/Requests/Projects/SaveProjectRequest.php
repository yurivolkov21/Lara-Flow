<?php

namespace App\Http\Requests\Projects;

use App\Enums\ProjectColor;
use App\Enums\ProjectSort;
use App\Enums\ProjectView;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveProjectRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $required = $this->isMethod('POST') ? 'required' : 'sometimes';

        return [
            'name' => [$required, 'string', 'max:100'],
            'color' => ['sometimes', Rule::enum(ProjectColor::class)],
            'icon' => ['sometimes', 'nullable', 'string', 'max:50', 'regex:/^[a-z0-9-]+$/'],
            'default_view' => ['sometimes', Rule::enum(ProjectView::class)],
            'sort_by' => ['sometimes', Rule::enum(ProjectSort::class)],
        ];
    }
}
