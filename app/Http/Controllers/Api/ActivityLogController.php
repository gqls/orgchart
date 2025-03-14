<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ActivityLogController extends Controller
{
    /**
     * Display a listing of activity logs for the organization with filtering options.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Organization $organization
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request, Organization $organization)
    {
        $this->authorize('view', $organization);

        $validator = Validator::make($request->all(), [
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'scenario_id' => 'nullable|exists:scenarios,id',
            'user_id' => 'nullable|exists:users,id',
            'action' => 'nullable|string',
            'per_page' => 'nullable|integer|min:1|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $query = $organization->activityLogs()->with(['user', 'loggable']);

        // Apply filters
        if ($request->has('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->has('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        if ($request->has('scenario_id')) {
            $query->where(function ($q) use ($request) {
                $q->where('loggable_type', 'App\\Models\\Scenario')
                    ->where('loggable_id', $request->scenario_id)
                    ->orWhere(function ($subQ) use ($request) {
                        $subQ->where('loggable_type', 'App\\Models\\ScenarioPosition')
                            ->whereHas('loggable', function ($sQ) use ($request) {
                                $sQ->where('scenario_id', $request->scenario_id);
                            });
                    });
            });
        }

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->has('action')) {
            $query->where('action', $request->action);
        }

        // Order by most recent first
        $query->orderBy('created_at', 'desc');

        // Paginate results
        $perPage = $request->get('per_page', 25);
        $logs = $query->paginate($perPage);

        return response()->json($logs);
    }

    /**
     * Display the specified activity log.
     *
     * @param \App\Models\Organization $organization
     * @param \App\Models\ActivityLog $activityLog
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Organization $organization, ActivityLog $activityLog)
    {
        $this->authorize('view', $organization);

        if ($activityLog->organization_id !== $organization->id) {
            return response()->json(['message' => 'ActivityLog does not belong to this organization'], 403);
        }

        $activityLog->load(['user', 'loggable']);

        return response()->json($activityLog);
    }
}