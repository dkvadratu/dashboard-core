<?php

namespace Dkvadratu\DashboardCore\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\NewAccessToken;
use Laravel\Sanctum\Sanctum;

class McpTokens
{
    /** @return array<string, string> */
    public static function abilities(): array
    {
        return config('dashboard.mcp_token_abilities', ['users:read' => 'List users', 'users:create' => 'Create users']);
    }

    public static function query(): Builder
    {
        return Sanctum::$personalAccessTokenModel::query()
            ->whereHasMorph('tokenable', [User::class], fn (Builder $query) => $query->where('is_mcp_service_account', true));
    }

    /** @param array<int, string> $abilities */
    public function issue(string $name, array $abilities, int $days): NewAccessToken
    {
        Validator::make(compact('name', 'abilities', 'days'), [
            'name' => ['required', 'string', 'max:100'],
            'abilities' => ['required', 'array', 'min:1'],
            'abilities.*' => ['required', 'string', 'distinct', Rule::in(array_keys(self::abilities()))],
            'days' => ['required', 'integer', Rule::in([30, 90, 365])],
        ])->validate();

        return DB::transaction(function () use ($name, $abilities, $days): NewAccessToken {
            $user = User::unguarded(fn () => User::query()->createOrFirst(
                ['email' => config('dashboard.mcp_user_email', 'dashboard-ai@example.invalid')],
                ['name' => 'Dashboard AI', 'password' => Str::random(64), 'is_admin' => false, 'is_mcp_service_account' => true],
            ));
            if (! $user->is_mcp_service_account || $user->is_admin) {
                throw ValidationException::withMessages(['name' => 'The MCP account email belongs to a different user. Set dashboard.mcp_user_email to an unused email.']);
            }

            return $user->createToken($name, ['mcp', ...$abilities], now()->addDays($days));
        });
    }
}
