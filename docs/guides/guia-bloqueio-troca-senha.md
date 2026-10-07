# Guia Técnico: Bloqueio da Funcionalidade de Troca de Senha no LibreBooking

> **Público-alvo:** Desenvolvedores e Administradores de Infraestrutura da UFCA  
> **Objetivo:** Instruções para desativar a alteração e redefinição de senhas locais no LibreBooking em ambiente federado com Keycloak/SSO.

---

## 1. Visão Geral

No LibreBooking integrado à infraestrutura de identidade da **UFCA**, as credenciais são administradas unicamente pelo **Keycloak / LDAP institucional**. Permitir a troca de senha na aplicação geraria descompasso com o cadastro institucional ou falhas de login.

A desativação é executada em modelo de **defesa em profundidade**, englobando:
1. **Configuração de Ambiente:** Uso da flag nativa do LibreBooking.
2. **Interface e Menu:** Remoção do link no cabeçalho.
3. **Ponto de Entrada (Web Route):** Redirecionamento da URL legada.
4. **Camada de Apresentação:** Inativação de formulários com mensagens informativas.
5. **Backend (Presenter):** Interrupção de rotinas de redefinição de credencial.

---

## 2. As 5 Camadas de Arquitetura da Funcionalidade

```
1. Rota Web (Entry Point)
   └── Web/password.php
       │
2. Controlador da View (Page)
   └── Pages/PasswordPage.php (Define $AllowPasswordChange)
       │
3. Lógica de Apresentação e Negócio (Presenter)
   └── Presenters/PasswordPresenter.php (Valida e atualiza UserRepository)
       │
4. Template da Interface (Smarty)
   └── tpl/MyAccount/password.tpl (Renderiza formulário ou alerta de erro)
       │
5. Menu de Navegação Global
   └── tpl/globalheader.tpl (Exibe link "Trocar Senha" em "Minha Conta")
```

---

## 3. Abordagem Rápida via Variável de Ambiente (Sem Modificar Código)

O LibreBooking possui uma flag nativa desenvolvida especificamente para este propósito:
* Chave interna: `password.disable.reset`
* Variável de ambiente correspondente: `LB_PASSWORD_DISABLE_RESET`

### Como ativar no Docker Compose:
No arquivo `librebooking-docker/docker-compose.yml` (ou no arquivo `librebooking-docker/lb.env`):

```yaml
services:
  app:
    environment:
      - LB_PASSWORD_DISABLE_RESET=true
```

Ao definir essa variável como `true`:
* O link "Esqueci minha senha" na tela de login é automaticamente suprimido.
* O método `PasswordPage::SetAllowedActions()` atribui `AllowPasswordChange = false`.
* O template nativo passa a exibir o aviso de credencial controlada externamente.

---

## 4. Implementação Completa (Defesa em Profundidade)

Para garantir segurança rigorosa e eliminar caminhos órfãos de navegação, aplique as seguintes alterações pontuais no código:

### 1. Ocultar o item no menu de navegação (`tpl/globalheader.tpl`)
Localize a linha com id `navPassword` (linha ~160) e comente o elemento:

```html
<!-- <li id="navPassword"><a class="dropdown-item" href="{$Path}{Pages::PASSWORD}">{translate key="ChangePassword"}</a></li> -->
```

### 2. Bloquear acesso direto à rota por URL (`Web/password.php`)
Adicione uma diretiva de redirecionamento imediato no topo do arquivo para encaminhar requisições ao painel principal:

```php
<?php
define('ROOT_DIR', '../');

// Redireciona qualquer tentativa de acesso direto de volta ao Dashboard institucional
header('Location: ' . ROOT_DIR . 'Web/dashboard.php');
exit;
```

### 3. Garantir mensagem explicativa no template caso a rota seja chamada (`tpl/MyAccount/password.tpl`)
O template nativo já possui a verificação `{if !$AllowPasswordChange}`. É possível estilizá-la com um alerta Bootstrap informativo amigável:

```smarty
{if !$AllowPasswordChange}
    <div class="alert alert-info text-center col-12 col-sm-8 mx-auto mt-4 p-4 shadow-sm">
        <i class="bi bi-info-circle-fill fs-3 d-block mb-2 text-primary"></i>
        <h4 class="alert-heading">Alteração de Senha Desativada</h4>
        <p class="mb-0">
            Suas credenciais são gerenciadas pelo sistema central de identificação da UFCA (Keycloak). 
            Para alterar sua senha, utilize o portal institucional da universidade.
        </p>
    </div>
{else}
    <!-- Formulário padrão -->
{/if}
```

### 4. Proteção na Camada de Negócio (`Presenters/PasswordPresenter.php`)
No método `PageLoad()`:

```php
if ($this->page->ResettingPassword()) {
    // Interrompe imediatamente sem persistir qualquer alteração de hash
    return;
}
```

---

## 5. Aplicação no Container Docker e Validação

Execute no terminal:

```bash
# 1. Copiar os arquivos modificados para o container
docker cp tpl/globalheader.tpl librebooking-app-1:/var/www/html/tpl/globalheader.tpl
docker cp Web/password.php librebooking-app-1:/var/www/html/Web/password.php
docker cp Presenters/PasswordPresenter.php librebooking-app-1:/var/www/html/Presenters/PasswordPresenter.php

# 2. Limpar o cache compilado do Smarty
docker exec librebooking-app-1 rm -rf /var/www/html/tpl_c/*
```

### Checklist de Validação:
- [ ] No menu superior "Minha Conta", confirme que o link "Trocar Senha" não aparece mais.
- [ ] Digite manualmente na barra de endereços do navegador `http://localhost:8080/Web/password.php` e certifique-se de que o sistema redireciona automaticamente para o dashboard (`/Web/dashboard.php`).
- [ ] Acesse a tela de login deslogado e verifique se o link "Esqueci a senha" não está visível.
