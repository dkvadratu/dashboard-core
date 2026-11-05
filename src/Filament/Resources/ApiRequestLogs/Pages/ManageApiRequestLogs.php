<?php

namespace Dkvadratu\DashboardCore\Filament\Resources\ApiRequestLogs\Pages;

use Dkvadratu\DashboardCore\Filament\Resources\ApiRequestLogs\ApiRequestLogResource;
use Filament\Resources\Pages\ManageRecords;

class ManageApiRequestLogs extends ManageRecords
{
    protected static string $resource = ApiRequestLogResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
