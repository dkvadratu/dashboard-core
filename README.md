# Laravel Dashboard

Versija: **v1.0.0**. [Pakeitimų istorija](CHANGELOG.md).

Viešas `dkvadratu/dashboard-core` Composer paketas Laravel administravimo projektams. Kiekviena aplikacija turi savo repozitoriją, konfigūraciją ir `composer.lock`; bendras dashboard kodas atnaujinamas per Composer.

Reikalavimai: PHP 8.5+, Laravel 13, Filament 5.5+. Automatinis diegiklis skirtas nepakeistam `laravel/laravel` 13.11.0 skeleton. Paketas diegiamas iš GitHub per HTTPS, be SSH rakto. Viešas kodas savaime nekeičia `composer.json` nurodytos `proprietary` licencijos.

## 1. Pagrindinės funkcijos

| Funkcija | Elgesys |
| --- | --- |
| `/adm` | Filament administracija, el. pašto patvirtinimas ir privaloma 2FA: autentifikatoriaus programėlė su recovery kodais arba el. pašto kodas |
| IP ribojimas | `DASHBOARD_ALLOWED_IPS`, palaiko CIDR; neleistinam IP grąžina 404 |
| `/adm/queued-jobs` | Database queue laukiantys ir rezervuoti darbai; kitiems driveriams rodomas nepalaikymo pranešimas |
| `/adm/api-request-logs` | Įeinančių API ir HTTP užklausų žurnalas |
| `/adm/logs` | Aplikacijos logai per Filament Log Viewer |
| README | Sidebar apačios `v{APP_VERSION} · README` nuoroda į aplikacijos README/CHANGELOG |
| Cache valdymas | Patvirtinimo reikalaujantis aplikacijos talpyklos valymas per `Cache::flush()` |
| `/uupp` | Laravel health check |
| `/api/status` | Paprastas API statuso pavyzdys |

MCP serveris, „MCP tokens“ puslapis, Sanctum migracijos, URL nukreipimai ir Boost paruošimas diegiami atskirai. Sentry priklausomybė ir klaidų integracija paliktos suderinamumui; siuntimui reikia sukonfigūruoto DSN.

## 2. Naujo projekto diegimas

Paketo GitHub repozitorijos šaknyje turi būti `composer.json` ir paskelbtas suderinamas `v1.x` tag. Tiesioginis HTTPS VCS veikia ir nepaskelbus paketo Packagist.

```bash
composer create-project laravel/laravel mano-dashboard 13.11.0
cd mano-dashboard
composer config repositories.dashboard-core vcs https://github.com/dkvadratu/dashboard-core.git
composer require 'dkvadratu/dashboard-core:^1.0'
php artisan dashboard:install --fresh --no-interaction
```

Diegiklis paruošia `User` su MFA, `is_admin` migraciją, panel provider, bootstrap, cache ir dashboard config, API statuso pavyzdį bei `.env` nustatymus. MCP failų ir migracijų nekuria. Originalius User/bootstrap/cache failus patikrina ir išsaugo `*.dashboard-backup` kopijas. Esamų README/CHANGELOG neperrašo.

Pakartotinai vykdant su `.dashboard-installed` žyma aplikacijos failai nekeičiami. Pritaikytame projekte `stubs/` integruok rankomis; diegiklis sustoja aptikęs kitokį skeleton ar konfliktuojančius failus.

1. `.env` nustatyk `APP_NAME`, `APP_URL`, DB, `MAIL_*`, `DASHBOARD_ALLOWED_IPS` ir `APP_VERSION`. Numatyti IP – `127.0.0.1,::1`. Jei reikia Sentry, nustatyk `SENTRY_LARAVEL_DSN`.
2. Paleisk migracijas, paruošk assets ir sukurk administratorių:

```bash
php artisan migrate --no-interaction
php artisan filament:assets --no-interaction
php artisan make:filament-user
php artisan dashboard:admin tavo@email.lt
```

