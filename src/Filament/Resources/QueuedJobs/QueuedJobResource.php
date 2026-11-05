<?php

namespace Dkvadratu\DashboardCore\Filament\Resources\QueuedJobs;

use BackedEnum;
use Carbon\CarbonImmutable;
use Dkvadratu\DashboardCore\Filament\Resources\QueuedJobs\Pages\ManageQueuedJobs;
use Dkvadratu\DashboardCore\Models\QueuedJob;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class QueuedJobResource extends Resource
{
    protected static ?string $model = QueuedJob::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static ?string $navigationLabel = 'Queued Jobs';

    protected static ?string $pluralModelLabel = 'Queued Jobs';

    protected static string|UnitEnum|null $navigationGroup = 'System';

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

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([]);
    }

    public static function table(Table $table): Table
    {
        if (! QueuedJob::usesDatabaseQueue()) {
            return $table->records(fn (): array => []);
        }

        return $table
            ->columns([
                TextColumn::make('id')
                    ->sortable(),
                TextColumn::make('queue')
                    ->badge()
                    ->sortable()
                    ->searchable(),
                TextColumn::make('job_class')
                    ->label('Job')
                    ->state(fn (QueuedJob $record): string => $record->jobClass())
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->where('payload', 'like', "%{$search}%"))
                    ->wrap(),
                TextColumn::make('attempts')
                    ->sortable(),
                TextColumn::make('status')
                    ->state(fn (QueuedJob $record): string => $record->statusLabel())
                    ->badge()
                    ->color(fn (string $state): string => $state === 'Reserved' ? 'warning' : 'success'),
                TextColumn::make('available_at')
                    ->label('Available At')
                    ->state(fn (QueuedJob $record): ?CarbonImmutable => $record->availableAt())
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('reserved_at')
                    ->label('Reserved At')
                    ->state(fn (QueuedJob $record): ?CarbonImmutable => $record->reservedAt())
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Queued At')
                    ->state(fn (QueuedJob $record): ?CarbonImmutable => $record->queuedAt())
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('queue')
                    ->options(fn (): array => QueuedJob::query()
                        ->distinct()
                        ->orderBy('queue')
                        ->pluck('queue', 'queue')
                        ->all()),
                SelectFilter::make('reservation_state')
                    ->options([
                        'queued' => 'Queued',
                        'reserved' => 'Reserved',
                    ])
                    ->query(function (Builder $query, array $data): void {
                        $value = $data['value'] ?? null;

                        if ($value === 'queued') {
                            $query->whereNull('reserved_at');
                        }

                        if ($value === 'reserved') {
                            $query->whereNotNull('reserved_at');
                        }
                    }),
            ])
            ->defaultSort('created_at', direction: 'desc')
            ->paginated([25, 50, 100])
            ->recordActions([
                Action::make('payload')
                    ->label('Payload')
                    ->icon(Heroicon::CodeBracket)
                    ->slideOver()
                    ->modalSubmitAction(false)
                    ->schema([
                        Textarea::make('payload_for_display')
                            ->label('Payload')
                            ->rows(20)
                            ->readOnly(),
                    ])
                    ->fillForm(fn (QueuedJob $record): array => [
                        'payload_for_display' => $record->payloadForDisplay(),
                    ]),
            ]);
    }

    public static function getNavigationBadge(): ?string
    {
        return QueuedJob::usesDatabaseQueue() ? (string) QueuedJob::query()->count() : null;
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageQueuedJobs::route('/'),
        ];
    }
}
