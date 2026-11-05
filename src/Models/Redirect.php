<?php

namespace Dkvadratu\DashboardCore\Models;

use Dkvadratu\DashboardCore\Database\Factories\RedirectFactory;
use Dkvadratu\DashboardCore\Support\RedirectPaths;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class Redirect extends Model
{
    /** @use HasFactory<RedirectFactory> */
    use HasFactory;

    public const string CACHE_KEY = 'dashboard:redirects:all';

    protected $table = 'dashboard_redirects';

    protected $fillable = ['source', 'destination', 'status_code', 'enabled', 'redirect_valid_until'];

    protected $attributes = ['status_code' => 301, 'enabled' => true, 'hits' => 0];

    protected function casts(): array
    {
        return ['enabled' => 'boolean', 'status_code' => 'integer', 'hits' => 'integer', 'redirect_valid_until' => 'datetime'];
    }

    protected static function newFactory(): RedirectFactory
    {
        return RedirectFactory::new();
    }

    protected static function booted(): void
    {
        static::saving(function (self $redirect): void {
            static::validateData($redirect->attributesToArray(), $redirect);
        });
        foreach (['saved', 'deleted'] as $event) {
            static::{$event}(function (self $redirect): void {
                static::clearCache();
                if ($redirect->getConnection()->transactionLevel() > 0) {
                    $redirect->getConnection()->afterCommit(fn () => static::clearCache());
                }
            });
        }
    }

    public static function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /** @return array<string, mixed> */
    public static function validateData(array $data, ?self $record = null): array
    {
        $validated = Validator::make($data, [
            'source' => ['required', 'string', 'max:255', Rule::unique('dashboard_redirects', 'source')->ignore($record?->getKey())],
            'destination' => ['required', 'string', 'max:255'],
            'status_code' => ['required', 'integer', Rule::in([301, 302])],
            'enabled' => ['required', 'boolean'],
            'redirect_valid_until' => ['nullable', 'date'],
        ])->validate();

        foreach (['source', 'destination'] as $field) {
            if (! RedirectPaths::isLocal($validated[$field]) || RedirectPaths::isExcluded($validated[$field])) {
                throw ValidationException::withMessages([$field => 'Use a public local path such as /old-page, without query strings, wildcards or a trailing slash.']);
            }
        }

        $paths = static::query()->when($record?->exists, fn (Builder $query): Builder => $query->whereKeyNot($record->getKey()))
            ->pluck('destination', 'source')->all();
        $paths[$validated['source']] = $validated['destination'];
        $path = $validated['source'];
        $seen = [];
        while (isset($paths[$path])) {
            if (isset($seen[$path])) {
                throw ValidationException::withMessages(['destination' => 'This redirect would create a redirect loop.']);
            }
            $seen[$path] = true;
            $path = $paths[$path];
        }

        return $validated;
    }
}
