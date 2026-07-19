# Phase 4: Layout e Navegacao - Discussion Log

> **Audit trail only.** Do not use as input to planning, research, or execution agents.
> Decisions are captured in CONTEXT.md — this log preserves the alternatives considered.

**Date:** 2026-07-19
**Phase:** 04-layout-navegacao
**Areas discussed:** App Shell + Sidebar, Topbar + User Menu, Theme System, Navegacao por Modulos

---

## App Shell + Sidebar

| Option | Description | Selected |
|--------|-------------|----------|
| Fixa com rotulos (GitHub) | Sempre expandida, mostra icone + nome do modulo | |
| Colapsavel (Linear) | Pode recolher para icones apenas | ✓ |
| Hibrida | Fixa por padrao com toggle para recolher | |

**User's choice:** Colapsavel (Linear)
**Notes:** Sidebar colapsavel estilo Linear — expandida 240px com rotulos, colapsada 64px so icones. Mobile: overlay drawer.

---

## Topbar + User Menu

| Option | Description | Selected |
|--------|-------------|----------|
| Menu do Usuario | Avatar, nome, link para perfil e logout | ✓ |
| Dark/Light Toggle | Alternar entre temas | ✓ |
| Notificacoes | Badge de notificacoes (placeholder) | ✓ |
| Hamburger Toggle | Botao para recolher/expandir sidebar | ✓ |
| Breadcrumbs | Caminho de navegacao | |

**User's choice:** Menu do Usuario, Dark/Light Toggle, Notificacoes, Hamburger Toggle
**Notes:** Topbar contem: avatar + menu do usuario, dark/light toggle, notificacoes placeholder, hamburger. Sem breadcrumbs.

---

## Theme System

| Option | Description | Selected |
|--------|-------------|----------|
| Toggle manual com persistencia | Sol/lua na topbar, salvo no localStorage | ✓ |
| Auto + prefers-color-scheme + toggle | Respeita preferencia do SO como default | |
| So dark mode (sem toggle) | Apenas dark, sem opcao light | |

**User's choice:** Toggle manual com persistencia
**Notes:** Dark mode padrao, toggle manual salvo no localStorage. Sem deteccao automatica de prefers-color-scheme.

---

## Navegacao por Modulos

| Option | Description | Selected |
|--------|-------------|----------|
| Lista plana ordenada | Todos os modulos em ordem | |
| Agrupados por categoria | Modulos separados por categoria com cabecalhos | ✓ |
| Fixos + separador admin | Secao fixa + secao admin separada | |

**User's choice:** Agrupados por categoria
**Notes:** Categorias: Gestao (Equipamentos, Estoque), Operacoes (Movimentacoes, Emprestimos, Calibracoes, Afericoes, Manutencoes), Administracao (Usuarios, Logs, Configuracoes), Relatorios. Dashboard fixo no topo.

---

## Agent's Discretion

- Ordem exata dos modulos dentro de cada categoria
- Animacoes e transicoes da sidebar
- Altura exata da topbar
- Implementacao tecnica do toggle dark/light
- Nomes dos componentes (AppLayout, AppSidebar, AppTopbar)

## Deferred Ideas

- Breadcrumbs para navegacao profunda
- Atalhos de teclado para sidebar
- Notificacoes funcionais (placeholder agora)
- Deteccao de preferencia do SO para tema
