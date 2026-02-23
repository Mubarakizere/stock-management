<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Production;
use App\Models\ProductionMaterial;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ManufacturingReportController extends Controller
{
    /**
     * Manufacturing Reports Dashboard — unified view with three report sections.
     */
    public function index(Request $request)
    {
        $start = Carbon::parse($request->input('start_date', now()->subMonth()->toDateString()))->startOfDay();
        $end   = Carbon::parse($request->input('end_date', now()->toDateString()))->endOfDay();
        $like  = DB::getDriverName() === 'pgsql' ? 'ilike' : 'like';

        // ==========================================
        // 1. PRODUCTION SUMMARY
        // ==========================================
        $productionRuns = Production::whereBetween('produced_at', [$start, $end])->count();

        $totalUnitsProduced = (float) Production::whereBetween('produced_at', [$start, $end])->sum('quantity');

        $topProduced = Production::select('product_id', DB::raw('SUM(quantity) as total_qty'))
            ->whereBetween('produced_at', [$start, $end])
            ->with('product:id,name,price')
            ->groupBy('product_id')
            ->orderByDesc('total_qty')
            ->take(10)
            ->get();

        // ==========================================
        // 2. RAW MATERIAL USAGE
        // ==========================================
        $rawMaterialUsage = ProductionMaterial::select(
                'raw_material_id',
                DB::raw('SUM(quantity_used) as total_used')
            )
            ->whereHas('production', fn($q) => $q->whereBetween('produced_at', [$start, $end]))
            ->with('rawMaterial:id,name,price,stock')
            ->groupBy('raw_material_id')
            ->orderByDesc('total_used')
            ->get()
            ->map(function ($m) {
                $rm = $m->rawMaterial;
                return [
                    'name'       => $rm->name ?? '—',
                    'total_used' => (float) $m->total_used,
                    'unit_cost'  => (float) ($rm->price ?? 0),
                    'total_cost' => (float) $m->total_used * (float) ($rm->price ?? 0),
                    'in_stock'   => (float) ($rm ? $rm->currentStock() : 0),
                ];
            });

        $totalMaterialCost = $rawMaterialUsage->sum('total_cost');

        // ==========================================
        // 3. STOCK LEVELS (Raw Materials vs Products)
        // ==========================================
        $rawMaterialsStock = Product::rawMaterials()
            ->with(['category' => fn($q) => $q->withTrashed()])
            ->withSum(['stockMovements as qty_in' => fn($q) => $q->where('type', 'in')], 'quantity')
            ->withSum(['stockMovements as qty_out' => fn($q) => $q->where('type', 'out')], 'quantity')
            ->orderBy('name')
            ->get()
            ->map(function ($p) {
                $stock = max(0, (float)($p->qty_in ?? 0) - (float)($p->qty_out ?? 0));
                return [
                    'name'     => $p->name,
                    'category' => $p->category->name ?? '—',
                    'stock'    => $stock,
                    'value'    => $stock * (float)($p->price ?? 0),
                    'low'      => $stock <= 5 && $stock > 0,
                    'out'      => $stock <= 0,
                ];
            });

        $finishedProductsStock = Product::products()
            ->has('recipeItems') // Only show products with recipes
            ->with(['category' => fn($q) => $q->withTrashed()])
            ->withSum(['stockMovements as qty_in' => fn($q) => $q->where('type', 'in')], 'quantity')
            ->withSum(['stockMovements as qty_out' => fn($q) => $q->where('type', 'out')], 'quantity')
            ->orderBy('name')
            ->get()
            ->map(function ($p) {
                $stock = max(0, (float)($p->qty_in ?? 0) - (float)($p->qty_out ?? 0));
                return [
                    'name'     => $p->name,
                    'category' => $p->category->name ?? '—',
                    'stock'    => $stock,
                    'value'    => $stock * (float)($p->price ?? 0),
                    'low'      => $stock <= 5 && $stock > 0,
                    'out'      => $stock <= 0,
                ];
            });

        // Summary stats
        $totalRmValue = $rawMaterialsStock->sum('value');
        $totalFpValue = $finishedProductsStock->sum('value');
        $rmOutCount   = $rawMaterialsStock->where('out', true)->count();
        $rmLowCount   = $rawMaterialsStock->where('low', true)->count();

        return view('reports.manufacturing', [
            'start' => $start->toDateString(),
            'end'   => $end->toDateString(),

            // Production
            'productionRuns'      => $productionRuns,
            'totalUnitsProduced'  => $totalUnitsProduced,
            'topProduced'         => $topProduced,

            // Material usage
            'rawMaterialUsage'    => $rawMaterialUsage,
            'totalMaterialCost'   => $totalMaterialCost,

            // Stock levels
            'rawMaterialsStock'       => $rawMaterialsStock,
            'finishedProductsStock'   => $finishedProductsStock,
            'totalRmValue'            => $totalRmValue,
            'totalFpValue'            => $totalFpValue,
            'rmOutCount'              => $rmOutCount,
            'rmLowCount'              => $rmLowCount,
        ]);
    }
}
