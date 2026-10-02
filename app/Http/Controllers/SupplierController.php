<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index()
    {
        return response()->json(Supplier::all());
    }

    public function store(Request $request)
    {
        $data = $request->only((new Supplier())->getFillable());
        $item = Supplier::create($data);

        return response()->json($item, 201);
    }

    public function show($id)
    {
        return response()->json(Supplier::findOrFail($id));
    }

    public function update(Request $request, $id)
    {
        $item = Supplier::findOrFail($id);
        $item->update($request->only((new Supplier())->getFillable()));

        return response()->json($item);
    }

    public function destroy($id)
    {
        Supplier::findOrFail($id)->delete();

        return response()->json(null, 204);
    }
}
