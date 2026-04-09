<?php

namespace App\Http\Controllers;

use App\Models\Structure;
use Illuminate\Http\Request;

class StructureController extends Controller
{
    public function index()
    {
        $structures = Structure::with('structure_type')
            ->orderBy('parent_id')
            ->orderBy('order')
            ->get();

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
            'order' => 'nullable|integer',
            'parent_id' => 'nullable|integer|exists:structure,id',
        ]);

        $parentId = $request->input('parent_id');
        $siblings = Structure::where('parent_id', $parentId);

        if ($request->filled('order')) {
            $newOrder = $request->input('order');
            // Shift siblings at or after the requested position
            $siblings->where('order', '>=', $newOrder)->increment('order');
        } else {
            // Append at the end
            $newOrder = ($siblings->max('order') ?? 0) + 1;
        }

        $structure = new Structure();
        $structure->structure_type_id = $request->input('structure_type_id');
        $structure->name = $request->input('name');
        $structure->description = $request->input('description');
        $structure->order = $newOrder;
        $structure->parent_id = $parentId;
        $structure->save();

        return response()->json($structure->load('structure_type'), 201);
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
        $oldParentId = $structure->parent_id;
        $oldOrder = $structure->order;
        $newParentId = $request->filled('parent_id') ? $request->input('parent_id') : $oldParentId;
        $newOrder = $request->filled('order') ? $request->input('order') : null;
        $parentChanged = $newParentId != $oldParentId;

        if ($parentChanged) {
            // Close gap in old parent's children
            Structure::where('parent_id', $oldParentId)
                ->where('order', '>', $oldOrder)
                ->decrement('order');

            // Make room in new parent's children
            if ($newOrder !== null) {
                Structure::where('parent_id', $newParentId)
                    ->where('order', '>=', $newOrder)
                    ->increment('order');
            } else {
                $newOrder = (Structure::where('parent_id', $newParentId)->max('order') ?? 0) + 1;
            }

            $structure->parent_id = $newParentId;
            $structure->order = $newOrder;
        } elseif ($newOrder !== null && $newOrder != $oldOrder) {
            // Reorder within the same parent
            if ($newOrder < $oldOrder) {
                // Moving up: shift items between [newOrder, oldOrder) down
                Structure::where('parent_id', $oldParentId)
                    ->where('id', '!=', $structure->id)
                    ->whereBetween('order', [$newOrder, $oldOrder - 1])
                    ->increment('order');
            } else {
                // Moving down: shift items between (oldOrder, newOrder] up
                Structure::where('parent_id', $oldParentId)
                    ->where('id', '!=', $structure->id)
                    ->whereBetween('order', [$oldOrder + 1, $newOrder])
                    ->decrement('order');
            }

            $structure->order = $newOrder;
        }

        // Update remaining fillable fields (except order and parent_id which are handled above)
        $skipFields = ['order', 'parent_id'];
        $fillable = $structure->getFillable();
        foreach ($fillable as $field) {
            if (in_array($field, $skipFields)) continue;
            if ($request->has($field) && $request->filled($field)) {
                $structure->$field = $request->input($field);
            }
        }

        $structure->save();

        return response()->json($structure->load('structure_type'));
    }

    public function destroy($id)
    {
        $request = request();
        $request->merge(['id' => $id]);

        $request->validate([
            'id' => 'required|integer|exists:structure,id',
        ]);

        $structure = Structure::find($id);
        $parentId = $structure->parent_id;
        $order = $structure->order;

        $structure->delete();

        // Close gap in siblings
        Structure::where('parent_id', $parentId)
            ->where('order', '>', $order)
            ->decrement('order');

        return response()->json(['message' => 'Struktur silindi']);
    }
}
