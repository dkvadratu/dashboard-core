<nav class="fi-sidebar-footer" aria-label="Dokumentacija">
    <ul>
        <x-filament-panels::sidebar.item
            :active="request()->routeIs(\Dkvadratu\DashboardCore\Filament\Pages\ProjectReadme::getRouteName())"
            icon="heroicon-o-document-text"
            :url="\Dkvadratu\DashboardCore\Filament\Pages\ProjectReadme::getUrl()"
        >
            v{{ config('dashboard.version') }} · README
        </x-filament-panels::sidebar.item>
    </ul>
</nav>
