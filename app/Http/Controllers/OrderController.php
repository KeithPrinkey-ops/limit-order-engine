<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;
use App\Events\OrderMatchedEvent;
use App\Models\Trade;

class OrderController extends Controller
{

    public function index(Request $request)
    {
        $data = $request->validate([
            'symbol' => 'required|string',
        ]);

        return response()->json([
            'orderbook' => [
                'buy' => Order::where('symbol', $data['symbol'])
                    ->where('side', 'buy')
                    ->where('status', 1)
                    ->orderByDesc('price')
                    ->get(),
                'sell' => Order::where('symbol', $data['symbol'])
                    ->where('side', 'sell')
                    ->where('status', 1)
                    ->orderBy('price')
                    ->get(),
            ],
            'orders' => Order::where('user_id', 4)->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'symbol' => 'required|string',
            'side'   => 'required|in:buy,sell',
            'price'  => 'required|numeric|min:0.00000001',
            'amount' => 'required|numeric|min:0.00000001',
        ]);

        return DB::transaction(function () use ($request, $data) {

            // hard coded user ID 4 for simplicity
            $user = User::where('id', 4)
                ->lockForUpdate()
                ->firstOrFail();

            if ($data['side'] === 'buy') {
                $cost = bcmul($data['price'], $data['amount'], 8);
                abort_if(bccomp($user->balance, $cost, 8) < 0, 422, 'Insufficient balance');

                $user->balance = bcsub($user->balance, $cost, 8);
                $user->save();
            }

            if ($data['side'] === 'sell') {
                $asset = Asset::where('user_id', $user->id)
                    ->where('symbol', $data['symbol'])
                    ->lockForUpdate()
                    ->firstOrFail();

                abort_if(bccomp($asset->amount, $data['amount'], 8) < 0, 422, 'Insufficient asset');

                $asset->amount = bcsub($asset->amount, $data['amount'], 8);
                $asset->locked_amount = bcadd($asset->locked_amount, $data['amount'], 8);
                $asset->save();
            }

            $order = Order::create([
                'user_id' => $user->id,
                'symbol'  => $data['symbol'],
                'side'    => $data['side'],
                'price'   => $data['price'],
                'amount'  => $data['amount'],
                'status'  => 1, // open
            ]);

            // matching will run next
            $this->matchOrder($order);

            return response()->json($order, 201);
        });
    }


    private function matchOrder(Order $order): void
    {
        $counterQuery = Order::where('symbol', $order->symbol)
            ->where('status', 1)
            ->where('side', $order->side === 'buy' ? 'sell' : 'buy');

        if ($order->side === 'buy') {
            $counterQuery
                ->where('price', '<=', $order->price)
                ->orderBy('price');
        } else {
            $counterQuery
                ->where('price', '>=', $order->price)
                ->orderByDesc('price');
        }

        $counterOrder = $counterQuery
            ->lockForUpdate()
            ->first();

        if (! $counterOrder) {
            return;
        }

        if (bccomp($counterOrder->amount, $order->amount, 8) !== 0) {
            return;
        }

        $price = $counterOrder->price;
        $amount = $order->amount;

        $grossUsd = bcmul($price, $amount, 8);
        $feeUsd   = bcmul($grossUsd, '0.015', 8);
        $netUsd   = bcsub($grossUsd, $feeUsd, 8);

        $buyer  = User::where('id', $order->side === 'buy' ? $order->user_id : $counterOrder->user_id)
            ->lockForUpdate()
            ->first();

        $seller = User::where('id', $order->side === 'sell' ? $order->user_id : $counterOrder->user_id)
            ->lockForUpdate()
            ->first();

        $buyerAsset = Asset::firstOrCreate(
            ['user_id' => $buyer->id, 'symbol' => $order->symbol],
            ['amount' => '0', 'locked_amount' => '0']
        );

        $sellerAsset = Asset::where('user_id', $seller->id)
            ->where('symbol', $order->symbol)
            ->lockForUpdate()
            ->first();

        $buyerAsset->amount = bcadd($buyerAsset->amount, $amount, 8);
        $buyerAsset->save();

        $sellerAsset->locked_amount = bcsub($sellerAsset->locked_amount, $amount, 8);
        $sellerAsset->save();

        $seller->balance = bcadd($seller->balance, $netUsd, 8);
        $seller->save();

        $order->status = 2;
        $counterOrder->status = 2;
        $order->save();


        $counterOrder->save();

        Trade::create([
            'buy_order_id'  => $order->side === 'buy' ? $order->id : $counterOrder->id,
            'sell_order_id' => $order->side === 'sell' ? $order->id : $counterOrder->id,
            'symbol'        => $order->symbol,
            'price'         => $price,
            'amount'        => $amount,
            'commission'    => $feeUsd,
        ]);

        event(new OrderMatchedEvent(
            $buyer->id,
            [
                'order_id' => $order->id,
                'symbol'   => $order->symbol,
                'side'     => 'buy',
                'status'   => 'filled',
            ]
        ));

        event(new OrderMatchedEvent(
            $seller->id,
            [
                'order_id' => $counterOrder->id,
                'symbol'   => $order->symbol,
                'side'     => 'sell',
                'status'   => 'filled',
            ]
        ));
    }

    /**
     * @throws Throwable
     */
    public function cancel(Order $order, Request $request)
    {
        abort_if($order->user_id !== $request->user()->id, 403);
        abort_if($order->status !== 1, 422, 'Order not open');

        return DB::transaction(function () use ($order) {

            $order = Order::where('id', $order->id)
                ->lockForUpdate()
                ->firstOrFail();

            $order->status = 3; // cancelled
            $order->save();

            if ($order->side === 'buy') {
                $refund = bcmul($order->price, $order->amount, 8);
                $order->user->increment('balance', $refund);
            }

            if ($order->side === 'sell') {
                $asset = Asset::where('user_id', $order->user_id)
                    ->where('symbol', $order->symbol)
                    ->lockForUpdate()
                    ->first();

                if ($asset) {
                    $asset->locked_amount = bcsub($asset->locked_amount, $order->amount, 8);
                    $asset->amount = bcadd($asset->amount, $order->amount, 8);
                    $asset->save();
                }
            }

            return response()->json(['status' => 'cancelled']);
        });
    }
}
