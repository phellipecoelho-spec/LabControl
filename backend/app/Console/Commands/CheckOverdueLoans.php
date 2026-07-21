<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Services\LoanService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CheckOverdueLoans extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'loans:check-overdue';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for overdue loans and create in-app notifications for admins and supervisors';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Checking for overdue loans...');

        $overdueLoans = app(LoanService::class)->checkOverdue();

        if ($overdueLoans->isEmpty()) {
            $this->info('Nenhum empréstimo atrasado encontrado.');

            return 0;
        }

        $this->info("Found {$overdueLoans->count()} overdue loan(s).");

        // Buscar usuários com role admin ou supervisor
        $adminAndSupervisorUserIds = Role::whereIn('slug', ['admin', 'supervisor'])
            ->with('users:id')
            ->get()
            ->flatMap(fn (Role $role) => $role->users->pluck('id'))
            ->unique()
            ->values()
            ->toArray();

        if (empty($adminAndSupervisorUserIds)) {
            $this->warn('Nenhum usuário admin ou supervisor encontrado para notificar.');

            return 0;
        }

        $notificationsCreated = 0;

        foreach ($overdueLoans as $loan) {
            $daysOverdue = (int) $loan->expected_return_at->diffInDays(now());
            $borrowerName = $loan->borrower?->name ?? 'N/A';

            foreach ($adminAndSupervisorUserIds as $userId) {
                DB::table('notifications')->insert([
                    'id' => (string) Str::uuid(),
                    'type' => 'App\Notifications\LoanOverdue',
                    'notifiable_type' => 'App\Models\User',
                    'notifiable_id' => $userId,
                    'data' => json_encode([
                        'loan_id' => $loan->id,
                        'borrower_name' => $borrowerName,
                        'equipment_count' => $loan->equipment->count(),
                        'expected_return_at' => $loan->expected_return_at->format('d/m/Y'),
                        'days_overdue' => $daysOverdue,
                        'message' => "Empréstimo #{$loan->getKey()} está atrasado há {$daysOverdue} dia(s). Tomador: {$borrowerName}",
                    ]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $notificationsCreated++;
            }

            $this->info("Notificação criada para empréstimo {$loan->getKey()} — {$daysOverdue} dia(s) em atraso");
        }

        $this->info("{$notificationsCreated} notificação(ões) criada(s) para " . count($adminAndSupervisorUserIds) . " usuário(s).");

        return 0;
    }
}
