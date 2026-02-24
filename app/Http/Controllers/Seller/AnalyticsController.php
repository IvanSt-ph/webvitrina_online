<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $sellerId = auth()->id();

        $period = (int) $request->input('period', 30);

        // Если нажали кнопку периода — игнорируем from/to
        if ($request->has('period')) {
            $to   = now()->toDateString();
            $from = now()->subDays($period - 1)->toDateString(); // 7 дней = сегодня + 6 назад
        } else {
            // Пользователь выбрал руками from/to
            $from = $request->input('from');
            $to   = $request->input('to');

            // Если не выбраны — ставим 30 дней
            if (!$from || !$to) {
                $to   = now()->toDateString();
                $from = now()->subDays(29)->toDateString();
                $period = 30;
            } else {
                // Recalculate period for the UI
                $period = max(1, now()->parse($from)->diffInDays($to) + 1);
            }
        }

        // Предыдущий период для сравнения
        $prevFrom = now()->parse($from)->subDays($period)->toDateString();
        $prevTo   = now()->parse($from)->subDay()->toDateString();

        $summary = $this->summaryStats($sellerId, $from, $to);
        $prev    = $this->summaryStats($sellerId, $prevFrom, $prevTo);

        $distribution = [
            'views'     => (int) $summary->views,
            'favorites' => (int) $summary->favorites,
            'carts'     => (int) $summary->carts,
        ];

        $timeline = DB::table('product_stats as ps')
            ->join('products as p', 'p.id', '=', 'ps.product_id')
            ->where('p.user_id', $sellerId)
            ->whereBetween('ps.date', [$from, $to])
            ->select(
                'ps.date',
                DB::raw('SUM(ps.views) as views'),
                DB::raw('SUM(ps.favorites) as favorites'),
                DB::raw('SUM(ps.carts) as carts')
            )
            ->groupBy('ps.date')
            ->orderBy('ps.date')
            ->get();

        // ✅ ИСПРАВЛЕНО: добавили slug в SELECT и GROUP BY
        $topProducts = DB::table('product_stats as ps')
            ->join('products as p', 'p.id', '=', 'ps.product_id')
            ->where('p.user_id', $sellerId)
            ->whereBetween('ps.date', [$from, $to])
            ->select(
                'p.id',
                'p.title',
                'p.slug',              // ← ДОБАВЛЕНО: выбираем slug
                DB::raw('SUM(ps.views) as views'),
                DB::raw('SUM(ps.favorites) as favorites'),
                DB::raw('SUM(ps.carts) as carts')
            )
            ->groupBy('p.id', 'p.slug') // ← ДОБАВЛЕНО: группируем по slug
            ->orderByDesc('views')
            ->limit(10)
            ->get()
            ->map(function ($row) {
                $views = (int) $row->views;
                $row->fav_rate  = $views ? round($row->favorites * 100 / $views, 1) : 0;
                $row->cart_rate = $views ? round($row->carts * 100 / $views, 1) : 0;
                
                // ✅ ИСПРАВЛЕНО: используем slug для URL, если есть
                $row->url = route('product.show', $row->slug ?? $row->id);
                
                return $row;
            });

        return view('seller.analytics.index', [
            'period'       => $period,
            'from'         => $from,
            'to'           => $to,
            'summary'      => $summary,
            'prev'         => $prev,
            'distribution' => $distribution,
            'timeline'     => $timeline,
            'topProducts'  => $topProducts,
        ]);
    }

public function dayStats($date)
{
    $sellerId = auth()->id();

    $stats = DB::table('product_stats as ps')
        ->join('products as p', 'p.id', '=', 'ps.product_id')
        ->where('p.user_id', $sellerId)
        ->where('ps.date', $date)
        ->select(
            'p.id', 
            'p.title', 
            'p.slug',              // ← ДОБАВЛЕНО: выбираем slug
            'ps.views', 
            'ps.favorites', 
            'ps.carts'
        )
        ->orderByDesc('ps.views')
        ->get();

    return view('seller.analytics.day', [
        'date'  => $date,
        'stats' => $stats,
    ]);
}

    private function summaryStats(int $sellerId, string $from, string $to)
    {
        $row = DB::table('product_stats as ps')
            ->join('products as p', 'p.id', '=', 'ps.product_id')
            ->where('p.user_id', $sellerId)
            ->whereBetween('ps.date', [$from, $to])
            ->selectRaw('
                COALESCE(SUM(ps.views), 0)     as views,
                COALESCE(SUM(ps.favorites), 0) as favorites,
                COALESCE(SUM(ps.carts), 0)     as carts
            ')
            ->first();

        if (!$row) {
            $row = (object)[
                'views'     => 0,
                'favorites' => 0,
                'carts'     => 0,
            ];
        }

        return $row;
    }
}