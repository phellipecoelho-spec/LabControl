# Phase 5: Equipamentos - Discussion Log

**Date:** 2026-07-19
**Status:** Context gathered, ready for planning

## Areas Discussed

### 1. Modelo de Dados
- **Pergunta:** Como estruturar dados de equipamento no banco?
  - Opções: Tabela única / Split principal+especificações / JSONB
  - **Decisão:** Tabela única com todos os campos
- **Pergunta:** Categorias planas ou hierárquicas?
  - **Decisão:** Categorias planas (tabela `categories`)
- **Pergunta:** Fabricantes e fornecedores?
  - Opções: Tabelas separadas / Tabela única com type / Campos inline
  - **Decisão:** Tabelas separadas (`manufacturers`, `suppliers`)
- **Pergunta:** Localização?
  - **Decisão:** Campo de texto simples no equipamento

### 2. Interface de Cadastro
- **Pergunta:** Formato da interface?
  - Opções: Página dedicada com abas / DataTable+Dialog / Row expansion
  - **Decisão:** Página dedicada com abas
- **Pergunta:** Quais abas?
  - **Decisão:** 5 abas: Principal, Localização, Técnica, Arquivos, Logs
- **Pergunta:** Campos obrigatórios no cadastro?
  - **Decisão:** Nome, categoria, fabricante, série, localização
- **Pergunta:** Layout da lista?
  - **Decisão:** DataTable com colunas de identificação + status

### 3. Upload de Anexos
- **Pergunta:** Tipos e limites?
  - **Decisão:** Apenas fotos, 5MB por arquivo
  - Manuais adiados para fase futura

### 4. Histórico de Alterações
- Reutilizar LogsActivity trait existente
- Visualização na aba "Logs" com Timeline filtrada

## Deferred Ideas
- Upload de manuais (fase futura)
- Categorias hierárquicas
- QR Code para equipamentos
- Tabela de locais
- Importação em lote via planilha

---

*Phase: 05-Equipamentos*
*Discussion recorded: 2026-07-19*