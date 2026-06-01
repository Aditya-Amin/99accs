<?php

namespace App\Filament\Resources\PaymentGatewayResource\Pages;

use App\Filament\Resources\PaymentGatewayResource;
use Filament\Resources\Pages\EditRecord;

class EditPaymentGateway extends EditRecord
{
    protected static string $resource = PaymentGatewayResource::class;

    protected function getHeaderActions(): array
    {
        // No delete — gateway rows are bound to code, deleting orphans the driver.
        return [];
    }

    /**
     * `credentials` is in the model's $hidden, so attributesToArray() (which
     * Filament uses to fill the form) omits it — the credential fields would
     * load blank and then OVERWRITE the saved secrets with empties on the next
     * save. Re-inject the decrypted arrays here so existing values populate the
     * dot-notation fields ("credentials.secret_key", etc.) and round-trip safely.
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['credentials'] = $this->getRecord()->credentials ?? [];
        $data['config']      = $this->getRecord()->config ?? [];

        return $data;
    }
}
