# Phase 12: Relatórios - Discussion Log

> **Audit trail only.** Do not use as input to planning, research, or execution agents.
> Decisions are captured in CONTEXT.md — this log preserves the alternatives considered.

**Date:** 2026-07-27
**Phase:** 12-relatorios
**Areas discussed:** Estrutura dos relatórios, Bibliotecas e formato, Experiência de download, Filtros e parâmetros

---

## Estrutura dos Relatórios

| Option | Description | Selected |
|--------|-------------|----------|
| Página centralizada única | Uma página /reports com lista de todos os relatórios disponíveis | |
| Dentro de cada módulo | Cada módulo com seus próprios botões de exportar na lista/detalhe | |
| Misto (Recomendado) | Página centralizada como hub + atalhos de exportação nas listas | ✓ |

**User's choice:** Misto (Recomendado)
**Notes:** Hub central + sem atalhos nos módulos por enquanto.

| Option | Description | Selected |
|--------|-------------|----------|
| Equipamentos | Lista completa de equipamentos | ✓ |
| Calibrações | Agenda de calibrações | ✓ |
| Movimentações de Estoque | Entradas, saídas, saldo por período | ✓ |
| Empréstimos | Empréstimos ativos, histórico | |
| Aferições | Registro de aferições diárias | |
| Manutenções | Ordens de manutenção | |
| Dashboard Export | Exportar dados dos gráficos do dashboard | ✓ |

**User's choice:** Equipamentos, Calibrações, Movimentações de Estoque, Dashboard Export
**Notes:** 4 relatórios pré-definidos. Empréstimos, Aferições e Manutenções diferidos.

| Option | Description | Selected |
|--------|-------------|----------|
| Tabular simples (Recomendado) | Tabelas com cabeçalho, dados em linhas, totalizadores | ✓ |
| Tabular com agrupamentos | Dados agrupados por categoria com subtotais | |
| Com gráficos | Inclui gráficos ECharts nos PDFs | |

**User's choice:** Tabular simples (Recomendado)

## Bibliotecas e Formato

| Option | Description | Selected |
|--------|-------------|----------|
| barryvdh/laravel-dompdf (Recomendado) | Renderiza HTML+CSS para PDF. Sem dependências externas. | ✓ |
| barryvdh/laravel-snappy | Baseado em wkhtmltopdf. Exige binário externo. | |

**User's choice:** barryvdh/laravel-dompdf (Recomendado)

| Option | Description | Selected |
|--------|-------------|----------|
| maatwebsite/laravel-excel (Recomendado) | Baseado em PhpSpreadsheet. Padrão de mercado. | ✓ |
| PhpSpreadsheet direto | Sem wrapper Laravel. Mais verboso. | |
| CSV apenas | Pular Excel. CSV resolve. | |

**User's choice:** maatwebsite/laravel-excel (Recomendado)

| Option | Description | Selected |
|--------|-------------|----------|
| Server-side 100% (Recomendado) | Laravel gera o arquivo, retorna StreamedResponse | ✓ |
| Client-side para CSV/Excel | Geração no browser com dados JSON | |

**User's choice:** Server-side 100% (Recomendado)

## Experiência de Download

| Option | Description | Selected |
|--------|-------------|----------|
| Dropdown por relatório (Recomendado) | SplitButton com PDF, XLSX, CSV | ✓ |
| Botão único + modal | Abre diálogo com opções antes de gerar | |
| Abas por formato | Três abas no hub: PDF | XLSX | CSV | |

**User's choice:** Dropdown por relatório (Recomendado)

| Option | Description | Selected |
|--------|-------------|----------|
| Download direto (Recomendado) | Loading spinner → geração → download automático | ✓ |
| Fila assíncrona | Job na fila → notificação → link temporário | |

**User's choice:** Download direto (Recomendado)

| Option | Description | Selected |
|--------|-------------|----------|
| Apenas no hub (Recomendado) | Relatórios gerados apenas pela página /reports | ✓ |
| Botão exportar nas listas | Cada lista ganha botão de exportação | |

**User's choice:** Apenas no hub (Recomendado)

## Filtros e Parâmetros

| Option | Description | Selected |
|--------|-------------|----------|
| Obrigatório | Data inicial + final obrigatórias | |
| Opcional | Se não informar, traz todos os registros | ✓ |
| Período + predefinidos | Além do customizado, atalhos: Hoje, 7 dias, 30 dias | |

**User's choice:** Opcional

| Option | Description | Selected |
|--------|-------------|----------|
| Período + padrão (Recomendado) | Apenas período e status geral | ✓ |
| Filtros avançados por módulo | Cada relatório com filtros específicos | |

**User's choice:** Período + padrão (Recomendado)

| Option | Description | Selected |
|--------|-------------|----------|
| Sidebar de filtros (Recomendado) | Painel lateral com período e status | ✓ |
| Filtros inline no topo | Campos acima da lista | |
| Modal antes da exportação | Diálogo ao clicar no relatório | |

**User's choice:** Sidebar de filtros (Recomendado)

---

## Deferred Ideas

- Relatório de Empréstimos, Aferições e Manutenções (futuras fases)
- Atalhos de exportação nas listas dos módulos
- Fila assíncrona para relatórios grandes
