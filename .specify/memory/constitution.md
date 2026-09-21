<!--
SYNC IMPACT REPORT
==================
Version change: (none) → 1.0.0
Added sections:
  - Core Principles (I–VII)
  - Requisitos de Segurança e Integração
  - Fluxo de Desenvolvimento e Qualidade
  - Governance
Removed sections: (none — constituição inicial)
Modified principles: (none — criação)
Follow-up TODOs:
  - TODO(RATIFICATION_DATE): Data de ratificação formal ainda não definida; marcada como 2026-09-21 (data de criação desta constituição).
-->

# LibreBooking UFCA — Constituição do Projeto

## Princípios Fundamentais

### I. Código Limpo e Sem Gambiarras (NON-NEGOTIABLE)

Todo código produzido ou modificado DEVE seguir os princípios de Clean Code:
nomes expressivos, funções pequenas com responsabilidade única, ausência de
comentários redundantes e eliminação completa de _workarounds_ temporários.
Soluções de contorno ("gambiarras") são PROIBIDAS: se uma correção exige uma
gambiarra, a causa raiz DEVE ser investigada e solucionada antes de avançar.

**Rationale**: O projeto serve uma instituição pública (UFCA). Código de baixa
qualidade cria dívida técnica que onera manutenções futuras e compromete a
segurança.

### II. Análise Antes de Implementar

Nenhuma funcionalidade nova DEVE ser implementada sem que o fluxo correspondente
tenha sido mapeado, documentado e revisado. Isso inclui: compreender o fluxo
completo de reservas, papéis de acesso (RBAC), integrações externas e impactos
em dados legados.

**Rationale**: O sistema será substituto direto de um sistema legado em uso
ativo. Implementar sem mapear gera risco de regressão funcional e confusão para
os usuários finais.

### III. Segurança por Padrão

Toda alteração DEVE ser avaliada sob perspectiva de segurança antes de ser
mesclada. Autenticação, autorização, gestão de sessão e proteção de dados
DEVEM seguir boas práticas (OWASP). Integrações de identidade (Keycloak, LDAP,
AD) DEVEM ser tratadas como componentes críticos, com testes de autenticação e
autorização dedicados.

**Rationale**: LibreBooking é exposto à rede institucional e potencialmente à
internet. Falhas de segurança em sistemas de reserva podem expor dados de
usuários e comprometer a integridade operacional da UFCA.

### IV. Internacionalização e Apresentação Adequada

Toda interface visível ao usuário DEVE estar em Português do Brasil (pt_BR),
clara, sem jargões técnicos desnecessários e acessível. Strings hard-coded em
outros idiomas DEVEM ser mapeadas e movidas para os arquivos de tradução do
sistema antes da entrada em produção.

**Rationale**: Os usuários finais são servidores, professores e alunos da UFCA
que operam em português. Uma interface confusa ou em idioma estrangeiro cria
barreira de adoção.

### V. Rastreabilidade e Documentação

Cada decisão de design, configuração de ambiente, integração ou mudança de
fluxo DEVE ser registrada — seja em arquivos de documentação do repositório,
comentários de PR ou arquivos de especificação no `.specify/`. Comandos Docker,
configurações de infraestrutura e passos de migração DEVEM ser documentados
de forma reprodutível.

**Rationale**: O projeto é mantido por uma equipe pequena inserida no contexto
institucional. Decisões não documentadas criam dependência de pessoas e
dificultam onboarding e auditorias.

### VI. Compatibilidade e Respeito ao Upstream

Modificações ao código do LibreBooking DEVEM distinguir claramente entre:
(a) configurações e customizações (preferidas), e (b) patches no código-fonte.
Patches diretos no código-fonte DEVEM ser minimizados, justificados e isolados,
de forma a facilitar futuras atualizações do upstream.

**Rationale**: LibreBooking é um projeto comunitário ativo (GPLv3). Manter
rastreabilidade das mudanças locais evita conflitos de merge e garante que
melhorias do upstream possam ser absorvidas sem grandes esforços.

### VII. Evolução Incremental e Controlada

O projeto avança por fases claramente definidas. Funcionalidades de fases
futuras (ex.: integração Keycloak, migração de banco legado, expansão para
frota/equipamentos) NÃO DEVEM ser implementadas antes que a fase atual esteja
estável, testada e aprovada pelos stakeholders.

**Rationale**: Escopo não controlado introduz instabilidade. Cada fase entregue
e validada aumenta a confiança da instituição e reduz o risco de falhas em
produção.

## Requisitos de Segurança e Integração

- Toda integração de identidade externa (Keycloak, LDAP, AD) DEVE passar por
  ambiente de homologação antes de ser ativada em produção.
- Credenciais e segredos JAMAIS devem ser commitados no repositório. DEVEM ser
  gerenciados via variáveis de ambiente ou cofres de segredos.
- O controle de acesso DEVE ser implementado através do RBAC nativo do
  LibreBooking (Application Administrator, Resource Administrator, Group
  Administrator, Regular User) sem patches desnecessários.
- Logs de auditoria DEVEM ser preservados e protegidos contra alteração.
- A migração de dados do sistema legado (Booked Scheduler) DEVE ser validada
  em ambiente isolado antes de afetar dados de produção.

## Fluxo de Desenvolvimento e Qualidade

- **Fases obrigatórias**: Mapeamento → Especificação → Implementação → Teste →
  Aprovação → Produção.
- Toda tarefa DEVE ter uma especificação correspondente em `.specify/` antes
  de qualquer código ser escrito.
- PRs DEVEM ser revisados com checklist de: clean code, segurança, tradução
  e documentação.
- Ambientes Docker DEVEM ser utilizados para desenvolvimento local e
  homologação, garantindo paridade com produção.
- Código estático DEVE passar pelos linters configurados (`php-cs-fixer`,
  `phpstan`, `eslint`, `stylelint`) sem erros antes de merge.

## Governança

Esta constituição é o documento de mais alta autoridade do projeto. Toda prática
de desenvolvimento, decisão de arquitetura e política de integração está
subordinada a ela.

**Procedimento de Emenda**:
1. Qualquer membro da equipe pode propor uma emenda via PR com justificativa.
2. A emenda DEVE ser aprovada pelo responsável técnico do projeto antes de
   ser mesclada.
3. Toda emenda DEVE incrementar `CONSTITUTION_VERSION` conforme versionamento
   semântico descrito abaixo.
4. Emendas DEVEM incluir um plano de migração para práticas já em vigor que
   sejam afetadas.

**Política de Versionamento**:
- MAJOR: Remoção ou redefinição incompatível de princípio existente.
- MINOR: Adição de novo princípio ou seção, ou expansão material de guideline.
- PATCH: Clarificações, correções de texto, ajustes não-semânticos.

**Revisão de Conformidade**: A cada início de nova fase do projeto, a equipe
DEVE revisar se as práticas correntes estão em conformidade com esta
constituição.

**Arquivo de referência de desenvolvimento**: `.specify/memory/guidance.md`
(quando criado) conterá orientações operacionais de desenvolvimento do dia a dia.

---

**Version**: 1.0.0 | **Ratified**: 2026-09-21 | **Last Amended**: 2026-09-21
