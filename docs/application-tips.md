# Aplikacijos nustatymai

Šie nustatymai yra rekomendacijos: pasirink juos pagal projektą, paketas jų automatiškai nekeičia. Kodo pavyzdžius ir vietas, kur juos įdėti, rodo `php artisan dashboard:code-tips`.

| Nustatymas | Rekomendacija |
| --- | --- |
| Laiko juosta | `config/app.php` naudok `env('APP_TIMEZONE', 'UTC')`, o `.env` nustatyk `APP_TIMEZONE=UTC`. Vietinę laiko juostą parink pagal projekto poreikius. |
| Filament valiuta ir datos | `Table` ir `Schema` konfigūracijoje nustatyk `defaultCurrency('EUR')` ir `defaultDateTimeDisplayFormat('Y-m-d H:i')`. Tai numatytasis atvaizdavimo formatas; datos įvesties laukus konfigūruok atskirai. |
| Carbon mėnesiai ir metai | Mėnesio pabaigoje naudok `subMonthNoOverflow()` arba `subYearNoOverflow()`, kai data neturi persikelti į kitą mėnesį. Globalūs `Carbon::useMonthsOverflow(false)` ir `Carbon::useYearsOverflow(false)` metodai yra pasenę ir keičia bendrą elgesį. |
| Griežti modeliai | `AppServiceProvider::boot()` įjunk `Model::shouldBeStrict(! app()->isProduction())`, kad kūrimo metu pastebėtum modelių klaidas. |
| Destruktyvios DB komandos | `AppServiceProvider::boot()` naudok `DB::prohibitDestructiveCommands(app()->isProduction())`. Tai blokuoja tam tikras Artisan komandas, bet ne savavališkus SQL pakeitimus. |
| Skaičių lokalė | `AppServiceProvider::boot()` nustatyk `Number::useLocale('lt_LT')`; Filament lentelėms ir schemoms galima naudoti `defaultNumberLocale('lt_LT')`. |

