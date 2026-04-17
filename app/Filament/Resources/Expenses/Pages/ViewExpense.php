<?php

namespace App\Filament\Resources\Expenses\Pages;

use App\Filament\Resources\Expenses\ExpenseResource;
use App\Filament\Resources\Staff\StaffResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ViewExpense extends ViewRecord
{
    protected static string $resource = ExpenseResource::class;

    public function getTitle(): string
    {
        $category = $this->record->category?->name ?? 'Expense';
        $amount   = number_format($this->record->amount ?? 0, 0, '.', ' ');

        return "{$category} — {$amount} UZS";
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Back')
                ->url(ExpenseResource::getUrl('index'))
                ->color('gray')
                ->icon('heroicon-o-arrow-left'),
            EditAction::make(),
            DeleteAction::make()
                ->visible(fn () => auth()->user()?->can('delete', $this->record)),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make()
                ->columns(['default' => 1, 'lg' => 3])
                ->schema([
                    Group::make([
                        Section::make('Expense Info')
                            ->icon('heroicon-m-banknotes')
                            ->schema([
                                Grid::make(2)->schema([
                                    TextEntry::make('amount')
                                        ->label('Amount')
                                        ->money('UZS')
                                        ->icon('heroicon-m-banknotes')
                                        ->weight('bold')
                                        ->color('danger')
                                        ->size('lg'),

                                    TextEntry::make('expenses_date')
                                        ->label('Date')
                                        ->date('d M Y')
                                        ->icon('heroicon-m-calendar')
                                        ->weight('bold')
                                        ->size('lg'),
                                ]),

                                Grid::make(2)->schema([
                                    TextEntry::make('category.name')
                                        ->label('Category')
                                        ->icon('heroicon-m-tag')
                                        ->badge()
                                        ->color('info'),

                                    TextEntry::make('staff.full_name')
                                        ->label('Staff')
                                        ->icon('heroicon-m-user')
                                        ->placeholder('—')
                                        ->url(fn () => $this->record->staff_id
                                            ? StaffResource::getUrl('view', ['record' => $this->record->staff_id])
                                            : null)
                                        ->openUrlInNewTab(),
                                ]),
                            ]),

                        Section::make('Description')
                            ->icon('heroicon-m-chat-bubble-left-ellipsis')
                            ->schema([
                                TextEntry::make('description')
                                    ->label('')
                                    ->placeholder('No description provided.')
                                    ->columnSpanFull(),
                            ]),
                    ])
                        ->columnSpan(['default' => 1, 'lg' => 2]),

                    Group::make([
                        Section::make('Timestamps')
                            ->icon('heroicon-m-clock')
                            ->schema([
                                TextEntry::make('created_at')
                                    ->label('Created')
                                    ->dateTime('d M Y, H:i')
                                    ->icon('heroicon-m-calendar'),

                                TextEntry::make('updated_at')
                                    ->label('Updated')
                                    ->dateTime('d M Y, H:i')
                                    ->icon('heroicon-m-pencil-square'),
                            ]),
                    ])
                        ->columnSpan(['default' => 1, 'lg' => 1]),
                ])
                ->columnSpanFull(),
        ]);
    }
}
