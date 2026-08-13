<?php

namespace App\Console\Commands;

use App\Models\Notification;
use App\Models\Rental;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CheckRentalDeadlines extends Command
{
    protected $signature = 'rentame:check-rentals';
    protected $description = 'Verifica contratos de renta próximos a vencer y vencidos para generar notificaciones';

    public function handle(): int
    {
        $this->info('Verificando fechas límite de contratos de renta...');

        $today = Carbon::today();
        $activeRentals = Rental::where('status', 'active')
            ->with(['customer', 'asset', 'user'])
            ->get();

        $expiringCount = 0;
        $overdueCount = 0;

        foreach ($activeRentals as $rental) {
            $user = $rental->user;
            if (! $user) continue;

            $daysBefore = (int) Setting::get($user->id, 'notification_days_before', 3);
            $endDate = Carbon::parse($rental->end_date);
            $daysRemaining = (int) $today->diffInDays($endDate, false);

            // 1. Contrato por vencer
            if ($daysRemaining >= 0 && $daysRemaining <= $daysBefore) {
                $exists = Notification::where('user_id', $user->id)
                    ->where('type', 'rental_expiring')
                    ->whereJsonContains('data->rental_id', $rental->id)
                    ->whereDate('created_at', $today)
                    ->exists();

                if (! $exists) {
                    $dayText = $daysRemaining === 0 ? 'hoy' : "en {$daysRemaining} día(s)";
                    Notification::create([
                        'user_id' => $user->id,
                        'type' => 'rental_expiring',
                        'title' => "Contrato por Vencer: {$rental->folio}",
                        'message' => "La renta del bien '{$rental->asset?->name}' con el cliente '{$rental->customer?->name}' vence {$dayText} ({$rental->end_date}).",
                        'data' => [
                            'rental_id' => $rental->id,
                            'folio' => $rental->folio,
                            'days_remaining' => $daysRemaining,
                        ],
                    ]);
                    $expiringCount++;
                }
            }

            // 2. Contrato vencido (overdue)
            if ($daysRemaining < 0) {
                $daysOverdue = abs($daysRemaining);
                $exists = Notification::where('user_id', $user->id)
                    ->where('type', 'rental_overdue')
                    ->whereJsonContains('data->rental_id', $rental->id)
                    ->whereDate('created_at', $today)
                    ->exists();

                if (! $exists) {
                    Notification::create([
                        'user_id' => $user->id,
                        'type' => 'rental_overdue',
                        'title' => "¡Contrato Vencido!: {$rental->folio}",
                        'message' => "El contrato de renta '{$rental->asset?->name}' para '{$rental->customer?->name}' venció hace {$daysOverdue} día(s).",
                        'data' => [
                            'rental_id' => $rental->id,
                            'folio' => $rental->folio,
                            'days_overdue' => $daysOverdue,
                        ],
                    ]);
                    $overdueCount++;
                }
            }
        }

        $this->info("Notificaciones generadas: {$expiringCount} por vencer, {$overdueCount} vencidas.");

        return Command::SUCCESS;
    }
}
