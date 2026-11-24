<?php

namespace Dkvadratu\DashboardCore\Console;

use Illuminate\Console\Command;

class CodeTipsCommand extends Command
{
    protected $signature = 'dashboard:code-tips';

    protected $description = 'Show optional application configuration recommendations';

    public function handle(): int
    {
        $this->info('Optional application settings: adapt these examples to your project.');

        $this->line(<<<'TIPS'

Timezone — config/app.php and .env
    // config/app.php: replace the existing timezone value
    'timezone' => env('APP_TIMEZONE', 'UTC'),

    # .env and .env.example
    APP_TIMEZONE=UTC

Keep UTC for consistent storage and calculations; choose a local timezone if your
application requires it. Display times in the appropriate user's timezone.

Filament defaults — app/Providers/AppServiceProvider.php
    // Add imports at the top of the file:
    use Filament\Schemas\Schema;
    use Filament\Tables\Table;

    // Add to the existing boot() method:
    Table::configureUsing(fn (Table $table) => $table
        ->defaultCurrency('EUR')
        ->defaultDateTimeDisplayFormat('Y-m-d H:i')
        ->defaultNumberLocale('lt_LT'));
    Schema::configureUsing(fn (Schema $schema) => $schema
        ->defaultCurrency('EUR')
        ->defaultDateTimeDisplayFormat('Y-m-d H:i')
        ->defaultNumberLocale('lt_LT'));

These set table and schema display defaults. Choose the currency, format and
locale for the project; individual components can override them.

Application defaults — app/Providers/AppServiceProvider.php
    // Add these imports at the top of the file:
    use Illuminate\Database\Eloquent\Model;
    use Illuminate\Support\Carbon;
    use Illuminate\Support\Facades\DB;
    use Illuminate\Support\Number;

    // Add to the existing boot() method:
    Model::shouldBeStrict(! $this->app->isProduction());
    DB::prohibitDestructiveCommands($this->app->isProduction());
    Number::useLocale('lt_LT');

Strict models reveal lazy loading, discarded attributes and missing attributes
during development. The DB setting blocks destructive Artisan commands in
production, including migration rollback; it does not prevent arbitrary SQL.
Number locale controls Laravel number formatting; choose the project's locale.

Carbon overflow — use the explicit methods where needed:
    $date->subMonthNoOverflow();
    $date->subYearNoOverflow();

The following global alternative can be added to AppServiceProvider::boot(),
but Carbon deprecates these setters because they can affect other libraries:
    Carbon::useMonthsOverflow(false);
    Carbon::useYearsOverflow(false);
TIPS);

        return self::SUCCESS;
    }
}
