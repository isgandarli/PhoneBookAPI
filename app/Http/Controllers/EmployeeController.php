<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'structure_id' => 'nullable|integer|exists:structure,id',
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|string|max:255',
            'phone_number' => 'nullable|string|max:255',
        ]);

        $query = Employee::with(['position', 'structure']);

        if ($request->filled('structure_id')) {
            $query->where('structure_id', $request->input('structure_id'));
        }

        if ($request->filled('name')) {
            $name = $request->input('name');
            $query->where(function ($q) use ($name) {
                $q->where('first_name', 'like', "%{$name}%")
                  ->orWhere('last_name', 'like', "%{$name}%")
                  ->orWhere('father_name', 'like', "%{$name}%");
            });
        }

        if ($request->filled('email')) {
            $query->where('email', 'like', "%{$request->input('email')}%");
        }

        if ($request->filled('phone_number')) {
            $phone = $request->input('phone_number');
            $query->where(function ($q) use ($phone) {
                $q->where('landline_number', 'like', "%{$phone}%")
                  ->orWhere('mobile_number', 'like', "%{$phone}%");
            });
        }

        $employees = $query->orderBy('structure_id')
            ->orderBy('order')
            ->get();

        return response()->json($employees);
    }

    public function show($id)
    {
        $request = request();
        $request->merge(['id' => $id]);

        $request->validate([
            'id' => 'required|integer|exists:employees,id',
        ]);

        $employee = Employee::with(['position', 'structure'])->find($id);

        return response()->json($employee);
    }

    public function store(Request $request)
    {
        $request->validate([
            'first_name' => 'required|alpha',
            'last_name' => 'required|alpha',
            'father_name' => 'alpha|nullable',
            'email' => 'email|nullable',
            'landline_number' => 'required|string|unique:employees,landline_number',
            'mobile_number' => 'string|unique:employees,mobile_number|nullable',
            'description' => 'nullable|string|max:1024',
            'order' => 'nullable|integer',
            'position_id' => 'required|integer|exists:positions,id',
            'structure_id' => 'required|integer|exists:structure,id',
        ]);

        $structureId = $request->input('structure_id');
        $siblings = Employee::where('structure_id', $structureId);

        if ($request->filled('order')) {
            $newOrder = $request->input('order');
            $siblings->where('order', '>=', $newOrder)->increment('order');
        } else {
            $newOrder = ($siblings->max('order') ?? 0) + 1;
        }

        $employee = new Employee();
        $employee->first_name = $request->input('first_name');
        $employee->last_name = $request->input('last_name');
        $employee->father_name = $request->input('father_name');
        $employee->email = $request->input('email');
        $employee->landline_number = $request->input('landline_number');
        $employee->mobile_number = $request->input('mobile_number');
        $employee->description = $request->input('description');
        $employee->order = $newOrder;
        $employee->position_id = $request->input('position_id');
        $employee->structure_id = $structureId;
        $employee->save();

        return response()->json($employee->load(['position', 'structure']), 201);
    }

    public function update(Request $request, $id)
    {
        $request->merge(['id' => $id]);

        $request->validate([
            'id' => 'required|integer|exists:employees,id',
        ]);

        $request->validate([
            'first_name' => 'nullable|alpha',
            'last_name' => 'nullable|alpha',
            'father_name' => 'nullable|alpha',
            'email' => 'nullable|email',
            'landline_number' => 'nullable|string|unique:employees,landline_number,' . $id,
            'mobile_number' => 'nullable|string|unique:employees,mobile_number,' . $id,
            'description' => 'nullable|string|max:1024',
            'order' => 'nullable|integer',
            'position_id' => 'nullable|integer|exists:positions,id',
            'structure_id' => 'nullable|integer|exists:structure,id',
        ]);

        $employee = Employee::find($id);
        $oldStructureId = $employee->structure_id;
        $oldOrder = $employee->order;
        $newStructureId = $request->filled('structure_id') ? $request->input('structure_id') : $oldStructureId;
        $newOrder = $request->filled('order') ? $request->input('order') : null;
        $structureChanged = $newStructureId != $oldStructureId;

        if ($structureChanged) {
            // Close gap in old structure's employees
            Employee::where('structure_id', $oldStructureId)
                ->where('order', '>', $oldOrder)
                ->decrement('order');

            // Make room in new structure's employees
            if ($newOrder !== null) {
                Employee::where('structure_id', $newStructureId)
                    ->where('order', '>=', $newOrder)
                    ->increment('order');
            } else {
                $newOrder = (Employee::where('structure_id', $newStructureId)->max('order') ?? 0) + 1;
            }

            $employee->structure_id = $newStructureId;
            $employee->order = $newOrder;
        } elseif ($newOrder !== null && $newOrder != $oldOrder) {
            if ($newOrder < $oldOrder) {
                Employee::where('structure_id', $oldStructureId)
                    ->where('id', '!=', $employee->id)
                    ->whereBetween('order', [$newOrder, $oldOrder - 1])
                    ->increment('order');
            } else {
                Employee::where('structure_id', $oldStructureId)
                    ->where('id', '!=', $employee->id)
                    ->whereBetween('order', [$oldOrder + 1, $newOrder])
                    ->decrement('order');
            }

            $employee->order = $newOrder;
        }

        // Update remaining fillable fields (except order and structure_id handled above)
        $skipFields = ['order', 'structure_id'];
        $fillable = $employee->getFillable();
        foreach ($fillable as $field) {
            if (in_array($field, $skipFields)) continue;
            if ($request->has($field) && $request->filled($field)) {
                $employee->$field = $request->input($field);
            }
        }

        $employee->save();

        return response()->json($employee->load(['position', 'structure']));
    }

    public function destroy($id)
    {
        $request = request();
        $request->merge(['id' => $id]);

        $request->validate([
            'id' => 'required|integer|exists:employees,id',
        ]);

        $employee = Employee::find($id);
        $structureId = $employee->structure_id;
        $order = $employee->order;

        $employee->delete();

        // Close gap in siblings
        Employee::where('structure_id', $structureId)
            ->where('order', '>', $order)
            ->decrement('order');

        return response()->json(['message' => 'İşçi uğurla silindi']);
    }
}
