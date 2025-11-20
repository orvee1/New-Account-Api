<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CategoryRequest;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $q = Category::query()->where('company_id', auth()->user()->company_id);

        if ($s = $request->get('q')) {
            $q->where('name', 'like', "%{$s}%");
        }
        if ($st = $request->get('status')) {
            $q->where('status', $st);
        }

        $q->orderBy('name');
        return response()->json($q->paginate((int)($request->get('per_page', 20))));
    }

    public function store(CategoryRequest $request)
    {
        $data = $request->validated();
        $data['company_id'] = auth()->user()->company_id;
        $cat = Category::create($data);
        return response()->json($cat, 201);
    }

    public function show(Category $category)
    {
        $this->authorizeCompany($category);
        return response()->json($category);
    }

    public function update(CategoryRequest $request, Category $category)
    {
        $this->authorizeCompany($category);
        $category->update($request->validated());
        return response()->json($category);
    }

    public function destroy(Category $category)
    {
        $this->authorizeCompany($category);
        $category->delete();
        return response()->json(['message' => 'Deleted']);
    }

    private function authorizeCompany(Category $category): void
    {
        abort_unless($category->company_id === auth()->user()->company_id, 403, 'Forbidden');
    }
}
