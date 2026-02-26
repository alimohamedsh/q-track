<?php

namespace App\Filament\Resources\TicketResource\Pages;

use App\Filament\Resources\TicketResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTicket extends EditRecord
{
    protected static string $resource = TicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('details')
                ->label('تفاصيل التذكرة')
                ->icon('heroicon-o-document-text')
                ->url(fn () => TicketResource::getUrl('details', ['record' => $this->record])),
            Actions\DeleteAction::make(),
        ];
    }
}
