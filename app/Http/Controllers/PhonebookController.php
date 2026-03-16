<?php

namespace App\Http\Controllers;

use App\Models\Employee;

class PhonebookController extends Controller
{
    public function index()
    {
        $employees = Employee::with(['position', 'structure.structure_type'])->get();

        return response()->json($employees);
    }
}
