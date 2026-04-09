<?php

namespace App\Application\Services\Dashboard;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AdminDashboardService
{
    public function summary(): array
    {
        $today = now()->startOfDay();
        $sevenDaysAgo = now()->copy()->subDays(6)->startOfDay();
        $thirtyDaysAgo = now()->copy()->subDays(29)->startOfDay();
        $dormantCutoff = now()->copy()->subDays(90)->startOfDay();

        $fundOverview = $this->buildFundOverview($today);
        $investorMetrics = $this->buildInvestorMetrics($today, $sevenDaysAgo, $thirtyDaysAgo, $dormantCutoff);
        $transactionMetrics = $this->buildTransactionMetrics($today);
        $planMetrics = $this->buildPlanMetrics($today);
        $todaySnapshot = $this->buildTodaySnapshot($today);
        $trends = $this->buildTrends($sevenDaysAgo, $thirtyDaysAgo);

        return [
            'generated_at' => now()->toDateTimeString(),
            'currency' => 'TZS',
            'fund_overview' => $fundOverview,
            'investor_metrics' => $investorMetrics,
            'transaction_metrics' => $transactionMetrics,
            'plan_metrics' => $planMetrics,
            'today_snapshot' => $todaySnapshot,
            'trends' => $trends,
        ];
    }

    protected function buildFundOverview(Carbon $today): array
    {
        $totalInvestedCapital = (float) DB::table('investment_transactions')
            ->where('transaction_type', 'purchase')
            ->where('status', 'completed')
            ->sum('gross_amount');

        $totalRedeemedCapital = (float) DB::table('investment_transactions')
            ->where('transaction_type', 'redemption')
            ->where('status', 'completed')
            ->sum('gross_amount');

        $purchaseToday = (float) DB::table('investment_transactions')
            ->where('transaction_type', 'purchase')
            ->where('status', 'completed')
            ->whereDate('trade_date', $today->toDateString())
            ->sum('gross_amount');

        $redemptionToday = (float) DB::table('investment_transactions')
            ->where('transaction_type', 'redemption')
            ->where('status', 'completed')
            ->whereDate('trade_date', $today->toDateString())
            ->sum('gross_amount');

        $planMetrics = $this->buildPlanMetrics($today);
        $totalAum = (float) collect($planMetrics['plans'])->sum('current_aum');
        $totalReturns = $totalAum - $totalInvestedCapital;

        return [
            'total_aum' => round($totalAum, 2),
            'total_invested_capital' => round($totalInvestedCapital, 2),
            'total_redeemed_capital' => round($totalRedeemedCapital, 2),
            'total_returns' => round($totalReturns, 2),
            'net_inflow_today' => round($purchaseToday - $redemptionToday, 2),
        ];
    }

    protected function buildInvestorMetrics(
        Carbon $today,
        Carbon $sevenDaysAgo,
        Carbon $thirtyDaysAgo,
        Carbon $dormantCutoff
    ): array {
        $totalInvestors = (int) DB::table('investors')->count();

        $newInvestorsToday = (int) DB::table('investors')
            ->whereDate('created_at', $today->toDateString())
            ->count();

        $newInvestorsLast7Days = (int) DB::table('investors')
            ->whereDate('created_at', '>=', $sevenDaysAgo->toDateString())
            ->count();

        $newInvestorsLast30Days = (int) DB::table('investors')
            ->whereDate('created_at', '>=', $thirtyDaysAgo->toDateString())
            ->count();

        $lastActivitySubquery = DB::table('investment_transactions')
            ->selectRaw('investor_id, MAX(created_at) as last_activity_at')
            ->groupBy('investor_id');

        $dormantInvestors = (int) DB::table('investors as i')
            ->leftJoinSub($lastActivitySubquery, 'tx', function ($join) {
                $join->on('tx.investor_id', '=', 'i.id');
            })
            ->whereDate('i.created_at', '<=', $dormantCutoff->toDateString())
            ->where(function ($query) use ($dormantCutoff) {
                $query->whereNull('tx.last_activity_at')
                    ->orWhereDate('tx.last_activity_at', '<', $dormantCutoff->toDateString());
            })
            ->count();

        return [
            'total_investors' => $totalInvestors,
            'new_investors_today' => $newInvestorsToday,
            'new_investors_last_7_days' => $newInvestorsLast7Days,
            'new_investors_last_30_days' => $newInvestorsLast30Days,
            'dormant_investors_90_days' => $dormantInvestors,
        ];
    }

