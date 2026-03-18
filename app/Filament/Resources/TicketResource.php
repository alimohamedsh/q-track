<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TicketResource\Pages;
use App\Models\RequiredItemTemplate;
use App\Models\Ticket;
use App\Models\TicketRequiredItem;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class TicketResource extends Resource
{
    protected static ?string $model = Ticket::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';

    protected static ?string $navigationGroup = 'Q-Track';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'المشاريع';

    protected static ?string $modelLabel = 'مشروع';

    protected static ?string $pluralModelLabel = 'المشاريع';

    protected static ?string $recordTitleAttribute = 'ticket_number';

    protected static bool $shouldSkipAuthorization = true;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('created_by'),
                Forms\Components\Hidden::make('uuid'),
                Forms\Components\Section::make('من عيّن ومن نفذ')
                    ->description('بيانات المسؤولين والتاريخ - تظهر عند التعديل')
                    ->schema([
                        Forms\Components\Placeholder::make('creator_info')
                            ->label('من أنشأ المشروع')
                            ->content(fn (Get $get) => User::find($get('created_by'))?->name ?? '—'),
                        Forms\Components\Placeholder::make('tracking_link')
                        ->label('رابط التتبع (للعميل)')
                        ->content(fn (Get $get) => $get('uuid') ? url('/track/' . $get('uuid')) : '—')
                        ->visibleOn('edit'),
                    Forms\Components\Placeholder::make('manager_info')
                            ->label('مدير الفنيين')
                            ->content(fn (Get $get) => User::find($get('assigned_manager_id'))?->name ?? '—'),
                        Forms\Components\Placeholder::make('technician_info')
                            ->label('الفني المكلف')
                            ->content(fn (Get $get) => User::find($get('assigned_to'))?->name ?? '—'),
                    ])
                    ->columns(3)
                    ->collapsible()
                    ->visibleOn('edit'),
                Forms\Components\Section::make('بيانات المشروع')->schema([
                    Forms\Components\TextInput::make('ticket_number')
                        ->label('رقم المشروع')
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true),
                    Forms\Components\TextInput::make('project_name')
                        ->label('اسم المشروع')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\Select::make('type')
                        ->label('النوع')
                        ->options([
                            'maintenance'  => 'صيانة',
                            'installation' => 'تركيب',
                            'visit'        => 'زيارة',
                        ])
                        ->required(),
                    Forms\Components\Select::make('status')
                        ->label('الحالة')
                        ->options([
                            'open'             => 'مفتوحة',
                            'in_progress'      => 'قيد التنفيذ',
                            'on_hold'          => 'معلّقة',
                            'revisit_required' => 'مطلوب إعادة زيارة',
                            'closed'           => 'مغلقة',
                            'canceled'         => 'ملغاة',
                        ])
                        ->default('open')
                        ->required(),
                    Forms\Components\Select::make('priority')
                        ->label('الأولوية')
                        ->options([
                            'low'    => 'منخفضة',
                            'medium' => 'متوسطة',
                            'high'   => 'عالية',
                            'critical' => 'ضرورة قصوى',
                        ])
                        ->default('medium')
                        ->required(),
                    Forms\Components\Select::make('warranty_status')
                        ->label('حالة الضمان')
                        ->options([
                            'in_warranty'     => 'داخل الضمان',
                            'out_of_warranty' => 'خارج الضمان',
                        ])
                        ->default('in_warranty')
                        ->required(),
                    Forms\Components\Select::make('assigned_manager_id')
                        ->label('مدير الفنيين')
                        ->options(
                            User::role('manager')->pluck('name', 'id')
                        )
                        ->searchable()
                        ->preload()
                        ->nullable(),
                    Forms\Components\Select::make('assigned_to')
                        ->label('الفني المكلف')
                        ->relationship('assignedTechnician', 'name')
                        ->searchable()
                        ->preload()
                        ->nullable(),
                ]),
                Forms\Components\Section::make('بيانات العميل والمواعيد')->schema([
                    Forms\Components\TextInput::make('client_name')
                        ->label('اسم العميل')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('client_phone')
                        ->label('رقم موبايل العميل')
                        ->tel()
                        ->maxLength(20),
                    Forms\Components\Textarea::make('client_address')
                        ->label('عنوان العميل')
                        ->required()
                        ->rows(2),
                    Forms\Components\DateTimePicker::make('scheduled_at')
                        ->label('موعد الزيارة المُجدول')
                        ->minDate(Carbon::now())
                        ->nullable()
                        ->dehydrateStateUsing(fn ($state) => static::sanitizeDate($state)),
                    Forms\Components\DatePicker::make('due_date')
                        ->label('الموعد النهائي')
                        ->minDate(Carbon::now())
                        ->nullable()
                        ->dehydrateStateUsing(fn ($state) => static::sanitizeDate($state)),
                    Forms\Components\TextInput::make('location_url')
                        ->label('رابط جوجل ماب')
                        ->placeholder('الصق رابط Google Maps وسيتم استخراج الإحداثيات تلقائياً')
                        ->url()
                        ->maxLength(255)
                        ->live()
                        ->afterStateUpdated(function (?string $state, Forms\Set $set): void {
                            if (empty($state)) {
                                return;
                            }
                            // أنماط Google Maps: @lat,lng أو ?q=lat,lng أو &q=lat,lng
                            if (preg_match('/@(-?\d+\.?\d*),(-?\d+\.?\d*)/', $state, $m)) {
                                $set('lat', $m[1]);
                                $set('lng', $m[2]);
                            } elseif (preg_match('/[?&]q=(-?\d+\.?\d*),(-?\d+\.?\d*)/', $state, $m)) {
                                $set('lat', $m[1]);
                                $set('lng', $m[2]);
                            }
                        }),
                    Forms\Components\Grid::make(2)->schema([
                        Forms\Components\TextInput::make('lat')
                            ->label('خط العرض')
                            ->numeric()
                            ->step(0.00000001)
                            ->readOnly()
                            ->dehydrated()
                            ->dehydrateStateUsing(fn ($state) => $state ?: null),
                        Forms\Components\TextInput::make('lng')
                            ->label('خط الطول')
                            ->numeric()
                            ->step(0.00000001)
                            ->readOnly()
                            ->dehydrated()
                            ->dehydrateStateUsing(fn ($state) => $state ?: null),
                    ]),
                ]),
                Forms\Components\Section::make('المهام')->schema([
                    Forms\Components\Repeater::make('tasks')
                        ->relationship()
                        ->schema([
                            Forms\Components\TextInput::make('description')
                                ->label('وصف المهمة')
                                ->required()
                                ->maxLength(255),
                        ])
                        ->defaultItems(0)
                        ->reorderableWithButtons()
                        ->collapsible(),
                ]),
                Forms\Components\Section::make('متطلبات المشروع')
                    ->description('أشياء يجب أن يحضرها الفني: اختر من القائمة أو "أخرى" لكتابة اسم مخصص.')
                    ->schema([
                        Forms\Components\Repeater::make('requiredItems')
                            ->relationship()
                            ->schema([
                                Forms\Components\Select::make('required_item_template_id')
                                    ->label('العنصر')
                                    ->options(fn () => [TicketRequiredItem::OTHER_TEMPLATE_KEY => 'أخرى'] + RequiredItemTemplate::orderBy('sort_order')->orderBy('name')->pluck('name', 'id')->toArray())
                                    ->required()
                                    ->live()
                                    ->searchable(),
                                Forms\Components\TextInput::make('name')
                                    ->label('اسم مخصص (لأخرى فقط)')
                                    ->maxLength(255)
                                    ->placeholder('اكتب اسم العنصر')
                                    ->required(fn (Get $get) => (int) $get('required_item_template_id') === TicketRequiredItem::OTHER_TEMPLATE_KEY)
                                    ->visible(fn (Get $get) => (int) $get('required_item_template_id') === TicketRequiredItem::OTHER_TEMPLATE_KEY),
                                Forms\Components\TextInput::make('quantity')
                                    ->label('الكمية')
                                    ->numeric()
                                    ->minValue(1)
                                    ->default(1),
                                Forms\Components\TextInput::make('notes')
                                    ->label('ملاحظات')
                                    ->maxLength(255)
                                    ->placeholder('لون، موديل...'),
                            ])
                            ->columns(3)
                            ->defaultItems(0)
                            ->reorderableWithButtons()
                            ->collapsible(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('ticket_number')
                    ->label('رقم المشروع')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('النوع')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'installation' => 'تركيب',
                        'maintenance'  => 'صيانة',
                        'visit'        => 'زيارة',
                        default       => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'installation' => 'success',
                        'maintenance'  => 'warning',
                        'visit'        => 'info',
                        default       => 'gray',
                    }),
                Tables\Columns\TextColumn::make('client_name')
                    ->label('العميل')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('assignedManager.name')
                    ->label('مدير الفنيين')
                    ->sortable(),
                Tables\Columns\TextColumn::make('assignedTechnician.name')
                    ->label('الفني المكلف')
                    ->sortable(),
                Tables\Columns\TextColumn::make('creator.name')
                    ->label('من أنشأها')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'open'             => 'مفتوحة',
                        'in_progress'      => 'قيد التنفيذ',
                        'on_hold'          => 'معلّقة',
                        'revisit_required' => 'إعادة زيارة',
                        'closed'           => 'مغلقة',
                        'canceled'         => 'ملغاة',
                        default            => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'open'             => 'info',
                        'in_progress'      => 'warning',
                        'on_hold'          => 'gray',
                        'revisit_required' => 'warning',
                        'closed'           => 'success',
                        'canceled'         => 'danger',
                        default            => 'gray',
                    }),
                Tables\Columns\TextColumn::make('priority')
                    ->label('الأولوية')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'low'      => 'منخفضة',
                        'medium'   => 'متوسطة',
                        'high'     => 'عالية',
                        'critical' => 'ضرورة قصوى',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('last_visit_at')
                    ->label('آخر إنهاء زيارة')
                    ->dateTime('d/m/Y H:i')
                    ->getStateUsing(fn ($record) => $record->visits()->max('check_out_at'))
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        'active'           => 'نشطة (مفتوحة / قيد التنفيذ / معلّقة)',
                        'archive'          => 'أرشيف (مغلقة / ملغاة)',
                        'open'             => 'مفتوحة فقط',
                        'in_progress'      => 'قيد التنفيذ',
                        'on_hold'          => 'معلّقة',
                        'revisit_required' => 'إعادة زيارة',
                        'closed'           => 'مغلقة',
                        'canceled'         => 'ملغاة',
                    ])
                    ->default('active')
                    ->query(function (Builder $query, array $data): Builder {
                        $v = $data['value'] ?? null;
                        if ($v === 'active') {
                            return $query->whereIn('status', ['open', 'in_progress', 'on_hold', 'revisit_required']);
                        }
                        if ($v === 'archive') {
                            return $query->whereIn('status', ['closed', 'canceled']);
                        }
                        if ($v) {
                            return $query->where('status', $v);
                        }
                        return $query;
                    }),
                Tables\Filters\Filter::make('no_visits')
                    ->label('بدون زيارات')
                    ->query(fn (Builder $query): Builder => $query->whereDoesntHave('visits')),
            ])
            ->actions([
                Tables\Actions\Action::make('details')
                    ->label('تفاصيل')
                    ->icon('heroicon-o-document-text')
                    ->url(fn (Ticket $record) => TicketResource::getUrl('details', ['record' => $record])),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * تحويل القيم الفارغة أو غير الصالحة إلى null لتجنب أخطاء التاريخ
     */
    protected static function sanitizeDate(mixed $state): ?string
    {
        if ($state === null || $state === '') {
            return null;
        }
        try {
            $dt = Carbon::parse($state);
            return $dt->year > 1900 ? $state : null;
        } catch (\Throwable) {
            return null;
        }
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['creator', 'assignedManager', 'assignedTechnician']);
        $user = auth()->user();

        if ($user && $user->hasRole('manager') && !$user->hasRole('admin')) {
            $query->where('assigned_manager_id', $user->id);
        }

        return $query;
    }

    public static function getRelations(): array
    {
        return [
            \App\Filament\Resources\TicketResource\RelationManagers\VisitsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'   => Pages\ListTickets::route('/'),
            'create'  => Pages\CreateTicket::route('/create'),
            'edit'    => Pages\EditTicket::route('/{record}/edit'),
            'details' => Pages\ViewTicketDetails::route('/{record}/details'),
        ];
    }

    /**
     * عنوان السجل في أعلى صفحات Filament (يتضمن اسم المشروع + رقم التذكرة).
     */
    public static function getRecordTitle(?Model $record): ?string
    {
        if (! $record) {
            return null;
        }

        $parts = [];

        if (! empty($record->project_name)) {
            $parts[] = $record->project_name;
        } elseif (! empty($record->client_name)) {
            $parts[] = $record->client_name;
        }

        if (! empty($record->ticket_number)) {
            $parts[] = $record->ticket_number;
        }

        return $parts ? implode(' - ', $parts) : (string) $record->getKey();
    }
}
