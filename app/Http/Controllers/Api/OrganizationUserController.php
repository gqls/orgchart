<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class OrganizationUserController extends Controller
{
    /**
     * Get users belonging to organization
     */
    public function index(Organization $organization)
    {
        //$this->authorize('view', $organization);

        // Get users with pivot data and role
        $users = $organization->users()->with('role')->get();

        return response()->json($users);
    }

    /**
     * Invite a user to the organization
     */
    public function invite(Request $request, Organization $organization)
    {
        //$this->authorize('update', $organization);

        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'role_id' => 'nullable|exists:roles,id',
            'is_admin' => 'boolean',
            'message' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Check if user already exists
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            // Create user with temporary password
            $password = Str::random(12);
            $user = User::create([
                'name' => explode('@', $request->email)[0], // Temporary name from email
                'email' => $request->email,
                'password' => bcrypt($password),
                'role_id' => $request->role_id,
            ]);

            // TODO: Send invitation email with temporary password
        }

        // Attach user to organization
        $organization->users()->syncWithoutDetaching([
            $user->id => ['is_admin' => $request->is_admin ? true : false]
        ]);

        return response()->json(['message' => 'User invited successfully'], 201);
    }

    /**
     * Update user role and permissions within organization
     */
    public function update(Request $request, Organization $organization, User $user)
    {
        //$this->authorize('update', $organization);

        $validator = Validator::make($request->all(), [
            'role_id' => 'nullable|exists:roles,id',
            'is_admin' => 'boolean',
            'permissions' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Check if user belongs to organization
        if (!$organization->users()->where('user_id', $user->id)->exists()) {
            return response()->json(['message' => 'User is not a member of this organization'], 403);
        }
        // Capture old values for logging
        $oldValues = $organization->only(['name', 'description', 'primary_color', 'secondary_color']);

        // Log the changes
        ActivityLog::create([
            'user_id' => $request->user()->id,
            'organization_id' => $organization->id,
            'loggable_id' => $organization->id,
            'loggable_type' => get_class($organization),
            'action' => 'updated',
            'old_values' => $oldValues,
            'new_values' => $organization->only(['name', 'description', 'primary_color', 'secondary_color']),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);


        // Update pivot data
        $organization->users()->updateExistingPivot($user->id, [
            'is_admin' => $request->is_admin,
            'permissions' => $request->has('permissions') ? json_encode($request->permissions) : null,
        ]);

        // Update role if provided
        if ($request->has('role_id')) {
            $user->role_id = $request->role_id;
            $user->save();
        }

        return response()->json(['message' => 'User updated successfully']);
    }

    /**
     * Remove user from organization
     */
    public function remove(Organization $organization, User $user)
    {
        //$this->authorize('update', $organization);

        // Check if user belongs to organization
        if (!$organization->users()->where('user_id', $user->id)->exists()) {
            return response()->json(['message' => 'User is not a member of this organization'], 403);
        }

        // Don't allow removing the last admin
        $adminCount = $organization->users()->wherePivot('is_admin', true)->count();
        $isUserAdmin = $organization->users()->where('user_id', $user->id)->wherePivot('is_admin', true)->exists();

        if ($adminCount === 1 && $isUserAdmin) {
            return response()->json(['message' => 'Cannot remove the last admin from organization'], 422);
        }

        // Detach user from organization
        $organization->users()->detach($user->id);

        return response()->json(['message' => 'User removed from organization']);
    }
}