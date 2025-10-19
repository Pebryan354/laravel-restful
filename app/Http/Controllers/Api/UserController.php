<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $limit      = $request->get('limit', 10);
        $page       = $request->get('page', 1);
        $search     = $request->get('search');
        $start_date = $request->get('start_date');
        $end_date   = $request->get('end_date');

        $query = User::query();

        if ($start_date && $end_date) {
            $query->whereBetween('created_at', [
                $start_date . ' 00:00:00',
                $end_date . ' 23:59:59'
            ]);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $total = $query->count();

        $users = $query
            ->orderBy('created_at', 'desc')
            ->skip(($page - 1) * $limit)
            ->take($limit)
            ->get(['id', 'name', 'email', 'created_at']);

        return response()->json([
            'success' => true,
            'message' => 'User list retrieved successfully',
            'data' => $users,
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
    public function store(Request $request) {}

    /**
     * Display the specified resource.
     */
    public function show(string $id) {}

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id) {}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id) {}
}
