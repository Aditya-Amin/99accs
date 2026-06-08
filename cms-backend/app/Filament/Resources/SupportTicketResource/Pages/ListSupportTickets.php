<?php

namespace App\Filament\Resources\SupportTicketResource\Pages;

use App\Filament\Resources\SupportTicketResource;
use App\Models\SupportTicket;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListSupportTickets extends ListRecords
{
    protected static string $resource = SupportTicketResource::class;

    /** Status tabs so staff can triage the queue at a glance. */
    public function getTabs(): array
    {
        return [
            'all'    => Tab::make('All'),
            'new'    => Tab::make('New')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', SupportTicket::STATUS_NEW))
                ->badge(SupportTicket::where('status', SupportTicket::STATUS_NEW)->count())
                ->badgeColor('warning'),
            'open'   => Tab::make('Open')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', SupportTicket::STATUS_OPEN)),
            'closed' => Tab::make('Closed')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', SupportTicket::STATUS_CLOSED)),
        ];
    }
}
