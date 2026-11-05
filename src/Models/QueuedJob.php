<?php

namespace Dkvadratu\DashboardCore\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use JsonException;

class QueuedJob extends Model
{
    protected $table = 'jobs';

    public $timestamps = false;

    public static function queueDriver(): string
    {
        return (string) config('queue.connections.'.config('queue.default').'.driver', 'unknown');
    }

    public static function usesDatabaseQueue(): bool
    {
        return static::queueDriver() === 'database';
    }

    public function getConnectionName(): ?string
    {
        return static::usesDatabaseQueue() ? config('queue.connections.'.config('queue.default').'.connection') : null;
    }

    public function getTable(): string
    {
        return config('queue.connections.'.config('queue.default').'.table', 'jobs');
    }

    /**
     * @var array<int, string>
     */
    protected $guarded = ['*'];

    /**
     * @return array<string, mixed>
     */
    public function decodedPayload(): array
    {
        $payload = $this->getRawOriginal('payload');

        if (! is_string($payload) || $payload === '') {
            return [];
        }

        try {
            /** @var array<string, mixed> $decodedPayload */
            $decodedPayload = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);

            return is_array($decodedPayload) ? $decodedPayload : [];
        } catch (JsonException) {
            return [];
        }
    }

    public function jobClass(): string
    {
        $payload = $this->decodedPayload();

        return (string) ($payload['displayName'] ?? data_get($payload, 'data.commandName') ?? 'Unknown job');
    }

    public function statusLabel(): string
    {
        return $this->getRawOriginal('reserved_at') === null ? 'Queued' : 'Reserved';
    }

    public function payloadForDisplay(): string
    {
        $payload = $this->decodedPayload();

        if ($payload === []) {
            return (string) $this->getRawOriginal('payload');
        }

        return json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    public function queuedAt(): ?CarbonImmutable
    {
        return $this->timestampToDate($this->getRawOriginal('created_at'));
    }

    public function availableAt(): ?CarbonImmutable
    {
        return $this->timestampToDate($this->getRawOriginal('available_at'));
    }

    public function reservedAt(): ?CarbonImmutable
    {
        return $this->timestampToDate($this->getRawOriginal('reserved_at'));
    }

    protected function timestampToDate(mixed $value): ?CarbonImmutable
    {
        if (! is_numeric($value)) {
            return null;
        }

        return CarbonImmutable::createFromTimestamp((int) $value);
    }
}
