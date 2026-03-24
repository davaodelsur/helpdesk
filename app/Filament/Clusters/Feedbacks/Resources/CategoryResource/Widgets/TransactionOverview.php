<?php

namespace App\Filament\Clusters\Feedbacks\Resources\CategoryResource\Widgets;

use App\Enums\UserRole;
use App\Filament\Clusters\Feedbacks\Resources\CategoryResource\Pages\ListCategories;
use App\Models\Feedback;
use App\Models\Request;
use App\Models\Transaction;
use Filament\Facades\Filament;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class TransactionOverview extends BaseWidget
{
    use InteractsWithPageTable;

    protected function getTablePage(): string
    {
        return ListCategories::class;
    }

    protected function getStats(): array
    {
        $panelID = Filament::getCurrentPanel()->getId();
        $tableFilters = $this->tableFilters;
        return [
            Stat::make('Total Transaction', $this->getTotalTransactions($tableFilters ?? [], $panelID))
                ->description('Total number of transactions recorded.')
                ->color('primary')
                ->chart($this->totalTransactionsChart()),
            Stat::make('Total Surveyed', $this->getTotalSurveyed($tableFilters ?? [], $panelID))
                ->color('success')
                ->description('Total number of transactions that have been surveyed.')
                ->chart($this->totalSurveyedChart()),
            Stat::make('Total Not Surveyed', $this->getTotalNotSurveyed($tableFilters ?? [], $panelID))
                ->description('Total number of transactions that have not been surveyed.')
                ->color('danger'),
            Stat::make('Percentage', $this->getPercentage($this->getTotalSurveyed($tableFilters ?? [], $panelID), $this->getTotalTransactions($tableFilters ?? [], $panelID)))
                ->description('Percentage of transactions that have been surveyed.')
                ->color('zinc'),
        ];
    }

    public function totalTransactionsChart() : array {
        return Request::where('organization_id', Auth::user()->organization_id)
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get()
            ->pluck('count')
            ->toArray();
    }

    public function totalSurveyedChart() : array {
        return Feedback::where('organization_id', Auth::user()->organization_id)
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get()
            ->pluck('count')
            ->toArray();
    }

    private function getTotalTransactions(array $tableFilters, string $panelID): int
    {
        if ($panelID ===  UserRole::ADMIN->value){

            $userOrganization = Auth::user()->organization_id;

            return Feedback::where('organization_id', $userOrganization)->count() +
                Transaction::where('organization_id', $userOrganization)->sum('total_transactions') +
                Request::where('organization_id', $userOrganization)->count();
        }

        if(!isset($tableFilters['organization_id']['value'])){
            return Feedback::count() + Request::count() + Transaction::sum('total_transactions');
        }else{
            return Feedback::where('organization_id', $tableFilters['organization_id']['value'])->count() +
                Transaction::where('organization_id', $tableFilters['organization_id']['value'])->sum('total_transactions') +
                Request::where('organization_id', $tableFilters['organization_id']['value'])->count();
        }

        return 0;
    }

    private function getTotalSurveyed(array $tableFilters, string $panelID): int
    {
        if ($panelID ===  UserRole::ADMIN->value){

            $userOrganization = Auth::user()->organization_id;

            return Feedback::where('organization_id', $userOrganization)->count();
        }

        if(!isset($tableFilters['organization_id']['value'])){
            return Feedback::count();
        }else{
            return Feedback::where('organization_id', $tableFilters['organization_id']['value'])->count();
        }

        return 0;
    }

    private function getTotalNotSurveyed(array $tableFilters, string $panelID): int
    {
        if ($panelID ===  UserRole::ADMIN->value){

            $userOrganization = Auth::user()->organization_id;

            $totalTransactions = Feedback::where('organization_id', $userOrganization)->count() +
                Transaction::where('organization_id', $userOrganization)->sum('total_transactions') +
                Request::where('organization_id', $userOrganization)->count();

            $totalSurveyed = Feedback::where('organization_id', $userOrganization)->count();

            return $totalTransactions - $totalSurveyed;
        }

        if(!isset($tableFilters['organization_id']['value'])){
            $totalTransactions = Feedback::count() + Request::count() + Transaction::sum('total_transactions');
            $totalSurveyed = Feedback::count();
            return $totalTransactions - $totalSurveyed;
        }else{
            $totalTransactions = Feedback::where('organization_id', $tableFilters['organization_id']['value'])->count() +
                Transaction::where('organization_id', $tableFilters['organization_id']['value'])->sum('total_transactions') +
                Request::where('organization_id', $tableFilters['organization_id']['value'])->count();

            $totalSurveyed = Feedback::where('organization_id', $tableFilters['organization_id']['value'])->count();

            return $totalTransactions - $totalSurveyed;
        }

        return 0;
    }

    private function getPercentage(int $totalSurveyed, int $totalTransactions): string
    {
        if ($totalTransactions === 0) {
            return '0%';
        }
        $percentage = ($totalSurveyed / $totalTransactions) * 100;
        return number_format($percentage, 2) . '%';
    }
}
