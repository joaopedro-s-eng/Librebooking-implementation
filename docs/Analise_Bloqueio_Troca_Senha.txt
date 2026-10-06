================================================================================
ANÁLISE COMPLETA: BLOQUEIO DA TROCA DE SENHA NO LIBREBOOKING
================================================================================
Data: 01/10/2026
Objetivo: Analisar como desativar ou restringir a funcionalidade de troca de 
          senha para usuários comuns no LibreBooking, comparando abordagens
          (ocultar aba vs. manter aba com mensagem informativa) e explicando
          a arquitetura interna para que você mesmo possa implementar.

--------------------------------------------------------------------------------
1. COMO A TROCA DE SENHA FUNCIONA NO LIBREBOOKING (ARQUITETURA)
--------------------------------------------------------------------------------
A funcionalidade de troca de senha é dividida em 5 camadas:

1. Rota Web (Entry point):
   Arquivo: `Web/password.php`
   Recebe a requisição HTTP do navegador e instancia a página `PasswordPage`.

2. Página e Renderização (View Controller):
   Arquivo: `Pages/PasswordPage.php`
   Configura os parâmetros da view, injeta o template e calcula se a troca de 
   senha é permitida através da variável `AllowPasswordChange`.

3. Lógica de Negócio (Presenter):
   Arquivo: `Presenters/PasswordPresenter.php`
   Valida a senha atual, checa complexidade e confirmação, criptografa a nova 
   senha e atualiza no banco de dados via `UserRepository`.

4. Template da Tela (Smarty):
   Arquivo: `tpl/MyAccount/password.tpl`
   Monta a interface visual da página de troca de senha.

5. Menu de Navegação Global (Cabeçalho):
   Arquivo: `tpl/globalheader.tpl`
   Renderiza o dropdown "Minha Conta" (MyAccount) que contém o link "Trocar Senha".

--------------------------------------------------------------------------------
2. DESCOBERTA CRÍTICA: O LIBREBOOKING JÁ POSSUI ESSA LÓGICA PRONTA!
--------------------------------------------------------------------------------
Ao analisar o arquivo `tpl/MyAccount/password.tpl` (linhas 5 a 10), nota-se que 
o LibreBooking JÁ foi desenhado para suportar o bloqueio com mensagem:

```smarty
{if !$AllowPasswordChange}
    <div class="alert alert-danger text-center">
        <i class="bi bi-exclamation-triangle-fill fs-5"></i> {translate key=PasswordControlledExternallyError}
    </div>
{else}
    <form id="password-reset-form" ...>
       <!-- Campos de senha aqui -->
    </form>
{/if}
```

Isso significa que:
- Quando a variável `$AllowPasswordChange` for `false`, o sistema automaticamente 
  NÃO EXIBE nenhum campo de senha e mostra uma mensagem de aviso ao usuário.
- Esse é o comportamento nativo quando o LibreBooking se integra a servidores 
  externos (LDAP, SAML, Keycloak), pois a senha não deve ser trocada no LibreBooking.

--------------------------------------------------------------------------------
3. COMPARAÇÃO DAS ABORDAGENS: QUAL É A MELHOR?
--------------------------------------------------------------------------------

OPÇÃO 1: Manter a aba no menu, mas exibir mensagem informativa
- Como o usuário vê: O usuário vê a opção "Trocar Senha" no menu. Ao clicar, a 
  página abre sem nenhum formulário/input, exibindo um card ou alerta elegante:
  "A alteração de senha local está desativada. As credenciais são gerenciadas 
   pela administração / serviço externo."
- Prós:
  - Não quebra a navegação esperada de um painel de usuário.
  - O usuário entende imediatamente por que não pode trocar a senha (evita 
    abertura de chamados com dúvidas do tipo "onde foi parar o botão de senha?").
  - Utiliza o padrão nativo do próprio LibreBooking (`{if !$AllowPasswordChange}`).
- Contras:
  - A aba ainda ocupa espaço no menu.

OPÇÃO 2: Excluir/Ocultar a aba do menu de navegação
- Como o usuário vê: No dropdown "Minha Conta", o item "Trocar Senha" simplesmente 
  não existe mais. Ficam apenas "Perfil", "Preferências de Notificação", etc.
- Prós:
  - Interface mais limpa e minimalista.
  - Elimina qualquer distração para o usuário comum.
- Contras:
  - Se você só ocultar o menu, usuários que conhecem a URL ainda conseguirão 
    digitar `/Web/password.php` diretamente no navegador e acessar o formulário, 
    a menos que você também bloqueie o arquivo `Web/password.php`.

