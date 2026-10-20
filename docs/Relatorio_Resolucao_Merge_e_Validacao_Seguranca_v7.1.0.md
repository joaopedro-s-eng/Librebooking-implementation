# Relatório Técnico: Resolução do Merge v7.1.0 e Validação da Mitigação de Vulnerabilidades

| Metadado | Detalhe |
| :--- | :--- |
| **Projeto** | Sistema de Reserva de Salas — LibreBooking UFCA |
| **Data de Conclusão** | 20/10/2026 |
| **Responsável Técnico** | Equipe de TI / DTI UFCA |
| **Branch de Trabalho** | `rmv2` |
| **Commit de Merge** | `c155325a1` (*"merge: atualizacao para v7.1.0 e correcoes criticas de seguranca"*) |
| **Versão Base Anterior** | v5.3.0 (com herança de falhas v3.0.x / v7.0.x) |
| **Versão Alvo Atualizada**| **v7.1.0** (Upstream estável e saneado) |

---

## 1. Contexto e Motivação da Atualização

O sistema de reservas institucional utilizava uma ramificação herdada do LibreBooking com débitos técnicos de versões antigas (inclusive a v3.0.3 e v5.3.0). O upstream oficial lançou a versão **v7.1.0** para solucionar em caráter de emergência a vulnerabilidade crítica:

