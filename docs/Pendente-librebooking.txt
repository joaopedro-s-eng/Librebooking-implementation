================================================================================
                    PENDÊNCIAS E GUIA DE ALTERAÇÕES - LIBREBOOKING UFCA
================================================================================
Data do relatório: 30/09/2026
Repositório base: /home/csi/Documentos/librebooking/

--------------------------------------------------------------------------------
1. CONFIGURAÇÃO DO PERFIL EM MODO SOMENTE LEITURA (READ-ONLY)
--------------------------------------------------------------------------------
Objetivo: Como a autenticação e credenciais são gerenciadas centralmente pelo 
Keycloak / LDAP, os usuários no LibreBooking NÃO devem alterar seus dados 
pessoais e NÃO devem alterar senha. O usuário poderá apenas visualizar seus dados.

ARQUIVOS QUE PRECISAM SER ALTERADOS:

[Arquivo 1] - Barra de navegação e menu superior
  Caminho: /home/csi/Documentos/librebooking/tpl/globalheader.tpl
  Linhas de referência: ~154 a 165
  O que alterar:
    - Ocultar/remover o item de "Alterar Senha" (<li id="navPassword">...</li>).
    - Manter apenas o item de "Perfil" (<li id="navProfile">...</li>) no menu "Minha Conta".

[Arquivo 2] - Template da interface de Perfil (MyAccount)
  Caminho: /home/csi/Documentos/librebooking/tpl/MyAccount/profile.tpl
  Linhas de referência: ~152 a 157, e campos de seleção ~84 a 100
  O que alterar:
    - Remover ou comentar o botão de submit "Update":
        <button type="submit" class="update btn btn-primary btn-block" name="{Actions::SAVE}" id="btnUpdate">
    - Tornar os campos de seleção "Página Padrão" (DefaultPage) e "Fuso Horário" (Timezone) desabilitados 
      (disabled="disabled") ou apenas textos informativos.
    - Como o template já possui a lógica de:
        {if $AllowNameChange}...{else}<span>{$FirstName}</span>{/if}
      quando os parâmetros estiverem como falso no backend, todos os dados 
      (Nome, Sobrenome, E-mail, Usuário, Telefone, Organização, Cargo) 
      serão exibidos automaticamente como texto puro (sem campos editáveis).

[Arquivo 3] - Controlador da Página de Perfil
  Caminho: /home/csi/Documentos/librebooking/Pages/ProfilePage.php
  Linhas de referência: ~225 a 233
  O que alterar:
    - No método SetAllowedActions($options), forçar todos os retornos como false:
        $this->Set('AllowEmailAddressChange', false);
        $this->Set('AllowNameChange', false);
        $this->Set('AllowOrganizationChange', false);
        $this->Set('AllowPhoneChange', false);
        $this->Set('AllowPositionChange', false);
        $this->Set('AllowUsernameChange', false);

[Arquivo 4] - Presenter de Perfil (Camada de Negócio / Proteção de Backend)
  Caminho: /home/csi/Documentos/librebooking/Presenters/ProfilePresenter.php
  Linhas de referência: método Update() (~ linha 100 em diante)
  O que alterar:
    - Adicionar trava de segurança no método de atualização para impedir que 
      requisições POST forjadas atualizem dados do usuário no banco.

[Arquivo 5] - Template e Página de Senha (Desativação de Segurança)
  Caminho do template: /home/csi/Documentos/librebooking/tpl/MyAccount/password.tpl
  Caminho da página:   /home/csi/Documentos/librebooking/Pages/PasswordPage.php
  Caminho do endpoint: /home/csi/Documentos/librebooking/Web/password.php
  O que alterar:
    - Em PasswordPage.php, garantir que AllowPasswordChange seja false.
    - Em Web/password.php, redirecionar qualquer acesso direto para Web/dashboard.php.

[Arquivo 6] - Plugin de Autenticação (LDAP / SAML)
  Caminho LDAP: /home/csi/Documentos/librebooking/plugins/Authentication/Ldap/Ldap.php
  Caminho SAML: /home/csi/Documentos/librebooking/plugins/Authentication/Saml/Saml.php
  O que verificar:
    - Os métodos AllowUsernameChange(), AllowEmailAddressChange(), AllowPasswordChange(), 
      AllowNameChange(), AllowPhoneChange(), AllowOrganizationChange() devem 
      retornar false.


--------------------------------------------------------------------------------
2. STATUS DO MAPEAMENTO E DOCUMENTAÇÃO DO PROJETO
--------------------------------------------------------------------------------

[+] CONCLUÍDO E MADURO:
  1. docs/architecture.md:
     - Documentação arquitetural para go-live e TI.
     - Detalhamento de containers (PHP 8.2, Apache, MariaDB 10.6, Keycloak 22).
     - Diagrama de sequência de autenticação SSO/SAML e perfis nativos RBAC.
  2. docs/configuration.md:
     - Dicionário completo de parâmetros e configurações do config/config.php.
  3. docs/architecture-decision-records/0001-adocao-librebooking-ufca.md:
     - Registro formal da ADR para adoção do LibreBooking na UFCA.
  4. specs/001-reserva-salas/spec.md:
     - Especificação funcional com histórias de usuário P1 e P2 e critérios de aceite.
  5. specs/001-reserva-salas/checklists/requirements.md:
     - Checklist de conformidade de requisitos validado.

[-] PENDÊNCIAS IDENTIFICADAS (ARQUIVOS ZERADOS OU EM TEMPLATE):
  1. specs/001-reserva-salas/plan.md:
     - Situação: Encontra-se como template genérico do Spec-Kit.
     - Ação necessária: Preencher com os detalhes técnicos e decisões de implementação reais.
  2. specs/001-reserva-salas/data-model.md:
     - Situação: Arquivo vazio (0 bytes).
     - Ação necessária: Mapear as tabelas principais do banco (users, resources, schedules, 
       series, reservation_instances, groups, etc.).
  3. specs/001-reserva-salas/contracts/rbac-roles.md:
     - Situação: Arquivo vazio (0 bytes).
     - Ação necessária: Documentar o contrato de papéis (Super Admin, Resource Admin, Regular User).
  4. specs/001-reserva-salas/contracts/reservation-flow.md:
     - Situação: Arquivo vazio (0 bytes).
     - Ação necessária: Documentar formalmente os estados da reserva (Pendente, Aprovada, Recusada).
  5. specs/001-reserva-salas/quickstart.md e research.md:
     - Situação: Arquivos vazios (0 bytes).
     - Ação necessária: Instruções de execução rápida do ambiente e notas de pesquisa técnica.
  6. Alinhamento de Protocolo no config/config.php:
     - O arquivo docs/architecture.md descreve SAML 2.0.
     - O config/config.php atual possui trechos de OAuth2, Keycloak nativo e OIDC comentado.
     - É necessário padronizar a escolha do protocolo definitivo no arquivo de configuração.

================================================================================