3. Į aplikacijos `composer.json` esamą `scripts.post-autoload-dump` masyvą pridėk `"@php artisan filament:upgrade"`, išsaugodamas kitas komandas.
4. Atidaryk `/adm`, patvirtink el. paštą ir nustatyk 2FA. Su `MAIL_MAILER=log` laiškus rasi `storage/logs/laravel.log`; produkcijai sukonfigūruok mail tiekėją. 2FA privaloma nepriklausomai nuo senos `dashboard.mfa_required` reikšmės.
5. Commitink `composer.lock`, aplikacijos failus ir `.dashboard-installed`. `.env`, `vendor/` ir atsarginių kopijų necommitink.

Naujo vartotojo `is_admin=false`. `dashboard:admin email` suteikia prieigą, `dashboard:admin email --revoke` ją atima. MCP techninėms paskyroms administratoriaus teisės draudžiamos.

Panelės adresas nustatomas `app/Providers/Filament/AdminPanelProvider.php` → `->path('adm')`, health kelias – `bootstrap/app.php`. Panelės ID yra `admin`.

## 3. Žurnalai ir talpykla

Užklausų registravimo jungikliai išsaugoti; numatytai visi išjungti:

```dotenv
DASHBOARD_LOG_API=false
DASHBOARD_LOG_HTTP=false
DASHBOARD_LOGS_DETAILED=false
```

Config atitikmenys: `log_api_requests`, `log_http_requests`, `log_detailed`. API – `/api`, `/api/*` arba maršrutai su `api` middleware; HTTP – likusios įeinančios užklausos, įskaitant administraciją ir Livewire. Išeinantys `Http::` kvietimai neregistruojami.

Paprastas režimas saugo tipą, metodą, kelią, statusą ir IP. Detalus režimas prideda maskuotas request/response antraštes, JSON kūnus ir URL-encoded formas. `redacted_keys` papildyk pagal projektą. Query string, HTML, multipart ir failai nesaugomi; >64 KiB kūnai ar antraščių blokai praleidžiami. URL keliuose nelaikyk slaptų tokenų. Įrašai saugomi `log_retention_days` (numatyta 14 dienų); `dashboard:prune-logs` suplanuotas kasdien.

Queue peržiūra naudoja aktyvią `QUEUE_CONNECTION`: `database` darbai rodomi, Redis ir kitiems driveriams rodomas nepalaikymo pranešimas.

Cache veiksmas kviečia `Cache::flush()`, todėl numatytą cache store skirk tik šiai aplikacijai. Naujame projekte `config/cache.php` → `serializable_classes` leidžia tik `Illuminate\Support\Carbon` ir `Carbon\CarbonImmutable`. Tai išlaiko el. pašto 2FA galiojimo datų atkūrimą `file` / `database` talpyklose. Esamame projekte šį pakeitimą pritaikyk rankomis ir atkurk config cache.

## 4. Pasirenkami moduliai

### MCP serveris ir tokenai

Pagrindinis paketas nebereikalauja `laravel/mcp` ir `laravel/sanctum`: jie nurodyti Composer `suggest`, kuris jų automatiškai neįdiegia. Boost gali atsinešti MCP kaip dev priklausomybę; aplikacijos MCP serveriui abu paketai turi būti aplikacijos **runtime `require`**, kad liktų po `composer install --no-dev`.

```bash
composer require 'laravel/mcp:^1.1' 'laravel/sanctum:^4.0'
```

Prieš naudojant MCP, aplikacijos `app/Models/User.php`:

- Pridėk `use \Laravel\Sanctum\HasApiTokens;` klasės viduje prie kitų traitų.
- Į `casts()` pridėk `'is_mcp_service_account' => 'boolean'`.
- `canAccessPanel()` turi atmesti technines paskyras: `&& ! ($this->is_mcp_service_account ?? false)`. Naujasis pagrindinio diegimo stubas šią apsaugą jau turi. Lauko neįtrauk į `$fillable`.

```bash
php artisan dashboard:install-mcp --no-interaction
php artisan migrate --no-interaction
php artisan optimize:clear --no-interaction
```

