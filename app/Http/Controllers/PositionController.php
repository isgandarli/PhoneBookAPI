<?php

namespace App\Http\Controllers;

use App\Models\Position;
use Illuminate\Http\Request;

class PositionController extends Controller
{
    public function index()
    {
        $positions = Position::all();

        return response()->json($positions);
    }

    public function show($id)
    {
        $request = request();
        $request->merge(['id' => $id]);

        $request->validate([
            'id' => 'required|integer|exists:positions,id',
        ]);

        $position = Position::find($id);

        return response()->json($position);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:positions,name',
        ]);

        $position = new Position();
        $position->name = $request['name'];
        $position->save();

        return response()->json($position, 201);
    }

    public function update(Request $request, $id)
    {
        $request->merge(['id' => $id]);

        $request->validate([
            'id' => 'required|integer|exists:positions,id',
            'name' => 'required|string|unique:positions,name,' . $id,
        ]);

        $position = Position::find($id);
        $position->name = $request['name'];
        $position->save();

        return response()->json($position);
    }

    public function destroy($id)
    {
        $request = request();
        $request->merge(['id' => $id]);

        $request->validate([
            'id' => 'required|integer|exists:positions,id',
        ]);

        $position = Position::find($id);
        $position->delete();

        return response()->json(['message' => 'Vəzifə silindi']);
    }
}
