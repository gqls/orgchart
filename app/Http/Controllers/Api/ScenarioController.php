<?php

// app/Http/Controllers/Api/ScenarioController.php
namespace App\Http\Controllers\Api;

use App\app\app\Http\Controllers\Controller;
use App\app\Models\Organization;
use App\app\Models\Position;
use App\app\Models\Scenario;
use App\app\Models\ScenarioRelationship;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use function App\Http\Controllers\Api\response;

class ScenarioController extends Controller
{
    public function index(Organization $organization)
    {
        $this->authorize('view', $organization);

        $scenarios = $organization->scenarios()->with('user')->get();

        return response()->json($scenarios);
    }

    public function store(Request $request, Organization $organization)
    {
        $this->authorize('update', $organization);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_base' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Begin transaction
        DB::beginTransaction();

        try {
            // Create the scenario
            $scenario = $organization->scenarios()->create([
                'name' => $request->name,
                'description' => $request->description,
                'user_id' => $request->user()->id,
                'is_base' => $request->is_base ?? false,
                'is_current' => false,
            ]);

            // If this is a base scenario or first scenario for the organization, set it as current
            if ($request->is_base || $organization->scenarios()->count() === 1) {
                // If making this a base scenario, unset any other base scenario
                if ($request->is_base) {
                    $organization->scenarios()->where('id', '!=', $scenario->id)->update(['is_base' => false]);
                }

                // If this is the first scenario, make it current
                if ($organization->scenarios()->count() === 1) {
                    $scenario->is_current = true;
                    $scenario->save();
                }
            }

            // Clone all positions and relationships from the current scenario if exists
            $currentScenario = $organization->getCurrentScenario();

            if ($currentScenario) {
                // Add all positions from current scenario
                $positions = $currentScenario->positions()->get();

                foreach ($positions as $position) {
                    $scenario->positions()->attach($position->id, ['status' => 'unchanged']);
                }

                // Clone all reporting relationships
                $relationships = $currentScenario->relationships()->get();

                foreach ($relationships as $relationship) {
                    ScenarioRelationship::create([
                        'scenario_id' => $scenario->id,
                        'manager_position_id' => $relationship->manager_position_id,
                        'direct_report_position_id' => $relationship->direct_report_position_id,
                    ]);
                }

                // Clone metrics
                $metrics = $currentScenario->metrics()->get();

                foreach ($metrics as $metric) {
                    $pivotData = $currentScenario->metrics()->where('metric_id', $metric->id)->first()->pivot;

                    $scenario->metrics()->attach($metric->id, [
                        'value' => $pivotData->value,
                        'goal' => $pivotData->goal,
                        'benchmark' => $pivotData->benchmark,
                    ]);
                }
            }

            // Calculate all metrics for this scenario
            $scenario->calculateMetrics();

            DB::commit();

            return response()->json($scenario, 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error creating scenario: ' . $e->getMessage()], 500);
        }
    }

    public function show(Organization $organization, Scenario $scenario)
    {
        $this->authorize('view', $organization);

        if ($scenario->organization_id !== $organization->id) {
            return response()->json(['message' => 'Scenario does not belong to this organization'], 403);
        }

        $scenario->load(['positions', 'metrics', 'user']);

        return response()->json($scenario);
    }

    public function update(Request $request, Organization $organization, Scenario $scenario)
    {
        $this->authorize('update', $organization);

        if ($scenario->organization_id !== $organization->id) {
            return response()->json(['message' => 'Scenario does not belong to this organization'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'is_current' => 'boolean',
            'is_base' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Begin transaction
        DB::beginTransaction();

        try {
            // If making this the current scenario, unset any other current scenario
            if ($request->has('is_current') && $request->is_current) {
                $organization->scenarios()->where('id', '!=', $scenario->id)->update(['is_current' => false]);
            }

            // If making this a base scenario, unset any other base scenario
            if ($request->has('is_base') && $request->is_base) {
                $organization->scenarios()->where('id', '!=', $scenario->id)->update(['is_base' => false]);
            }

            $scenario->update($request->all());

            DB::commit();

            return response()->json($scenario);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error updating scenario: ' . $e->getMessage()], 500);
        }
    }

    public function destroy(Organization $organization, Scenario $scenario)
    {
        $this->authorize('update', $organization);

        if ($scenario->organization_id !== $organization->id) {
            return response()->json(['message' => 'Scenario does not belong to this organization'], 403);
        }

        // Don't allow deleting the current scenario
        if ($scenario->is_current) {
            return response()->json(['message' => 'Cannot delete the current scenario'], 422);
        }

        $scenario->delete();

        return response()->json(null, 204);
    }

    public function positions(Organization $organization, Scenario $scenario)
    {
        $this->authorize('view', $organization);

        if ($scenario->organization_id !== $organization->id) {
            return response()->json(['message' => 'Scenario does not belong to this organization'], 403);
        }

        $positions = $scenario->positions()->with('department')->get();

        return response()->json($positions);
    }

    public function addPosition(Request $request, Organization $organization, Scenario $scenario)
    {
        $this->authorize('update', $organization);

        if ($scenario->organization_id !== $organization->id) {
            return response()->json(['message' => 'Scenario does not belong to this organization'], 403);
        }

        $validator = Validator::make($request->all(), [
            'position_id' => 'required|exists:positions,id',
            'status' => 'required|in:unchanged,new,changed,removed',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Ensure position belongs to the organization
        $position = $organization->positions()->find($request->position_id);

        if (!$position) {
            return response()->json(['message' => 'Position does not belong to this organization'], 422);
        }

        // Add position to scenario
        $scenario->positions()->syncWithoutDetaching([
            $request->position_id => ['status' => $request->status]
        ]);

        return response()->json(['message' => 'Position added to scenario']);
    }

    public function updatePosition(Request $request, Organization $organization, Scenario $scenario, Position $position)
    {
        $this->authorize('update', $organization);

        if ($scenario->organization_id !== $organization->id) {
            return response()->json(['message' => 'Scenario does not belong to this organization'], 403);
        }

        if ($position->organization_id !== $organization->id) {
            return response()->json(['message' => 'Position does not belong to this organization'], 403);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:unchanged,new,changed,removed',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Update position status in scenario
        $scenario->positions()->updateExistingPivot($position->id, ['status' => $request->status]);

        return response()->json(['message' => 'Position status updated']);
    }

    public function removePosition(Organization $organization, Scenario $scenario, Position $position)
    {
        $this->authorize('update', $organization);

        if ($scenario->organization_id !== $organization->id) {
            return response()->json(['message' => 'Scenario does not belong to this organization'], 403);
        }

        if ($position->organization_id !== $organization->id) {
            return response()->json(['message' => 'Position does not belong to this organization'], 403);
        }

        // Remove position from scenario
        $scenario->positions()->detach($position->id);

        return response()->json(['message' => 'Position removed from scenario']);
    }

    public function relationships(Organization $organization, Scenario $scenario)
    {
        $this->authorize('view', $organization);

        if ($scenario->organization_id !== $organization->id) {
            return response()->json(['message' => 'Scenario does not belong to this organization'], 403);
        }

        $relationships = $scenario->relationships()->with(['manager', 'directReport'])->get();

        return response()->json($relationships);
    }

    public function addRelationship(Request $request, Organization $organization, Scenario $scenario)
    {
        $this->authorize('update', $organization);

        if ($scenario->organization_id !== $organization->id) {
            return response()->json(['message' => 'Scenario does not belong to this organization'], 403);
        }

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

        // Check if the direct report already has a manager in this scenario
        $existingRelationship = $scenario->relationships()
            ->where('direct_report_position_id', $request->direct_report_position_id)
            ->first();

        if ($existingRelationship) {
            return response()->json(['message' => 'This position already has a manager in this scenario'], 422);
        }

        // Add relationship to scenario
        $relationship = $scenario->relationships()->create([
            'manager_position_id' => $request->manager_position_id,
            'direct_report_position_id' => $request->direct_report_position_id,
        ]);

        $relationship->load(['manager', 'directReport']);

        return response()->json($relationship, 201);
    }

    public function updateRelationship(Request $request, Organization $organization, Scenario $scenario, ScenarioRelationship $relationship)
    {
        $this->authorize('update', $organization);

        if ($scenario->organization_id !== $organization->id) {
            return response()->json(['message' => 'Scenario does not belong to this organization'], 403);
        }

        if ($relationship->scenario_id !== $scenario->id) {
            return response()->json(['message' => 'Relationship does not belong to this scenario'], 403);
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

    public function removeRelationship(Organization $organization, Scenario $scenario, ScenarioRelationship $relationship)
    {
        $this->authorize('update', $organization);

        if ($scenario->organization_id !== $organization->id) {
            return response()->json(['message' => 'Scenario does not belong to this organization'], 403);
        }

        if ($relationship->scenario_id !== $scenario->id) {
            return response()->json(['message' => 'Relationship does not belong to this scenario'], 403);
        }

        $relationship->delete();

        return response()->json(['message' => 'Relationship removed from scenario']);
    }

    public function calculateMetrics(Organization $organization, Scenario $scenario)
    {
        $this->authorize('view', $organization);

        if ($scenario->organization_id !== $organization->id) {
            return response()->json(['message' => 'Scenario does not belong to this organization'], 403);
        }

        // Calculate metrics for this scenario
        $scenario->calculateMetrics();

        // Return the updated metrics
        $metrics = $scenario->metrics()->get()->map(function ($metric) use ($scenario) {
            $metric->pivot_data = $scenario->metrics()->where('metric_id', $metric->id)->first()->pivot;
            return $metric;
        });

        return response()->json($metrics);
    }

    public function compareScenarios(Request $request, Organization $organization)
    {
        $this->authorize('view', $organization);

        $validator = Validator::make($request->all(), [
            'scenario1_id' => 'required|exists:scenarios,id',
            'scenario2_id' => 'required|exists:scenarios,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Ensure both scenarios belong to the organization
        $scenario1 = $organization->scenarios()->find($request->scenario1_id);
        $scenario2 = $organization->scenarios()->find($request->scenario2_id);

        if (!$scenario1 || !$scenario2) {
            return response()->json(['message' => 'One or both scenarios do not belong to this organization'], 422);
        }

        // Load metrics for both scenarios
        $metrics1 = $scenario1->metrics()->get()->keyBy('id');
        $metrics2 = $scenario2->metrics()->get()->keyBy('id');

        // Compare metrics
        $comparison = [];

        foreach ($metrics1 as $id => $metric) {
            $comparison[] = [
                'metric' => $metric->name,
                'code' => $metric->code,
                'unit' => $metric->unit,
                'format' => $metric->format,
                'scenario1_value' => $metric->pivot->value,
                'scenario2_value' => isset($metrics2[$id]) ? $metrics2[$id]->pivot->value : null,
                'difference' => isset($metrics2[$id]) ? $metric->pivot->value - $metrics2[$id]->pivot->value : null,
                'difference_percentage' => isset($metrics2[$id]) && $metrics2[$id]->pivot->value != 0 ?
                    (($metric->pivot->value - $metrics2[$id]->pivot->value) / $metrics2[$id]->pivot->value) * 100 : null,
            ];
        }

        // Add metrics that are in scenario2 but not in scenario1
        foreach ($metrics2 as $id => $metric) {
            if (!isset($metrics1[$id])) {
                $comparison[] = [
                    'metric' => $metric->name,
                    'code' => $metric->code,
                    'unit' => $metric->unit,
                    'format' => $metric->format,
                    'scenario1_value' => null,
                    'scenario2_value' => $metric->pivot->value,
                    'difference' => $metric->pivot->value,
                    'difference_percentage' => null,
                ];
            }
        }

        return response()->json([
            'scenario1' => $scenario1,
            'scenario2' => $scenario2,
            'comparison' => $comparison,
        ]);
    }
}
