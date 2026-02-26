<?php

namespace App\Filament\Resources\TicketResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class VisitsRelationManager extends RelationManager
{
    protected static string $relationship = 'visits';

    protected static ?string $title = 'الزيارات (من نفذها ومتى)';

    protected static ?string $recordTitleAttribute = 'id';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('technician.name')
                    ->label('الفني المنفذ')
                    ->sortable(),
                Tables\Columns\TextColumn::make('check_in_at')
                    ->label('في الطريق')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('arrived_at')
                    ->label('وصل وبدء العمل')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('check_out_at')
                    ->label('إنهاء الزيارة')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('technician_notes')
                    ->label('ملاحظات الفني')
                    ->limit(40)
                    ->tooltip(fn ($record) => $record->technician_notes)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'completed'  => 'مكتملة',
                        'incomplete' => 'غير مكتملة',
                        default     => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'completed'  => 'success',
                        'incomplete' => 'warning',
                        default     => 'gray',
                    }),
            ])
            ->defaultSort('check_in_at', 'desc')
            ->headerActions([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('عرض التفاصيل والصور')
                    ->url(fn ($record) => \App\Filament\Resources\VisitResource::getUrl('edit', ['record' => $record])),
            ])
            ->bulkActions([
                //
            ]);
    }
}
