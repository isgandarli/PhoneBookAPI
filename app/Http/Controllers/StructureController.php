<?php

namespace App\Http\Controllers;

use App\Models\Structure;
use Illuminate\Http\Request;

class StructureController extends Controller
{
    public function index()
    {
        $structures = Structure::with('structure_type')->get();

        return response()->json($structures);
    }

    public function show($id)
    {
        $request = request();
        $request->merge(['id' => $id]);

        $request->validate([
            'id' => 'required|integer|exists:structure,id',
        ]);

        $structure = Structure::with('structure_type')->find($id);

        return response()->json($structure);
    }

    public function store(Request $request)
    {
        $request->validate([
            'structure_type_id' => 'required|integer|exists:structure_types,id',
            'name' => 'required|string|unique:structure,name',
            'description' => 'nullable|string',
            'order' => 'required|integer',
            'parent_id' => 'nullable|integer|exists:structure,id',
        ]);

        $structure = new Structure();
        $structure->structure_type_id = $request['structure_type_id'];
        $structure->name = $request['name'];
        $structure->description = $request['description'] ?? null;
        $structure->order = $request['order'];
        $structure->parent_id = $request['parent_id'] ?? null;
        $structure->save();

        return response()->json($structure, 201);
    }

    public function update(Request $request, $id)
    {
        $request->merge(['id' => $id]);

        $request->validate([
            'id' => 'required|integer|exists:structure,id',
        ]);

        $request->validate([
            'parent_id' => 'nullable|integer|exists:structure,id',
            'structure_type_id' => 'nullable|integer|exists:structure_types,id',
            'name' => 'nullable|string|unique:structure,name,' . $id,
            'description' => 'nullable|string',
            'order' => 'nullable|integer',
        ]);

        $structure = Structure::find($id);
        $fillable = $structure->getFillable();
        foreach ($fillable as $field)
        {
            if ($request->has($field) && $request->filled($field))
            {
                $structure->$field = $request->input($field);
            }
        }
        $structure->save();

        return response()->json($structure);
    }

    public function destroy($id)
    {
        $request = request();
        $request->merge(['id' => $id]);

        $request->validate([
            'id' => 'required|integer|exists:structure,id',
        ]);

        $structure = Structure::find($id);
        $structure->delete();

        return response()->json(['message' => 'Struktur silindi']);
    }
}
