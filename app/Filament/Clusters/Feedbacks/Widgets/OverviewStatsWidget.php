<?php

namespace App\Filament\Clusters\Feedbacks\Widgets;

use App\Enums\UserRole;
use App\Models\Feedback;
use App\Models\Response;
use App\Models\Transaction;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Number;
use Livewire\Attributes\Reactive;
use Livewire\Livewire;

class OverviewStatsWidget extends StatsOverviewWidget
{
    #[Reactive]
    public ?string $selectedOrganizationId = null;

    protected static string $view = 'filament.panels.feedback.widgets.stats-overview-widget';

    protected function getStats(): array
    {
        $panelID = Filament::getCurrentPanel()->getId();

        return [
            Stat::make('CC Awareness', $this->getAwareness($panelID))
                ->description('Percentage of respondents aware of the service.')
                ->color('primary'),
            Stat::make('CC Visibilty', $this->getVisibility($panelID))
                ->description('Percentage of respondents who have used the service.')
                ->color('success'),
            Stat::make('CC Helpfulness', $this->getHelpfulness($panelID))
                ->description('Percentage of respondents who found the service helpful.')
                ->color('danger'),
            Stat::make('Response Rate', $this->getResponseRate($panelID))
                ->description('Percentage of customers who provided feedback out of total transactions.')
                ->color('warning'),
            Stat::make('Overall Score', $this->getOverallScore($panelID))
                ->description('Overall customer satisfaction score based on feedback responses.')
                ->color('zinc')
                ->extraAttributes(['class' => 'col-span-1 max-sm:col-span-2']),
        ];
    }

    protected function getAwareness(string $panelID): string
    {   try{
            $partCount = $this->responsePartCountScope($this->selectedOrganizationId, $panelID)
                              ->where('question', 'CC1')
                              ->whereBetween('answer', [1,3])->count();
            $totalCount = $this->responseTotalCountScope($this->selectedOrganizationId, $panelID)
                               ->where('question', 'CC1')
                               ->count();

            return  Number::percentage(($partCount / $totalCount) * 100, 1);
        }catch (\DivisionByZeroError $e){
            return '0.0%';
        }

    }

    protected function getVisibility(string $panelID): string
    {
        try{
            $partCount = $this->responsePartCountScope($this->selectedOrganizationId, $panelID)
                          ->where('question', 'CC2')
                          ->where('answer', 1)->count();
            $totalCount = $this->responseTotalCountScope($this->selectedOrganizationId, $panelID)
                               ->where('question', 'CC2')
                               ->count();

            return  Number::percentage(($partCount / $totalCount) * 100, 1);
        }catch (\DivisionByZeroError $e){
            return '0.0%';
        }
    }

    protected function getHelpfulness(string $panelID) : string
    {
        try{
            $partCount = $this->responsePartCountScope($this->selectedOrganizationId, $panelID)
                          ->where('question', 'CC3')
                          ->where('answer', 1)->count();
            $totalCount = $this->responseTotalCountScope($this->selectedOrganizationId, $panelID)
                               ->where('question', 'CC3')
                               ->count();

            return  Number::percentage(($partCount / $totalCount) * 100, 1);
        }catch (\DivisionByZeroError $e){
            return '0.0%';
        }

    }

    protected function getResponseRate(string $panelID) : string
    {
        $transactionQuery = Transaction::query();
        $feedbackQuery = Feedback::query();

        try{
            if ($panelID === UserRole::ADMIN->value){
                $transactionQuery->where('organization_id', request()->user()->organization_id);
                $feedbackQuery->where('organization_id', request()->user()->organization_id);
            }

            if($this->selectedOrganizationId) {
                $transactionQuery->where('organization_id', $this->selectedOrganizationId);
                $feedbackQuery->where('organization_id', $this->selectedOrganizationId);
            }

                $totalTransactionsCount = $transactionQuery->sum('total_transactions');
                $totalFeedbacksCount = $feedbackQuery->count();

            return Number::percentage(($totalFeedbacksCount / $totalTransactionsCount) * 100, 1);

        }catch (\DivisionByZeroError $e){
            return '0.0%';
        }


    }

    protected function getOverallScore(string $panelID) : string
    {
        $positiveResponses = Response::where('answer','>=', 4)->where('question','not like', 'CC%')->where('question', '!=', 'SQD0')->count();

        $totalRespondents = function() use ($panelID) {
            $query = Feedback::query();

            if ($panelID === UserRole::ADMIN->value){
                $query->where('organization_id', request()->user()->organization_id);
            }

            if($this->selectedOrganizationId) {
                $query->where('organization_id', $this->selectedOrganizationId);
            }

            return $query->count();
        };

        $totalNoReponse = function() use ($panelID) {
            $query = Response::query()->where('question', 'not like', 'CC%')->where('question', '!=', 'SQD0')->where('answer', 0);

            if ($panelID === UserRole::ADMIN->value){
                $query->whereHas('feedback', function ($q) {
                    $q->where('organization_id', request()->user()->organization_id);
                });
            }

            if($this->selectedOrganizationId) {
                $query->whereHas('feedback', function ($q) use ($panelID) {
                    $q->where('organization_id', $this->selectedOrganizationId);
                });
            }

            return $query->count();
        };

        $positiveResponses = function() use ($panelID) {
            $query = Response::query()->where('answer','>=', 4)->where('question','not like', 'CC%')->where('question', '!=', 'SQD0');

            if ($panelID === UserRole::ADMIN->value){
                $query->whereHas('feedback', function ($q) {
                    $q->where('organization_id', request()->user()->organization_id);
                });
            }

            if($this->selectedOrganizationId) {
                $query->whereHas('feedback', function ($q) use ($panelID) {
                    $q->where('organization_id', $this->selectedOrganizationId);
                });
            }

            return $query->count();
        };
        try{
            $totalScore = (($positiveResponses()) / (($totalRespondents()*8) - $totalNoReponse()) * 100);
            return Number::percentage($totalScore, 1);
        } catch (\DivisionByZeroError $e) {
            return '0.0%';
        }

    }

    private function responsePartCountScope(?string $selectedOrganizationId, string $panelID): Builder
    {
        $partCountQuery = Response::query();
        if ($panelID === UserRole::ADMIN->value){
            $partCountQuery->whereHas('feedback', function ($query){
                $query->where('organization_id', request()->user()->organization_id);
            });
        }
        if($selectedOrganizationId) {
            $partCountQuery->whereHas('feedback', function ($query) use ($selectedOrganizationId) {
                $query->where('organization_id', $selectedOrganizationId);
            });
        }
        return $partCountQuery;
    }

    private function responseTotalCountScope(?string $selectedOrganizationId, string $panelID): Builder
    {
        $totalCountQuery = Response::query();

        if ($panelID === UserRole::ADMIN->value){
            $totalCountQuery->whereHas('feedback', function ($query){
                $query->where('organization_id', request()->user()->organization_id);
            });
        }

        if($selectedOrganizationId) {
            $totalCountQuery->whereHas('feedback', function ($query) use ($selectedOrganizationId) {
                $query->where('organization_id', $selectedOrganizationId);
            });
        }

        return $totalCountQuery;
    }

}
