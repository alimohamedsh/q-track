<?php

namespace App\Filament\Resources\TicketResource\Pages;

use App\Filament\Resources\TicketResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewTicketDetails extends ViewRecord
{
    protected static string $resource = TicketResource::class;

    protected static string $view = 'filament.resources.ticket-resource.pages.view-ticket-details';

    public function getTitle(): string
    {
        return 'تفاصيل التذكرة ' . ($this->record->ticket_number ?? '');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('edit')
                ->label('تعديل التذكرة')
                ->icon('heroicon-o-pencil-square')
                ->url(TicketResource::getUrl('edit', ['record' => $this->record])),
        ];
    }

    /** عدم عرض Relation Managers لأن التفاصيل معروضة في الصفحة نفسها */
    public function getRelationManagers(): array
    {
        return [];
    }

    protected function resolveRecord(mixed $key): \Illuminate\Database\Eloquent\Model
    {
        $record = parent::resolveRecord($key);
        $record->load([
            'requiredItems',
            'creator',
            'assignedManager',
            'assignedTechnician',
            'evaluation',
            'visits' => fn ($q) => $q->with([
                'technician',
                'attachments',
                'taskResults.ticketTask',
                'failureReason',
            ])->orderByDesc('id'),
        ]);
        return $record;
    }
}
