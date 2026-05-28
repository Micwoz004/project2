# AGENTS.md instructions for /Users/michalwozniak/Documents/project-2

<!-- MANUAL ADDITIONS START -->
### Null-safety i kontrakty (obowiązkowe)
- Nie duplikuj defensywnych `null-checków` w każdej metodzie.
- `null-checki` rób na granicach systemu (np. controller, adapter, deserializacja, integracje zewnętrzne), gdzie dane faktycznie mogą być niepełne.
- W warstwach wewnętrznych (service/domain) opieraj się na kontrakcie wejścia: jeśli wcześniej jest walidacja, nie powtarzaj jej lokalnie.
- Dodatkowy `null-check` w środku metody jest dozwolony tylko gdy:
  - wynika z realnego przypadku legacy/historycznych danych, oraz
  - brak tego checka może powodować niekontrolowany wyjątek.
- Preferuj czytelny kod i pojedyncze miejsce walidacji zamiast wielu guardów.

### Importy i nazewnictwo typów (obowiązkowe)
- Nie używaj pełnych namespace/FQCN w kodzie (`pl....ClassName`).
- Zawsze używaj `import` i w kodzie operuj krótką nazwą klasy.
- Dotyczy to:
  - typów zwracanych,
  - typów pól/zmiennych,
  - odwołań do enumów i stałych (`PollVoteIntervalTypeEnum.ONCE`, nie pełny pakiet).

### Logging Standard (obowiązkowe)
Przy każdej nowej lub modyfikowanej operacji biznesowej (endpoint/service use-case):
1) `INFO` na starcie operacji: co robimy + kluczowe ID kontekstu.
2) `INFO` po sukcesie: wynik + finalny stan/ID.
3) `WARN` dla oczekiwanych odrzuceń biznesowych/walidacyjnych (bez stacktrace).
4) `ERROR` tylko dla nieoczekiwanych wyjątków, logowany raz na granicy warstwy (ze stacktrace).

Dodatkowe zasady precyzujące:
- Loguj kluczowe decyzje biznesowe, szczególnie miejsca `if -> return`, ale tylko jeśli to istotne zakończenie biznesowe/walidacyjne.
- W przypadku wczesnego zakończenia metody, które zmienia wynik biznesowy (np. zwrot pustych statystyk, odrzucenie walidacji, pominięcie operacji), log `INFO`/`WARN` jest obowiązkowy.
- Nie loguj technicznych/szumowych guardów, które nie niosą wartości diagnostycznej.
- Unikaj spamowania logów (`INFO` nie może zalewać hot-path).
- Nie używaj poziomu `DEBUG`.
- Nie loguj danych wrażliwych (PII, tokenów, pełnych payloadów, treści załączników).
- Używaj placeholderów SLF4J (`log.info("x={}, y={}", x, y)`), bez konkatenacji.
- Logi muszą być krótkie, jednoznaczne i filtrowalne po ID.

### Front mieszkańca jako SPA (obowiązkowe)
- Cała część mieszkańca/zwykłego zalogowanego użytkownika musi być utrzymana jako pełne SPA.
- Widoki mieszkańca (np. panel, moje projekty, zgłoszenie projektu, korekta projektu, konto, logowanie mieszkańca) renderuj przez publiczny SPA shell i stan przekazywany do frontendu, a nie przez osobne Blade views.
- Backendowe endpointy POST/PUT/PATCH mogą pozostać klasycznymi endpointami Laravel, ale ekran wejściowy i nawigacja mieszkańca mają pozostać w SPA.
- Panel administracyjny i zasoby Filament nie są częścią tej reguły.
<!-- MANUAL ADDITIONS END -->

# Liquibase scripts
- kiedy tworzysz skrypty migracyjne z liquibase zawsze w author=michal.wozniak i zawsze pamietaj o preCondition
- kazdy changeset musi dzialac dla bazy Oracle 11g, Postgres oraz H2 na testy

## Production SSH / AI-Ops

