<?php

namespace App\Filament\Resources\BroadsheetResource\Pages;

use App\Filament\Resources\BroadsheetResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBroadsheet extends EditRecord
{
    protected static string $resource = BroadsheetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