    protected function buildTransactionMetrics(Carbon $today): array
    {
        $purchaseCountTotal = (int) DB::table('investment_transactions')
            ->where('transaction_type', 'purchase')
            ->where('status', 'completed')
            ->count();

        $purchaseCountToday = (int) DB::table('investment_transactions')
            ->where('transaction_type', 'purchase')
            ->where('status', 'completed')
            ->whereDate('trade_date', $today->toDateString())
            ->count();

        $pendingPaymentsCount = (int) DB::table('payments')
            ->whereIn('status', ['pending', 'processing'])
            ->count();

        $failedPaymentsCount = (int) DB::table('payments')
            ->where('status', 'failed')
            ->count();

        $awaitingPaymentCount = (int) DB::table('purchase_requests')
            ->where('status', 'pending_payment')
            ->count();

        $awaitingNextNavConfirmationCount = (int) DB::table('purchase_requests')
            ->where('status', 'awaiting_next_nav_confirmation')
            ->count();

        return [
            'purchase_count_total' => $purchaseCountTotal,
            'purchase_count_today' => $purchaseCountToday,
            'pending_payments_count' => $pendingPaymentsCount,
            'failed_payments_count' => $failedPaymentsCount,
            'awaiting_payment_count' => $awaitingPaymentCount,
            'awaiting_next_nav_confirmation_count' => $awaitingNextNavConfirmationCount,
        ];
    }

    protected function buildPlanMetrics(Carbon $today): array
    {
        $plans = DB::table('plans')
            ->select('id', 'code', 'name', 'status')
            ->orderBy('id')
            ->get()
            ->keyBy('id');

        $planInvestmentTotals = DB::table('investment_transactions')
            ->selectRaw('plan_id, COUNT(*) as purchase_count_total, SUM(gross_amount) as invested_amount_total')
            ->where('transaction_type', 'purchase')
            ->where('status', 'completed')
            ->groupBy('plan_id')
            ->get()
            ->keyBy('plan_id');

        $planInvestedToday = DB::table('investment_transactions')
            ->selectRaw('plan_id, COUNT(*) as purchase_count_today, SUM(gross_amount) as invested_amount_today')
            ->where('transaction_type', 'purchase')
            ->where('status', 'completed')
            ->whereDate('trade_date', $today->toDateString())
            ->groupBy('plan_id')
            ->get()
            ->keyBy('plan_id');

        $planInvestorCounts = DB::table('investment_transactions')
            ->selectRaw('plan_id, COUNT(DISTINCT investor_id) as investor_count')
            ->where('transaction_type', 'purchase')
            ->where('status', 'completed')
            ->groupBy('plan_id')
            ->get()
            ->keyBy('plan_id');

        $planLots = DB::table('unit_lots')
            ->selectRaw('plan_id, SUM(remaining_units) as remaining_units_total')
            ->where('status', 'active')
            ->groupBy('plan_id')
            ->get()
            ->keyBy('plan_id');

        $publishedNavs = DB::table('nav_records')
            ->select('plan_id', 'valuation_date', 'nav_per_unit')
            ->where('status', 'published')
            ->orderBy('plan_id')
            ->orderByDesc('valuation_date')
            ->get();

        $navSnapshots = [];
        foreach ($publishedNavs as $record) {
            if (! isset($navSnapshots[$record->plan_id])) {
                $navSnapshots[$record->plan_id] = [
                    'latest_nav' => (float) $record->nav_per_unit,
                    'latest_valuation_date' => $record->valuation_date,
                    'previous_nav' => null,
                    'previous_valuation_date' => null,
                ];
                continue;
            }

            if ($navSnapshots[$record->plan_id]['previous_nav'] === null) {
                $navSnapshots[$record->plan_id]['previous_nav'] = (float) $record->nav_per_unit;
                $navSnapshots[$record->plan_id]['previous_valuation_date'] = $record->valuation_date;
            }
        }

        $compiledPlans = [];
        foreach ($plans as $planId => $plan) {
            $remainingUnits = (float) ($planLots[$planId]->remaining_units_total ?? 0);
            $latestNav = (float) ($navSnapshots[$planId]['latest_nav'] ?? 0);
            $previousNav = (float) ($navSnapshots[$planId]['previous_nav'] ?? 0);

            $currentAum = $remainingUnits * $latestNav;
            $navChangeAmount = $latestNav - $previousNav;
            $navChangePercentage = $previousNav > 0
                ? (($latestNav - $previousNav) / $previousNav) * 100
                : 0;

            $compiledPlans[] = [
                'plan_id' => $planId,
                'plan_code' => $plan->code,
                'plan_name' => $plan->name,
                'status' => $plan->status,
                'investor_count' => (int) ($planInvestorCounts[$planId]->investor_count ?? 0),
                'purchase_count_total' => (int) ($planInvestmentTotals[$planId]->purchase_count_total ?? 0),
                'purchase_count_today' => (int) ($planInvestedToday[$planId]->purchase_count_today ?? 0),
                'invested_amount_total' => round((float) ($planInvestmentTotals[$planId]->invested_amount_total ?? 0), 2),
                'invested_amount_today' => round((float) ($planInvestedToday[$planId]->invested_amount_today ?? 0), 2),
                'remaining_units_total' => round($remainingUnits, 6),
                'latest_nav' => round($latestNav, 6),
                'previous_nav' => round($previousNav, 6),
                'latest_valuation_date' => $navSnapshots[$planId]['latest_valuation_date'] ?? null,
                'previous_valuation_date' => $navSnapshots[$planId]['previous_valuation_date'] ?? null,
                'nav_change_amount' => round($navChangeAmount, 6),
                'nav_change_percentage' => round($navChangePercentage, 4),
                'current_aum' => round($currentAum, 2),
            ];
        }

        $topPlanByAum = collect($compiledPlans)
            ->sortByDesc('current_aum')
            ->values()
            ->first();

        $topPlanByNavGrowth = collect($compiledPlans)
            ->sortByDesc('nav_change_percentage')
            ->values()
            ->first();

        return [
            'total_plans' => count($compiledPlans),
            'top_plan_by_aum' => $topPlanByAum,
            'top_plan_by_nav_growth' => $topPlanByNavGrowth,
            'plans' => $compiledPlans,
        ];
    }

