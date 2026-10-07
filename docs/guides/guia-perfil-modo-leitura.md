# Guia Técnico: Modo Somente Leitura do Perfil no LibreBooking

> **Público-alvo:** Desenvolvedores e Administradores de Sistemas da UFCA  
> **Objetivo:** Instruções práticas para configurar os dados cadastrais do perfil de usuário em modo somente leitura (texto estático), preservando as preferências individuais (Fuso Horário e Página Inicial).

---

## 1. Visão Geral e Arquitetura da Solução

No ambiente da **UFCA**, os dados de cadastro dos usuários (Nome, E-mail, Matrícula/Usuário, Telefone, Setor/Organização e Cargo) são gerenciados centralmente pelo **Keycloak / LDAP**. Portanto, os usuários não devem alterar essas informações dentro do LibreBooking.

No entanto, o usuário ainda deve conseguir personalizar suas preferências locais:
* **Página Inicial Padrão** (`DefaultPage`)
* **Fuso Horário** (`Timezone`)

Para isso, o LibreBooking é configurado para travar os campos cadastrais enquanto mantém o botão de salvamento ativo para as preferências.

```
┌────────────────────────────────────────────────────────┐
│                   Navegador do Usuário                 │
│                                                        │
│  [ Nome: João Silva (texto estático span)            ] │
│  [ E-mail: joao.silva@ufca.edu.br (texto estático)   ] │
│  [ Fuso Horário: [ America/Fortaleza  ▼ ] (editável) ] │
│  [ Página Inicial: [ Minha Agenda     ▼ ] (editável) ] │
│                                                        │
│               [ Botão Atualizar (Ativo) ]              │
└───────────────────────────┬────────────────────────────┘
                            │ POST (Salva apenas preferências)
                            ▼
           ProfilePresenter::UpdateProfile()
```

---

## 2. Diagnóstico Comum: Por que alterações locais não refletem de imediato?

Ao editar um arquivo `.tpl` ou `.php` localmente, é comum que a mudança não apareça no navegador. Isso ocorre por dois motivos:

### Motivo 1: O código local não está montado no container Docker
O serviço `app` no arquivo `librebooking-docker/docker-compose.yml` utiliza a imagem pré-construída `librebooking/librebooking:develop`. Por padrão, apenas os diretórios `/config` e uploads de imagens são compartilhados como volume. O código-fonte em execução roda internamente em `/var/www/html/`.

### Motivo 2: Cache de Templates do Smarty (`tpl_c`)
O motor de templates Smarty compila os arquivos `.tpl` em scripts PHP otimizados e os armazena em cache dentro de:
```bash
/var/www/html/tpl_c/
```
Se esse diretório não for limpo após a alteração, o Apache continuará servindo o template compilado antigo.

---

## 3. Estratégias de Desenvolvimento

### Opção A: Cópia Rápida (Para testes pontuais)
Copie o arquivo alterado para o container e remova o cache do Smarty:

```bash
# 1. Copiar arquivo para o container
docker cp Pages/ProfilePage.php librebooking-app-1:/var/www/html/Pages/ProfilePage.php
docker cp tpl/MyAccount/profile.tpl librebooking-app-1:/var/www/html/tpl/MyAccount/profile.tpl

# 2. Limpar o cache compilado
docker exec librebooking-app-1 rm -rf /var/www/html/tpl_c/*
```

Em seguida, limpe o cache do navegador pressionando `Ctrl + Shift + R` (ou `Ctrl + F5`).

### Opção B: Mapeamento de Volumes (Para ciclo ágil de desenvolvimento)
No arquivo `librebooking-docker/docker-compose.yml`, adicione o mapeamento dos diretórios locais na seção `services.app.volumes`:

```yaml
services:
  app:
    volumes:
      - ./config:/config
      - ../tpl:/var/www/html/tpl
      - ../Pages:/var/www/html/Pages
      - ../Presenters:/var/www/html/Presenters
      - ../plugins:/var/www/html/plugins
      - app_img:/var/www/html/Web/uploads/images
      - app_res:/var/www/html/Web/uploads/reservation
```