Diegiklis pirmiausia tikrina priklausomybes, sukuria `app/Mcp/`, `routes/ai.php`, trūkstamas Sanctum ir AI paskyros migracijas bei `.dashboard-mcp-installed`. User failo automatiškai neredaguoja. Konfliktuojantys failai be žymos stabdo diegimą: tuomet pavyzdžius integruok rankomis. Pakartotinis diegimas failų neperrašo. Commitink sugeneruotus failus, User pakeitimus ir žymą.

`dashboard.mcp_enabled` numatytoji reikšmė remiasi `.dashboard-mcp-installed` žyma. „MCP tokens“ puslapis registruojamas tik įjungus MCP ir turint abu paketus. Senas paskelbtas config be šio nustatymo taip pat atpažįsta žymą. Jei config turi aiškią `mcp_enabled=false` reikšmę, pakeisk ją į `true`; po diegimo atkurk config ir route cache. Vien paketų įdiegimas ar Boost paruošimas šio puslapio neįjungia.

Prisijungęs administratorius su 2FA atidaro **System → MCP tokens → Create token**, pasirenka teises ir galiojimą (30, 90 arba 365 dienos). Numatytai siūloma tik `users:read`. Tokenas parodomas vieną kartą, DB saugomas hash; **Revoke** jį panaikina. Tokenai priklauso atskirai ne administratoriaus paskyrai `dashboard-ai@example.invalid` (keičiama per `mcp_user_email`); esama paprasta paskyra neperimama.

MCP kliente naudok HTTP endpointą `https://tavo-projektas.lt/mcp/users` ir `Authorization: Bearer <token>`. Production naudok HTTPS. Serveris reikalauja `api`, `auth:sanctum`, `EnsureMcpToken`, dažnio ribojimo ir `mcp` teisės, kurią tokenų langas prideda automatiškai. OAuth / automatinė klientų registracija neįdiegta.

| Įrankis | Teisė | Rezultatas |
| --- | --- | --- |
| `list-users` | `users:read` | ID, vardas, el. paštas; `limit` 1–100, puslapiavimas per `after_id` / `next_after_id` |
| `create-user` | `users:create` | Paprastas nepatvirtintas vartotojas be admin teisių; pakvietimo laišką / slaptažodžio nustatymą įgyvendina aplikacija |

Savo serverį kurk pagal `app/Mcp/Servers/UsersServer.php` ir `app/Mcp/Tools/` pavyzdžius. Registruok `routes/ai.php` su tomis pačiomis apsaugomis, teises pridėk į `mcp_token_abilities`, tikrink jas kiekviename įrankyje. Šie failai priklauso aplikacijai ir Composer jų neperrašo.

`mcp_enabled=false` paslepia tokenų puslapį, bet neišjungia aplikacijos `routes/ai.php` endpointų. Norėdamas atsisakyti MCP, pirmiausia atšauk tokenus ir pašalink endpointus bei User `HasApiTokens` naudojimą; tik tada šalink nebereikalingas priklausomybes. Duomenų / migracijų automatiškai netriname.

### Boost ir kūrimo įrankiai

Boost paruošimas pagal nutylėjimą nepaleidžiamas. Įrankius diek aplikacijoje kaip dev priklausomybes:

```bash
composer config allow-plugins.pestphp/pest-plugin true
composer require --dev 'barryvdh/laravel-debugbar:^4.2' 'laravel/boost:^2.10' 'laravel/pint:^1.0' 'pestphp/pest:^4.0' 'pestphp/pest-plugin-laravel:^4.0' --with-all-dependencies
php artisan dashboard:boost --no-interaction
```

Arba naują pagrindinį diegimą paleisk su `--with-boost`, kai Boost jau įdiegtas. Senas `--without-boost` parametras tebepalaikomas. `dashboard:boost` paruošia arba atnaujina agentų guidelines, skills ir IDE MCP; tai atskira paskirtis nuo aplikacijos `/mcp/users`.

Numatytasis agentas – Codex, esami pasirinkimai išsaugomi; kitus rinkis per `php artisan boost:install`. WWTD skill originalas yra `resources/boost/skills/wwtd/SKILL.md`, aplikacijos išimtis – `.ai/skills/wwtd/SKILL.md`. Po atnaujinimo peržiūrėk `boost.json`, agentų konfigūraciją ir skills; sugeneruotų failų tiesiogiai neredaguok.

