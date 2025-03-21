<?php

// app/Http/Controllers/Api/DepartmentController.php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DepartmentController extends Controller
{
    public function index(Organization $organization)
    {
        //$this->authorize('view', $organization);

        $departments = $organization->departments;

        return response()->json($departments);
    }

    public function store(Request $request, Organization $organization)
    {
        //$this->authorize('update', $organization);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:7',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $department = $organization->departments()->create($request->all());

        return response()->json($department, 201);
    }

    public function show(Organization $organization, Department $department)
    {
        //$this->authorize('view', $organization);

        if ($department->organization_id !== $organization->id) {
            return response()->json(['message' => 'Department does not belong to this organization'], 403);
        }

        $department->load('positions');

        return response()->json($department);
    }

    public function update(Request $request, Organization $organization, Department $department)
    {
        //$this->authorize('update', $organization);

        if ($department->organization_id !== $organization->id) {
            return response()->json(['message' => 'Department does not belong to this organization'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'code' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:7',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $department->update($request->all());

        return response()->json($department);
    }

    public function destroy(Organization $organization, Department $department)
    {
        //$this->authorize('update', $organization);

        if ($department->organization_id !== $organization->id) {
            return response()->json(['message' => 'Department does not belong to this organization'], 403);
        }

        $department->delete();

        return response()->json(null, 204);
    }
}