# Feature Specification: Sistema de Reserva de Salas

**Feature Branch**: `001-reserva-salas`

**Created**: 2026-09-21

**Status**: Draft

**Input**: User description: "Este projeto tem como visão a manipulação de reservas de salas
(futuramente podendo ampliar e atribuir reservas de objetos ou algo do tipo). Cada usuário
será tratado de forma limitada podendo apenas visualizar salas e nada mais além de também
poder reservar salas de forma que tenha essa permissão sendo aluno ou algum agente da
instituição. Além disso teremos os administradores que poderão editar tudo exceto as
credenciais dos usuários, pois eles serão gerenciados pelo painel do keycloak"

---

## User Scenarios & Testing *(mandatory)*

### User Story 1 — Visualizar disponibilidade de salas (Priority: P1)

Um usuário autenticado (aluno, servidor ou docente da UFCA) acessa o sistema e visualiza
o calendário de disponibilidade das salas, podendo consultar quais estão livres ou ocupadas
em determinada data e horário, sem necessidade de qualquer permissão especial além do login.

**Why this priority**: É o ponto de entrada fundamental. Sem visibilidade da disponibilidade,
nenhum outro fluxo do sistema faz sentido. Toda reserva começa aqui.

**Independent Test**: Pode ser testado completamente fazendo login com qualquer conta válida,
acessando o calendário e verificando se as salas e seus status (livre/ocupado) são exibidos
corretamente.

**Acceptance Scenarios**:

1. **Given** um usuário autenticado, **When** acessa a agenda de salas, **Then** visualiza
   todas as salas cadastradas com indicação visual de disponibilidade por período.
2. **Given** um usuário autenticado, **When** seleciona uma data específica, **Then** o
   calendário filtra e exibe a ocupação das salas naquela data.
3. **Given** um usuário não autenticado, **When** tenta acessar o sistema, **Then** é
   redirecionado para a tela de autenticação (Keycloak) sem acesso ao conteúdo.

---

### User Story 2 — Realizar uma reserva de sala (Priority: P2)

Um usuário com permissão de reserva (aluno ou agente institucional com perfil habilitado)
seleciona uma sala disponível, define o período desejado e submete a solicitação de reserva.
O sistema registra a reserva e notifica o usuário sobre o resultado.

**Why this priority**: É o objetivo-fim do sistema. Sem essa capacidade, o sistema serve
apenas como consulta de agenda.

**Independent Test**: Pode ser testado completamente com um usuário habilitado selecionando
uma sala livre, preenchendo o período e confirmando — verificando se a reserva aparece no
calendário e no histórico do usuário.

**Acceptance Scenarios**:

1. **Given** um usuário com permissão de reserva, **When** seleciona uma sala disponível e
   submete o período desejado, **Then** a reserva é registrada e a sala passa a aparecer como
   ocupada naquele período.
2. **Given** um usuário com permissão de reserva, **When** tenta reservar uma sala já
   ocupada no mesmo período, **Then** recebe mensagem de conflito e a reserva não é criada.
3. **Given** um usuário sem permissão de reserva (apenas visualizador), **When** tenta
   acessar a opção de criar reserva, **Then** a ação é bloqueada e o usuário recebe mensagem
   explicativa.
4. **Given** uma sala que exige aprovação prévia, **When** um usuário envia solicitação de
   reserva, **Then** a reserva fica com status "Pendente" até aprovação do responsável.

---

### User Story 3 — Gerenciar reservas pelo administrador (Priority: P2)

Um administrador do sistema acessa o painel administrativo e pode visualizar, aprovar,
rejeitar, editar ou cancelar qualquer reserva do sistema. Pode também cadastrar, editar
e desativar salas, definir regras de uso (cotas, horários permitidos, capacidade) e
gerenciar grupos de usuários e suas permissões de reserva.

**Why this priority**: Sem administração, o sistema não pode ser configurado nem mantido
para o contexto real da UFCA.

**Independent Test**: Pode ser testado com um usuário administrador aprovando uma reserva
pendente, alterando dados de uma sala e verificando que as mudanças refletem imediatamente
para os usuários comuns.

**Acceptance Scenarios**:

1. **Given** um administrador autenticado, **When** acessa o painel de gestão, **Then**
   visualiza todas as reservas, salas, grupos e configurações do sistema.
2. **Given** uma reserva com status "Pendente", **When** o administrador aprova, **Then**
   a reserva muda para "Confirmada" e o usuário é notificado.
3. **Given** um administrador, **When** tenta alterar credenciais de um usuário, **Then**
   essa opção não está disponível no sistema — credenciais são gerenciadas exclusivamente
   pelo Keycloak.
4. **Given** um administrador, **When** cadastra uma nova sala com suas configurações
   (capacidade, requer aprovação, horários permitidos), **Then** a sala aparece disponível
   para reserva no calendário.

---

### User Story 4 — Cancelar uma reserva própria (Priority: P3)

Um usuário com reservas ativas pode cancelar suas próprias reservas dentro do prazo
permitido pelas regras da instituição, liberando a sala para outros usuários.

**Why this priority**: Funcionalidade de suporte ao ciclo de vida da reserva; importante
mas não bloqueia o fluxo principal.

**Independent Test**: Testável com um usuário que possui reserva ativa cancelando-a e
verificando se o slot volta a estar disponível no calendário.

**Acceptance Scenarios**:

1. **Given** um usuário com reserva confirmada, **When** solicita cancelamento dentro do
   prazo permitido, **Then** a reserva é cancelada e a sala liberada no calendário.
