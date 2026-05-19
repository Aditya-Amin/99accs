<?php

namespace App\Filament\Pages;

use App\Models\SiteSetting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Support\Exceptions\Halt;
use Filament\Notifications\Notification;

class ManageSiteSettings extends Page implements Forms\Contracts\HasForms
{
    use Forms\Concerns\InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static string $view = 'filament.pages.manage-site-settings';

    protected static ?string $navigationGroup = 'Settings';
    
    protected static ?string $title = 'General Settings';

    public ?array $data = [];

    public function mount(): void
    {
        $settings = SiteSetting::getSettings();
        $this->form->fill($settings->toArray());
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Site Information')
                    ->schema([
                        Forms\Components\TextInput::make('site_title')
                            ->label('Site Title')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('tag_line')
                            ->label('Tag Line')
                            ->maxLength(255),
                        Forms\Components\Select::make('language')
                            ->label('Default Language')
                            ->options([
                                'en' => 'English',
                                'es' => 'Spanish',
                                'fr' => 'French',
                                'de' => 'German',
                            ])
                            ->required(),
                        Forms\Components\Select::make('timezone')
                            ->label('Timezone')
                            ->searchable()
                            ->options(array_combine(timezone_identifiers_list(), timezone_identifiers_list()))
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make('Branding')
                    ->schema([
                        Forms\Components\FileUpload::make('site_icon')
                            ->label('Site Icon (Favicon)')
                            ->image()
                            ->directory('settings'),
                        Forms\Components\FileUpload::make('logo')
                            ->label('Site Logo')
                            ->image()
                            ->directory('settings'),
                    ])->columns(2),
                    
                Forms\Components\Section::make('Contact Details')
                    ->schema([
                        Forms\Components\TextInput::make('contact_email')
                            ->label('Contact Email')
                            ->email()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('contact_phone')
                            ->label('Contact Phone')
                            ->tel()
                            ->maxLength(255),
                    ])->columns(2),

                
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label(__('filament-panels::pages/tenancy/edit-tenant-profile.form.actions.save.label'))
                ->submit('save'),
        ];
    }

    public function save(): void
    {
        try {
            $data = $this->form->getState();

            $settings = SiteSetting::getSettings();
            $settings->update($data);

            Notification::make()
                ->success()
                ->title('Settings Saved Successfully')
                ->send();
        } catch (Halt $exception) {
            return;
        }
    }
}

