<?php

namespace Dkvadratu\DashboardCore\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Str;
use UnitEnum;

class ProjectReadme extends Page
{
    protected static ?string $navigationLabel = 'Project documentation';

    protected static string|UnitEnum|null $navigationGroup = 'System';

    protected string $view = 'dashboard-core::document';

    public function documentHtml(): string
    {
        $html = '';
        foreach (['readme', 'changelog'] as $document) {
            $path = config('dashboard.'.$document);
            $markdown = is_string($path) && is_file($path) ? file_get_contents($path) : ucfirst($document).' is not available.';
            $html .= Str::markdown($markdown, ['html_input' => 'strip', 'allow_unsafe_links' => false]);
        }

        return $html;
    }
}
