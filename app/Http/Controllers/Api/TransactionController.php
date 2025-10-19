<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TransactionHeader;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\TransactionDetail;

class TransactionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $limit = $request->get('limit', 10);
        $page = $request->get('page', 1);
        $search = $request->get('search');
        $orderBy    = $request->get('order_by', 'id');
        $orderDir   = $request->get('order_dir', 'asc');
        $start_date = $request->get('start_date');
        $end_date   = $request->get('end_date');
        $category   = $request->get('category');

        $orderMap = [
            'code'          => 'h.code',
            'description'   => 'h.description',
            'rate_euro'     => 'h.rate_euro',
            'date_paid'     => 'h.date_paid',
            'name'          => 'd.name',
            'value_idr'     => 'd.value_idr',
            'category_name' => 'c.name',
            'created_at'    => 'h.created_at',
            'updated_at'    => 'h.updated_at',
        ];

        $orderColumn = $orderMap[$orderBy] ?? 'h.code';

        $orderDir = strtolower($orderDir) === 'desc' ? 'desc' : 'asc';

        $query = DB::table('transaction_header as h')
            ->join('transaction_detail as d', 'h.id', '=', 'd.transaction_id')
            ->leftJoin('ms_category as c', 'd.transaction_category_id', '=', 'c.id')
            ->select(
                'h.id',
                'h.code',
                'h.description',
                'h.rate_euro',
                'h.date_paid',
                'd.id as detail_id',
                'd.name',
                'd.value_idr',
                'c.name as category_name'
            );

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('h.code', 'like', "%{$search}%")
                    ->orWhere('h.description', 'like', "%{$search}%")
                    ->orWhere('h.date_paid', 'like', "%{$search}%")
                    ->orWhere('d.name', 'like', "%{$search}%")
                    ->orWhere('c.name', 'like', "%{$search}%");
            });
        }

        if ($start_date && $end_date) {
            $query->whereBetween('h.date_paid', [$start_date, $end_date]);
        }

        if ($category) {
            $query->where('c.name', 'like', "%{$category}%");
        }

        $total = $query->count();

        $data = $query
            ->orderBy($orderColumn, $orderDir)
            ->skip(($page - 1) * $limit)
            ->take($limit)
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Data transaksi',
            'data' => $data,
            'meta' => [
                'total' => $total,
                'page' => (int) $page,
                'limit' => (int) $limit,
                'pages' => ceil($total / $limit),
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string|max:50|unique:transaction_header,code',
            'description' => 'required|string',
            'rate_euro' => 'required|numeric',
            'date_paid' => 'required|date',
            'details' => 'required|array|min:1',
            'details.*.transaction_category_id' => 'required|integer',
            'details.*.name' => 'required|string|max:255',
            'details.*.value_idr' => 'required|numeric|min:0'
        ], [
            'code.required' => 'Kode transaksi tidak boleh kosong',
            'code.unique' => 'Kode transaksi sudah terdaftar',
            'description.required' => 'Deskripsi tidak boleh kosong',
            'rate_euro.required' => 'Rate Euro tidak boleh kosong',
            'date_paid.required' => 'Tanggal tidak boleh kosong',
            'rate_euro.numeric' => 'Rate Euro harus angka',
            'rate_euro.min' => 'Rate Euro minimal 0',
            'date_paid.date' => 'Tanggal harus format tanggal',
            'description.required' => 'Deskripsi tidak boleh kosong',
            'details.required' => 'Detail transaksi tidak boleh kosong',
            'details.*.transaction_category_id.required' => 'Kategori transaksi tidak boleh kosong',
            'details.*.name.required' => 'Nama detail tidak boleh kosong',
            'details.*.value_idr.required' => 'Nilai detail tidak boleh kosong',
            'details.*.value_idr.numeric' => 'Nilai detail harus angka',
            'details.*.value_idr.min' => 'Nilai detail minimal 0',
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
        DB::beginTransaction();
        try {
            // Simpan transaksi utama
            $transaction = TransactionHeader::create([
                'code' => $validated['code'],
                'description' => $validated['description'],
                'rate_euro' => $validated['rate_euro'],
                'date_paid' => $validated['date_paid'],
            ]);

            // Simpan semua detail
            foreach ($validated['details'] as $detail) {
                $transaction->details()->create([
                    'transaction_category_id' => $detail['transaction_category_id'],
                    'name' => $detail['name'],
                    'value_idr' => $detail['value_idr']
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Data berhasil dibuat',
                'data' => [
                    'transaction' => $transaction->load('details')
                ]
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $transaction = TransactionHeader::with('details')->find($id);

        if (!$transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Transaksi tidak ditemukan',
                'error' => 'process'
            ], 404);
        }

        // kembalikan data dalam bentuk JSON rapi
        return response()->json([
            'success' => true,
            'message' => 'Data transaksi',
            'data' => [
                'id' => $transaction->id,
                'code' => $transaction->code,
                'description' => $transaction->description,
                'rate_euro' => $transaction->rate_euro,
                'date_paid' => $transaction->date_paid,
                'created_at' => $transaction->created_at,
                'details' => $transaction->details->map(function ($d) {
                    return [
                        'id' => $d->id,
                        'transaction_id' => $d->transaction_id,
                        'transaction_category_id' => $d->transaction_category_id,
                        'name' => $d->name,
                        'value_idr' => $d->value_idr,
                        'created_at' => $d->created_at,
                    ];
                }),
            ]
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $transaction = TransactionHeader::with('details')->find($id);

        if (!$transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Transaksi tidak ditemukan',
                'error' => 'process'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'code' => 'sometimes|string|max:50|unique:transaction_header,code,' . $id,
            'description' => 'sometimes|string',
            'rate_euro' => 'sometimes|numeric',
            'date_paid' => 'sometimes|date',
            'details' => 'sometimes|array|min:1',
            'details.*.id' => 'sometimes|integer|exists:transaction_detail,id',
            'details.*.transaction_category_id' => 'sometimes|required|integer|exists:ms_category,id',
            'details.*.name' => 'sometimes|required|string|max:255',
            'details.*.value_idr' => 'sometimes|required|numeric|min:0'
        ], [
            'code.required' => 'Kode transaksi tidak boleh kosong',
            'code.unique' => 'Kode transaksi sudah terdaftar',
            'description.required' => 'Deskripsi tidak boleh kosong',
            'rate_euro.required' => 'Rate Euro tidak boleh kosong',
            'rate_euro.numeric' => 'Rate Euro harus angka',
            'rate_euro.min' => 'Rate Euro minimal 0',
            'date_paid.date' => 'Tanggal harus format tanggal',
            'description.required' => 'Deskripsi tidak boleh kosong',
            'details.required' => 'Detail transaksi tidak boleh kosong',
            'details.*.transaction_category_id.required' => 'Kategori transaksi tidak boleh kosong',
            'details.*.name.required' => 'Nama detail tidak boleh kosong',
            'details.*.value_idr.required' => 'Nilai detail tidak boleh kosong',
            'details.*.value_idr.numeric' => 'Nilai detail harus angka',
            'details.*.value_idr.min' => 'Nilai detail minimal 0',
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

        DB::beginTransaction();
        try {
            $transaction->update([
                'code' => $validated['code'] ?? $transaction->code,
                'description' => $validated['description'] ?? $transaction->description,
                'rate_euro' => $validated['rate_euro'] ?? $transaction->rate_euro,
                'date_paid' => $validated['date_paid'] ?? $transaction->date_paid,
            ]);

            if (!empty($validated['details'])) {
                $existing = $transaction->details->keyBy('id');
                $incoming = collect($validated['details']);

                $incomingIds = $incoming->pluck('id')->filter()->all();

                $toDelete = $existing->keys()->diff($incomingIds);
                if ($toDelete->isNotEmpty()) {
                    TransactionDetail::whereIn('id', $toDelete)->delete();
                }

                foreach ($incoming as $detail) {
                    if (isset($detail['id']) && $existing->has($detail['id'])) {
                        $existing[$detail['id']]->update([
                            'transaction_category_id' => $detail['transaction_category_id'],
                            'name' => $detail['name'],
                            'value_idr' => $detail['value_idr'],
                        ]);
                    } else {
                        $transaction->details()->create([
                            'transaction_category_id' => $detail['transaction_category_id'],
                            'name' => $detail['name'],
                            'value_idr' => $detail['value_idr'],
                        ]);
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Data berhasil diperbarui',
                'data' => $transaction->fresh('details')
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui data',
                'error' => 'process',
                'data' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        DB::beginTransaction();

        try {
            $transactionDetail = TransactionDetail::find($id);

            if (!$transactionDetail) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data tidak ditemukan',
                    'error' => 'process'
                ], 404);
            }

            $transactionDetail->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Data berhasil dihapus'
            ], 200);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus Data',
                'error' => 'process',
                'data' => $e->getMessage()
            ], 500);
        }
    }

    public function recap(Request $request)
    {
        $limit      = (int) $request->input('limit', 10);
        $page       = (int) $request->input('page', 1);
        $offset     = ($page - 1) * $limit;
        $search     = $request->input('search');
        $startDate  = $request->input('start_date');
        $endDate    = $request->input('end_date');
        $orderBy    = $request->input('order_by', 'date');
        $orderDir   = strtolower($request->input('order_dir', 'desc')) === 'desc' ? 'desc' : 'asc';
        $category   = $request->get('category');

        $allowedOrder = ['date', 'category', 'value_idr'];
        if (!in_array($orderBy, $allowedOrder)) {
            $orderBy = 'date';
        }

        $query = DB::table('transaction_detail as td')
            ->join('transaction_header as th', 'th.id', '=', 'td.transaction_id')
            ->join('ms_category as mc', 'mc.id', '=', 'td.transaction_category_id')
            ->select(
                DB::raw('DATE(th.date_paid) as date'),
                'mc.name as category',
                DB::raw('SUM(td.value_idr) as value_idr')
            )
            ->groupBy('date', 'mc.name');

        if ($startDate && $endDate) {
            $query->whereBetween(DB::raw('DATE(th.date_paid)'), [$startDate, $endDate]);
        }

        if ($search) {
            $search = trim($search);
            $query->havingRaw('(category LIKE ? OR date LIKE ?)', ["%{$search}%", "%{$search}%"]);
        }

        if ($category) {
            $query->where('mc.name', 'like', "%{$category}%");
        }

        $countQuery = clone $query;
        $total = DB::table(DB::raw("({$countQuery->toSql()}) as temp_table"))
            ->mergeBindings($countQuery)
            ->count();

        $data = $query
            ->orderBy($orderBy, $orderDir)
            ->offset($offset)
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Summary by date retrieved successfully',
            'data'    => $data,
            'meta'    => [
                'total'      => $total,
                'page'       => $page,
                'limit'      => $limit,
                'pages'      => ceil($total / $limit),
            ]
        ]);
    }
}
