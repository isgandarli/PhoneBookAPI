<?php

namespace App\Http\Controllers;

use App\Models\Structure_Type;
use Illuminate\Http\Request;

class StructureTypeController extends Controller
{
    public function index()
    {
        $structure_types = Structure_Type::all();

        return response()->json($structure_types);
    }

    public function show($id)
    {
        $request = request();
        $request->merge(['id' => $id]);

        $request->validate([
            'id' => 'required|integer|exists:structure_types,id',
        ]);

        $structure_type = Structure_Type::find($id);

        return response()->json($structure_type);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:structure_types,name',
        ]);

        $structure_type = new Structure_Type();
        $structure_type->name = $request['name'];
        $structure_type->save();

        return response()->json($structure_type, 201);
    }

    public function update(Request $request, $id)
    {
        $request->merge(['id' => $id]);

        $request->validate([
            'id' => 'required|integer|exists:structure_types,id',
            'name' => 'required|string|unique:structure_types,name,' . $id,
        ]);

        $structure_type = Structure_Type::find($id);
        $structure_type->name = $request['name'];
        $structure_type->save();

        return response()->json($structure_type);
    }

    public function destroy($id)
    {
        $request = request();
        $request->merge(['id' => $id]);

        $request->validate([
            'id' => 'required|integer|exists:structure_types,id',
        ]);

        $structure_type = Structure_Type::find($id);
        $structure_type->delete();

        return response()->json(['message' => 'Struktur tipi silindi']);
    }
}
