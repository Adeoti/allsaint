<?php

namespace App\Filament\Resources\BroadsheetResource\Pages;

use App\Filament\Resources\BroadsheetResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBroadsheets extends ListRecords
{
    protected static string $resource = BroadsheetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
