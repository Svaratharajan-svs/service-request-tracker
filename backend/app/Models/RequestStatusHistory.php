<?php

namespace App\Models;

use App\Enums\ServiceRequestStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequestStatusHistory extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'service_request_id',
        'changed_by_user_id',
        'from_status',
        'to_status',
        'version',
    ];

    protected function casts(): array
    {
        return [
            'from_status' => ServiceRequestStatus::class,
            'to_status' => ServiceRequestStatus::class,
            'version' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function serviceRequest(): BelongsTo
    {
        return $this->belongsTo(ServiceRequest::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'changed_by_user_id'
        );
    }
}