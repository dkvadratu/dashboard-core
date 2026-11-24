<?php

namespace Dkvadratu\DashboardCore\Filament\Resources\Redirects;

use Closure;
use Dkvadratu\DashboardCore\Filament\Resources\Redirects\Pages\ManageRedirects;
use Dkvadratu\DashboardCore\Models\Redirect;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use UnitEnum;

class RedirectResource extends Resource
{
    protected static ?string $model = Redirect::class;

    protected static ?string $recordTitleAttribute = 'source';

    protected static string|UnitEnum|null $navigationGroup = 'Public web';

    public static function getAuthorizationResponse(string|UnitEnum $action, ?Model $record = null): Response
    {
        $user = Filament::auth()->user();
        if (! config('dashboard-redirects.enabled', false) || ! $user?->is_admin || ($user?->is_mcp_service_account ?? false)) {
            return Response::deny();
        }

        return parent::getAuthorizationResponse($action, $record);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('source')->required()->maxLength(255)->unique(ignoreRecord: true)
                ->placeholder('/old-page')
                ->helperText('Exact public path, without a query string or trailing slash.'),
            TextInput::make('destination')->required()->maxLength(255)->placeholder('/new-page')
                ->rules([
                    fn (Get $get, ?Redirect $record): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get, $record): void {
                        try {
                            Redirect::validateData([
                                'source' => $get('source'), 'destination' => $value,
                                'status_code' => $get('status_code'), 'enabled' => $get('enabled'),
                                'redirect_valid_until' => $get('redirect_valid_until'),
                            ], $record);
                        } catch (ValidationException $exception) {
                            $fail($exception->validator->errors()->first());
                        }
                    },
                ]),
            Select::make('status_code')->options([301 => '301 — Permanent', 302 => '302 — Temporary'])->default(301)->required(),
            Toggle::make('enabled')->default(true)->required(),
            DateTimePicker::make('redirect_valid_until')->label('Valid until')->helperText('Leave empty for no expiry. Times use the application timezone.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('source')->searchable()->wrap(),
            TextColumn::make('destination')->searchable()->wrap(),
            TextColumn::make('status_code')->badge(),
            IconColumn::make('enabled')->boolean(),
            TextColumn::make('redirect_valid_until')->dateTime()->placeholder('No expiry'),
            TextColumn::make('hits')->numeric()->sortable()->visible(fn (): bool => (bool) config('dashboard-redirects.record_hits', false)),
        ])->filters([TernaryFilter::make('enabled')])
            ->defaultSort('id', 'desc')
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageRedirects::route('/')];
    }
}
