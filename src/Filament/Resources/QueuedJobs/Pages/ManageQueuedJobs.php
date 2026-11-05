<?php

namespace Dkvadratu\DashboardCore\Filament\Resources\QueuedJobs\Pages;

use Dkvadratu\DashboardCore\Filament\Resources\QueuedJobs\QueuedJobResource;
use Dkvadratu\DashboardCore\Models\QueuedJob;
use Filament\Resources\Pages\ManageRecords;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;

class ManageQueuedJobs extends ManageRecords
{
    protected static string $resource = QueuedJobResource::class;

    public function content(Schema $schema): Schema
    {
        if (QueuedJob::usesDatabaseQueue()) {
            return parent::content($schema);
        }

        $driver = QueuedJob::queueDriver();
        $message = $driver === 'redis'
            ? 'Naudojama Redis eilė. Šiame lange Redis užduočių atvaizduoti negalime; peržiūra palaiko tik database driverį.'
            : "Naudojamas {$driver} queue driveris. Užduočių peržiūra palaiko tik database driverį.";

        return $schema->components([
            Section::make('Queued Jobs')->schema([Text::make($message)]),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
