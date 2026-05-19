<?php

namespace App\Filament\Pages;

use App\Models\SiteSetting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Support\Exceptions\Halt;
use Filament\Notifications\Notification;

class ManageMailSettings extends Page implements Forms\Contracts\HasForms
{
    use Forms\Concerns\InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-envelope';

    protected static string $view = 'filament.pages.manage-mail-settings';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?string $title = 'Mail Settings';

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
                Forms\Components\Section::make('Mail Server Settings')
                    ->description('Configure SMTP or other mail driver details.')
                    ->schema([
                        Forms\Components\Select::make('mail_mailer')
                            ->label('Mailer')
                            ->options([
                                'smtp' => 'SMTP',
                                'sendmail' => 'Sendmail',
                                'mailgun' => 'Mailgun',
                                'ses' => 'Amazon SES',
                                'postmark' => 'Postmark',
                            ])
                            ->default('smtp')
                            ->required(),
                        Forms\Components\TextInput::make('mail_host')
                            ->label('Mail Host')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('mail_port')
                            ->label('Mail Port')
                            ->numeric(),
                        Forms\Components\TextInput::make('mail_username')
                            ->label('Mail Username')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('mail_password')
                            ->label('Mail Password')
                            ->password()
                            ->maxLength(255),
                        Forms\Components\Select::make('mail_encryption')
                            ->label('Mail Encryption')
                            ->options([
                                'tls' => 'TLS',
                                'ssl' => 'SSL',
                                '' => 'None',
                            ])
                            ->default('tls'),
                        Forms\Components\TextInput::make('mail_from_address')
                            ->label('Mail From Address')
                            ->email()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('mail_from_name')
                            ->label('Mail From Name')
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
                ->title('Mail Settings Saved Successfully')
                ->send();
        } catch (Halt $exception) {
            return;
        }
    }
}
