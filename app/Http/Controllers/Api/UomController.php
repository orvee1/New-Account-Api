<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UnitOfMeasure;
use Illuminate\Http\Request;

class UomController extends Controller
{
    public function index()
    {
        return response()->json(UnitOfMeasure::all());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'symbol' => 'required|string|max:50',
        ]);

        $uom = UnitOfMeasure::create($validated);
        return response()->json($uom, 201);
    }
}
