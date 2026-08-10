---
status: testing
phase: 16-verifica-o-uat
source: 16-01-SUMMARY.md
started: 2026-08-10T01:25:39Z
updated: 2026-08-10T02:30:00Z
---

## Notas de Execução

- **UI (único caminho):** http://localhost:5173 (vite dev server; proxy `/api` → :80). NÃO usar nginx :80.
- **Login admin:** admin@labcontrol.com / @dmin123 (AdminUserSeeder).
- **Usuários de permissão (criados via tinker no PostgreSQL real em 16-02 T1):**
  - tecnico@uat.test / senha123 — role `tecnico` (SEM `afericoes.view`, SEM `afericoes.create`).
  - consulta@uat.test / senha123 — role `consulta` (COM `afericoes.view`, SEM `afericoes.create`).
  - Comprovado via tinker (saída): `tecnico=tecnico consulta=consulta` (roles anexadas) + verificação de permissões: tecnico sem `afericoes.view`/`afericoes.create`; consulta com `afericoes.view` sem `afericoes.create`.
- **Rate limit login:** 5 falhas → 429/60s ("Muitas tentativas. Aguarde 1 minuto.") — usar sempre credenciais corretas.
- **Evidência automatizada de apoio (executada na W2, 16-02 T1 — verde):**
  - `php artisan test --filter=VerificationUatFixTest` — 5 passed / 28 assertions (apoio S1-S4: criação, tolerância, histórico).
  - `php artisan test --filter=RbacRegressionTest` — 14 passed / 23 assertions (apoio S5/S6: 403 para sem-permissão).
  - `php artisan test --filter=SeederIdempotencyTest` — 2 passed / 14 assertions (apoio S1 base de dados/BUG-01).
- **Estado do banco real (após evidência automatizada + re-seed em 16-02 T1):** `migrate:fresh --seed --force` executado (tabelas recriadas, seeders idempotentes OK) + 2 usuários de teste recriados via tinker. Pendentes de aferição reais existem (equipamentos com verification_frequency sem aferição recente).

## Current Test

number: 1
name: DataTable de Aferições Pendentes — layout, loading e estado vazio
expected: |
  Página `/verifications` carrega DataTable com colunas: Equipamento, Patrimônio, Nº Série, Categoria, Frequência, Última Aferição, Ações.
  Loading skeleton visível durante carregamento.
  Se não houver pendentes, exibe estado vazio "Todos os equipamentos estão em dia".
awaiting: user response

## Tests

### 1. DataTable de Aferições Pendentes — layout, loading e estado vazio
expected: Página `/verifications` carrega DataTable com colunas (Equipamento, Patrimônio, Nº Série, Categoria, Frequência, Última Aferição, Ações). Loading skeleton durante carregamento. Estado vazio "Todos os equipamentos estão em dia" quando sem pendentes. Apoio automatizado: VerificationUatFixTest (5/28) + SeederIdempotencyTest (2/14), verdes na W2.
result: pending
source: manual
coverage_id: UAT-01-S1

### 2. Formulário de Aferição — campos dinâmicos de parâmetros
expected: Dialog "Nova Aferição" renderiza um InputNumber por parâmetro do template, com label, unidade e faixa de tolerância (min–max) exibidos. Select de equipamento funcional. TextArea para observações. Apoio automatizado: VerificationUatFixTest (5/28), verde na W2.
result: pending
source: manual
coverage_id: UAT-01-S2

### 3. Aba Aferições no EquipmentDetailPage — timeline com parâmetros expansíveis
expected: Aba "Aferições" (tab 3) no detalhe do equipamento mostra DataTable paginada com: Data, Operador, # Parâmetros, indicador "Fora do Intervalo". Linhas expansíveis mostram detalhes dos parâmetros com tags coloridas (verde dentro do intervalo, vermelho fora). Botão "Aferir" no topo da aba. Apoio automatizado: VerificationUatFixTest (5/28 — histórico por equipamento), verde na W2.
result: pending
source: manual
coverage_id: UAT-01-S3

### 4. Alerta de tolerância excedida após salvar aferição
expected: Ao salvar uma aferição com algum parâmetro fora da tolerância, um Toast de aviso (warn) é exibido com a mensagem "Tolerância excedida para o equipamento". Pitfall 7: o código atual mostra summary "Tolerância excedida" + detail "Um ou mais parâmetros estão fora da tolerância permitida." — equivalência de intenção, NÃO reprovar por redação. Apoio automatizado: VerificationUatFixTest (5/28 — tolerância), verde na W2.
result: pending
source: manual
coverage_id: UAT-01-S4

### 5. Aba Aferições condicional à permissão afericoes.view
expected: Usuário sem permissão `afericoes.view` não vê a aba "Aferições" no EquipmentDetailPage. Abas permanecem: Principal (0), Localização (1), Técnica (2), Arquivos (4), Manutenções (6), Logs (5). A numeração das abas após Aferições não quebra. Apoio automatizado: RbacRegressionTest (14/23 — 403 real para sem-permissão), verde na W2.
result: pending
source: manual
coverage_id: UAT-01-S5

### 6. Botão "Aferir" condicional à permissão afericoes.create
expected: Botão "Aferir" visível na página de pendentes e na aba de histórico apenas quando o usuário tem permissão `afericoes.create`. Apoio automatizado: RbacRegressionTest (14/23 — 403 real para sem-permissão), verde na W2.
result: pending
source: manual
coverage_id: UAT-01-S6

## Summary

total: 6
passed: 0
issues: 0
pending: 6
skipped: 0
blocked: 0

## Gaps

[none yet]
