# Registros de Decisão de Arquitetura (ADRs) - LibreBooking UFCA

Este diretório contém os **Registros de Decisão de Arquitetura** (Architecture Decision Records - ADRs) para a customização e implementação do projeto **LibreBooking** na **Universidade Federal do Cariri (UFCA)**.

## O Contexto do Projeto
O LibreBooking é um sistema open-source para agendamento e gerenciamento de recursos. Na UFCA, ele será a base tecnológica para organizar a reserva de ambientes (salas, laboratórios, auditórios) e equipamentos para alunos, docentes e técnicos.

Para adequar o sistema à realidade da universidade, diversas decisões de software precisarão ser tomadas (métodos de autenticação, modificações no banco de dados, fluxos de deploy, etc.). É para documentar isso que utilizamos os ADRs.

## O que é um ADR?
Um ADR é um documento curto que descreve uma escolha arquitetural ou técnica importante, o contexto (o problema) em que foi tomada e as suas consequências para o projeto. 

## A Escolha de Utilizar ADRs
A adoção do padrão de ADRs garante que o conhecimento técnico e os "porquês" das escolhas não se percam com a rotatividade natural de membros e desenvolvedores no ambiente universitário da UFCA. Isso simplifica a manutenção do projeto pela equipe de desenvolvimento e mantém um histórico claro da evolução do sistema. A estrutura permite documentar futuras decisões como integrações de login e sincronização, e alterações na UI/UX ou banco de dados.

## Como usar o padrão?

Sempre que a equipe for realizar uma implementação nova ou tomar uma decisão estrutural, siga os passos:

1. Faça uma cópia do arquivo `0000-template.md`.
2. Renomeie o arquivo de forma sequencial descrevendo a decisão. Exemplo: `0002-integracao-login-ldap-ufca.md`.
3. Preencha detalhadamente os tópicos do documento (Status, Contexto, Decisão e Consequências).
4. Faça o commit e adicione ao controle de versão.

Ao manter esse padrão, garantimos que qualquer novo desenvolvedor que entre no projeto da UFCA entenda rapidamente como o sistema funciona e por que foi construído de tal forma.
