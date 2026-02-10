<?php

namespace App\Filament\Clusters\Feedbacks\Resources;

use App\Enums\UserRole;
use App\Filament\Actions\Tables\GenerateFeedbackReport;
use App\Filament\Clusters\Feedbacks;
use App\Filament\Clusters\Feedbacks\Resources\FeedbacksResource\Pages;
use App\Models\Feedback as FeedbackModel;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\ForceDeleteAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class FeedbacksResource extends Resource
{
    protected static ?string $model = FeedbackModel::class;

    protected static ?string $navigationIcon = 'gmdi-feedback-o';

    protected static ?string $cluster = Feedbacks::class;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('feedbacks.category_id')
                    ->label('Service Type')
                    ->limit(50)
                    ->sortable()
                    ->getStateUsing(fn ($record) => $record->category?->name),
                TextColumn::make('organization.code')
                    ->label('Organization')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('sqdAverage')
                    ->label('SQD Average')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 2) : 'N/A')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Date')
                    ->date()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault:true)
                    ->sortable(),
                ])
            ->filters([
                SelectFilter::make('organization_id')
                    ->label('Organization')
                    ->searchable()
                    ->options(fn () => \App\Models\Organization::pluck('code', 'id'))
                    ->hidden(fn() => !in_array(Filament::getCurrentPanel()->getId(), ['root', 'auditor'])),
            ])
            ->actions([
                DeleteAction::make()
                    ->hidden(fn ($record) => $record->trashed() === true || !in_array(Filament::getCurrentPanel()->getId(), [UserRole::ROOT->value]))
                    ->action(function ($record) {
                        $record->responses()->delete();
                        $record->delete();
                    }),
                ForceDeleteAction::make()
                    ->hidden(fn () => !in_array(Filament::getCurrentPanel()->getId(), [UserRole::ROOT->value]))
                    ->action(function ($record) {
                        $record->responses()->forceDelete();
                        $record->forceDelete();
                    }),
            ])
            ->recordUrl(fn ($record): string => static::getUrl('view', ['record' => $record]))
            ->bulkActions([
                GenerateFeedbackReport::make(),

                // BulkAction::make('generated-pdf')
                //     ->label('Generate')
                //     ->icon('gmdi-picture-as-pdf')
                //     ->action(function($records){
                //          $pdf = Pdf::view('filament.panels.feedback.feedback-form', ['records' => $records,'preview' => false])
                //             ->margins(10, 10, 10, 10)
                //             ->paperSize(8.5, 13, Unit::Inch)
                //             ->withBrowsershot(function (Browsershot $browsershot) {
                //                 return $browsershot
                //                     ->noSandbox()
                //                     ->emulateMedia('print')
                //                     ->portrait()
                //                     ->timeout(120)
                //                     ->showBackground();
                //             })
                //             ->base64();

                //             return response()->streamDownload(
                //                 function() use ($pdf) {
                //                     echo base64_decode($pdf);
                //                 },
                //                 'feedback_form_'.now()->format('Y_m_d_H_i_s').'.pdf',
                //             );
                //         })
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        return match (Filament::getCurrentPanel()->getId()) {
            UserRole::ROOT->value => $query,
            UserRole::AUDITOR->value => $query,
            UserRole::ADMIN->value => $query->whereHas('organization', function ($q) {
                $q->where('id', Filament::auth()->user()->organization_id);
                }),
            default => $query->whereRaw('1 = 0'),
        };
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFeedbacks::route('/'),
            'view' => Pages\FeedbackForm::route('/{record}'),
        ];
    }
}

