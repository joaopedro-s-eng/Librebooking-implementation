================================================================================
          PROJETO LIBREBOOKING - SISTEMA DE RESERVAS UFCA
================================================================================

1. VISÃO GERAL
Este documento define a estrutura e o fluxo de implementação do sistema LibreBooking 
para a Universidade Federal do Cariri (UFCA). O objetivo inicial é o gerenciamento 
eficiente de salas (reunião, aula, laboratórios), com arquitetura escalável para novas 
aquisições (equipamentos, veículos) no futuro.

2. ARQUITETURA DE SEGURANÇA E PERFIS (Nativo do Sistema)
O LibreBooking possui um sistema robusto de Controle de Acesso Baseado em Papéis (RBAC).
Não são necessários patches ou modificações no código para gerenciar permissões.

- Application Administrator (Super Admin): Acesso total. Gerencia configurações gerais, 
  agendas, layouts e permissões globais.
- Resource Administrator: Gerencia recursos específicos. Pode aprovar ou rejeitar 
  reservas solicitadas para as salas sob sua responsabilidade.
- Group Administrator: Gerencia permissões e cotas para grupos específicos.
- Usuário Comum (Regular User): O padrão do sistema. Pode visualizar calendários 
  (conforme permissão), buscar disponibilidade e solicitar/fazer reservas.

3. ESTRUTURA LÓGICA DO SISTEMA

A. Agendas (Schedules)
A estrutura principal de organização. Atuam como os "Calendários".
- Inicial: "Salas UFCA" (Agrupa todas as salas do projeto inicial).
- Futuro: "Frota Oficial", "Equipamentos de TI", etc.

B. Recursos (Resources)
Os itens físicos alocados nas agendas.
- Exemplo: "Sala de Reunião Bloco A", "Laboratório de Informática 01".
- Propriedades úteis: Capacidade de pessoas, Requer aprovação prévia, 
  Tempo mínimo/máximo de reserva.

C. Grupos (Groups)
Agrupamentos lógicos de usuários para facilitar regras em massa.
- Exemplo: "Professores", "Servidores TAEs", "Diretoria".

D. Cotas (Quotas) e Regras
Limites de utilização configuráveis.
- Exemplo: "Um usuário comum só pode reservar a sala por no máximo 4 horas seguidas".

4. PAINEL INICIAL (DASHBOARD) E VISIBILIDADE
O LibreBooking permite a utilização de "Widgets" na tela inicial.
- Visão de Uso Atual: É possível exibir painéis que mostram as "Reservas de Hoje" 
  ou o "Status dos Recursos", indicando exatamente quais salas estão ocupadas e quais 
  estão livres no momento, servindo como um mapa rápido de ocupação.

5. PLANO DE AÇÃO E PRÓXIMOS PASSOS

Fase 1: Setup Básico e Cadastro (Foco em Salas)
[x] Subida do ambiente (Docker) na porta 8081.
[x] Criação da conta Application Administrator.
[ ] Criação da Agenda principal "Salas UFCA".
[ ] Cadastro das salas físicas como Recursos.
[ ] Organização dos Widgets no Dashboard para visão de ocupação.

Fase 2: Segurança e Acessos
[ ] Criação de um perfil de "Usuário Comum" para simulação.
[ ] Definição de regras: quem pode aprovar reservas de salas especiais?
[ ] Validação do fluxo de agendamento (Pedido -> Aprovação -> Uso).

Fase 3: Expansão Futura
[ ] Inserção de novos tipos de recursos (Equipamentos/Veículos).
[ ] Possível integração com o banco de usuários existente (LDAP/AD).
================================================================================

Testes Locais: Subir o LibreBooking com Docker local para você fuçar no sistema, entender as telas, cadastrar salas e ver as configurações.

Conexão com o Keycloak: Testar a integração do LibreBooking com o Keycloak da UFCA (ou um Keycloak de testes).

Migração do Banco: Entender como pegar os dados do Booked Scheduler antigo e importar para o banco de dados oficial que o LibreBooking usará.

Documentação: Registrar os comandos do Docker utilizados, como configurar o arquivo do LibreBooking para apontar para o banco oficial da UFCA e os passos da integração com o Keycloak.
