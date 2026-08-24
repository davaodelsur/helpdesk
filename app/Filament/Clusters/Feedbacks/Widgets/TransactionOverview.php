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
use Livewire\Attributes\Reactive;

class TransactionOverview extends BaseWidget
{
    use InteractsWithPageTable;

    #[Reactive]
    public ?string $selectedOrganizationId = null;

    protected function getTablePage(): string
    {
        return ListCategories::class;
    }

    protected function getStats(): array
    {
        $panelID = Filament::getCurrentPanel()->getId();

        $selectedOrganizationId = $this->tableFilters['organization_id']['value'] ?? null;
        $selectServiceType = $this->tableFilters['service_type']['value'] ?? null;
        $startDate = $this->tableFilters['date']['startDate'] ?? null;
        $endDate = $this->tableFilters['date']['endDate'] ?? null;

        $totalTransactions = $this->getTotalTransactions($selectedOrganizationId, $panelID, $selectServiceType, $startDate, $endDate);
        $totalSurveyed = $this->getTotalSurveyed($selectedOrganizationId, $panelID, $selectServiceType, $startDate, $endDate);
        $totalNotSurveyed = $this->getTotalNotSurveyed($totalTransactions, $totalSurveyed);

        return [
            Stat::make('Total Transaction', $totalTransactions)
                ->description('Total number of transactions recorded.')
                ->color('primary')
                ->chart($this->totalTransactionsChart($selectedOrganizationId, $panelID, $selectServiceType, $startDate, $endDate)),
            Stat::make('Total Surveyed', $totalSurveyed)
                ->color('success')
                ->description('Total number of transactions that have been surveyed.')
                ->chart($this->totalSurveyedChart($selectedOrganizationId, $panelID, $selectServiceType, $startDate, $endDate)),
            Stat::make('Total Not Surveyed', $totalNotSurveyed)
                ->description('Total number of transactions that have not been surveyed.')
                ->color('danger'),
            Stat::make('Percentage', $this->getPercentage($totalSurveyed, $totalTransactions))
                ->description('Percentage of transactions that have been surveyed.')
                ->color('zinc'),
        ];
    }

    private function totalTransactionsChart(?string $organizationId, string $panelID, ?string $serviceType, ?string $startDate = null, ?string $endDate = null): array
    {
        return Transaction::query()
            ->tap(function ($query) use ($panelID) {
                match ($panelID) {
                    UserRole::ADMIN->value => $query->where('organization_id', Auth::user()->organization_id),
                    default => $query,
                };
            })
            ->when($organizationId, function ($query) use ($organizationId) {
                $query->where('organization_id', $organizationId);
            })
            ->when($serviceType, function ($query) use ($serviceType) {
                $query->whereHas('category', function ($query) use ($serviceType) {
                    $query->where('service_type', $serviceType);
                });
            })
            ->when($startDate, function ($query) use ($startDate) {
                $query->whereDate('date', '>=', $startDate);
            })
            ->when($endDate, function ($query) use ($endDate) {
                $query->whereDate('date', '<=', $endDate);
            })
            ->selectRaw('DATE_FORMAT(date, "%Y-%m") as date, SUM(total_transactions) as count')
            ->groupByRaw('DATE_FORMAT(date, "%Y-%m")')
            ->orderByRaw('DATE_FORMAT(date, "%Y-%m") ASC')
            ->get()
            ->pluck('count')
            ->map(fn ($v) => (int) $v)
            ->toArray();
    }

    private function totalSurveyedChart(?string $organizationId, string $panelID, ?string $serviceType, ?string $startDate = null, ?string $endDate = null): array
    {
        return Feedback::query()
            ->tap(function ($query) use ($panelID) {
                match ($panelID) {
                    UserRole::ADMIN->value => $query->where('organization_id', Auth::user()->organization_id),
                    default => $query,
                };
            })
            ->when($organizationId, function ($query) use ($organizationId) {
                $query->where('organization_id', $organizationId);
            })
            ->when($serviceType, function ($query) use ($serviceType) {
                $query->whereHas('category', function ($query) use ($serviceType) {
                    $query->where('service_type', $serviceType);
                });
            })
            ->when($startDate, function ($query) use ($startDate) {
                $query->whereDate('created_at', '>=', $startDate);
            })
            ->when($endDate, function ($query) use ($endDate) {
                $query->whereDate('created_at', '<=', $endDate);
            })
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as date, COUNT(*) as count')
            ->groupByRaw('DATE_FORMAT(created_at, "%Y-%m")')
            ->orderByRaw('DATE_FORMAT(created_at, "%Y-%m") ASC')
            ->get()
            ->pluck('count')
            ->map(fn ($v) => (int) $v)
            ->toArray();
    }

    private function getTotalTransactions(?string $organizationId, string $panelID, ?string $serviceType, ?string $startDate = null, ?string $endDate = null): int
    {
        return Transaction::query()
            ->tap(function ($query) use ($panelID) {
                match ($panelID) {
                    UserRole::ADMIN->value => $query->where('organization_id', Auth::user()->organization_id),
                    default => $query,
                };
            })
            ->when($organizationId, function ($query) use ($organizationId) {
                $query->where('organization_id', $organizationId);
            })
            ->when($serviceType, function ($query) use ($serviceType) {
                $query->whereHas('category', function ($query) use ($serviceType) {
                    $query->where('service_type', $serviceType);
                });
            })
            ->when($startDate, function ($query) use ($startDate) {
                $query->whereDate('date', '>=', $startDate);
            })
            ->when($endDate, function ($query) use ($endDate) {
                $query->whereDate('date', '<=', $endDate);
            })
            ->sum('total_transactions');
    }

    private function getTotalSurveyed(?string $organizationId, string $panelID, ?string $serviceType, ?string $startDate = null, ?string $endDate = null): int
    {
        return Feedback::query()
            ->tap(function ($query) use ($panelID) {
                match ($panelID) {
                    UserRole::ADMIN->value => $query->where('organization_id', Auth::user()->organization_id),
                    default => $query,
                };
            })
            ->when($organizationId, function ($query) use ($organizationId) {
                $query->where('organization_id', $organizationId);
            })
            ->when($serviceType, function ($query) use ($serviceType) {
                $query->whereHas('category', function ($query) use ($serviceType) {
                    $query->where('service_type', $serviceType);
                });
            })
            ->when($startDate, function ($query) use ($startDate) {
                $query->whereDate('created_at', '>=', $startDate);
            })
            ->when($endDate, function ($query) use ($endDate) {
                $query->whereDate('created_at', '<=', $endDate);
            })
            ->count();
    }

    private function getTotalNotSurveyed(int $totalTransactions, int $totalSurveyed): int
    {
        return $totalTransactions - $totalSurveyed;
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
