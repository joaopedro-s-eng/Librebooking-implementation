# Guias Técnicos e Tutoriais — LibreBooking UFCA

Este diretório contém os manuais de implementação técnica, tutoriais passo a passo e diagnósticos operacionais desenvolvidos para apoiar os mantenedores do projeto **LibreBooking** na **Universidade Federal do Cariri (UFCA)**.

---

## Índice de Guias

| Guia | Descrição | ADR Relacionada |
| :--- | :--- | :---: |
| [Modo Somente Leitura do Perfil](./guia-perfil-modo-leitura.md) | Como travar dados cadastrais do usuário preservando preferências locais de Timezone e Página Inicial. Inclui resolução de problemas de cache do Smarty e volumes Docker. | [ADR 0002](../architecture-decision-records/0002-perfil-usuario-modo-somente-leitura.md) |
| [Bloqueio da Troca de Senha Local](./guia-bloqueio-troca-senha.md) | Passo a passo e arquitetura em 5 camadas para desativar a troca e redefinição de senhas locais sob SSO/Keycloak. | [ADR 0003](../architecture-decision-records/0003-bloqueio-troca-senha-local.md) |

---

## Boas Práticas ao Adicionar Novos Guias

1. Use arquivos Markdown com sintaxe do **GitHub Flavored Markdown**.
2. Inclua blocos de código com linguagem explícita (`php`, `bash`, `yaml`, `smarty`).
3. Destaque dicas e avisos com alertas padrão (`> [!NOTE]`, `> [!IMPORTANT]`, `> [!WARNING]`).
4. Vincule o guia ao respectivo **ADR** quando a implementação for derivada de uma decisão de arquitetura.
