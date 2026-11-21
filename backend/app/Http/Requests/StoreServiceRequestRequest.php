<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Enums\ServiceRequestPriority;

class StoreServiceRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => [
                'required',
                'string',
                'min:3',
                'max:100',
            ],

            'description' => [
                'required',
                'string',
                'min:10',
                'max:500',
            ],

            'priority' => [
                'required',
                Rule::enum(ServiceRequestPriority::class),
            ],
        ];
    }
}