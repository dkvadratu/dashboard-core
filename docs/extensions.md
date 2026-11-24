# Papildomos integracijos

## Jei projektas turi viešąją svetainę / public routes

Šiame skyriuje – pasirenkamas paketo nukreipimų modulis ir papildomų paketų rekomendacijos projektams su lankytojams skirtais puslapiais. Papildomi paketai automatiškai neįdiegiami.

### URL nukreipimų valdymas (pasirenkamas modulis)

Kai administratorius turi valdyti senų puslapių adresus, aplikacijos šaknyje vykdyk:

```bash
php artisan dashboard:install-redirects --no-interaction
php artisan migrate --no-interaction
php artisan optimize:clear --no-interaction
```

Filament meniu atsiras **Public web → Redirects** (`/adm/redirects`). Komanda sukuria `config/dashboard-redirects.php`, `dashboard_redirects` lentelės migraciją ir `.dashboard-redirects-installed` žymą. Šiuos failus commitink aplikacijoje. Pakartotinis diegimas esamų nustatymų neperrašo; aptikęs konfliktuojantį config ar migraciją be žymos, sustoja prieš rašymą.

Modelis `Dkvadratu\DashboardCore\Models\Redirect`, middleware ir Filament Resource lieka pakete ir atnaujinami per Composer. Į `bootstrap/app.php` middleware rankomis pridėti nereikia: paketas jį registruoja, kai `dashboard-redirects.enabled=true`. Neįdiegus modulio, nėra jo lentelės, Filament Resource ar middleware. Išjungti galima config reikšme `enabled=false`, po to atnaujinant konfigūracijos ir maršrutų talpyklas.

**Taisyklė:** `source=/senas`, `destination=/naujas`, `status_code=301` arba `302`, `enabled` ir neprivaloma `redirect_valid_until`. 301 skirtas nuolatiniam pakeitimui, 302 – laikinam. Naudok tikslius svetainės vidinius kelius su pradiniu `/`, be galinio `/` (išskyrus šakninį `/`). Wildcard, išoriniai URL, procentais koduoti keliai, query ir fragmentai taisyklėse nepalaikomi. Užklausos query parametrai į naują adresą neperkeliami.

- Taikoma GET/HEAD prieš maršruto vykdymą, todėl veikia ir senam URL, kuriam maršruto jau nebėra. Taisyklė turi pirmenybę ir prieš tebeegzistuojantį viešą maršrutą.
- Praleidžiamos Ajax, JSON, `X-Livewire` ir `Authorization` užklausos. Visada saugomi Filament panelių keliai, `/adm`, `/admin`, `/api`, `/mcp`, Livewire, `/uupp`, `/up`, Sanctum, Debugbar ir Boost keliai.
- Projekto kitokius API/MCP/health adresus bei privačių puslapių prefiksus įrašyk į `config/dashboard-redirects.php` → `except`. Ten jau yra prisijungimo, paskyros ir krepšelio keliai. Prefiksas apima ir jo subkelius; nekurk taisyklių privatiems puslapiams.
- Unikalūs šaltiniai ir ciklai tikrinami išsaugant; ciklų tikrinimas apima ir išjungtas / pasibaigusias taisykles. Middleware papildomai aptinka ciklą vykdymo metu ir tokios taisyklės netaiko.
- Taisyklės laikomos aplikacijos cache iki `cache_seconds` (numatyta 300 s). Modelio sukūrimas, redagavimas ir trynimas išvalo tik nukreipimų talpyklą, taip pat po DB transakcijos commit. Galiojimas tikrinamas per kiekvieną užklausą; po rollback neužsilaiko nepatvirtintų pakeitimų talpykla.
- `record_hits=false` pagal nutylėjimą. Įjungus, Filament rodo `hits`; skaičius didinamas po atsakymo tame pačiame PHP procese, be queue workerio. Tai nėra tikslus lankytojų skaitiklis: naršyklės ar CDN kešuojami nukreipimai gali nepasiekti aplikacijos. Laikinam galiojimui rinkis 302, nes 301 gali likti kliento talpykloje.

Keisdamas taisykles kode naudok modelio `save()` / `delete()`. Masiniai SQL pakeitimai ir `saveQuietly()` apeina validaciją bei cache įvykius; importus validuok ir po jų kviesk `Redirect::clearCache()`. Globalus middleware turi veikti prieš papildomai prijungtą viso atsakymo kešavimą.

