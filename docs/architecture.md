# Arquitetura do Sistema — LibreBooking UFCA

> Documento destinado à aprovação de go-live.  
> Audiência: gestores, TI, comitê de aprovação.

---

## 1. Visão Geral

O **LibreBooking** é o sistema de agendamento de recursos da **Universidade Federal do Cariri (UFCA)**. Ele substitui o Booked Scheduler legado e é executado como uma aplicação **PHP 8.2+** sobre **MariaDB 10.6+**, com autenticação delegada ao **Keycloak** institucional via SAML 2.0 / OAuth 2.0.

```
┌────────────────────────────────────────────────────────────────────┐
│                         Rede Interna UFCA                          │
│                                                                    │
│  Navegador do Usuário                                              │
│        │  HTTPS                                                    │
│        ▼                                                           │
│  ┌───────────┐   PHP-FPM / Apache   ┌──────────────────────┐     │
│  │  Nginx /  │ ──────────────────► │  LibreBooking (PHP)   │     │
│  │  Proxy    │                      │  porta 8080 (container│     │
│  └───────────┘                      └──────────┬───────────┘     │
│                                                │ PDO/MySQL        │
│                              ┌─────────────────▼──────────────┐  │
│                              │  MariaDB 10.6  (container db)  │  │
│                              └────────────────────────────────┘  │
│                                                                    │
│  ┌─────────────────────────────────────────┐                      │
│  │  Keycloak 22  (container keycloak)      │                      │
│  │  Realm: Teste_Librebooking              │                      │
│  │  Porta: 8082                            │                      │
│  └─────────────────────────────────────────┘                      │
└────────────────────────────────────────────────────────────────────┘
```

---

## 2. Componentes e Responsabilidades

| Componente | Tecnologia | Responsabilidade |
|---|---|---|
| **app** | PHP 8.2 / Apache | Lógica de negócio, UI, REST API |
| **db** | MariaDB 10.6 | Persistência de reservas, usuários, recursos |
| **keycloak** | Keycloak 22 | Identity Provider (IdP): autenticação e SSO |
| **cron** | supercronic | Jobs agendados: lembretes, limpeza de dados, e-mails |
| **proxy** | Nginx (externo) | Terminação TLS, roteamento público |

Todos os serviços rodam em containers Docker gerenciados pelo `docker-compose.yml` em `librebooking-docker/`.

---

## 3. Fluxo de Autenticação (SAML 2.0 + Keycloak)

O LibreBooking não gerencia senhas próprias para o ambiente da UFCA. A identidade do usuário é verificada exclusivamente pelo **Keycloak** via **SAML 2.0**.

```
Usuário            LibreBooking             Keycloak (IdP)
   │                     │                       │
   │  1. Acessa /login   │                       │
   │────────────────────►│                       │
   │                     │  2. Redireciona para  │
   │                     │     SSO SAML          │
   │◄────────────────────│                       │
   │                     │                       │
   │  3. Credenciais     │                       │
   │─────────────────────────────────────────────►
   │                     │                       │
   │                     │  4. Assertion SAML    │
   │                     │◄──────────────────────│
   │                     │                       │
   │                     │  5. Sincroniza usuário│
   │                     │     no banco local    │
   │                     │  (nome, e-mail, grupos)│
   │                     │                       │
   │  6. Sessão criada   │                       │
   │◄────────────────────│                       │
   │                     │                       │
```

### Detalhes técnicos

