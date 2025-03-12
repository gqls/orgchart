<?php

// app/Http/Controllers/Api/OrganizationController.php
namespace App\Http\Controllers\Api;

use App\app\app\Http\Controllers\Controller;
use App\app\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use function App\Http\Controllers\Api\response;

class OrganizationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $organizations = $user->organizations;

        return response()->json($organizations);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'logo' => 'nullable|image|max:2048',
            'primary_color' => 'nullable|string|max:7',
            'secondary_color' => 'nullable|string|max:7',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $logoPath = null;
        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('logos', 'public');
        }

        $organization = Organization::create([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'description' => $request->description,
            'logo_path' => $logoPath,
            'primary_color' => $request->primary_color,
            'secondary_color' => $request->secondary_color,
        ]);

        $user = $request->user();
        $user->organizations()->attach($organization->id, ['is_admin' => true]);

        return response()->json($organization, 201);
    }

    public function show(Organization $organization)
    {
        $this->authorize('view', $organization);

        $organization->load('departments', 'strategies');

        return response()->json($organization);
    }

    public function update(Request $request, Organization $organization)
    {
        $this->authorize('update', $organization);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'logo' => 'nullable|image|max:2048',
            'primary_color' => 'nullable|string|max:7',
            'secondary_color' => 'nullable|string|max:7',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if ($request->hasFile('logo')) {
            $organization->logo_path = $request->file('logo')->store('logos', 'public');
        }

        if ($request->has('name')) {
            $organization->name = $request->name;
            $organization->slug = Str::slug($request->name);
        }

        if ($request->has('description')) {
            $organization->description = $request->description;
        }

        if ($request->has('primary_color')) {
            $organization->primary_color = $request->primary_color;
        }

        if ($request->has('secondary_color')) {
            $organization->secondary_color = $request->secondary_color;
        }

        $organization->save();

        return response()->json($organization);
    }

    public function destroy(Organization $organization)
    {
        $this->authorize('delete', $organization);

        $organization->delete();

        return response()->json(null, 204);
    }
}