Reinicie os containers:
```bash
docker compose down && docker compose up -d
```

---

## 4. Passo a Passo da Implementação

### Passo 1: Forçar Campos em Modo Estático no Controlador
Abra o arquivo [`Pages/ProfilePage.php`](file:///home/csi/Documentos/librebooking/Pages/ProfilePage.php) e localize o método `SetAllowedActions($options)` (linhas ~225 a 235).

Substitua a chamada dinâmica às opções por literais `false`:

```php
public function SetAllowedActions($options)
{
    $this->Set('AllowEmailAddressChange', false);
    $this->Set('AllowNameChange', false);
    $this->Set('AllowOrganizationChange', false);
    $this->Set('AllowPhoneChange', false);
    $this->Set('AllowPositionChange', false);
    $this->Set('AllowUsernameChange', false);
}
```

> [!NOTE]
> Quando essas variáveis são definidas como `false`, o template `profile.tpl` automaticamente entra no bloco `{else}`, substituindo os campos de texto `<input type="text">` por tags `<span>{$Valor}</span>` e inputs ocultos de integridade.

---

### Passo 2: Preservar o Botão de Atualização no Template
Abra o arquivo [`tpl/MyAccount/profile.tpl`](file:///home/csi/Documentos/librebooking/tpl/MyAccount/profile.tpl).

Certifique-se de que o botão de submissão `btnUpdate` **não** foi removido:

```html
<div class="d-grid mt-3">
    <button type="submit" class="update btn btn-primary btn-block" name="{Actions::SAVE}" id="btnUpdate">
        {translate key='Update'}
    </button>
</div>
```

> [!IMPORTANT]
> **Não remova este botão.** A classe controladora `ProfilePresenter` necessita do evento `{Actions::SAVE}` no payload POST para salvar as preferências de Fuso Horário e Página Inicial do usuário.

---

### Passo 3: Adicionar Proteção de Backend no Presenter
Abra o arquivo [`Presenters/ProfilePresenter.php`](file:///home/csi/Documentos/librebooking/Presenters/ProfilePresenter.php) e localize o método `Update()` (linhas ~105):

Para impedir que usuários avançados forjem alterações enviando requisições HTTP manuais via DevTools ou cURL, certifique-se de que os dados imutáveis não sejam reatribuídos ao modelo:

```php
// Garante que apenas as preferências sejam persistidas caso as ações estejam bloqueadas
$user->SetTimezone($this->page->GetTimezone());
$user->SetDefaultHomepage($this->page->GetDefaultHomepage());
```

---

### Passo 4: Ocultar o Item "Alterar Senha" da Navegação
Abra o arquivo [`tpl/globalheader.tpl`](file:///home/csi/Documentos/librebooking/tpl/globalheader.tpl) e comente a linha correspondente a `navPassword`:

```html
<!-- <li id="navPassword"><a class="dropdown-item" href="{$Path}{Pages::PASSWORD}">{translate key="ChangePassword"}</a></li> -->
```

---

### Passo 5: Aplicar no Docker e Validar

Execute no terminal:

```bash
# 1. Copiar os arquivos editados para o container em execução
docker cp Pages/ProfilePage.php librebooking-app-1:/var/www/html/Pages/ProfilePage.php
docker cp Presenters/ProfilePresenter.php librebooking-app-1:/var/www/html/Presenters/ProfilePresenter.php
docker cp tpl/MyAccount/profile.tpl librebooking-app-1:/var/www/html/tpl/MyAccount/profile.tpl
docker cp tpl/globalheader.tpl librebooking-app-1:/var/www/html/tpl/globalheader.tpl

# 2. Limpar o cache Smarty compilado
docker exec librebooking-app-1 rm -rf /var/www/html/tpl_c/*
```

Acesse `http://localhost:8080/Web/profile.php` e recarregue com `Ctrl + Shift + R`:
1. Verifique se Nome, E-mail e demais dados aparecem como texto puro.
2. Modifique o Fuso Horário ou Página Inicial e clique em **Update**.
3. Confirme que a mensagem de sucesso é exibida e as preferências continuam salvas após novo refresh.
