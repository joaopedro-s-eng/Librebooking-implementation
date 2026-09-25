# Referência de Configuração — LibreBooking UFCA

> **Audiência:** administradores de sistema na instalação, atualização ou diagnóstico.  
> Este documento **não faz parte da explicação do sistema** para aprovação de go-live.  
> Para entender o fluxo geral, consulte [`docs/architecture.md`](./architecture.md).

---

## Arquivo principal: `config/config.php`

Localização: [`config/config.php`](file:///home/csi/Documentos/librebooking/config/config.php)

O arquivo retorna um array associativo com a chave `'settings'`. Todas as seções estão descritas abaixo.

> [!IMPORTANT]
> Nunca commite senhas ou secrets no repositório. Use variáveis de ambiente ou um arquivo `.env` fora do controle de versão para injetar os valores sensíveis em produção.

---

## Seções de configuração

### Aplicação

```php
'app.title'     => 'LibreBooking',   // nome exibido na UI
'app.debug'     => false,            // true habilita display_errors (somente dev)
'admin.email'   => 'admin@example.com',
'admin.email.name' => 'LB Administrator',
'company.name'  => '',
'company.url'   => '',
```

---

### Idioma e Fuso Horário

```php
'default.timezone' => 'America/Fortaleza',   // fuso da UFCA
'default.language' => 'pt_br',
'enabled.languages' => '',   // vazio = todos disponíveis; ex: 'pt_br,en_us'
```

---

### Frontend

```php
'script.url'          => '',       // URL pública do diretório Web/; deixar vazio para auto-detect
'install.password'    => '',       // senha do wizard Web/install/ (deixar vazio em produção)
'cache.templates'     => true,     // recomendado em produção
'use.local.js.libs'   => true,     // assets servidos localmente (sem CDN)
'inactivity.timeout'  => 30,       // minutos até logout por inatividade
'home.url'            => '',
'default.homepage'    => 1,        // 1=Dashboard 2=Agenda 3=Meu Calendário 4=Calendário de Recursos
'default.page.size'   => 50,
'css.theme'           => 'default',// temas: default, dimgray, dark_red, dark_green, french_blue…
'name.format'         => '{first} {last}',
```

---

### Banco de Dados

```php
'database' => [
    'type'     => 'mysql',       // único tipo suportado
    'hostspec' => '127.0.0.1',  // hostname/IP do MariaDB
    'name'     => 'librebooking',
    'user'     => 'lb_user',
    'password' => '***',         // ⚠ não commitar
],
```

**Requisitos mínimos:** MySQL 8.0+ ou MariaDB 10.6+.

---

### E-mail (PHPMailer)

```php
'phpmailer' => [
    'mailer'      => 'smtp',        // mail | smtp | sendmail
    'smtp.host'   => '',
    'smtp.port'   => 25,
    'smtp.secure' => '',            // tls | ssl
    'smtp.autotls'=> true,
    'smtp.auth'   => true,
    'smtp.username' => '',
    'smtp.password' => '',          // ⚠ não commitar
    'smtp.debug'  => false,
],

'email' => [
    'enabled'              => true,
    'default.from.address' => 'no-reply@ufca.edu.br',
    'default.from.name'    => 'LibreBooking UFCA',
],
```

---

### Logging

```php
'logging' => [
    'folder' => '/var/log/librebooking/log',  // deve ter permissão de escrita
    'level'  => 'error',   // none | error | debug
    'sql'    => false,      // true loga todas as queries (verbose)
],
```

---

### Autenticação e Plugin SAML (configuração UFCA)

A UFCA usa **dois mecanismos paralelos** de autenticação via Keycloak:

#### 4a. Plugin SAML (autenticação de página web)

```php
// Ativa o plugin SAML para login via browser
'plugins' => ['Authentication' => 'Saml'],

'saml' => [
    'idp'                    => 'http://localhost:8082/realms/Teste_Librebooking',
    'sp_entity_id'           => 'librebooking',
    'single_sign_on_service' => 'http://localhost:8082/realms/Teste_Librebooking/protocol/saml',
    'single_logout_service'  => 'http://localhost:8082/realms/Teste_Librebooking/protocol/saml',
    'attribute_mapping' => [
        'username'  => 'username',
        'email'     => 'email',
        'firstname' => 'firstName',
        'lastname'  => 'lastName',
    ],
],
```

#### 4b. OAuth 2.0 / Keycloak (botão de login alternativo)

```php
'authentication' => [
    'keycloak.login.enabled'  => true,
    'keycloak.url'            => 'http://localhost:8082',
    'keycloak.realm'          => 'Teste_Librebooking',
    'keycloak.client.id'      => 'librebooking',
    'keycloak.client.secret'  => '***',   // ⚠ não commitar
    'keycloak.client.uri'     => '/Web/keycloak-auth.php',
    'logout.url'              => 'http://localhost:8082/realms/Teste_librebooking/protocol/openid-connect/logout',
],
```

> [!TIP]
> Em produção, substitua `localhost:8082` pelo hostname real do Keycloak institucional e utilize HTTPS.

#### Outros plugins disponíveis (não ativos na UFCA)

`ActiveDirectory | Apache | CAS | Drupal | Krb5 | Ldap | Mellon | Moodle | MoodleAdv | Shibboleth | WordPress`

---

### Reservas

```php
'reservation' => [
    'start.time.constraint'   => 'future',   // none | future | current
    'prevent.recurrence'      => false,
    'prevent.participation'   => false,
    'allow.wait.list'         => false,
    'updates.require.approval'=> false,
    'title.required'          => false,
    'description.required'    => false,
    'checkin.minutes.prior'   => 5,
    'checkin.admin.only'      => false,
    'checkout.admin.only'     => false,
    'reminders.enabled'       => false,
],
```

---

### Agendas (Schedule)

```php
'schedule' => [
    'auto.scroll.today'           => true,
    'show.week.numbers'           => false,
    'hide.blocked.periods'        => false,
    'show.inaccessible.resources' => true,
    'reservation.label'           => '{name}',
    'use.per.user.colors'         => false,
    'fast.reservation.load'       => false,
    'load.mobile.views'           => true,
],
```

---

### Cadastro de Usuários

```php
'registration' => [
    'allow.self.registration'  => true,
    'require.phone'            => false,
    'require.position'         => false,
    'require.organization'     => false,
    'captcha.enabled'          => true,
    'require.email.activation' => false,
    'notify.admin'             => false,
],
```

> [!NOTE]
> Com o plugin SAML ativo, o auto-cadastro (`allow.self.registration`) pode ser desabilitado: usuários são criados automaticamente na primeira autenticação via Keycloak.

---

### Privacidade

```php
'privacy' => [
    'view.schedules'           => true,   // público pode ver calendários
    'view.reservations'        => false,  // false = oculta detalhes de reservas para não logados
    'hide.user.details'        => false,
    'hide.reservation.details' => false,
    'allow.guest.reservations' => false,
],
```

---

### Segurança (HTTP Headers)

```php
'security' => [
    'headers'            => false,               // habilitar em produção com HTTPS
    'strict-transport'   => 'max-age=31536000',
    'x-frame'            => 'deny',
    'x-content-type'     => 'nosniff',
    'content-security-policy' => '',
],
```

> [!IMPORTANT]
> Habilite `security.headers => true` e configure o CSP antes do go-live em produção.

---

### Retenção de Dados (Cron)

```php
'cleanup' => [
    'years.old.data'           => 3,     // reservas com mais de 3 anos
    'delete.old.announcements' => false,
    'delete.old.blackouts'     => false,
    'delete.old.reservations'  => false,
],
```

Requer o job `deleteolddata.php` configurado no crontab.

---

### API REST

```php
'api' => [
    'enabled'              => false,   // habilitar apenas se integração externa for necessária
    'registration.allow.self' => false,
    'authentication.group' => '',     // grupo obrigatório para usar a API
    // Permissões granulares por endpoint:
    'reservations.ro.group' => '',
    'reservations.rw.group' => '',
    'resources.ro.group'    => '',
    'users.ro.group'        => '',
    // …
],
```

---

### ICS / Feeds de Calendário

```php
'ics' => [
    'enabled'          => true,
    'subscription.key' => '',    // chave secreta para feeds externos; gerar uma aleatória
    'future.days'      => 30,
    'past.days'        => 0,
],
```

---

### Uploads

```php
'uploads' => [
    'image.upload.directory'         => 'Web/uploads/images',
    'image.upload.url'               => 'uploads/images',
    'reservation.attachments.enabled'=> false,
    'reservation.attachment.path'    => 'uploads/reservation',
    'reservation.attachment.extensions' => 'csv,doc,docx,gif,jpeg,jpg,pdf,png,ppt,pptx,txt,xls,xlsx',
],
```

---

## Arquivo de ambiente Docker: `lb.env`

Localização: `librebooking-docker/lb.env`

As variáveis de ambiente injetadas no container `app` sobrescrevem as chaves de `config.php`. Usar este arquivo para segredos de produção (nunca commitar).

---

## Arquivo de configuração de referência (vanilla)

O upstream mantém `config/config.dist.php` com todos os valores padrão comentados. Consulte-o para entender o valor default de qualquer chave antes de alterar `config.php`.

Documentação oficial adicional disponível em `docs/source/`:

| Arquivo | Conteúdo |
|---|---|
| `CONFIGURATION.rst` | Visão geral do config.php |
| `BASIC-CONFIGURATION.rst` | Primeiros passos |
| `ADVANCED-CONFIGURATION.rst` | Cotas, temas, plugins |
| `LDAP-Authentication.rst` | Integração com LDAP/AD |
| `Oauth2-Configuration.rst` | Configuração OAuth 2.0 |
| `INSTALLATION.rst` | Instalação do zero |
| `API.rst` | Documentação da REST API |

---

*Referência gerada em 2026-09-25 a partir da leitura direta de `config/config.php` do repositório.*
