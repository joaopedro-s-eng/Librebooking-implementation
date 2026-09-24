# Adoção do LibreBooking para Gestão de Recursos na UFCA

## Status

Aceita

## Contexto

A Universidade Federal do Cariri (UFCA) possui uma demanda crescente por organização e controle no uso de seus espaços físicos e recursos (como salas de aula, laboratórios, auditórios e equipamentos acadêmicos). O projeto tem como visão a substituição de um sistema legado (Booked), que já está ultrapassado, e cuja versão paga não é viável para a instituição.

Além disso, o sistema precisa ser acessível pela comunidade acadêmica (alunos, professores e servidores técnico-administrativos) e suportar customizações futuras. Pagar por um plano de sistema no qual não temos total controle e que possivelmente tenha limitações conforme as demandas institucionais deixou de ser uma opção.

## Decisão

Foi decidido adotar e customizar o **LibreBooking** (um fork open-source do antigo Booked Scheduler) como a plataforma base para o sistema de agendamentos da UFCA.

A escolha se justifica porque o LibreBooking já é semelhante ao sistema anterior, porém é mais atualizado, seguro, possui uma comunidade forte e é bem escalável. Torna-se imprescindível a adoção desse sistema para que a instituição não deixe a desejar nesse aspecto de gestão e tenha independência tecnológica. Com o LibreBooking, é possível manter a plataforma atualizada não só pela própria comunidade enviando atualizações, mas também pelos técnicos da própria instituição.

## Consequências

* **Positivas:**
  * **Independência e Economia:** Não é necessário pagar por um plano fechado, dando total controle para a UFCA.
  * **Evolução Colaborativa:** Possibilidade de receber atualizações da comunidade open-source e realizar modificações internas.
  * **Familiaridade:** Como é um fork do sistema legado utilizado, possui forte semelhança, facilitando a migração.
* **Negativas / Desafios:**
  * A equipe precisará dedicar tempo para compreender a arquitetura (em PHP) do LibreBooking antes de conseguir implementar as integrações profundas com a infraestrutura da UFCA de forma segura.