* **CVE / GitHub Security Advisory:** [GHSA-mvjx-72w3-wqhp](https://github.com/LibreBooking/librebooking/security/advisories/GHSA-mvjx-72w3-wqhp)
* **Vetor de Ataque:** Sequestro de conta (*Account Takeover*) sem credenciais por meio da manipulação e forjamento do cookie HTTP de persistência de sessão (`persist_login`).
* **Vulnerabilidades Auxiliares Corrigidas:**
  - `fa454d019`: Vazamento de permissões administrativas em reservas de quiosques públicos.
  - `265aca30d`: Agendamento forjado de recursos privados a partir de totens de autoatendimento.

---

## 2. Diagnóstico dos Conflitos de Merge (`develop` -> `rmv2`)

Durante a execução do comando:
```bash
git merge develop -m "merge: atualizacao para v7.1.0 e correcoes criticas de seguranca"
```
O Git interrompeu o processo acusando 36 conflitos do tipo **`CONFLICT (modify/delete)`**.

### Causa Raiz:
* **No ramo local (`rmv2`):** No commit inicial de estruturação da UFCA, diversos arquivos que não seriam utilizados pela universidade (workflows de CI do GitHub, templates de issues e idiomas estrangeiros como árabe, checo, japonês, etc.) haviam sido deletados para enxugar o repositório.
* **No ramo upstream (`develop`):** O upstream alterou esses mesmos arquivos na preparação da release v7.1.0.
* **Resolução Aplicada:** Seguindo a diretriz estabelecida na **ADR 0004**, foi executado o checkout das versões mais recentes do upstream (`theirs`) para garantir a integridade dos pacotes e finalizar o merge sem perda de dependências:
  ```bash
  git checkout --theirs .github/ lang/ AGENTS.md CHANGELOG.md CONTRIBUTING.md requirements-lint.txt
  git add -A
  git commit -m "merge: atualizacao para v7.1.0 e correcoes criticas de seguranca"
  ```

---

## 3. Garantia de Preservação das Customizações Institucionais (UFCA)

A auditoria pós-merge comprovou que **todas as regras de negócio e adaptações da UFCA permaneceram 100% ativas**:

| Arquivo Customizado | Função Institucional | Estado Pós-Merge |
| :--- | :--- | :---: |
| [`Pages/ProfilePage.php`](file:///home/csi/Documentos/librebooking/Pages/ProfilePage.php) | Força modo Somente Leitura em dados cadastrais (ADR 0002) | **Intacto** |
| [`Web/password.php`](file:///home/csi/Documentos/librebooking/Web/password.php) | Bloqueia troca de senha local e redireciona ao dashboard (ADR 0003) | **Intacto** |
| [`tpl/globalheader.tpl`](file:///home/csi/Documentos/librebooking/tpl/globalheader.tpl) | Remove o botão "Trocar Senha" do menu do usuário (ADR 0003) | **Intacto** |
| Pasta [`docs/`](file:///home/csi/Documentos/librebooking/docs/) | Documentação de arquitetura, dicionário de configuração e ADRs 0001 a 0004 | **Intacto** |
| Pasta [`specs/`](file:///home/csi/Documentos/librebooking/specs/) | Especificações de requisitos e histórias de usuário para Reserva de Salas | **Intacto** |

---

## 4. Como Verificar e Provar que a Vulnerabilidade Foi Eliminada

Para auditar e comprovar que o "fator de erro" / vulnerabilidade não pode mais ocorrer, execute as 4 etapas de verificação abaixo:

### Verificação 1: Execução dos Testes Automatizados de Segurança (PHPUnit)
O upstream adicionou testes que garantem que o cookie de auto-login não é mais gravado e que o sistema rejeita sessões legadas. Execute no terminal:

```bash
docker exec librebooking-app-1 vendor/bin/phpunit tests/Application/Authentication/WebAuthenticationTest.php
```
* **Resultado Esperado:** `OK (8 tests, 24 assertions)`
* **O que esse teste valida:** O teste `testLoginDoesNotSetAPersistentLoginCookie` verifica diretamente que `CookieKeys::PERSIST_LOGIN` nunca é emitido, e os testes antigos de auto-login por cookie foram removidos.

```bash
docker exec librebooking-app-1 vendor/bin/phpunit tests/Presenters/LoginPresenterTest.php
```
* **Resultado Esperado:** `OK (23 tests, 40 assertions)`

---

### Verificação 2: Auditoria Estática de Código (Remoção do Vetor de Ataque)
Verifique diretamente no repositório que as classes e campos que processavam o cookie foram eliminados:

1. **Classe de Cookie Deletada:**
   ```bash
   ls /home/csi/Documentos/librebooking/lib/Application/Authentication/LoginCookie.php
   ```
   *Resultado:* `Arquivo ou diretório inexistente` (o manipulador do cookie vulnerável não existe mais).

2. **Campo Visual Removido da Tela de Login:**
   ```bash
   grep -i "remember" /home/csi/Documentos/librebooking/tpl/login.tpl
   ```
   *Resultado:* Nenhuma ocorrência retornada. O checkbox "Lembrar-me" foi extirpado do template.

3. **Comandos SQL de Validação de Cookie Deletados:**
   ```bash
   grep -rn "CookieLoginCommand" /home/csi/Documentos/librebooking/lib/
   ```
   *Resultado:* Nenhuma ocorrência. A consulta que validava tokens de cookie no banco de dados foi eliminada.

---

### Verificação 3: Simulação Prática de Exploit (Teste de Invasão via cURL)
Tente simular a ação de um invasor forjando o cookie `persist_login` para acessar uma página interna protegida sem credenciais válidas:

```bash
curl -i -s "http://localhost:8081/Web/dashboard.php" \
  -H "Cookie: persist_login=1%3Aqualquervalor"
```

* **Resultado Seguro (Comprovado):**
  ```http
  HTTP/1.1 302 Found
  Location: http://localhost:8081/Web/index.php?redirect=%2FWeb%2Fdashboard.php
  ```
* **Interpretação:** O sistema **rejeita sumariamente** o cookie falso, não estabelece nenhuma sessão privilegiada e redireciona o cliente para a tela de autenticação. Em versões vulneráveis anteriores, o retorno seria `HTTP 200 OK` carregando os dados administrativos do usuário 1.

---

### Verificação 4: Verificação Visual na Interface Web
1. Limpe os templates compilados em cache no Docker:
   ```bash
   docker exec librebooking-app-1 rm -rf /var/www/html/tpl_c/*
   ```
2. Abra o navegador em: `http://localhost:8081/`
3. Na tela de Login:
   - O campo **"Remember Me" (Lembrar-me)** não deve mais existir abaixo do campo de senha.
   - Mesmo que um usuário salve cookies antigos no navegador, eles expirarão no logout e serão ignorados pelo backend.

---

## 5. Conclusão e Prontidão para o GitLab

Com a conclusão do merge `c155325a1` na branch `rmv2`, o projeto do Sistema de Reserva de Salas da UFCA atinge os seguintes marcos:
1. **Segurança Sanada:** Totalmente livre da vulnerabilidade de Account Takeover e das brechas de quiosque.
2. **Conformidade Institucional:** Regras de Keycloak (Read-Only de Perfil e Bloqueio de Troca de Senha) íntegras e validadas.
3. **Repositório Pronto para Push:** O histórico do upstream está preservado e pronto para envio ao GitLab oficial da instituição (`git.ufca.edu.br`).
