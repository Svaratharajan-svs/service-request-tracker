<?php

namespace App\Http\Controllers\Api;

use App\Enums\ServiceRequestStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreServiceRequestRequest;
use App\Http\Requests\UpdateServiceRequestStatusRequest;
use App\Http\Resources\RequestStatusHistoryResource;
use App\Http\Resources\ServiceRequestResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use App\Enums\ServiceRequestPriority;
class ServiceRequestController extends Controller
{
    /**
     * List requests belonging to the authenticated employee.
     */
    public function index(Request $request)
    {
        $filters = $request->validate([
            'status' => [
                'sometimes',
                'nullable',
                Rule::enum(ServiceRequestStatus::class),
            ],
            'priority' => [
                'sometimes',
                'nullable',
                Rule::enum(ServiceRequestPriority::class),
            ],
            'search' => [
                'sometimes',
                'nullable',
                'string',
                'max:150',
            ],
            'per_page' => [
                'sometimes',
                'integer',
                'min:1',
                'max:100',
            ],
        ]);

        $query = $request->user()
            ->serviceRequests()
            ->with('creator')
            ->latest();

        // Filter by status
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Filter by priority
        if (!empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        // Search by title or description
        if (!empty($filters['search'])) {
            $search = $filters['search'];

            $driver = DB::connection()->getDriverName();
            $operator = $driver === 'pgsql' ? 'ILIKE' : 'LIKE';

            $query->where(function ($query) use ($search, $operator) {
                $searchTerm = '%' . $search . '%';

                $query->where('title', $operator, $searchTerm)
                    ->orWhere('description', $operator, $searchTerm);
            });
        }

        $requests = $query->paginate(
            $filters['per_page'] ?? 10
        );

        return ServiceRequestResource::collection($requests);
    }

    /**
     * Create a new service request.
     */
    public function store(StoreServiceRequestRequest $request)
    {
        $serviceRequest = DB::transaction(function () use ($request) {
            $user = $request->user();

            $serviceRequest = $user->serviceRequests()->create([
                'title' => $request->validated('title'),
                'description' => $request->validated('description'),
                'priority' => $request->validated('priority'),
                'status' => ServiceRequestStatus::OPEN,
                'version' => 1,
            ]);

            $serviceRequest->histories()->create([
                'changed_by_user_id' => $user->id,
                'from_status' => null,
                'to_status' => ServiceRequestStatus::OPEN,
                'version' => 1,
            ]);

            return $serviceRequest->load('creator');
        });

        return (new ServiceRequestResource($serviceRequest))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Get one request belonging to the authenticated employee.
     */
    public function show(Request $request, int $id)
    {
        $serviceRequest = $request->user()
            ->serviceRequests()
            ->with('creator')
            ->findOrFail($id);

        return new ServiceRequestResource($serviceRequest);
    }

    /**
     * Change status using optimistic concurrency control.
     */
    public function updateStatus(
        UpdateServiceRequestStatusRequest $request,
        int $id
    ): JsonResponse {
        $data = $request->validated();

        $user = $request->user();

        $expectedVersion = (int) $data['expected_version'];

        $nextStatus = ServiceRequestStatus::from($data['status']);

        $result = DB::transaction(function () use ($user, $id, $expectedVersion, $nextStatus) {
            // 1. Retrieve the request within the authenticated user's scope.
            $serviceRequest = $user->serviceRequests()
                ->lockForUpdate()
                ->findOrFail($id);

            // 2. Check optimistic concurrency.
            if ($serviceRequest->version !== $expectedVersion) {
                return [
                    'conflict' => true,
                    'current_version' => $serviceRequest->version,
                ];
            }

            $currentStatus = $serviceRequest->status;

            // 3. Validate the requested state transition.
            if (!$currentStatus->canTransitionTo($nextStatus)) {
                throw ValidationException::withMessages([
                    'status' => [
                        "Transition from {$currentStatus->value} "
                        . "to {$nextStatus->value} is not allowed.",
                    ],
                ]);
            }

            $newVersion = $expectedVersion + 1;

            // 4. Execute an atomic compare-and-swap update.
            $updated = $user->serviceRequests()
                ->whereKey($serviceRequest->id)
                ->where('version', $expectedVersion)
                ->where('status', $currentStatus->value)
                ->update([
                    'status' => $nextStatus->value,
                    'version' => $newVersion,
                ]);

            // 5. Handle concurrent modification.
            if ($updated !== 1) {
                $currentVersion = $user->serviceRequests()
                    ->whereKey($serviceRequest->id)
                    ->value('version');

                return [
                    'conflict' => true,
                    'current_version' => $currentVersion,
                ];
            }

            // 6. Insert history inside the same transaction.
            // If this fails, Laravel rolls back the status update too.
            $serviceRequest->histories()->create([
                'changed_by_user_id' => $user->id,
                'from_status' => $currentStatus,
                'to_status' => $nextStatus,
                'version' => $newVersion,
            ]);

            // 7. Return the updated model with creator relationship.
            return [
                'conflict' => false,
                'request' => $serviceRequest->fresh()->load('creator'),
            ];
        });

        // 8. Return HTTP 409 for stale versions.
        if ($result['conflict']) {
            return response()->json([
                'success' => false,
                'message' => 'This request was modified by another operation.',
                'error' => [
                    'code' => 'VERSION_CONFLICT',
                    'current_version' => $result['current_version'],
                ],
            ], 409);
        }

        // 9. Return the updated resource.
        return response()->json([
            'success' => true,
            'message' => 'Request status updated successfully.',
            'data' => new ServiceRequestResource($result['request']),
        ]);
    }

    /**
     * Get history for an employee's own request.
     */
    public function history(Request $request, int $id)
    {
        $serviceRequest = $request->user()
            ->serviceRequests()
            ->findOrFail($id);

        $histories = $serviceRequest->histories()
            ->with('changedBy')
            ->orderBy('version')
            ->get();

        return RequestStatusHistoryResource::collection($histories);
    }
}