    protected function buildTodaySnapshot(Carbon $today): array
    {
        $purchasesToday = DB::table('investment_transactions')
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(gross_amount), 0) as amount')
            ->where('transaction_type', 'purchase')
            ->where('status', 'completed')
            ->whereDate('trade_date', $today->toDateString())
            ->first();

        $redemptionsToday = DB::table('investment_transactions')
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(gross_amount), 0) as amount')
            ->where('transaction_type', 'redemption')
            ->where('status', 'completed')
            ->whereDate('trade_date', $today->toDateString())
            ->first();

        $investedTodayByPlan = DB::table('investment_transactions as t')
            ->join('plans as p', 'p.id', '=', 't.plan_id')
            ->selectRaw('
                t.plan_id,
                p.code as plan_code,
                p.name as plan_name,
                COUNT(*) as purchase_count,
                COALESCE(SUM(t.gross_amount), 0) as invested_amount
            ')
            ->where('t.transaction_type', 'purchase')
            ->where('t.status', 'completed')
            ->whereDate('t.trade_date', $today->toDateString())
            ->groupBy('t.plan_id', 'p.code', 'p.name')
            ->orderByDesc('invested_amount')
            ->get()
            ->map(fn ($row) => [
                'plan_id' => $row->plan_id,
                'plan_code' => $row->plan_code,
                'plan_name' => $row->plan_name,
                'purchase_count' => (int) $row->purchase_count,
                'invested_amount' => round((float) $row->invested_amount, 2),
            ])
            ->values()
            ->all();

        $newInvestorsToday = (int) DB::table('investors')
            ->whereDate('created_at', $today->toDateString())
            ->count();

        return [
            'date' => $today->toDateString(),
            'invested_today_all_plans' => round((float) ($purchasesToday->amount ?? 0), 2),
            'purchase_count_today' => (int) ($purchasesToday->count ?? 0),
            'redemption_amount_today' => round((float) ($redemptionsToday->amount ?? 0), 2),
            'redemption_count_today' => (int) ($redemptionsToday->count ?? 0),
            'net_flow_today' => round(
                ((float) ($purchasesToday->amount ?? 0)) - ((float) ($redemptionsToday->amount ?? 0)),
                2
            ),
            'new_investors_today' => $newInvestorsToday,
            'invested_today_by_plan' => $investedTodayByPlan,
        ];
    }

    protected function buildTrends(Carbon $sevenDaysAgo, Carbon $thirtyDaysAgo): array
    {
        $purchases7 = (float) DB::table('investment_transactions')
            ->where('transaction_type', 'purchase')
            ->where('status', 'completed')
            ->whereDate('trade_date', '>=', $sevenDaysAgo->toDateString())
            ->sum('gross_amount');

        $redemptions7 = (float) DB::table('investment_transactions')
            ->where('transaction_type', 'redemption')
            ->where('status', 'completed')
            ->whereDate('trade_date', '>=', $sevenDaysAgo->toDateString())
            ->sum('gross_amount');

        $purchases30 = (float) DB::table('investment_transactions')
            ->where('transaction_type', 'purchase')
            ->where('status', 'completed')
            ->whereDate('trade_date', '>=', $thirtyDaysAgo->toDateString())
            ->sum('gross_amount');

        $redemptions30 = (float) DB::table('investment_transactions')
            ->where('transaction_type', 'redemption')
            ->where('status', 'completed')
            ->whereDate('trade_date', '>=', $thirtyDaysAgo->toDateString())
            ->sum('gross_amount');

        $newInvestors7 = (int) DB::table('investors')
            ->whereDate('created_at', '>=', $sevenDaysAgo->toDateString())
            ->count();

        $newInvestors30 = (int) DB::table('investors')
            ->whereDate('created_at', '>=', $thirtyDaysAgo->toDateString())
            ->count();

        return [
            'invested_last_7_days' => round($purchases7, 2),
            'invested_last_30_days' => round($purchases30, 2),
            'net_inflow_last_7_days' => round($purchases7 - $redemptions7, 2),
            'net_inflow_last_30_days' => round($purchases30 - $redemptions30, 2),
            'new_investors_last_7_days' => $newInvestors7,
            'new_investors_last_30_days' => $newInvestors30,
        ];
    }
}