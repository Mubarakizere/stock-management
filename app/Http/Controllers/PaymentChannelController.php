<?php

namespace App\Http\Controllers;

use App\Models\PaymentChannel;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PaymentChannelController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:payment-channels.view')->only(['index', 'show']);
        $this->middleware('permission:payment-channels.create')->only(['create', 'store']);
        $this->middleware('permission:payment-channels.edit')->only(['edit', 'update']);
        $this->middleware('permission:payment-channels.delete')->only(['destroy']);
    }

    public function index(Request $request)
    {
        $q = trim((string) $request->input('q', ''));
        $filterStatus = $request->input('status', 'all'); // 'all', 'active', 'inactive'
        $perPageParam = $request->input('per_page', 15);
        $perPage = ($perPageParam === 'all') ? 999 : (int) $perPageParam;
        if ($perPage <= 0) {
            $perPage = 15;
        }

        // Global KPI Statistics
        $totalChannels = PaymentChannel::count();
        $activeChannels = PaymentChannel::where('is_active', true)->count();
        $inactiveChannels = PaymentChannel::where('is_active', false)->count();
        $totalTransactions = Transaction::count();
        $totalVolume = (float) Transaction::sum('amount');

        $stats = [
            'total'        => $totalChannels,
            'active'       => $activeChannels,
            'inactive'     => $inactiveChannels,
            'transactions' => $totalTransactions,
            'volume'       => $totalVolume,
        ];

        // Query with search and status filters
        $query = PaymentChannel::query();

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('slug', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%");
            });
        }

        if ($filterStatus === 'active') {
            $query->where('is_active', true);
        } elseif ($filterStatus === 'inactive') {
            $query->where('is_active', false);
        }

        $channels = $query->orderBy('name', 'asc')->paginate($perPage)->withQueryString();

        // Transaction stats mapping by method/slug to avoid N+1
        $txStats = Transaction::select('method', DB::raw('count(*) as count'), DB::raw('sum(amount) as total'))
            ->groupBy('method')
            ->get()
            ->keyBy(fn($item) => strtolower((string)$item->method));

        foreach ($channels as $channel) {
            $slugKey = strtolower((string)$channel->slug);
            $nameKey = strtolower((string)$channel->name);

            $count = 0;
            $vol = 0.0;

            if (isset($txStats[$slugKey])) {
                $count += $txStats[$slugKey]->count;
                $vol += (float) $txStats[$slugKey]->total;
            }
            if ($nameKey !== $slugKey && isset($txStats[$nameKey])) {
                $count += $txStats[$nameKey]->count;
                $vol += (float) $txStats[$nameKey]->total;
            }

            $channel->tx_count = $count;
            $channel->tx_volume = $vol;
        }

        // If AJAX request or table_only requested, return partial table
        if ($request->ajax() || $request->has('table_only')) {
            return view('payment_channels._table', compact('channels', 'stats', 'q', 'filterStatus'));
        }

        if ($request->wantsJson()) {
            return response()->json([
                'channels' => $channels,
                'stats'    => $stats,
            ]);
        }

        return view('payment_channels.index', compact('channels', 'stats', 'q', 'filterStatus'));
    }

    public function create()
    {
        return view('payment_channels.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255|unique:payment_channels,name',
            'slug'        => 'nullable|string|max:255|unique:payment_channels,slug',
            'description' => 'nullable|string|max:1000',
            'is_active'   => 'sometimes|boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['slug'] = !empty($validated['slug']) 
            ? Str::slug($validated['slug']) 
            : Str::slug($validated['name']);

        $channel = PaymentChannel::create($validated);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Payment channel '{$channel->name}' created successfully.",
                'channel' => $channel,
            ]);
        }

        return redirect()->route('payment-channels.index')
            ->with('success', "Payment channel '{$channel->name}' created successfully.");
    }

    public function show(PaymentChannel $paymentChannel)
    {
        return redirect()->route('payment-channels.edit', $paymentChannel);
    }

    public function edit(PaymentChannel $paymentChannel)
    {
        // Load channel transaction usage stats
        $slugKey = strtolower((string)$paymentChannel->slug);
        $nameKey = strtolower((string)$paymentChannel->name);

        $txCount = Transaction::where(function ($q) use ($slugKey, $nameKey) {
            $q->whereRaw('LOWER(method) = ?', [$slugKey])
              ->orWhereRaw('LOWER(method) = ?', [$nameKey]);
        })->count();

        $txVolume = (float) Transaction::where(function ($q) use ($slugKey, $nameKey) {
            $q->whereRaw('LOWER(method) = ?', [$slugKey])
              ->orWhereRaw('LOWER(method) = ?', [$nameKey]);
        })->sum('amount');

        $paymentChannel->tx_count = $txCount;
        $paymentChannel->tx_volume = $txVolume;

        return view('payment_channels.edit', compact('paymentChannel'));
    }

    public function update(Request $request, PaymentChannel $paymentChannel)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255|unique:payment_channels,name,' . $paymentChannel->id,
            'slug'        => 'nullable|string|max:255|unique:payment_channels,slug,' . $paymentChannel->id,
            'description' => 'nullable|string|max:1000',
            'is_active'   => 'sometimes|boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $validated['slug'] = !empty($validated['slug']) 
            ? Str::slug($validated['slug']) 
            : Str::slug($validated['name']);

        $paymentChannel->update($validated);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Payment channel '{$paymentChannel->name}' updated successfully.",
                'channel' => $paymentChannel,
            ]);
        }

        return redirect()->route('payment-channels.index')
            ->with('success', "Payment channel '{$paymentChannel->name}' updated successfully.");
    }

    public function destroy(Request $request, PaymentChannel $paymentChannel)
    {
        $slugKey = strtolower((string)$paymentChannel->slug);
        $nameKey = strtolower((string)$paymentChannel->name);

        $hasTransactions = Transaction::where(function ($q) use ($slugKey, $nameKey) {
            $q->whereRaw('LOWER(method) = ?', [$slugKey])
              ->orWhereRaw('LOWER(method) = ?', [$nameKey]);
        })->exists();

        if ($hasTransactions && !$request->has('force')) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => "Cannot delete '{$paymentChannel->name}' because transactions are recorded under this channel. Deactivate it instead.",
                ], 422);
            }

            return redirect()->route('payment-channels.index')
                ->with('error', "Cannot delete '{$paymentChannel->name}' because transactions are recorded under this channel. We recommend marking it as Inactive instead to maintain financial audit integrity.");
        }

        $name = $paymentChannel->name;
        $paymentChannel->delete();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Payment channel '{$name}' deleted successfully.",
            ]);
        }

        return redirect()->route('payment-channels.index')
            ->with('success', "Payment channel '{$name}' deleted successfully.");
    }
}
