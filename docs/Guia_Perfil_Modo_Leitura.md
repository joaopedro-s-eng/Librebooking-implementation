================================================================================
GUIA TÉCNICO: COMO TORNAR O PERFIL SOMENTE LEITURA NO LIBREBOOKING
================================================================================
Data: 01/10/2026
Objetivo: Explicar a lógica completa, os arquivos envolvidos e o passo a passo
          para deixar os dados pessoais do usuário em modo somente leitura
          (sem campos de input), mantendo as opções de preferência ativas.

--------------------------------------------------------------------------------
1. COMO FUNCIONA A LÓGICA DO TEMPLATE DO PERFIL (profile.tpl)
--------------------------------------------------------------------------------
O LibreBooking utiliza o motor de templates Smarty. No arquivo:
tpl/MyAccount/profile.tpl

Cada campo de dado pessoal do usuário (Nome, Sobrenome, E-mail, Telefone, 
Organização, Cargo) é encapsulado por uma condicional booleana do Smarty.

Exemplo de como o código está estruturado para o Sobrenome (LastName):

    {if $AllowNameChange}
        {textbox name="LAST_NAME" class="input" value="LastName" ...}
    {else}
        <span>{$LastName}</span>
        <input type="hidden" {formname key=LAST_NAME} value="{$LastName}" />
    {/if}

- Se $AllowNameChange for VERDADEIRO (true):
  O template gera a tag <input type="text"> editável na tela.
- Se $AllowNameChange for FALSO (false):
  O template cai no bloco {else}. Ele gera apenas a tag <span> com o texto fixo 
  do nome do usuário (modo leitura) e um <input type="hidden"> invisível para 
  manter a integridade caso o formulário seja processado.

O mesmo padrão se repete para:
- $AllowEmailAddressChange  -> E-mail
- $AllowNameChange          -> Nome e Sobrenome
- $AllowPhoneChange         -> Telefone
- $AllowOrganizationChange  -> Organização
- $AllowPositionChange      -> Cargo/Função
- $AllowUsernameChange      -> Nome de Login/Usuário

Campos como "Página Inicial Padrão" (DefaultPage) e "Fuso Horário" (Timezone) 
NÃO dependem dessas variáveis, portanto continuam sendo dropdowns normais.

--------------------------------------------------------------------------------
2. ONDE ESSAS VARIÁVEIS SÃO DEFINIDAS NO PHP
--------------------------------------------------------------------------------
Arquivo: Pages/ProfilePage.php
Método: SetAllowedActions($options) (linhas ~224 a 239)

No código original do LibreBooking:

    public function SetAllowedActions($options)
    {
        $this->Set('AllowEmailAddressChange', $options->AllowEmailAddressChange());
        $this->Set('AllowNameChange', $options->AllowNameChange());
        $this->Set('AllowOrganizationChange', $options->AllowOrganizationChange());
        $this->Set('AllowPhoneChange', $options->AllowPhoneChange());
        $this->Set('AllowPositionChange', $options->AllowPositionChange());
        $this->Set('AllowUsernameChange', $options->AllowUsernameChange());
    }

O método $this->Set('NomeDaVariavel', $valor) envia a variável diretamente para 
o Smarty. 
O parâmetro $options vem do plugin de autenticação ativo (banco local, LDAP, etc.).

PARA TRAVAR TUDO EM MODO LEITURA:
Basta ignorar a chamada a $options e passar o valor literal `false`:

    public function SetAllowedActions($options)
    {
        $this->Set('AllowEmailAddressChange', false);
        $this->Set('AllowNameChange', false);
        $this->Set('AllowOrganizationChange', false);
        $this->Set('AllowPhoneChange', false);
        $this->Set('AllowPositionChange', false);
        $this->Set('AllowUsernameChange', false);
    }

Ao fazer isso, todas as condições {if $Allow...} no template avaliam como FALSO, 
e todos os inputs viram texto puro (<span>).

--------------------------------------------------------------------------------
3. POR QUE O BOTÃO DE SALVAR (UPDATE) DEVE FICAR NO TEMPLATE?
--------------------------------------------------------------------------------
No arquivo `tpl/MyAccount/profile.tpl`:
    <div class="d-grid mt-3">
        <button type="submit" class="update btn btn-primary btn-block" 
                name="{Actions::SAVE}" id="btnUpdate">
            {translate key='Update'}
        </button>
    </div>

Se você remover esse botão:
- O usuário não consegue salvar alterações legítimas, como a Página Inicial Padrão 
  ou o Fuso Horário.
- No LibreBooking, a classe controladora (ProfilePresenter) só executa o método 
  `UpdateProfile()` quando recebe no POST a chave correspondente a {Actions::SAVE}.
- Mantendo os `false` em ProfilePage.php, os dados pessoais já estão protegidos 
  e não são editáveis, mas o usuário ainda consegue clicar em "Update" para 
  salvar sua Página Inicial Padrão e Fuso Horário.

--------------------------------------------------------------------------------
4. POR QUE A ALTERAÇÃO NÃO APARECE IMEDIATAMENTE (DOCKER + SMARTY CACHE)
--------------------------------------------------------------------------------
Se você apenas editar o arquivo no seu computador (host), o LibreBooking não muda 
por dois motivos:

1. O container Docker (librebooking-app-1) utiliza uma imagem pré-construída 
   (librebooking/librebooking:develop). A pasta do código PHP local NÃO está 
   mapeada como volume dentro do container por padrão no docker-compose.yml.
2. O Smarty compila os templates em arquivos PHP intermediários e salva em cache 
   na pasta interna: `/var/www/html/tpl_c/`.

--------------------------------------------------------------------------------
5. PASSO A PASSO DA EXECUÇÃO MANUAL
--------------------------------------------------------------------------------

PASSO 1: Editar o arquivo PHP localmente
Abra `Pages/ProfilePage.php` no editor e localize `SetAllowedActions`.
Deixe o método assim:
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

PASSO 2: Garantir que o botão no template profile.tpl está presente
Verifique se `tpl/MyAccount/profile.tpl` possui o botão de submit (linhas ~152):
```html
<div class="d-grid mt-3">
    <button type="submit" class="update btn btn-primary btn-block" name="{Actions::SAVE}" id="btnUpdate">
        {translate key='Update'}
    </button>
</div>
```

PASSO 3: Copiar os arquivos para dentro do container Docker
No terminal, execute:
$ docker cp /home/csi/Documentos/librebooking/Pages/ProfilePage.php librebooking-app-1:/var/www/html/Pages/ProfilePage.php
$ docker cp /home/csi/Documentos/librebooking/tpl/MyAccount/profile.tpl librebooking-app-1:/var/www/html/tpl/MyAccount/profile.tpl

PASSO 4: Limpar o cache compilado do Smarty
No terminal, execute:
$ docker exec librebooking-app-1 rm -rf /var/www/html/tpl_c/*

PASSO 5: Recarregar no Navegador
Acesse a página de perfil e pressione `Ctrl + Shift + R` (ou `Ctrl + F5`) para 
limpar o cache do navegador. 
Agora os campos pessoais estarão em texto fixo, enquanto os seletores de 
Página Inicial e Fuso Horário continuam funcionando e salvando perfeitamente.
================================================================================
