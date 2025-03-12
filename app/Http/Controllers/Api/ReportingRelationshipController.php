<?php

// app/Http/Controllers/Api/ReportingRelationshipController.php
namespace App\Http\Controllers\Api;

use App\app\app\Http\Controllers\Controller;
use App\app\Models\Organization;
use App\app\Models\ReportingRelationship;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use function App\Http\Controllers\Api\response;

class ReportingRelationshipController extends Controller
{
    public function index(Organization $organization)
    {
        $this->authorize('view', $organization);

        $relationships = $organization->reportingRelationships()
            ->with(['manager', 'directReport'])
            ->get();

        return response()->json($relationships);
    }

    public function store(Request $request, Organization $organization)
    {
        $this->authorize('update', $organization);

        $validator = Validator::make($request->all(), [
            'manager_position_id' => 'required|exists:positions,id',
            'direct_report_position_id' => 'required|exists:positions,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Ensure both positions belong to the organization
        $managerPosition = $organization->positions()->find($request->manager_position_id);
        $directReportPosition = $organization->positions()->find($request->direct_report_position_id);

        if (!$managerPosition || !$directReportPosition) {
            return response()->json(['message' => 'One or both positions do not belong to this organization'], 422);
        }

        // Check if the direct report already has a manager
        $existingRelationship = $organization->reportingRelationships()
            ->where('direct_report_position_id', $request->direct_report_position_id)
            ->first();

        if ($existingRelationship) {
            return response()->json(['message' => 'This position already has a manager'], 422);
        }

        $relationship = $organization->reportingRelationships()->create([
            'manager_position_id' => $request->manager_position_id,
            'direct_report_position_id' => $request->direct_report_position_id,
        ]);

        $relationship->load(['manager', 'directReport']);

        return response()->json($relationship, 201);
    }

    public function update(Request $request, Organization $organization, ReportingRelationship $relationship)
    {
        $this->authorize('update', $organization);

        if ($relationship->organization_id !== $organization->id) {
            return response()->json(['message' => 'Relationship does not belong to this organization'], 403);
        }

        $validator = Validator::make($request->all(), [
            'manager_position_id' => 'required|exists:positions,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Ensure the new manager position belongs to the organization
        $managerPosition = $organization->positions()->find($request->manager_position_id);

        if (!$managerPosition) {
            return response()->json(['message' => 'Manager position does not belong to this organization'], 422);
        }

        $relationship->manager_position_id = $request->manager_position_id;
        $relationship->save();

        $relationship->load(['manager', 'directReport']);

        return response()->json($relationship);
    }

    public function destroy(Organization $organization, ReportingRelationship $relationship)
    {
        $this->authorize('update', $organization);

        if ($relationship->organization_id !== $organization->id) {
            return response()->json(['message' => 'Relationship does not belong to this organization'], 403);
        }

        $relationship->delete();

        return response()->json(null, 204);
    }
}
