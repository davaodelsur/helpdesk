<?php

namespace App\Filament\Clusters\Feedbacks\Widgets;

use App\Enums\UserRole;
use App\Filament\Clusters\Feedbacks\Resources\CategoryResource\Pages\ListCategories;
use App\Models\Feedback;
use App\Models\Request;
use App\Models\Transaction;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use Filament\Widgets\Concerns\InteractsWithPageTable;

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

        $selectedOrganizationId = $this->tableFilters['organization_id']['value'] ?? null;

        return [
            Stat::make('Total Transaction', $this->getTotalTransactions($selectedOrganizationId, $panelID))
                ->description('Total number of transactions recorded.')
                ->color('primary')
                ->chart($this->totalTransactionsChart()),
            Stat::make('Total Surveyed', $this->getTotalSurveyed($selectedOrganizationId, $panelID))
                ->color('success')
                ->description('Total number of transactions that have been surveyed.')
                ->chart($this->totalSurveyedChart()),
            Stat::make('Total Not Surveyed', $this->getTotalNotSurveyed($selectedOrganizationId, $panelID))
                ->description('Total number of transactions that have not been surveyed.')
                ->color('danger'),
            Stat::make('Percentage', $this->getPercentage($this->getTotalSurveyed($selectedOrganizationId, $panelID), $this->getTotalTransactions($selectedOrganizationId, $panelID)))
                ->description('Percentage of transactions that have been surveyed.')
                ->color('zinc'),
        ];
    }

    private function totalTransactionsChart() : array {
        return Request::where('organization_id', Auth::user()->organization_id)
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get()
            ->pluck('count')
            ->toArray();
    }

    private function totalSurveyedChart() : array {
        return Feedback::where('organization_id', Auth::user()->organization_id)
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get()
            ->pluck('count')
            ->toArray();
    }

    private function getTotalTransactions(?string $organizationId, string $panelID): int
    {
        if ($panelID ===  UserRole::ADMIN->value){

            $userOrganization = Auth::user()->organization_id;

            return Transaction::where('organization_id', $userOrganization)->sum('total_transactions');
        }

        if(!$organizationId){
            return Transaction::sum('total_transactions');
        }else{
            return Transaction::where('organization_id', $organizationId)->sum('total_transactions');
        }

        return 0;
    }

    private function getTotalSurveyed(?string $organizationId, string $panelID): int
    {
        if ($panelID ===  UserRole::ADMIN->value){

            $userOrganization = Auth::user()->organization_id;

            return Feedback::where('organization_id', $userOrganization)->count();
        }

        if(!$organizationId){
            return Feedback::count();
        }else{
            return Feedback::where('organization_id', $organizationId)->count();
        }

        return 0;
    }

    private function getTotalNotSurveyed(?string $organizationId, string $panelID): int
    {

        if ($panelID ===  UserRole::ADMIN->value){

            $userOrganization = Auth::user()->organization_id;

            $totalTransactions= Transaction::where('organization_id', $userOrganization)->sum('total_transactions');

            $totalSurveyed = Feedback::where('organization_id', $userOrganization)->count();

            return $totalTransactions - $totalSurveyed;
        }

        if(!$organizationId){

            $totalSurveyed = Feedback::count();

            return Transaction::sum('total_transactions') - $totalSurveyed;
        }else{
            $totalTransactions = Transaction::where('organization_id',$organizationId)->sum('total_transactions');

            $totalSurveyed = Feedback::where('organization_id',$organizationId)->count();

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
