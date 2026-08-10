Plano de Ação - Revisão Geral do LabControl
Este plano estabelece a estratégia detalhada para implementar as correções e melhorias solicitadas no projeto LabControl, divididas nos quatro pilares indicados.

1. Cronograma de Execução e Escopo por Pilar
PILAR 1: Testes e Integridade da Base de Dados (Backend / Database / UI Sync)
Status Sync & FK Constraints: Garantir que o status do equipamento seja alterado apropriadamente e em transações seguras. Exemplo: status muda para "Emprestado" ao ativar empréstimo, status muda para "Manutenção" ao iniciar ordem de manutenção. Impedir a exclusão física de equipamentos atrelados a empréstimos ativos (verificar restrição na API/Controller de exclusão).
Métricas de Estoque: Validar se a movimentação no estoque reflete o saldo real de forma transacional e aciona alertas de estoque mínimo no frontend.
Resolução de Bugs Críticos:
Corrigir a falta de tag de fechamento no arquivo 
InventoryItemFormPage.vue
 (conforme screenshot enviado com erro Element is missing end tag).
Corrigir bugs de alinhamento e botões de ação na tabela de Equipamentos.
PILAR 2: Harmonização e Responsividade da Interface (UI/UX Refactoring)
Remoção da Licença Inválida: Identificar a origem do toast/badge de "Invalid PrimeUI License" e removê-lo (comumente removido ou silenciado via CSS ou desativação de marca nos componentes PrimeVue).
Cabeçalhos de Tabela e Comandos:
Posicionar botões de ação como "Novo Equipamento", "Nova Manutenção", "Novo Empréstimo" e "Nova Movimentação" dentro do cabeçalho (Toolbar ou header) das respectivas tabelas, alinhando-os simetricamente aos filtros e barra de busca de maneira responsiva.
Formulários e Modais:
Revisar modais de formulário (Manutenção, Movimentações, etc.) garantindo rolagem interna adequada, campos com larguras proporcionais, margens consistentes e uso da paleta de cores escura refinada.
Dashboard e Gráficos:
Ajustar o dimensionamento dos gráficos no Dashboard para ocupar espaços proporcionais e redimensionar graciosamente com a largura da tela.
PILAR 3: Limpeza de Código e Organização (Clean Code)
Arquivos Obsoletos:
Remover arquivos temporários da raiz do backend, como 
debug_login.php
 e 
tmp_test.php
.
Validar a árvore de componentes e excluir scripts ou estilos não utilizados.
Dead Code: Remover logs de desenvolvimento e variáveis declaradas órfãs.
PILAR 4: Documentação para Apresentação no GitHub
Criar um README.md de nível profissional com a descrição da plataforma modular de gestão laboratorial, contendo badges de status, stack de tecnologia (Laravel 12, Vue 3, PostgreSQL, Dexie, PrimeVue), guias passo a passo de instalação e execução (Docker/Local) e seções dedicadas para capturas de tela.
2. Perguntas Abertas / Alinhamento com o Usuário
IMPORTANT

Modo de Silenciamento da Licença: Para eliminar o aviso "Invalid PrimeUI License", podemos aplicar uma regra CSS global para ocultar o elemento flutuante correspondente?
Cenário de Deleção em Cascata: No backend, ao tentar excluir um equipamento que possui empréstimo ativo, você prefere que a API retorne um erro HTTP 422 descritivo (ex: "Não é possível excluir um equipamento com empréstimo pendente"), ou prefere uma deleção lógica suave (soft delete) mantendo a integridade referencial?
3. Plano de Verificação
Testes Automatizados
Executar os testes PHPUnit existentes no backend para certificar que as regras de negócios e a integridade continuam válidas após ajustes de validação de FK: php artisan test ou vendor/bin/phpunit na pasta do backend.
Validar a compilação do TypeScript no frontend após remoção de dead code e correção de sintaxe: npm run typecheck
Testes Manuais
Cadastrar movimentação de estoque de saída até atingir o estoque mínimo e validar se o Toast de Alerta de Estoque Crítico é exibido em tela.
Abrir a modal de Nova Ordem de Manutenção em resoluções mobile e verificar se há rolagem interna fluida sem quebrar a tela.