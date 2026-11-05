<?php

namespace Dkvadratu\DashboardCore\Filament\Pages;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Dashboard;
use Illuminate\Support\Facades\Cache;

class AdminDashboard extends Dashboard
{
    protected function getHeaderActions(): array
    {
        return [
            Action::make('clearCache')
                ->label('Clear application cache')
                ->modalDescription('Clears the default cache store. Use a dedicated store for this application.')
                ->requiresConfirmation()
                ->action(function (): void {
                    $cleared = Cache::flush();
                    Notification::make()
                        ->title($cleared ? 'Application cache cleared' : 'Cache could not be cleared')
                        ->status($cleared ? 'success' : 'danger')
                        ->send();
                }),
        ];
    }
}
