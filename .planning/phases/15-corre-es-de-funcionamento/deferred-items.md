# Deferred Items — Phase 15 (Correções de Funcionamento)

Out-of-scope discoveries logged per executor deviation rules (scope boundary).
These are NOT fixed by the 15-02 plan — they belong to the frontend integration /
phase verification plan (VALIDATION.md Manual-Only).

## 2026-08-09 — Pre-existing frontend typecheck errors (wave 2)

`npm run typecheck` (frontend/) reports 7 errors in files NOT modified by plan 15-02
nor by the prior session's working-tree changes (status loaned / tag fix):

- `src/components/auth/PasswordInput.vue:9` — TS2345: `string | undefined` not assignable to `string`
- `src/modules/equipment/components/EquipmentLogsSection.vue:96` — TS2322: `string | undefined` not assignable to `string`
- `src/modules/loans/components/LoanCreateDialog.vue:61,79` — TS2322: `string` not assignable to `Date | Date[] | ...`
- `src/modules/loans/components/LoanCreateDialog.vue:264,267` — TS2358: `instanceof` left-hand side invalid
- `src/router/index.ts:29` — TS2339: Property `some` does not exist on type `{}`

Verified: none of these files were touched by 15-02 (seeders/tests only) nor by the
working-tree frontend changes (Equipment pages + InventoryItemFormPage pass cleanly).
Pre-existing — logged for the frontend verification plan to address.
