<?php

// app/Http/Controllers/Api/PositionController.php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\Position;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PositionController extends Controller
{
    public function index(Organization $organization)
    {
        $this->authorize('view', $organization);

        $positions = $organization->positions()->with('department')->get();

        return response()->json($positions);
    }

    public function store(Request $request, Organization $organization)
    {
        $this->authorize('update', $organization);

        $validator = Validator::make($request->all(), [
            'department_id' => 'nullable|exists:departments,id',
            'title' => 'required|string|max:255',
            'employee_id' => 'nullable|string|max:50',
            'grade' => 'nullable|string|max:50',
            'function' => 'nullable|string|max:100',
            'sub_function' => 'nullable|string|max:100',
            'region' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'office' => 'nullable|string|max:100',
            'tenure' => 'nullable|integer',
            'fully_loaded_cost' => 'nullable|numeric',
            'cost_center' => 'nullable|string|max:50',
            'contract_type' => 'nullable|string|max:50',
            'manager_id' => 'nullable|string|max:50',
            'name' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Ensure department belongs to the organization
        if ($request->has('department_id')) {
            $department = $organization->departments()->find($request->department_id);
            if (!$department) {
                return response()->json(['message' => 'Department does not belong to this organization'], 422);
            }
        }

        $position = $organization->positions()->create($request->all());

        return response()->json($position, 201);
    }

    public function show(Organization $organization, Position $position)
    {
        $this->authorize('view', $organization);

        if ($position->organization_id !== $organization->id) {
            return response()->json(['message' => 'Position does not belong to this organization'], 403);
        }

        $position->load('department', 'directReports.directReport', 'manager.manager');

        return response()->json($position);
    }

    public function update(Request $request, Organization $organization, Position $position)
    {
        $this->authorize('update', $organization);

        if ($position->organization_id !== $organization->id) {
            return response()->json(['message' => 'Position does not belong to this organization'], 403);
        }

        $validator = Validator::make($request->all(), [
            'department_id' => 'nullable|exists:departments,id',
            'title' => 'sometimes|required|string|max:255',
            'employee_id' => 'nullable|string|max:50',
            'grade' => 'nullable|string|max:50',
            'function' => 'nullable|string|max:100',
            'sub_function' => 'nullable|string|max:100',
            'region' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'office' => 'nullable|string|max:100',
            'tenure' => 'nullable|integer',
            'fully_loaded_cost' => 'nullable|numeric',
            'cost_center' => 'nullable|string|max:50',
            'contract_type' => 'nullable|string|max:50',
            'manager_id' => 'nullable|string|max:50',
            'name' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Ensure department belongs to the organization
        if ($request->has('department_id')) {
            $department = $organization->departments()->find($request->department_id);
            if (!$department && $request->department_id !== null) {
                return response()->json(['message' => 'Department does not belong to this organization'], 422);
            }
        }

        $position->update($request->all());

        return response()->json($position);
    }

    public function destroy(Organization $organization, Position $position)
    {
        $this->authorize('update', $organization);

        if ($position->organization_id !== $organization->id) {
            return response()->json(['message' => 'Position does not belong to this organization'], 403);
        }

        $position->delete();

        return response()->json(null, 204);
    }
}
