# Discussion Log — Phase 13: PWA e Offline

**Date:** 2026-07-27
**Mode:** discuss (default)

## Areas Discussed

### 1. Estratégia de Cache Offline
- **Options presented:** A) Cache API network-first (read-only offline) / B) IndexedDB + Background Sync (full offline)
- **Selection:** B — IndexedDB + Background Sync com operações offline enfileiradas
- **Notes:** Usuário quer operações completas offline, não apenas leitura

### 2. Sincronização Automática
- **Options presented:** A) Silenciosa automática / B) Manual / C) Híbrida
- **Selection:** C — Automática em background + indicador visual + botão "Sincronizar" manual

### 3. Experiência Offline
- **Options presented:** A) Read-only / B) Leitura + criação enfileirada / C) Completa
- **Selection:** C — Experiência completa offline com dados cacheados, operações enfileiradas

### 4. Instalação (Manifest)
- **Decisions:**
  - Nome: LabControl
  - Nome curto: LabControl
  - Ícones: template com tema indigo (#6366f1)
  - Splash: fundo #0f172a
  - Display: standalone
  - Tema: escuro

### 5. Conflitos de Sincronização
- **Options presented:** A) LWW / B) Merge automático / C) Detecção + resolução manual
- **Selection:** C — Detecção automática com diff visual e resolução manual

## Deferred Ideas
- Aplicativo mobile nativo via Capacitor — v2

## Decisions Summary
22 decisions captured (D-01 through D-22) in 13-CONTEXT.md
