<?php
namespace App\Filament\Resources\FooterWidgetResource\Pages;
use App\Filament\Resources\FooterWidgetResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditFooterWidget extends EditRecord {
    protected static string $resource = FooterWidgetResource::class;
    protected function getHeaderActions(): array { return [Actions\DeleteAction::make()]; }
}
