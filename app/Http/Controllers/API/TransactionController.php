<?php

namespace App\Http\Controllers\API;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TransactionController extends Controller
{
    public function all(Request $request)
    {
        $id = $request->input('id');
        $status = $request->input('status');
        $limit = $request->input('limit', 6);

        if ($id) {
            $transaction = Transaction::with(['items.product'])->find($id);

            if ($transaction) {
                return ResponseFormatter::success($transaction, 'Data transaksi berhasil diambil');
            } else {
                return ResponseFormatter::error(null, 'Data transaksi tidak ada', 404);
            }
        }

        $transaction = Transaction::with(['items.product'])->where('users_id', Auth::user()->id);

        $status ?? $transaction->where('status', $status);

        return ResponseFormatter::success($transaction->paginate($limit), 'Data list transaksi berhasil diambil');
    }

    public function checkout(Request $request)
    {
        $request->validate([
            'address' => 'required',
            'items' => 'required|array',
            'items.*.id' => 'exists:products,id',
            'status' => 'required|in:PENDING,SUCCESS,CANCEL,FAILED,SHIPPING,SHIPPED',
            'total_price' => 'required',
            'shipping_price' => 'required',
        ]);

        $transaction = Transaction::create([
            'users_id' => Auth::user()->id,
            'address' => $request->address,
            'total_price' => $request->total_price,
            'shipping_price' => $request->shipping_price,
            'status' => $request->status,
        ]);

        foreach ($request->items as $product) {
            TransactionItem::create([
                'users_id' => Auth::user()->id,
                'products_id' => $product['id'],
                'transactions_id' => $transaction->id,
                'quantity' => $product['quantity'],
            ]);
        }

        return ResponseFormatter::success($transaction->load('items.product'), 'Transaksi berhasil');
    }
}