- For production server work, use `ssh ovh-agent` as `codex-agent`. Use `ssh ovh` / `ubuntu` only as an emergency administrative fallback, not for normal agent work.
- The `codex-agent` user has direct ACL-based read/write access and Git `safe.directory` entries for the current Git working trees under `/var/www`. Git operations inside those app directories may be run without `sudo`; read-only commands such as `git status`, `git diff`, `git log`, and `git fetch --dry-run` are allowed for diagnosis.
- Mutating Git operations on production, such as `git fetch`, `git stash`, `git checkout`, `git pull`, `git reset`, or `git clean`, require explicit user approval and must be scoped to the selected `/var/www/<app>` working tree. Do not print `git remote -v` or raw Git config, because remote URLs may contain deploy credentials.
- Production operations are exposed through `/usr/local/sbin/ai-ops` on the server. Start every investigation with read-only discovery: `sudo ai-ops apps`, `sudo ai-ops status all`, `sudo ai-ops metrics overview`, `sudo ai-ops metrics apps`, and `sudo ai-ops supervisor`.
- Other read-only commands available to agents are `sudo ai-ops status <app>`, `sudo ai-ops deploy-status <app>`, `sudo ai-ops metrics workers`, and `sudo ai-ops logs <app> laravel|horizon|queue|schedule <lines>`.
- Production changes require explicit user approval in the conversation.
- Allowed apply actions are `deploy <app> [--test]`, `stash-local-changes <app>`, `restart-runtime <app>`, `horizon-scale <app> default|registry-email|ksef 1..3`, `sentry-env-copy <source-app> <target-app> [target-app...]`, `nginx-profile off|balanced|uploads128|long-timeouts`, `php-fpm-reload`, `soketi-restart`, and `disable-broken-autodeploy`.
- For deploys, first run read-only discovery and `sudo ai-ops deploy-status <app>` only to identify the app, branch, upstream, dirty state and current health. If the user explicitly says to deploy a `/var/www/<app>` working tree, that instruction is approval to run that working tree's own deployment script directly: `cd /var/www/<app> && ./deploy.sh`. Do not stop the deploy because `ai-ops deploy` would refuse tracked local changes; the app's `deploy.sh` is the source of truth for deploy behavior.
- If a deploy target has tracked local changes, mention them before running `deploy.sh`, but continue with `deploy.sh` when the user's instruction was explicit. Do not run `git stash`, `git reset`, `git clean`, or modify those local changes unless the user explicitly asks for that separate operation.
- Before every production change, show the diagnosis, risk, validation steps, rollback path, and exact command to run. After every deploy script run, run `sudo ai-ops status <app>` or `sudo ai-ops status all` plus relevant `sudo ai-ops metrics ...` checks.
- Do not use raw `sudo`, `systemctl`, `docker`, `supervisorctl`, `nginx`, editors, `rm`, `chown`, `chmod`, or `tee` for production operations outside the `ai-ops` wrapper. Direct `./deploy.sh` execution inside an explicitly requested `/var/www/<app>` deployment target is allowed and preferred for deploys.
- Do not read, print, or copy secrets from production `.env` files. Use only allowlisted environment fields surfaced by `ai-ops`.
- If a new `/var/www` app is added, `codex-agent` needs the same per-repo ACL and `safe.directory` bootstrap before it can manage Git there; use the `ssh ovh` administrative fallback only for that setup step.
- If a new production operation is needed, propose or implement a new `ai-ops` wrapper action instead of running ad hoc root commands. Back up `/usr/local/sbin/ai-ops` to `/var/backups/ai-ops/`, keep the change minimal, run Python syntax validation, and verify with read-only `ssh ovh-agent 'sudo ai-ops apps'` before using any new action.

## Commit messages
- Format wiadomości commita: `[NAZWA_BRANCHA] {opis wykonanych zmian w 1-2 zdaniach}`
- `NAZWA_BRANCHA` to branch, na który commitujemy zmiany (najczęściej numer zadania z JIRA, np. `OATPO-2430`)
- Opis zmian zawsze piszemy w języku polskim

@/Users/michalwozniak/.codex/RTK.md