OPÇÃO 3 (Recomendada - A mais segura e profissional):
- Ocultar o link no menu para limpar o visual (`globalheader.tpl`).
- E, ao mesmo tempo, fixar `AllowPasswordChange = false` ou redirecionar caso 
  alguém tente digitar a URL diretamente no navegador.

--------------------------------------------------------------------------------
4. COMO IMPLEMENTAR CADA OPÇÃO (PASSO A PASSO TEÓRICO)
--------------------------------------------------------------------------------

=== CENÁRIO A: Manter a aba e exibir apenas mensagem explicativa ===

1. No arquivo `Pages/PasswordPage.php`:
   Localize o método `SetAllowedActions` (linha ~64):
   Original:
   ```php
   public function SetAllowedActions($authenticationOptions)
   {
       $allowPasswordChange = !Configuration::Instance()->GetKey(ConfigKeys::PASSWORD_DISABLE_RESET, new BooleanConverter());
       $this->Set('AllowPasswordChange', $authenticationOptions->AllowPasswordChange() && $allowPasswordChange);
   }
   ```
   Alteração: Fixe o valor para `false`:
   ```php
   public function SetAllowedActions($authenticationOptions)
   {
       $this->Set('AllowPasswordChange', false);
   }
   ```

2. No arquivo `tpl/MyAccount/password.tpl`:
   Personalize a mensagem que o usuário verá no lugar do erro padrão:
   Original:
   ```smarty
   {if !$AllowPasswordChange}
       <div class="alert alert-danger text-center">
           <i class="bi bi-exclamation-triangle-fill fs-5"></i> {translate key=PasswordControlledExternallyError}
       </div>
   {else}
   ```
   Personalização sugerida (alerta informativo azul ou amarelo):
   ```smarty
   {if !$AllowPasswordChange}
       <div class="alert alert-info text-center col-12 col-sm-8 mx-auto mt-4 p-4 shadow-sm">
           <i class="bi bi-info-circle-fill fs-3 d-block mb-2 text-primary"></i>
           <h4 class="alert-heading">Alteração de Senha Desativada</h4>
           <p class="mb-0">A troca de senha pelo sistema não está disponível. Caso precise alterar ou redefinir sua senha, entre em contato com a administração.</p>
       </div>
   {else}
   ```

3. Proteção no Backend (`Presenters/PasswordPresenter.php`):
   Para impedir que alguém envie um POST manual via script ou DevTools:
   No método `PageLoad()`:
   ```php
   if ($this->page->ResettingPassword()) {
       // Se o recurso está desativado, interrompa a execução imediatamente
       return;
   }
   ```

--------------------------------------------------------------------------------
=== CENÁRIO B: Remover a aba do menu de navegação ===

1. No arquivo `tpl/globalheader.tpl`:
   Localize a linha ~160:
   ```html
   <li id="navPassword"><a class="dropdown-item" href="{$Path}{Pages::PASSWORD}">{translate key="ChangePassword"}</a></li>
   ```
   Comente a linha em formato HTML/Smarty:
   ```html
   <!-- <li id="navPassword"><a class="dropdown-item" href="{$Path}{Pages::PASSWORD}">{translate key="ChangePassword"}</a></li> -->
   ```

2. No arquivo `Web/password.php`:
   Para evitar acesso direto via URL:
   ```php
   <?php
   define('ROOT_DIR', '../');
   // Redireciona qualquer tentativa de acesso direto de volta ao Dashboard
   header('Location: ' . ROOT_DIR . 'Web/dashboard.php');
   exit;
   ```

--------------------------------------------------------------------------------
=== CENÁRIO C: Via Configuração do LibreBooking (Sem mexer em código PHP) ===

O LibreBooking possui uma variável nativa de configuração:
- Chave interna: `password.disable.reset` (ou variável de ambiente `LB_PASSWORD_DISABLE_RESET`)
- Se você definir no arquivo `docker-compose.yml` (ou no `lb.env`):
  `LB_PASSWORD_DISABLE_RESET=true`
- O LibreBooking desativa automaticamente o link de "Esqueci minha senha" na tela 
  de login e faz o `PasswordPage.php` setar `$AllowPasswordChange = false`.

--------------------------------------------------------------------------------
5. FLUXO DE EXECUÇÃO NO DOCKER QUANDO VOCÊ FIZER A ALTERAÇÃO
--------------------------------------------------------------------------------
Sempre que você editar qualquer um desses arquivos no seu Linux:
1. Copie o arquivo alterado para o container:
   $ docker cp <caminho_local_do_arquivo> librebooking-app-1:<caminho_no_container>
2. Limpe o cache de templates do Smarty no container:
   $ docker exec librebooking-app-1 rm -rf /var/www/html/tpl_c/*
3. Recarregue a página no navegador com `Ctrl + Shift + R`.
================================================================================
