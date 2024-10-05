<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDefaultCategoryRequest;
use App\Http\Requests\UpdateDefaultCategoryRequest;
use App\Http\Resources\DefaultCategoryResource;
use App\Models\DefaultCategory;
use Illuminate\Http\Request;

class DefaultCategoryController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(DefaultCategory::class, 'defaultCategory');
    }

    public function index(Request $request)
    {
        $filterByType = $request->type ?? '';
        $filterByName = $request->name ?? '';

        $query = DefaultCategory::query();

        if ($filterByType !== "") {
            $query->where('type', '=', $filterByType);
        }

        if ($filterByName !== "") {
            $query->whereRaw('UPPER(name) LIKE ?', ['%' . strtoupper($filterByName) . '%']);
        }

        // Paginate the results
        $categories = $query->paginate(10);

        return DefaultCategoryResource::collection($categories);
    }

    public function show(DefaultCategory $defaultCategory)
    {
        return new DefaultCategoryResource($defaultCategory);
    }

    public function store(StoreDefaultCategoryRequest $request)
    {
        $dataToSave = $request->validated();
        $defaultCategory = new DefaultCategory();

        if (DefaultCategory::query()->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($request->name) . '%'])->where('type', $request->type)->count() > 0) {
            return response()->json(['message' => 'Default Category already exists!'], 409);
        }

        $defaultCategory->type = $dataToSave['type'];
        $defaultCategory->name = $dataToSave['name'];
        $defaultCategory->custom_options = $dataToSave['custom_options'] ?? null;
        $defaultCategory->custom_data = $dataToSave['custom_data'] ?? null;

        $defaultCategory->save();
        return new DefaultCategoryResource($defaultCategory);
    }

    public function update(UpdateDefaultCategoryRequest $request, DefaultCategory $defaultCategory)
    {
        $dataToSave = $request->validated();

        $defaultCategory->type = $dataToSave['type'] ?? $defaultCategory->type;
        $defaultCategory->name = $dataToSave['name'] ?? $defaultCategory->name;
        $defaultCategory->custom_options = $dataToSave['custom_options'] ?? $defaultCategory->custom_options;
        $defaultCategory->custom_data = $dataToSave['custom_data'] ?? $defaultCategory->custom_data;

        $defaultCategory->save();
        return new DefaultCategoryResource($defaultCategory);
    }

    public function destroy(DefaultCategory $defaultCategory)
    {
        $defaultCategory->delete();
        return new DefaultCategoryResource($defaultCategory);
    }
}
