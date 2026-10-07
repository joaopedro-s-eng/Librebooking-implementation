================================================================================
          GUIA COMPLETO: MODO SOMENTE LEITURA DO PERFIL (LIBREBOOKING)
================================================================================
Data: 30/09/2026
Destino: /home/csi/Documentos/Passosprofileread.txt

--------------------------------------------------------------------------------
PARTE 1: POR QUE A ALTERAÇÃO NO .TPL NÃO SURTIU EFEITO NA TELA?
--------------------------------------------------------------------------------
Você comentou o botão no template `.tpl` e atualizou a página, mas nada mudou. 
Isso acontece por 2 MOTIVOS CRÍTICOS na arquitetura atual:

1. O CÓDIGO DA SUA MÁQUINA NÃO ESTÁ MONTADO NO CONTAINER DOCKER:
   - No arquivo `librebooking-docker/docker-compose.yml`, o serviço `app` usa a 
     imagem pronta `librebooking/librebooking:develop`.
   - Os únicos diretórios compartilhados entre o seu computador e o container são:
       ./config:/config
       app_img:/var/www/html/Web/uploads/images
       app_res:/var/www/html/Web/uploads/reservation
   - Ou seja: a pasta `/home/csi/Documentos/librebooking/tpl` onde você editou fica
     no seu computador físico, enquanto o Apache está rodando os arquivos internos 
     do container em `/var/www/html/tpl/`!
   - Qualquer alteração feita no seu arquivo local NÃO reflete no container a menos 
     que você monte o volume ou copie o arquivo para dentro do container.

2. CACHE DO SMARTY (tpl_c):
   - O LibreBooking utiliza a engine de templates Smarty com `'cache.templates' => true`.
   - O Smarty compila os arquivos `.tpl` em scripts PHP e os salva na pasta:
       `/var/www/html/tpl_c/`
   - Se o template em cache não for apagado, o LibreBooking continuará exibindo 
     a versão antiga compilada.

COMO FAZER A ALTERAÇÃO APARECER DE FATO:

