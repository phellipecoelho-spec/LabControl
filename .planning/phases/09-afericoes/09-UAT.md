---
status: testing
phase: 09-afericoes
source: 09-01-SUMMARY.md, 09-02-SUMMARY.md, 09-VERIFICATION.md
started: 2026-07-25T23:55:00Z
updated: 2026-07-25T23:55:00Z
---

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
expected: Página `/verifications` carrega DataTable com colunas (Equipamento, Patrimônio, Nº Série, Categoria, Frequência, Última Aferição, Ações). Loading skeleton durante carregamento. Estado vazio "Todos os equipamentos estão em dia" quando sem pendentes.
result: [pending]

### 2. Formulário de Aferição — campos dinâmicos de parâmetros
expected: Dialog "Nova Aferição" renderiza um InputNumber por parâmetro do template, com label, unidade e faixa de tolerância (min–max) exibidos. Select de equipamento funcional. TextArea para observações.
result: [pending]

### 3. Aba Aferições no EquipmentDetailPage — timeline com parâmetros expansíveis
expected: Aba "Aferições" (tab 3) no detalhe do equipamento mostra DataTable paginada com: Data, Operador, # Parâmetros, indicador "Fora do Intervalo". Linhas expansíveis mostram detalhes dos parâmetros com tags coloridas (verde dentro do intervalo, vermelho fora). Botão "Aferir" no topo da aba.
result: [pending]

### 4. Alerta de tolerância excedida após salvar aferição
expected: Ao salvar uma aferição com algum parâmetro fora da tolerância, um Toast de aviso (warn) é exibido com a mensagem "Tolerância excedida para o equipamento".
result: [pending]

### 5. Aba Aferições condicional à permissão afericoes.view
expected: Usuário sem permissão `afericoes.view` não vê a aba "Aferições" no EquipmentDetailPage. Abas permanecem: Principal (0), Localização (1), Técnica (2), Arquivos (4), Manutenções (6), Logs (5). A numeração das abas após Aferições não quebra.
result: [pending]

### 6. Botão "Aferir" condicional à permissão afericoes.create
expected: Botão "Aferir" visível na página de pendentes e na aba de histórico apenas quando o usuário tem permissão `afericoes.create`.
result: [pending]

## Summary

total: 6
passed: 0
issues: 0
pending: 6
skipped: 0

## Gaps

[none yet]