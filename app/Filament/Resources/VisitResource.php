<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VisitResource\Pages;
use App\Models\Visit;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class VisitResource extends Resource
{
    protected static ?string $model = Visit::class;

    protected static ?string $navigationIcon = 'heroicon-o-map-pin';

    protected static ?string $navigationGroup = 'Q-Track';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'الزيارات';

    protected static ?string $modelLabel = 'زيارة';

    protected static ?string $pluralModelLabel = 'الزيارات';

    protected static bool $shouldSkipAuthorization = true;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('بيانات الزيارة')->schema([
                    Forms\Components\Select::make('ticket_id')
                        ->label('التذكرة')
                        ->relationship(
                            'ticket',
                            'ticket_number',
                            modifyQueryUsing: function (Builder $query) {
                                $user = auth()->user();
                                if ($user && $user->hasRole('manager') && !$user->hasRole('admin')) {
                                    $query->where('assigned_manager_id', $user->id);
                                }
                            }
                        )
                        ->searchable()
                        ->preload()
                        ->required(),
                    Forms\Components\Select::make('user_id')
                        ->label('الفني')
                        ->relationship('technician', 'name')
                        ->searchable()
                        ->preload()
                        ->required(),
                    Forms\Components\Select::make('status')
                        ->label('الحالة')
                        ->options([
                            'incomplete' => 'غير مكتملة',
                            'completed'  => 'مكتملة',
                        ])
                        ->default('incomplete')
                        ->required(),
                ]),
                Forms\Components\Section::make('أوقات الزيارة')->schema([
                    Forms\Components\DateTimePicker::make('check_in_at')
                        ->label('وقت الدخول'),
                    Forms\Components\DateTimePicker::make('check_out_at')
                        ->label('وقت الخروج'),
                ]),
                Forms\Components\Section::make('الإحداثيات')->schema([
                    Forms\Components\Grid::make(2)->schema([
                        Forms\Components\TextInput::make('start_lat')
                            ->label('خط العرض (البداية)')
                            ->numeric()
                            ->step(0.00000001),
                        Forms\Components\TextInput::make('start_lng')
                            ->label('خط الطول (البداية)')
                            ->numeric()
                            ->step(0.00000001),
                        Forms\Components\TextInput::make('end_lat')
                            ->label('خط العرض (النهاية)')
                            ->numeric()
                            ->step(0.00000001),
                        Forms\Components\TextInput::make('end_lng')
                            ->label('خط الطول (النهاية)')
                            ->numeric()
                            ->step(0.00000001),
                    ]),
                ]),
                Forms\Components\Section::make('ملاحظات')->schema([
                    Forms\Components\Textarea::make('technician_notes')
                        ->label('ملاحظات الفني')
                        ->rows(3)
                        ->columnSpanFull(),
                    Forms\Components\TextInput::make('failure_reason')
                        ->label('سبب الفشل')
                        ->maxLength(255),
                ]),
                Forms\Components\Section::make('صور الزيارة (Check-out)')
                    ->schema([
                        Forms\Components\ViewField::make('attachments_view')
                            ->view('filament.forms.components.visit-attachments')
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
                Forms\Components\Section::make('نتائج المهام (من الفني)')
                    ->schema([
                        Forms\Components\ViewField::make('task_results_view')
                            ->view('filament.forms.components.visit-task-results')
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('#')
                    ->sortable(),
                Tables\Columns\TextColumn::make('ticket.ticket_number')
                    ->label('رقم التذكرة')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('technician.name')
                    ->label('الفني')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'incomplete' => 'غير مكتملة',
                        'completed'  => 'مكتملة',
                        default     => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'incomplete' => 'warning',
                        'completed'  => 'success',
                        default     => 'gray',
                    }),
                Tables\Columns\TextColumn::make('check_in_at')
                    ->label('وقت الدخول')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('check_out_at')
                    ->label('وقت الخروج')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user && $user->hasRole('manager') && !$user->hasRole('admin')) {
            $query->whereHas('ticket', fn (Builder $q) => $q->where('assigned_manager_id', $user->id));
        }

        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVisits::route('/'),
            'create' => Pages\CreateVisit::route('/create'),
            'edit' => Pages\EditVisit::route('/{record}/edit'),
        ];
    }
}
