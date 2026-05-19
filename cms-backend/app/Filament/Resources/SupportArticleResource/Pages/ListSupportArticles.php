<?php
namespace App\Filament\Resources\SupportArticleResource\Pages;
use App\Filament\Resources\SupportArticleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListSupportArticles extends ListRecords {
    protected static string $resource = SupportArticleResource::class;
    protected function getHeaderActions(): array { return [Actions\CreateAction::make()]; }
}
