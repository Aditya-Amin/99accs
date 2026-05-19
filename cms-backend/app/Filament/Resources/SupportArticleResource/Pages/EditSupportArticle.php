<?php
namespace App\Filament\Resources\SupportArticleResource\Pages;
use App\Filament\Resources\SupportArticleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditSupportArticle extends EditRecord {
    protected static string $resource = SupportArticleResource::class;
    protected function getHeaderActions(): array { return [Actions\DeleteAction::make()]; }
}