2. **Given** um usuário com reserva passada (data já ocorreu), **When** tenta cancelar,
   **Then** o cancelamento é bloqueado com mensagem explicativa.

---

### Edge Cases

- O que acontece quando dois usuários tentam reservar a mesma sala no mesmo horário
  simultaneamente? O sistema DEVE garantir que apenas um consiga, sem dupla alocação.
- O que acontece quando o usuário perde a sessão durante o preenchimento de uma reserva?
  O formulário DEVE ser preservado ou o usuário informado claramente.
- O que acontece quando o Keycloak está indisponível? O sistema DEVE exibir mensagem de
  erro amigável e impedir acesso não autenticado.
- O que acontece quando uma sala é desativada e possui reservas futuras? O administrador
  DEVE ser alertado e as reservas afetadas notificadas.

---

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: O sistema DEVE autenticar todos os usuários exclusivamente via Keycloak
  (SSO); nenhuma credencial de usuário é gerenciada internamente pelo sistema.
- **FR-002**: O sistema DEVE exibir o calendário de disponibilidade de salas para
  qualquer usuário autenticado, independentemente de ter permissão de reserva.
- **FR-003**: O sistema DEVE permitir que usuários com permissão de reserva (alunos e
  agentes institucionais habilitados) criem solicitações de reserva de sala.
- **FR-004**: O sistema DEVE impedir que usuários sem permissão de reserva criem,
  editem ou cancelem reservas.
- **FR-005**: O sistema DEVE suportar o fluxo de aprovação: reservas de salas que
  exigem aprovação prévia ficam com status "Pendente" até decisão do responsável.
- **FR-006**: O sistema DEVE notificar o usuário sobre o resultado de sua solicitação
  de reserva (confirmada, pendente ou rejeitada).
- **FR-007**: Administradores DEVEM poder gerenciar salas (criar, editar, desativar),
  grupos de usuários, permissões e regras de cotas.
- **FR-008**: Administradores NÃO DEVEM ter acesso a funcionalidades de gerenciamento
  de credenciais de usuários — essa responsabilidade pertence exclusivamente ao Keycloak.
- **FR-009**: O sistema DEVE garantir atomicidade na criação de reservas, prevenindo
  conflitos de dupla alocação do mesmo recurso no mesmo período.
- **FR-010**: O sistema DEVE suportar a expansão futura para reservas de outros tipos
  de recursos (equipamentos, veículos etc.) sem necessidade de reescrita arquitetural.
- **FR-011**: Toda a interface voltada ao usuário final DEVE estar em Português do Brasil.
- **FR-012**: O sistema DEVE registrar log de auditoria para criações, modificações e
  cancelamentos de reservas, identificando o ator responsável.

### Key Entities

- **Sala (Resource)**: Espaço físico reservável. Possui nome, capacidade, localização,
  status (ativa/inativa), flag de exige aprovação e regras de uso (horários, cotas).
- **Reserva (Reservation)**: Registro de alocação de uma sala por um usuário em um
  período. Possui status (pendente, confirmada, rejeitada, cancelada) e histórico.
- **Usuário (User)**: Pessoa autenticada via Keycloak. Possui papel (visualizador,
  reservador, administrador) e pertence a um ou mais grupos.
- **Grupo (Group)**: Agrupamento lógico de usuários (ex.: "Professores", "Alunos TAE").
  Define regras e cotas de reserva em massa.
- **Agenda (Schedule)**: Calendário que agrupa salas de uma mesma categoria ou unidade.
  Ex.: "Salas UFCA". Futuramente poderá incluir outros tipos de recursos.
- **Quota (Quota)**: Regra de limite de uso. Ex.: "Usuário pode reservar no máximo
  4 horas consecutivas por dia".

---

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Um usuário autenticado consegue visualizar a disponibilidade completa das
  salas em menos de 3 segundos após acessar o calendário.
- **SC-002**: Um usuário habilitado consegue concluir todo o processo de criação de uma
  reserva em menos de 2 minutos, desde a consulta de disponibilidade até a confirmação.
- **SC-003**: 100% das tentativas de reserva conflitante (dupla alocação simultânea)
  são bloqueadas pelo sistema sem necessidade de intervenção manual.
- **SC-004**: Um administrador consegue cadastrar uma nova sala e disponibilizá-la para
  reservas em menos de 5 minutos.
- **SC-005**: A interface está 100% em Português do Brasil antes da entrada em produção,
  sem strings em outros idiomas visíveis ao usuário final.
- **SC-006**: Nenhuma credencial de usuário é armazenada ou gerenciada pelo sistema;
  toda autenticação ocorre exclusivamente via Keycloak.

---

## Assumptions

- Os usuários da instituição já possuem contas gerenciadas no Keycloak da UFCA; o sistema
  importa identidade e papéis a partir do token de autenticação.
- A distinção entre "usuário com permissão de reserva" e "apenas visualizador" será
  controlada por papéis (roles) configurados no Keycloak e/ou em grupos do sistema.
- A fase inicial contempla apenas salas como recurso reservável; equipamentos e veículos
  são escopo futuro e não serão implementados nesta especificação.
- O sistema utiliza o controle de acesso baseado em papéis (RBAC) nativo do LibreBooking
  sem necessidade de patches no código-fonte.
- Administradores não têm acesso a funcionalidades de criação ou alteração de senhas;
  esse fluxo é gerenciado 100% no painel do Keycloak.
- Reservas de salas especiais (laboratórios, salas de reunião de direção) poderão exigir
  aprovação prévia de um administrador de recurso.
- Suporte mobile não é objetivo desta fase, mas a interface responsiva (Bootstrap 5) do
  LibreBooking atende a esse uso de forma básica.

