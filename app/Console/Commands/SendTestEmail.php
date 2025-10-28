<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Solicitud;
use App\Models\SolicitudAccion;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use App\Mail\AccionRegistradaMail;

class SendTestEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:test {email=juniorluna6666@gmail.com} {--resuelve=false : Si la acción resuelve la solicitud}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Enviar email de prueba de notificación de acción';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email') ?? 'juniorluna6666@gmail.com';
        $resuelve = $this->option('resuelve') !== 'false';

        $this->info("📧 Enviando email de prueba a: {$email}");
        $this->info("🔄 ¿Resuelve solicitud?: " . ($resuelve ? 'SÍ' : 'NO'));

        try {
            // Preparar datos para el email
            $emailData = [
                'ue_nombre' => "Jorge Marquez Luna",
                'solicitud_id' => 2,
                'descripcion_problema' => "Este año quiero mas aguinaldo.",
                'entidad_gestionada' => "Secretaria de aguinaldos",
                'descripcion_accion' => "Se contacto y se pasaron los documentos a la secretaria de aguinaldos atra vez de email",
                'fecha_inicio' => "2025-01-01",
                'fecha_fin' => "2025-01-02",
                'resuelve_solicitud' => $resuelve,
            ];

            // Enviar email
            Mail::to($email)->send(new AccionRegistradaMail($emailData));

            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Error al enviar email: " . $e->getMessage());
            return 1;
        }
    }
}
