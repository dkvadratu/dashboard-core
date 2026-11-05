<?php

namespace Dkvadratu\DashboardCore\Filament\Pages;

use Dkvadratu\DashboardCore\Support\McpTokens as TokenManager;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Laravel\Sanctum\PersonalAccessToken;
use UnitEnum;

class McpTokens extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $title = 'MCP tokens';

    protected static string|UnitEnum|null $navigationGroup = 'System';

    protected string $view = 'dashboard-core::mcp-tokens';

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();

        return $user?->is_admin && ! $user->is_mcp_service_account;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(TokenManager::query())
            ->columns([
                TextColumn::make('name')->searchable(),
                TextColumn::make('tokenable.email')->label('AI user'),
                TextColumn::make('abilities')->badge(),
                TextColumn::make('created_at')->dateTime(),
                TextColumn::make('last_used_at')->dateTime()->placeholder('Never'),
                TextColumn::make('expires_at')->dateTime(),
            ])
            ->defaultSort('id', 'desc')
            ->recordActions([
                Action::make('revoke')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (PersonalAccessToken $record): void {
                        abort_unless(static::canAccess(), 403);
                        TokenManager::query()->findOrFail($record->getKey())->delete();
                    }),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('createToken')
                ->label('Create token')
                ->schema([
                    TextInput::make('name')->required()->maxLength(100)->default('AI client'),
                    CheckboxList::make('abilities')->options(TokenManager::abilities())->default(['users:read'])->required()->minItems(1),
                    Select::make('days')->label('Expires in')->options([30 => '30 days', 90 => '90 days', 365 => '365 days'])->default(30)->required(),
                ])
                ->action(function (array $data, TokenManager $tokens): void {
                    abort_unless(static::canAccess(), 403);
                    $token = $tokens->issue($data['name'], $data['abilities'], (int) $data['days']);
                    session()->flash('dashboard_mcp_token', $token->plainTextToken);
                    $this->redirect(static::getUrl());
                }),
        ];
    }
}
