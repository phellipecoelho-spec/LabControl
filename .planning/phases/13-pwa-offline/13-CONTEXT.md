# Phase 13: PWA e Offline — Context

**Gathered:** 2026-07-27
**Status:** Ready for planning

<domain>
## Phase Boundary

Tornar o LabControl funcional sem conexão com internet e instalável como aplicativo desktop/mobile via PWA. Isso inclui:

- Estratégia de cache para assets estáticos e dados da API
- Sincronização automática de operações offline quando a conexão for restabelecida
- Experiência completa offline (navegação, consulta e criação/edição de registros)
- Instalação como aplicativo via manifesto PWA
- Detecção e resolução de conflitos de sincronização

Não inclui: aplicativo mobile nativo (Capacitor) — v2.

</domain>

<decisions>
## Implementation Decisions

### 1. Estratégia de Cache Offline
- **D-01:** Cache de assets estáticos (JS, CSS, imagens, fontes) via Service Worker + Cache API
- **D-02:** Dados da API armazenados em IndexedDB para consulta offline
- **D-03:** Operações de criação/edição offline enfileiradas via Background Sync API
- **D-04:** Estratégia de leitura: network-first com fallback para cache (dados sempre tentam rede primeiro; se offline, servem do IndexedDB)

### 2. Sincronização Automática
- **D-05:** Sincronização automática em background ao detectar restauração de conexão
- **D-06:** Indicador visual discreto no topo exibindo "N operações pendentes"
- **D-07:** Botão "Sincronizar" para sincronização manual forçada
- **D-08:** Indicador desaparece quando todas as pendências são sincronizadas

### 3. Experiência Offline
- **D-09:** Experiência completa offline — todas as funcionalidades do app funcionam com dados cacheados
- **D-10:** Dashboard exibe últimos dados sincronizados
- **D-11:** Operações de criação/edição enfileiradas para sincronização posterior
- **D-12:** Sincronização ocorre em background sem intervenção do usuário

### 4. Instalação (Manifest)
- **D-13:** Nome do app: `LabControl`
- **D-14:** Nome curto: `LabControl`
- **D-15:** Ícones: gerados a partir de template com tema indigo (`#6366f1`)
- **D-16:** Splash screen: fundo `#0f172a` (tema escuro) com logo centralizado
- **D-17:** Display mode: `standalone` (sem barra de navegação do navegador)
- **D-18:** Theme color: escuro (`#0f172a`)

### 5. Conflitos de Sincronização
- **D-19:** Detecção automática de conflitos (mesmo registro editado offline e online)
- **D-20:** Resolução manual via diff visual — usuário escolhe qual versão manter
- **D-21:** Se não houver conflito (campos diferentes), merge automático
- **D-22:** Última sincronização vence como fallback se usuário não resolver em N dias

### the agent's Discretion
- Implementação técnica detalhada (versão do `vite-plugin-pwa`, configuração Workbox, estrutura do IndexedDB)
- Design do indicador visual de pendências
- Design do diff visual para resolução de conflitos

</decisions>

<canonical_refs>
## Canonical References

**Downstream agents MUST read these before planning or implementing.**

### Requirements
- `.planning/REQUIREMENTS.md` — PWA-01 (offline sync), PWA-02 (installable app)

### Project Decisions
- `.planning/PROJECT.md` — Stack decisions (Vue 3 + Vite + PrimeVue), PWA vs native app decision
- `.planning/STATE.md` — Current project state, prior phase decisions

### Codebase Analysis
- `.planning/codebase/CONCERNS.md` §184-185 — Gap analysis: no PWA packages or manifest configured
- `.planning/codebase/STRUCTURE.md` — Frontend structure (Vite config, router, services)

### Existing Code
- `frontend/vite.config.ts` — Current Vite configuration (proxy, aliases) — PWA plugin will be added here
- `frontend/src/services/api.ts` — Axios instance with Sanctum interceptor — may need offline-aware wrapper
- `frontend/src/router/index.ts` — Router configuration — offline detection middleware

</canonical_refs>

<code_context>
## Existing Code Insights

### Reusable Assets
- Vite 8.1.5 + Vue 3.5 — suporte nativo a PWA via `vite-plugin-pwa` (Workbox)
- PrimeVue 5 — componentes de UI (Dialog, Toast, Badge) reutilizáveis para modais de conflito e indicadores
- Pinia 4 — stores podem ser adaptadas para persistência offline (IndexedDB mirror)
- Vue Router 5 — navigation guards para detectar estado offline

### Established Patterns
- Sanctum SPA com cookies de sessão — impacto offline: sessão expira, operações offline acumulam token de sessão antigo
- Axios interceptors com tratamento de erro — base para criar wrapper offline-aware
- API REST versionada (`/api/v1/`) — facilita cache por endpoint

### Integration Points
- `vite.config.ts` — adicionar `vite-plugin-pwa` com config Workbox
- `src/main.ts` — registrar service worker
- `src/services/api.ts` — adicionar interceptor para enfileirar requisições offline
- `src/App.vue` ou `AppLayout.vue` — adicionar indicador visual de pendências
- Novas stores Pinia para fila de sincronização e estado de conexão

</code_context>

<specifics>
## Specific Ideas

- Referência de design: Linear, GitHub, Notion — todos têm indicadores de sincronização discretos
- Experiência similar ao Google Docs offline: usuário não percebe a transição online/offline

</specifics>

<deferred>
## Deferred Ideas

- Aplicativo mobile nativo via Capacitor — fase futura v2 (já documentado em REQUIREMENTS.md como MOBL-01)

</deferred>

---

*Phase: 13-PWA-e-Offline*
*Context gathered: 2026-07-27*