**Atnaujinimas:** atnaujink `dkvadratu/dashboard-core` per Composer, pritaikyk release pastabose nurodytas config / migracijų pataisas, paleisk migracijas ir išvalyk cache pagal bendrą atnaujinimo eigą. `dashboard:install-redirects` pakartotinai vykdyti nereikia. Jau paskelbtos migracijos ir config priklauso aplikacijai; jų nauja paketo versija neperrašo.

### Viešųjų puslapių kešavimas

Kai viešųjų puslapių turinys kartojasi, rekomenduojama naudoti [spatie/laravel-responsecache](https://github.com/spatie/laravel-responsecache). Paketas išsaugo visą HTTP atsakymą, todėl pakartotinėms užklausoms puslapio nereikia generuoti iš naujo. Tinka, pavyzdžiui, naujienoms, informaciniams puslapiams ar viešam katalogui.

Pasirinkęs šį paketą, aplikacijoje vykdyk:

```bash
composer require spatie/laravel-responsecache
php artisan vendor:publish --tag=responsecache-config --no-interaction
```

Toliau pagal [oficialią dokumentaciją](https://spatie.be/docs/laravel-responsecache/v8/introduction):

1. `Spatie\ResponseCache\Middlewares\CacheResponse` prijunk tik prie pasirinktų viešųjų GET maršrutų ar jų grupės. Šiame starteryje jo nedėk į bendrą `web` middleware grupę.
2. Nustatyk turinio atnaujinimo dažniui tinkamą galiojimo laiką ir cache store. Numatyk talpyklos išvalymą po turinio pakeitimų administravimo dalyje.
3. Į kešuojamą grupę neįtrauk `/adm`, prisijungimo, MFA, `/mcp/users`, `/uupp`, krepšelio ar kitų personalizuotų puslapių. Viešas URL savaime nereiškia, kad atsakymas visiems vienodas: patikrink kalbos, sesijos ir slapukų sutikimo įtaką turiniui.
4. Patikrink, kad po turinio pakeitimo lankytojai matytų atnaujintą puslapį, o skirtingų lankytojų duomenys nesimaišytų.

### Slapukų valdymas ir BDAR

Kai projektui reikia slapukų ir naudotojo sutikimų valdymo, rekomenduojama naudoti [dkvadratu/Laravel-cookie-consent](https://github.com/dkvadratu/Laravel-cookie-consent). Diegimo ir konfigūravimo instrukcijas žiūrėk paketo README; konfigūraciją pritaikyk konkretaus projekto BDAR reikalavimams.

Tai pasirenkamas aplikacijos paketas: `dashboard-core` jo automatiškai neįdiegia. Slapukų sutikimų valdymas yra viena BDAR atitikties dalis, todėl vien paketo įdiegimas visos atitikties neužtikrina.

## Modelių pokyčių istorija (pasirenkama)

Kai keli administratoriai keičia duomenis ir reikia matyti **kas, ką ir kada pakeitė**, rekomenduojama naudoti [spatie/laravel-activitylog](https://github.com/spatie/laravel-activitylog). Žurnalas gali saugoti pakeitimo autorių, modelį, įvykį ir laukų reikšmes prieš / po pakeitimo. `dashboard-core` šios priklausomybės automatiškai neįdiegia.

Stebėjimą junk prie **keičiamo modelio**: `User` – paskyrų pokyčiams, `Order` – užsakymams ir t. t. Prisijungęs vartotojas yra pakeitimo autorius (`causer`); vien stebint `User`, kitų modelių pokyčiai nebus registruojami. Bendro bazinio modelio su visų laukų registravimu nereikia.

### 1. Įdiek aplikacijoje

Pavyzdys skirtas **Spatie v5, Laravel 13 ir Filament 5**. V4 turi kitus namespace, metodus ir pokyčių lauką; esamą v4 diegimą atnaujink pagal [oficialią atnaujinimo instrukciją](https://github.com/spatie/laravel-activitylog/blob/main/UPGRADING.md).

```bash
composer require 'spatie/laravel-activitylog:^5.1'
php artisan vendor:publish --provider='Spatie\Activitylog\ActivitylogServiceProvider' --tag=activitylog-migrations --no-interaction
php artisan vendor:publish --provider='Spatie\Activitylog\ActivitylogServiceProvider' --tag=activitylog-config --no-interaction
php artisan migrate --no-interaction
```

### 2. Prijunk prie modelio

Esamo `app/Models/User.php` viršuje pridėk importus:

```php
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
```

**Esamos `User` klasės viduje** pridėk traitą ir metodą, išsaugodamas jau esančius MFA, Sanctum ir kitus traitus bei metodus:

```php
use LogsActivity;

public function getActivitylogOptions(): LogOptions
{
    return LogOptions::defaults()
        ->useLogName('users')
        ->logOnly(['name', 'email', 'is_admin'])
        ->logOnlyDirty()
        ->dontLogEmptyChanges();
}
```

Tai registruos sukūrimą, pakeitimą ir ištrynimą; keičiant bus saugomi tik pasikeitę pasirinkti laukai. Kitam modeliui pakeisk `useLogName()` ir `logOnly()` sąrašą. Slaptažodžių, tokenų, MFA paslapčių ir recovery kodų reikšmių į jį netrauk. `$fillable` keisti nereikia.

### 3. Pridėk istorijos veiksmą Filament lentelėje

Pavyzdys skirtas tavo aplikacijos `UserResource` lentelei (jei jos dar nėra, sukurk ją savo projekte). Lentelę aprašančio failo viršuje pridėk:

```php
use App\Models\User;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Textarea;
use Spatie\Activitylog\Models\Activity;
```

Į **esamą** `$table->recordActions([...])` masyvą įdėk šį veiksmą, palikdamas jau turimus Edit / Delete veiksmus:

```php
Action::make('history')
    ->label('Pokyčių istorija')
    ->authorize(fn (): bool => (bool) Filament::auth()->user()?->is_admin)
    ->slideOver()
    ->modalHeading('Kas, ką ir kada pakeitė')
    ->modalDescription('Rodoma iki 50 naujausių šio įrašo įvykių.')
    ->modalSubmitAction(false)
    ->modalCancelActionLabel('Uždaryti')
    ->schema([
        Textarea::make('history')->label('Istorija')->rows(20)->readOnly(),
    ])
    ->fillForm(fn (User $record): array => [
        'history' => json_encode(
            $record->activitiesAsSubject()
                ->with('causer')
                ->orderByDesc('id')
                ->limit(50)
                ->get()
                ->map(fn (Activity $activity): array => [
                    'kada' => $activity->created_at
                        ->setTimezone(config('app.timezone'))->toDateTimeString(),
                    'kas' => $activity->causer?->name ?? 'Sistema / autorius nepasiekiamas',
                    'autoriaus_id' => $activity->causer_id,
                    'ivykis' => $activity->event,
                    'pries' => $activity->attribute_changes?->get('old', []),
                    'po' => $activity->attribute_changes?->get('attributes', []),
                ])->all(),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
        ),
    ]),
```

Veiksmas rodo tik pasirinkto įrašo istoriją ir leidžiamas administratoriams. Kitai lentelei pakeisk `User` tipą į jos modelį; prieigos taisyklę pritaikyk projekto policy / tenant riboms. Didesnei istorijai naudok atskirą puslapiuojamą Activity lentelę.

### 4. Patikrink ir nustatyk saugojimą

Prisijunk vienu administratoriumi, pakeisk kito vartotojo vardą ir atidaryk jo istoriją: matysi autorių, laiką, seną ir naują vardą. Tuščias išsaugojimas neturi sukurti naujo pokyčio įrašo. Istorija kaupiama tik nuo stebėjimo įjungimo.

Įprastoje autentifikuotoje užklausoje autorius nustatomas iš aktyvaus auth guard. Queue / CLI atveju nėra automatinio prisijungusio administratoriaus: jei veiksmas vykdomas jo vardu, perduok autoriaus ID ir vykdymo metu aiškiai nustatyk autorių, pvz., v5 `Activity::defaultCauser($admin, fn () => $order->update([...]))`. Bendra AI paskyra istorijoje bus matoma kaip AI paskyra, o ne konkretus žmogus.

`config/activitylog.php` nustatyk `clean_after_days`, o `routes/console.php` suplanuok `Schedule::command('activitylog:clean --force --no-interaction')->daily();` su `Illuminate\Support\Facades\Schedule` importu. Tai atskira istorija nuo HTTP/API žurnalo ir jo saugojimo nustatymų.

Tiesioginės SQL, masinės Eloquent `update` / `delete` operacijos ir įvykius nutildantys metodai nepaleidžia įprasto modelių stebėjimo. Tokiems veiksmams numatyk atskirą įrašą. Žr. [Laravel modelių įvykius](https://laravel.com/docs/13.x/eloquent#events).

