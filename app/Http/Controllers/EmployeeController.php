<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function index()
    {
        $employees = Employee::with(['position', 'structure'])->get();

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
            'order' => 'required|integer',
            'position_id' => 'required|integer|exists:positions,id',
            'structure_id' => 'required|integer|exists:structure,id',
        ]);

        $employee = new Employee();
        $fillable = $employee->getFillable();
        foreach ($fillable as $field)
        {
            if ($request->has($field) && $request->filled($field))
            {
                $employee->$field = $request->input($field);
            }
        }
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
            'order' => 'nullable|integer',
            'position_id' => 'nullable|integer|exists:positions,id',
            'structure_id' => 'nullable|integer|exists:structure,id',
        ]);

        $employee = Employee::find($id);
        $fillable = $employee->getFillable();
        foreach ($fillable as $field)
        {
            if ($request->has($field) && $request->filled($field))
            {
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
        $employee->delete();

        return response()->json(['message' => 'İşçi uğurla silindi']);
    }
}
