<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Vcard;
use App\Models\Category;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\CategoryResource;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;

class CategoryController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Category::class, 'category', [
            'except' => ['getTransactions'],
        ]);
    }

    public function index()
    {
        return CategoryResource::collection(Category::all());
    }

    public function show(Category $category)
    {
        return new CategoryResource($category);
    }

    public function store(StoreCategoryRequest $request)
    {
        $dataToSave = $request->validated();
        $category = new Category();

        if (Category::query()->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($request->name) . '%'])->where('vcard', $request->vcard)->where('type', $request->type)->count() > 0) {
            return response()->json(['message' => 'Category already exists!'], 409);
        }

        $category->vcard = $dataToSave['vcard'];
        $category->type = $dataToSave['type'];
        $category->name = $dataToSave['name'];
        $category->custom_options = $dataToSave['custom_options'] ?? null;
        $category->custom_data = $dataToSave['custom_data'] ?? null;

        $category->save();
        return new CategoryResource($category);
    }

    public function update(UpdateCategoryRequest $request, Category $category)
    {
        $dataToSave = $request->validated();

        if ($category->transactions()->count() > 0 && $dataToSave['type']) {
            return response()->json(['message' => 'Category cannot be updated because it has transactions'], 409);
        } else {
            $category->type = $dataToSave['type'] ?? $category->type;
        }
        $category->name = $dataToSave['name'] ?? $category->name;
        $category->custom_options = $dataToSave['custom_options'] ?? $category->custom_options;
        $category->custom_data = $dataToSave['custom_data'] ?? $category->custom_data;

        $category->save();
        return new CategoryResource($category);
    }

    public function destroy(Category $category)
    {
        if ($category->transactions()->count() > 0) {
            $category->transactions()->update(['category_id' => null]); // ??
            $category->delete();
        } else {
            $category->forceDelete();
        }
        return new CategoryResource($category);
    }

    public function getTransactions(Request $request, Category $category)
    {
        $this->authorize('getTransactions', $category);
        $query = $category->transactions();

        $filterByDateStart = $request->date_start ?? '';
        $filterByDateFinish = $request->date_finish ?? '';
        $filterByPaymentType = $request->payment_type ?? '';
        $filterByVcard = $request->vcard ?? '';

        if ($filterByDateStart != "") {
            $query->where('date', '>=', $filterByDateStart);
        }
        if ($filterByDateFinish != "") {
            $query->where('date', '<=', $filterByDateFinish);
        }

        if ($filterByPaymentType != "") {
            $query->where('payment_type', '=', $filterByPaymentType);
            if ($filterByPaymentType == "VCARD" && $filterByVcard != "") {
                $query->where('pair_vcard', '=', $filterByVcard);
            }
        }

        return [
            'category' => $category,
            'transactions' => $query->paginate(10)
        ];
    }
}
