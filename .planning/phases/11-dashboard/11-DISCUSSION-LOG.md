# Phase 11: Dashboard - Discussion Log

> **Audit trail only.** Do not use as input to planning, research, or execution agents.
> Decisions are captured in CONTEXT.md — this log preserves the alternatives considered.

**Date:** 2026-07-27
**Phase:** 11-dashboard
**Areas discussed:** Layout do Dashboard, Métricas e KPIs, API do Dashboard, Interatividade, Período e Filtros

---

## Layout do Dashboard

| Option | Description | Selected |
|--------|-------------|----------|
| Widget Grid | Grid responsivo de widgets/cards com KPIs no topo e gráficos abaixo | ✓ |
| Abas por módulo | Uma aba para cada módulo | |
| Scroll vertical único | Seções em scroll vertical na mesma página | |

**User's choice:** Widget Grid
**Notes:** --

| Option | Description | Selected |
|--------|-------------|----------|
| 3 colunas flex | 3 colunas que reorganizam para 2 em tablet e 1 em mobile | ✓ |
| 2 colunas fixas | 2 colunas sempre | |
| Masonry | Alturas variáveis, layout tipo masonry | |

**User's choice:** 3 colunas flex
**Notes:** --

| Option | Description | Selected |
|--------|-------------|----------|
| Sim, linha de KPIs | Fileira horizontal com 4-6 cards de números grandes | ✓ |
| Só gráficos | Pula KPIs numéricos, vai direto para gráficos | |

**User's choice:** Sim, linha de KPIs
**Notes:** --

| Option | Description | Selected |
|--------|-------------|----------|
| Mensagem central + onboarding | 'Nenhum dado cadastrado' com links para cadastro | ✓ |
| Widgets com zero | Mostra widgets com valores zero | |

**User's choice:** Mensagem central + onboarding
**Notes:** --

## Métricas e KPIs

| Option | Description | Selected |
|--------|-------------|----------|
| 5 KPIs principais | Total Equipamentos, Calibrações a Vencer 30d, Empréstimos Ativos, Aferições Pendentes, Manutenções Abertas | ✓ |
| 4 KPIs (sem aferições) | Sem aferições | |
| 6 KPIs (add estoque crítico) | Adiciona itens com estoque crítico | |

**User's choice:** 5 KPIs principais
**Notes:** --

| Option | Description | Selected |
|--------|-------------|----------|
| 3 gráficos principais | Equipamentos por Categoria, Calibrações 6 meses, Movimentações Estoque | ✓ |
| 4+ gráficos (add empréstimos) | Adiciona empréstimos e manutenções | |
| 2 gráficos | Só categorias + calibrações | |

**User's choice:** 3 gráficos principais
**Notes:** --

| Option | Description | Selected |
|--------|-------------|----------|
| Sim, todos clicáveis | KPIs e gráficos navegam para listagens filtradas | ✓ |
| Só visual, sem navegação | Dashboard apenas para consulta | |

**User's choice:** Sim, todos clicáveis
**Notes:** --

## API do Dashboard

| Option | Description | Selected |
|--------|-------------|----------|
| Endpoint único | GET /api/v1/dashboard retorna tudo | ✓ |
| Múltiplos endpoints | Cada widget carrega independente | |

**User's choice:** Endpoint único
**Notes:** --

| Option | Description | Selected |
|--------|-------------|----------|
| Cache Redis com TTL | Cache de 5 minutos no Redis | ✓ |
| Sem cache | Consulta direta ao banco | |

**User's choice:** Cache Redis com TTL
**Notes:** --

| Option | Description | Selected |
|--------|-------------|----------|
| DashboardService | Classe DashboardService em backend/app/Services/ | ✓ |
| Controller direto | Lógica dentro do DashboardController | |

**User's choice:** DashboardService
**Notes:** --

| Option | Description | Selected |
|--------|-------------|----------|
| Estrutura nomeada | { kpis: {...}, charts: {...} } | ✓ |
| Payload plano | Todos os campos no mesmo nível | |

**User's choice:** Estrutura nomeada
**Notes:** --

## Interatividade

| Option | Description | Selected |
|--------|-------------|----------|
| Sim, drill-down nos gráficos | Clique navega para listagem filtrada | ✓ |
| Não, gráficos estáticos | Apenas tooltips e legendas | |

**User's choice:** Sim, drill-down nos gráficos
**Notes:** --

| Option | Description | Selected |
|--------|-------------|----------|
| Atualização manual apenas | Botão "Atualizar" | ✓ |
| Refresh periódico a cada 5 min | Polling automático | |

**User's choice:** Atualização manual apenas
**Notes:** --

| Option | Description | Selected |
|--------|-------------|----------|
| Skeleton screens | Esqueletos no lugar de cada widget | |
| Spinner centralizado | Spinner no centro até carregar | ✓ |

**User's choice:** Spinner centralizado
**Notes:** --

## Período e Filtros

| Option | Description | Selected |
|--------|-------------|----------|
| Últimos 12 meses | Período padrão anual | ✓ |
| Últimos 6 meses | Curto prazo | |
| Mês corrente | Apenas o mês atual | |

**User's choice:** Últimos 12 meses
**Notes:** --

| Option | Description | Selected |
|--------|-------------|----------|
| Sim, seletor de período no topo | DateRangePicker global | ✓ |
| Não, período fixo | Sem filtro global | |

**User's choice:** Sim, seletor de período no topo
**Notes:** --

| Option | Description | Selected |
|--------|-------------|----------|
| Só período | Apenas filtro de data | ✓ |
| Período + laboratório | Adiciona select de laboratório | |
| Período + laboratório + categoria | Filtros completos | |

**User's choice:** Só período
**Notes:** --

## the agent's Discretion

- Tipos de gráfico específicos (pizza vs rosca, barra vs empilhada, linha vs área)
- Cores e tema dos gráficos (seguir tema escuro Aura)

## Deferred Ideas

- Filtro por laboratório/local — fase futura (multiempresa)
- Dashboard customizável pelo usuário — versão futura
- Exportar dashboard como PDF/imagem — Phase 12 (Relatórios)