- **Plugin ativo:** `plugins/Authentication/Saml/Saml.php`
- **Classe:** `Saml extends Authentication implements IAuthentication`
- **Configuração ativa em** [`config/config.php`](file:///home/csi/Documentos/librebooking/config/config.php#L237-L254):
  ```php
  'plugins' => ['Authentication' => 'Saml'],
  'saml' => [
      'idp'                    => 'http://keycloak.local:8082/realms/Teste_Librebooking',
      'sp_entity_id'           => 'librebooking',
      'single_sign_on_service' => '…/protocol/saml',
      'single_logout_service'  => '…/protocol/saml',
  ]
  ```
- Na autenticação, o plugin chama `Saml::Validate()` → conecta ao IdP → obtém o `SamlUser` → chama `Synchronize()`, que cria ou atualiza o registro do usuário no banco com nome, e-mail e grupos vindos do Keycloak.
- **Logout** é propagado de volta ao Keycloak via `Saml::Logout()` (SLO — Single Logout).
- Alteração de senha, nome e e-mail **é bloqueada no LibreBooking** quando o plugin SAML está ativo (gerenciado exclusivamente pelo Keycloak).

### Perfis de acesso (RBAC nativo)

| Perfil | Escopo |
|---|---|
| Application Administrator | Acesso total: configurações, agendas, recursos, usuários |
| Resource Administrator | Aprova/rejeita reservas dos recursos sob sua gestão |
| Group Administrator | Gerencia permissões e cotas de um grupo específico |
| Regular User | Consulta calendários e solicita reservas conforme regras |

---

## 4. Fluxo de Criação de Reserva

```
Usuário               Presenter (PHP)          Validation Layer           Banco (MariaDB)
   │                       │                          │                         │
   │  1. Preenche form     │                          │                         │
   │   e submete           │                          │                         │
   │──────────────────────►│                          │                         │
   │                       │  2. ReservationSavePresenter                       │
   │                       │     instancia ReservationSeries                    │
   │                       │                          │                         │
   │                       │  3. Executa regras de validação                    │
   │                       │──────────────────────────►                         │
   │                       │     ScheduleAvailabilityRule                       │
   │                       │     QuotaRule                                      │
   │                       │     BlackoutValidationRule                         │
   │                       │     TitleRequiredRule (se habilitado)              │
   │                       │◄──────────────────────────                         │
   │                       │                          │                         │
   │                       │  4a. Falha → retorna erros ao usuário              │
   │◄──────────────────────│                          │                         │
   │                       │                          │                         │
   │                       │  4b. Sucesso → persiste reserva                    │
   │                       │──────────────────────────────────────────────────► │
   │                       │     INSERT em reservations / reservation_series     │
   │                       │◄────────────────────────────────────────────────── │
   │                       │                          │                         │
   │                       │  5. Dispara notificações por e-mail (PHPMailer)    │
   │                       │     (para o reservante, admin de recurso, etc.)    │
   │                       │                          │                         │
   │  6. Confirmação       │                          │                         │
   │◄──────────────────────│                          │                         │
```

### Estados de uma reserva

```
[Solicitada] ──► [Aprovada] ──► [Check-in] ──► [Check-out]
      │                │
      └──► [Recusada]  └──► [Cancelada]
      │
      └──► [Lista de Espera]  (se recurso lotado e waitlist habilitada)
```

As transições são controladas pela classe [`ReservationAction`](file:///home/csi/Documentos/librebooking/lib/Application/Reservation/ReservationAction.php):  
`Create | Update | Delete | Approve | Checkin | Checkout | WaitList`

> [!NOTE]
> Recursos marcados com **"Requer aprovação"** ficam no estado *Solicitada* até que um **Resource Administrator** aprove ou rejeite. Recursos sem aprovação obrigatória transitam diretamente para *Aprovada*.

---

## 5. Conexão com o Banco de Dados

O acesso ao banco é centralizado via **Singleton** em [`DatabaseFactory`](file:///home/csi/Documentos/librebooking/lib/Database/DatabaseFactory.php):

```php
// lib/Database/DatabaseFactory.php
public static function GetDatabase() {
    // Lê config/config.php → seção 'database'
    $instance = new Database(
        new MySqlConnection($user, $password, $hostSpec, $dbName)
    );
}
```

A camada de acesso a dados usa **Command Objects** (padrão Command Pattern) com PDO, sem ORM externo. Exemplo de uso no fluxo de autenticação:

```php
$reader = ServiceLocator::GetDatabase()->Query(new GetVersionCommand());
```

### Parâmetros de conexão (produção)

Definidos em [`config/config.php`](file:///home/csi/Documentos/librebooking/config/config.php#L141-L156):

```php
'database' => [
    'type'     => 'mysql',
    'hostspec' => '127.0.0.1',   // hostname do container db
    'name'     => 'librebooking',
    'user'     => 'lb_user',
    'password' => '***',
]
```

> [!IMPORTANT]
> A senha de banco **nunca deve ser commitada**. Em produção, ela deve ser injetada via variável de ambiente ou arquivo `.env` fora do controle de versão.

---

## 6. Jobs Agendados (Cron)

O container `cron` executa o mesmo binário da aplicação com o agendador **supercronic**, via arquivo `librebooking-docker/crontab`:

| Job | Frequência típica | Função |
|---|---|---|
| `sendreminders.php` | A cada 5 min | E-mails de lembrete de reservas próximas |
| `deleteolddata.php` | Diário | Remove reservas/blackouts com mais de N anos |
| `sendnotifications.php` | A cada minuto | Notificações em fila |

---

## 7. Estrutura de Camadas (MVP + Domain)

```
Web/          → Entry points HTTP (index.php, páginas)
Presenters/   → Camada de controle (coordena UI ↔ Application)
lib/
  Application/
    Reservation/  → Regras de negócio de reservas
    Authentication/ → Autenticação e sessão
    Authorization/  → Permissões e RBAC
  Database/     → Fábrica de conexão e Command Objects
Domain/         → Entidades: ReservationSeries, Resource, User…
WebServices/    → REST API (JSON) para integrações externas
plugins/        → Extensões: SAML, LDAP, AD, etc.
config/         → config.php (ponto único de configuração)
```

---

## 8. Diagrama de Implantação (Docker)

```
Host UFCA
└── docker-compose (librebooking-docker/)
    ├── app      → librebooking/librebooking:develop  (porta 8081)
    ├── db       → linuxserver/mariadb:10.6.13
    ├── keycloak → quay.io/keycloak/keycloak:22.0.5   (porta 8082)
    └── cron     → librebooking/librebooking:develop  (supercronic)

Volumes persistentes:
    db_conf    → dados MariaDB
    app_img    → uploads de imagens
    app_res    → anexos de reservas
    keycloak_data → configuração do realm
```

---

## 9. Checklist de Go-Live

| Item | Status |
|---|---|
| Ambiente Docker sobe limpo | ✅ |
| Login via Keycloak funciona | ✅ |
| Reserva de sala ponta-a-ponta | ⬜ pendente validação final |
| E-mail de confirmação entregue | ⬜ configurar SMTP produção |
| Backup do banco configurado | ⬜ |
| Senhas de produção fora do repositório | ⬜ |
| Integração com usuários UFCA no Keycloak | ⬜ |
| Dados migrados do Booked legado | ⬜ |

---

*Documento gerado em 2026-09-25. Mantenha atualizado a cada mudança de infraestrutura ou integração.*
