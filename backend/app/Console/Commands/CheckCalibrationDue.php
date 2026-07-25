<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Services\CalibrationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CheckCalibrationDue extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'calibrations:check-due';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verificar calibrações próximas do vencimento (30 dias) e criar notificações in-app';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Verificando calibrações próximas do vencimento...');

        $dueSoon = app(CalibrationService::class)->checkDueSoon(30);

        if ($dueSoon->isEmpty()) {
            $this->info('Nenhuma calibração próxima do vencimento encontrada.');

            return 0;
        }

        $this->info("Encontradas {$dueSoon->count()} calibração(ões) próxima(s) do vencimento.");

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

        foreach ($dueSoon as $calibration) {
            $daysUntilDue = (int) now()->diffInDays($calibration->next_due_at);
            $equipmentName = $calibration->equipment?->name ?? 'N/A';

            foreach ($adminAndSupervisorUserIds as $userId) {
                DB::table('notifications')->insert([
                    'id' => (string) Str::uuid(),
                    'type' => 'App\Notifications\CalibrationDue',
                    'notifiable_type' => 'App\Models\User',
                    'notifiable_id' => $userId,
                    'data' => json_encode([
                        'calibration_id' => $calibration->id,
                        'equipment_name' => $equipmentName,
                        'next_due_at' => $calibration->next_due_at->format('d/m/Y'),
                        'days_until_due' => $daysUntilDue,
                        'message' => "Calibração de \"{$equipmentName}\" vence em {$daysUntilDue} dia(s).",
                    ]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $notificationsCreated++;
            }

            $this->info("Notificação criada para calibração {$calibration->id} — {$equipmentName} vence em {$daysUntilDue} dia(s)");
        }

        $this->info("{$notificationsCreated} notificação(ões) criada(s) para " . count($adminAndSupervisorUserIds) . " usuário(s).");

        return 0;
    }
}
