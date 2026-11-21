<?php

namespace App\Http\Requests;

use App\Enums\ServiceRequestStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateServiceRequestStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'status' => [
                'required',
                Rule::enum(ServiceRequestStatus::class),
            ],

            'expected_version' => [
                'required',
                'integer',
                'min:1',
            ],
        ];
    }
}