<?php

namespace Dkvadratu\DashboardCore\Filament\Resources\ApiRequestLogs;

use Dkvadratu\DashboardCore\Filament\Resources\ApiRequestLogs\Pages\ManageApiRequestLogs;
use Dkvadratu\DashboardCore\Models\ApiRequestLog;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class ApiRequestLogResource extends Resource
{
    protected static ?string $model = ApiRequestLog::class;

    protected static string|UnitEnum|null $navigationGroup = 'System';

    protected static ?string $navigationLabel = 'Requests';

    protected static ?string $pluralModelLabel = 'Requests';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')->dateTime()->sortable(),
                TextColumn::make('request_type')->label('Type')->badge()->formatStateUsing(fn (string $state): string => strtoupper($state)),
                TextColumn::make('method')->badge(),
                TextColumn::make('path')->searchable()->wrap(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('ip_address'),
            ])
            ->filters([SelectFilter::make('request_type')->label('Type')->options(['api' => 'API', 'http' => 'HTTP'])])
            ->defaultSort('id', 'desc')
            ->recordActions([
                Action::make('details')
                    ->slideOver()
                    ->modalSubmitAction(false)
                    ->schema([Textarea::make('details')->readOnly()->rows(20)])
                    ->fillForm(fn (ApiRequestLog $record): array => [
                        'details' => json_encode($record->only(['request_type', 'request_headers', 'response_headers', 'request_body', 'response_body']), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE),
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageApiRequestLogs::route('/')];
    }
}
