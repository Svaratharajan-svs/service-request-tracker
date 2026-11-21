<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RequestStatusHistoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'from_status' => $this->from_status?->value,

            'to_status' => $this->to_status->value,

            'version' => $this->version,

            'changed_by' => [
                'id' => $this->changedBy->id,
                'name' => $this->changedBy->name,
            ],

            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}