### Kiti moduliai ir rekomendacijos

- [URL nukreipimai, viešųjų puslapių cache, slapukų sutikimai ir modelių pokyčių istorija](docs/extensions.md). Nukreipimų diegimas: `php artisan dashboard:install-redirects`; po jo atlik migracijas ir išvalyk cache.
- [Aplikacijos nustatymų rekomendacijos](docs/application-tips.md): laiko juosta, valiuta, datos, griežti modeliai ir lokalė. Kodo pavyzdžius rodo `php artisan dashboard:code-tips`.

## 5. Esamų projektų atnaujinimas

**Prieš atnaujindamas projektą su MCP arba Sanctum `HasApiTokens`, įrašyk abu pasirenkamus paketus į aplikacijos runtime `require`:**

```bash
composer require 'laravel/mcp:^1.1' 'laravel/sanctum:^4.0' --no-update
```

Tai būtina, nes naujas `dashboard-core` jų nebeįtraukia tranzityviai. Vien `require-dev` esantis Boost production MCP priklausomybių neišsaugo. `.dashboard-mcp-installed` palik Git: ji išlaiko esamą MCP puslapį. Anksčiau MCP rankomis integruotame projekte be žymos nustatyk `dashboard.mcp_enabled=true`.

Jei naudojai seną SSH adresą, pakeisk jį aplikacijoje:

```bash
composer config repositories.dashboard-core vcs https://github.com/dkvadratu/dashboard-core.git
composer update dkvadratu/dashboard-core --with-dependencies
php artisan migrate --no-interaction
php artisan filament:assets --no-interaction
php artisan optimize:clear --no-interaction
php artisan test --compact
```

Jei naudoji Boost, kūrimo aplinkoje papildomai vykdyk `php artisan dashboard:boost --no-interaction`. Peržiūrėk ir commitink `composer.lock`, aplikacijos pataisas bei sugeneruotas instrukcijas.

`dashboard:install` nėra atnaujinimo komanda. Esami User/config/bootstrap/MCP failai ir migracijos priklauso aplikacijai; stubų pakeitimai jų neperrašo. `mfa_required=false` nuo šio pakeitimo nebeišjungia 2FA. Priklausomybių atskyrimą išleisk kaip nesuderinamą major leidimą, jei ankstesnė stabili versija jau naudojama: aplikacijos versijos apribojimą keisk sąmoningai pagal release pastabas. Naujo pirmo leidimo atveju galima pradėti nuo `v1.0.0`.

## 6. Production diegimas

Diek patikrintą aplikacijos commit ir jo `composer.lock`. Nustatyk `APP_DEBUG=false`, mail, leidžiamus IP ir, jei naudojama, Sentry. Viešo GitHub repo skaitymui per HTTPS SSH rakto nereikia; dažniems API kvietimams GitHub gali taikyti limitus.

```bash
composer install --no-dev --optimize-autoloader --no-interaction
php artisan migrate --force --no-interaction
php artisan filament:assets --no-interaction
php artisan optimize:clear --no-interaction
php artisan optimize --no-interaction
php artisan queue:restart --no-interaction
```

Queue workerį valdyk per process manager. `php artisan schedule:run` vykdyk kas minutę. Horizon/Octane perkrauk pagal projekto diegimo eigą. Patikimus reverse proxy nustatyk aplikacijoje.

## 7. Paketo leidimai

Bendrą kodą keisk pakete, DB pakeitimams pridėk naujas migracijas. Patikrink su Laravel aplikacija, release pastabose aprašyk rankinius pakeitimus ir paskelbk SemVer tag. Stabilų paketą naudojančiame projekte suderinami pakeitimai gaunami per Composer; naujam major reikia pakeisti aplikacijos apribojimą. Aplikacijų kodą, `.env`, DB ir `vendor/` laikyk atskirai.

Dokumentacija: [Composer suggest](https://getcomposer.org/doc/04-schema.md#suggest), [Laravel MCP](https://laravel.com/docs/13.x/mcp), [Sanctum](https://laravel.com/docs/13.x/sanctum), [Boost](https://laravel.com/docs/13.x/boost).
