# Pakeitimų istorija

## v1.0.0 — 2026-11-24

Pirmas stabilus leidimas.

### Pagrindinis paketas

- Filament `/adm`, administravimo IP ribojimas, privaloma 2FA ir el. pašto patvirtinimas.
- Database queue, API / HTTP užklausų bei aplikacijos logų peržiūra, README / CHANGELOG puslapis ir `/uupp` health check.
- Laravel cache valdymas ir išsaugoti `DASHBOARD_LOG_API`, `DASHBOARD_LOG_HTTP`, `DASHBOARD_LOGS_DETAILED` nustatymai.
- Pagrindinis diegimas nebekuria MCP serverio, tokenų puslapio ar Sanctum migracijų. Boost paruošimas pasirenkamas per `--with-boost` arba atskirą komandą.

### Pasirenkamas MCP

- `laravel/mcp` ir `laravel/sanctum` perkelti iš `require` į `suggest`.
- `dashboard:install-mcp` prieš rašydamas failus tikrina pasirenkamas priklausomybes.
- Tokenų puslapis registruojamas tik įjungus MCP ir turint abi priklausomybes; ankstesnių diegimų žyma išlaiko jų įjungimą.

### Perėjimas iš ankstesnės nepažymėtos versijos

- Projektuose su MCP arba Sanctum `HasApiTokens` prieš atnaujinimą įrašyk `laravel/mcp:^1.1` ir `laravel/sanctum:^4.0` į aplikacijos runtime `require`.
- Išsaugok `.dashboard-mcp-installed`; rankinei integracijai be žymos nustatyk `dashboard.mcp_enabled=true`. Atkurk config ir route cache.
- 2FA privaloma ir tada, kai senas `dashboard.mfa_required=false`.
- Aplikacijos User, config, bootstrap ir MCP failai automatiškai neperrašomi. Pilna atnaujinimo eiga pateikta README.

### Dokumentacija

- Viešo repo diegimas per HTTPS be SSH rakto.
- Suvienodinta README struktūra; išsamūs papildomų integracijų ir aplikacijos nustatymų pavyzdžiai perkelti į `docs/`.
