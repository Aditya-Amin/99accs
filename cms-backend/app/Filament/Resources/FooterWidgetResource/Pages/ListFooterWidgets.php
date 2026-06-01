<?php
namespace App\Filament\Resources\FooterWidgetResource\Pages;
use App\Filament\Resources\FooterWidgetResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListFooterWidgets extends ListRecords {
    protected static string $resource = FooterWidgetResource::class;
    protected function getHeaderActions(): array { return [Actions\CreateAction::make()]; }
}
