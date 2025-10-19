<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MsCategory;
use Illuminate\Support\Facades\Validator;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $limit      = $request->get('limit', 10);
        $page       = $request->get('page', 1);
        $search     = $request->get('search');
        $orderBy    = $request->get('order_by', 'id');
        $orderDir   = $request->get('order_dir', 'asc');

        $allowedOrderFields = ['id', 'name', 'created_at', 'updated_at'];
        if (!in_array($orderBy, $allowedOrderFields)) {
            $orderBy = 'id';
        }

        $orderDir = strtolower($orderDir) === 'desc' ? 'desc' : 'asc';

        $query = MsCategory::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        $total = $query->count();

        $categories = $query
            ->orderBy($orderBy, $orderDir)
            ->skip(($page - 1) * $limit)
            ->take($limit)
            ->get(['id', 'name']);

        return response()->json([
            'success' => true,
            'message' => 'Category list retrieved successfully',
            'data' => $categories,
            'meta' => [
                'total' => $total,
                'page' => (int) $page,
                'limit' => (int) $limit,
                'pages' => ceil($total / $limit)
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:ms_category,name',
        ], [
            'name.required' => 'Nama kategori tidak boleh kosong',
            'name.unique' => 'Nama kategori sudah terdaftar',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Silahkan periksa kembali inputan anda',
                'error' => 'validation',
                'data' => $validator->errors()
            ], 422);
        }

        $validated = $validator->validated();
        $category = MsCategory::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Data berhasil dibuat',
            'data' => $category
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id) {}

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:ms_category,name,' . $id,
        ], [
            'name.required' => 'Nama kategori tidak boleh kosong',
            'name.unique' => 'Nama kategori sudah terdaftar',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Silahkan periksa kembali inputan anda',
                'error' => 'validation',
                'data' => $validator->errors()
            ], 422);
        }

        $validated = $validator->validated();
        $category = MsCategory::find($id);
        $category->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Data berhasil diupdate',
            'data' => $category
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $category = MsCategory::find($id);
        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Data berhasil dihapus',
            'data' => $category
        ], 200);
    }

    public function listOption()
    {
        $categories = MsCategory::select('id', 'name')->orderBy('name', 'asc')->get();
        return response()->json([
            'success' => true,
            'message' => 'Category list retrieved successfully',
            'data' => $categories
        ]);
    }
}
