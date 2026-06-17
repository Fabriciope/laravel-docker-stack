# Lado Laravel (glue)

Esta stack Docker depende de alguns arquivos **dentro da aplicação Laravel** para que
**Log Viewer**, **backups** e os dashboards **Horizon/Pulse** funcionem. Aqui estão os arquivos
prontos para copiar e os trechos que você precisa **mesclar** nos arquivos que o seu projeto já
tem (não dá para sobrescrever esses).

> 📘 Visão geral e como subir: [README principal](../README.md) ·
> 🐳 Estrutura Docker: [`../docker/README.md`](../docker/README.md) ·
> 🛠️ Scripts: [`../scripts/README.md`](../scripts/README.md)

> Lembre-se de rodar o **find/replace do macro `{{project-name}}`** antes de subir (ver
> [README principal › Substituindo o macro](../README.md#substituindo-o-macro)). Os arquivos
> desta pasta **não** contêm o macro — só os de `docker/` e `scripts/`.

---

## 1. Pacotes (Composer)

| Pacote | Por que faz parte da stack |
|---|---|
| `laravel/horizon` | Workers + dashboard das filas sobre Redis. A stack roda um container `horizon` dedicado. |
| `laravel/pulse` | Dashboard de métricas de performance da aplicação. |
| `opcodesio/log-viewer` | Lê os logs (bind-montados em `storage/logs`) pelo navegador em `/log-viewer`. |
| `spatie/laravel-backup` | Dump agendado do banco; roda no container `scheduler` e grava no volume de backups. |

> [!IMPORTANT]
> Esses pacotes precisam estar no `composer.json`/`composer.lock` **antes de subir** — em
> produção o build da imagem roda `composer install` e **embute o `vendor/`**. E como o
> `phcomposer` depende de um container já rodando, o `composer require` é um passo de
> **bootstrap** (feito uma vez), de um destes jeitos:

**A) Com Composer no host** (mais simples):

```sh
composer require laravel/horizon laravel/pulse opcodesio/log-viewer spatie/laravel-backup
```

**B) Fluxo só-Docker** (sem Composer no host): suba o ambiente de **dev** e use o wrapper — o
código de dev é bind-mounted, então o `composer.json`/`composer.lock` atualizados ficam no seu host:

```sh
./scripts/deploy dev --first-deploy
./scripts/phcomposer require laravel/horizon laravel/pulse opcodesio/log-viewer spatie/laravel-backup
./scripts/deploy dev --skip-build   # recria horizon/scheduler já com os pacotes
```

> **Commite o `composer.lock` resultante.** Em produção ele precisa conter esses pacotes **antes**
> do `./scripts/deploy prod`, porque o build embute o `vendor/` a partir dele.

Depois, publique os configs/migrations de cada pacote — com o ambiente no ar via
`./scripts/phartisan`, ou localmente com `php artisan`: `horizon:install`, `vendor:publish` do
Pulse, e `migrate` (tabelas do Pulse). **Horizon exige Redis**, e o `HorizonServiceProvider` desta
pasta (seção 2) substitui o publicado pelo `horizon:install`.

---

## 2. Arquivos para copiar (estão nesta pasta)

Copie cada um para o caminho equivalente no seu projeto:

| Arquivo aqui | Destino no seu projeto | O que faz |
|---|---|---|
| `config/log-viewer.php` | `config/log-viewer.php` | Liga o Basic Auth dedicado (middleware abaixo) e varre `storage/logs/**/*.log`. |
| `config/backup.php` | `config/backup.php` | Dump **só do banco** para o disco `backups`; retenção; e-mail de alerta. |
| `app/Http/Middleware/LogViewerBasicAuth.php` | `app/Http/Middleware/` | Protege `/log-viewer` (fail-closed em produção). |
| `app/Providers/HorizonServiceProvider.php` | `app/Providers/` | Define o gate `viewHorizon` (edite os e-mails de admin). |

> O `HorizonServiceProvider` vem com o gate **genérico** (`app()->isLocal()` libera em dev; em
> produção, preencha a lista de e-mails). Se você rodou `horizon:install`, este arquivo
> **substitui** o provider publicado.

---

## 3. Trechos para mesclar (você já tem esses arquivos)

### `config/filesystems.php` — disco `backups`

O `spatie/laravel-backup` grava no disco `backups`, que em produção é um **bind mount** no host
(`storage/app/backups`), para os dumps sobreviverem a deploys.

```php
'backups' => [
    'driver' => 'local',
    'root'   => storage_path('app/backups'),
    'throw'  => false,
    'report' => false,
],
```

### `config/database.php` — dump consistente (recomendado)

Para dump InnoDB sem travar tabelas, adicione na conexão `mysql`:

```php
'dump' => [
    'useSingleTransaction' => true,
],
```

### `routes/console.php` — agendamento do backup

O container `scheduler` roda `schedule:run` a cada 60s; estas entradas disparam o backup diário.

```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('backup:clean')
    ->daily()->at('01:30')
    ->withoutOverlapping()
    ->onOneServer();

Schedule::command('backup:run --only-db')
    ->daily()->at('02:00')
    ->withoutOverlapping()
    ->onOneServer();
```

### `app/Providers/AppServiceProvider.php` — gate do Pulse

No `boot()`, defina quem acessa `/pulse` (não copiamos o `AppServiceProvider` porque o seu já
tem lógica própria):

```php
use Illuminate\Support\Facades\Gate;

Gate::define('viewPulse', function ($user = null) {
    // Local liberado; em produção, preencha os e-mails de admin.
    return app()->isLocal() || in_array(optional($user)->email, [
        // 'admin@example.com',
    ], true);
});
```

### `bootstrap/providers.php` — registrar o Horizon provider

```php
return [
    App\Providers\AppServiceProvider::class,
    App\Providers\HorizonServiceProvider::class, // <- adicionar
];
```

---

## 4. Variáveis de ambiente

As variáveis usadas por esses arquivos (`LOG_VIEWER_AUTH_USER/PASSWORD`,
`BACKUP_NOTIFICATION_EMAIL`, `BACKUP_ARCHIVE_PASSWORD`, drivers Redis etc.) estão em
[`../.env.stack.example`](../.env.stack.example) — veja
[README principal › Configuração](../README.md#configuração-env).

> O symlink `public/storage → storage/app/public` é criado pelo `./scripts/deploy <env> --first-deploy`,
> então você **não** precisa rodar `php artisan storage:link` manualmente.