Opção A (Rápida - Copiar para o container e limpar cache):
  $ docker cp /home/csi/Documentos/librebooking/tpl/MyAccount/profile.tpl librebooking-app-1:/var/www/html/tpl/MyAccount/profile.tpl
  $ docker exec -it librebooking-app-1 rm -rf /var/www/html/tpl_c/*
  Em seguida, no navegador, pressione Ctrl + F5 (ou Ctrl + Shift + R) para limpar o cache da página.

Opção B (Definitiva - Mapear volumes no docker-compose.yml):
  No arquivo `librebooking-docker/docker-compose.yml`, na seção `services.app.volumes`, 
  adicione o mapeamento do código do host:
      volumes:
        - ./config:/config
        - ../tpl:/var/www/html/tpl
        - ../Pages:/var/www/html/Pages
        - ../Presenters:/var/www/html/Presenters
        - ../plugins:/var/www/html/plugins
        - app_img:/var/www/html/Web/uploads/images
        - app_res:/var/www/html/Web/uploads/reservation
  E reinicie:
  $ docker compose down && docker compose up -d


--------------------------------------------------------------------------------
PARTE 2: COMO COLOCAR 'FALSE' EM AllowNameChange E MÉTODOS SIMILARES
--------------------------------------------------------------------------------

DÚVIDA: "como eu coloco falso se é uma classe e está requisitando um parâmetro?"

Vamos entender o que acontece em `Pages/ProfilePage.php`:

O código original nas linhas 225 a 233 é:
```php
public function SetAllowedActions($options)
{
    $this->Set('AllowEmailAddressChange', $options->AllowEmailAddressChange());
    $this->Set('AllowNameChange', $options->AllowNameChange());
    $this->Set('AllowOrganizationChange', $options->AllowOrganizationChange());
    $this->Set('AllowPhoneChange', $options->AllowPhoneChange());
    $this->Set('AllowPositionChange', $options->AllowPositionChange());
    $this->Set('AllowUsernameChange', $options->AllowUsernameChange());
}
```

EXPLICAÇÃO DO FUNCIONAMENTO:
1. O que é `$this->Set('Chave', $valor)`:
   - É um método herdado de `SmartyPage`.
   - O primeiro argumento (ex: `'AllowNameChange'`) é o nome da variável que o 
     Smarty lerá no template: `{if $AllowNameChange} ... {else} ... {/if}`.
   - O segundo argumento é o VALOR atribuído a essa variável.

2. O que é `$options`:
   - É um objeto (instância de classe que implementa `IAuthenticationActionOptions`), 
     injetado pelo Presenter a partir do plugin de autenticação ativo.
   - Ao chamar `$options->AllowNameChange()`, o PHP executa esse método e retorna 
     um booleano (`true` ou `false`).

COMO ALTERAR PARA FALSE NA PRÁTICA:

Existem duas maneiras:

--- MANEIRA 1 (Direto em Pages/ProfilePage.php - Simples e Imediata) ---
Basta ignorar a chamada ao objeto `$options` e passar o booleano `false` diretamente 
como o segundo argumento de `$this->Set()`. O parâmetro `$options` continua na 
declaração da função apenas para manter a assinatura da interface, mas você fixa `false`:

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
Efeito disso:
- Quando o template `tpl/MyAccount/profile.tpl` executa:
  `{if $AllowNameChange}` -> avalia como FALSO!
  Ele cai no `{else}`:
  `<span>{$FirstName}</span>` e `<input type="hidden" ... />`
  Ou seja: o campo de input desaparece e o nome do usuário vira texto fixo!

--- MANEIRA 2 (Na classe do Plugin de Autenticação - Arquiteturalmente Limpo) ---
Se você estiver utilizando LDAP ou Keycloak (via plugin SAML/LDAP), esses plugins já 
possuem os métodos implementados.
Por exemplo, no arquivo:
`/home/csi/Documentos/librebooking/plugins/Authentication/Ldap/Ldap.php`
Linhas 218 a 250:
```php
public function AllowUsernameChange()
{
    return false;
}

public function AllowEmailAddressChange()
{
    return false;
}

public function AllowPasswordChange()
{
    return false;
}

public function AllowNameChange()
{
    return false;
}

// Por padrão no Librebooking estes vêm como true:
public function AllowPhoneChange()
{
    return false; // Altere de true para false
}

public function AllowOrganizationChange()
{
    return false; // Altere de true para false
}

public function AllowPositionChange()
{
    return false; // Altere de true para false
}
```
Quando o plugin retorna `false`, o `$options->AllowNameChange()` automaticamente 
retorna `false` para o `ProfilePage.php`.


--------------------------------------------------------------------------------
PARTE 3: PASSO A PASSO EXATO DOS ARQUIVOS A ALTERAR
--------------------------------------------------------------------------------

PASSO 1: Remover o botão "Update" e travar seletores
Arquivo: /home/csi/Documentos/librebooking/tpl/MyAccount/profile.tpl
- Localize o botão de submit (linhas ~152-157):
  Comente ou remova:
  <!--
  <div class="d-grid mt-3">
      <button type="submit" class="update btn btn-primary btn-block" name="{Actions::SAVE}" id="btnUpdate">
          {translate key='Update'}
      </button>
  </div>
  -->
- Localize os selects de página inicial e fuso horário (linhas ~86-99):
  Adicione o atributo `disabled="disabled"` nos tags `<select>` para que o usuário 
  não altere.

PASSO 2: Forçar todos os campos em modo texto estático
Arquivo: /home/csi/Documentos/librebooking/Pages/ProfilePage.php
- No método `SetAllowedActions($options)` (linhas ~225-233):
  Troque para:
    $this->Set('AllowEmailAddressChange', false);
    $this->Set('AllowNameChange', false);
    $this->Set('AllowOrganizationChange', false);
    $this->Set('AllowPhoneChange', false);
    $this->Set('AllowPositionChange', false);
    $this->Set('AllowUsernameChange', false);

PASSO 3: Ocultar o item "Alterar Senha" da navegação
Arquivo: /home/csi/Documentos/librebooking/tpl/globalheader.tpl
- Localize as linhas ~160-161 dentro do `#navMyAccountDropdown`:
  Comente a linha do Password:
  <!--
  <li id="navPassword"><a class="dropdown-item" href="{$Path}{Pages::PASSWORD}">{translate key="ChangePassword"}</a></li>
  -->

PASSO 4: Bloqueio de segurança no Backend (Presenter)
Arquivo: /home/csi/Documentos/librebooking/Presenters/ProfilePresenter.php
- No método `Update()` (linhas ~105):
  Mesmo sem botão na tela, um usuário avançado poderia enviar um POST simulado via DevTools.
  Adicione no início do método `Update()`:
    return; // Ignora qualquer submissão de atualização

PASSO 5: Bloquear tela de troca de senha direta por URL
Arquivo: /home/csi/Documentos/librebooking/Web/password.php
- Adicione no início do arquivo:
    header('Location: ' . ROOT_DIR . 'Web/dashboard.php');
    exit;

PASSO 6: Aplicar no Docker e Limpar Cache
Execute no terminal:
  $ docker cp /home/csi/Documentos/librebooking/tpl/MyAccount/profile.tpl librebooking-app-1:/var/www/html/tpl/MyAccount/profile.tpl
  $ docker cp /home/csi/Documentos/librebooking/Pages/ProfilePage.php librebooking-app-1:/var/www/html/Pages/ProfilePage.php
  $ docker cp /home/csi/Documentos/librebooking/tpl/globalheader.tpl librebooking-app-1:/var/www/html/tpl/globalheader.tpl
  $ docker cp /home/csi/Documentos/librebooking/Presenters/ProfilePresenter.php librebooking-app-1:/var/www/html/Presenters/ProfilePresenter.php
  $ docker cp /home/csi/Documentos/librebooking/Web/password.php librebooking-app-1:/var/www/html/Web/password.php
  $ docker exec -it librebooking-app-1 rm -rf /var/www/html/tpl_c/*

Pronto! Recarregue a página no navegador com Ctrl + Shift + R e todas as mudanças 
aparecerão imediatamente.
================================================================================
