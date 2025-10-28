<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Empresa;
use App\Models\Solicitud;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use App\Mail\SolicitudRegistradaMail;

class SendTestSolicitudEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:test-solicitud {email=juniorluna6666@gmail.com}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Enviar email de prueba de notificación de solicitud registrada';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');

        $this->info("📧 Enviando email de prueba de solicitud registrada a: {$email}");

        try {
            // Preparar datos para el email de solicitud
            $emailData = [
                'ue_nombre' => "Jorge Marquez Luna",
                'solicitud_id' => 123,
                'descripcion_problema' => "Necesito apoyo financiero para expandir mi negocio y poder contratar a más personal para mejorar el servicio.",
                'empresa_razon_social' => "Soluciones Tecnológicas Marquez S.A. de C.V.",
                'empresa_rfc' => "MAMJ8510221A0",
                'fecha_registro' => now()->format('Y-m-d H:i:s'),
            ];

            // Enviar email
            Mail::to($email)->send(new SolicitudRegistradaMail($emailData));

            $this->info("✅ Email de solicitud enviado exitosamente a {$email}");
            $this->info("📋 Folio de prueba: #{$emailData['solicitud_id']}");
            $this->info("📊 Empresa: {$emailData['empresa_razon_social']}");
            $this->info("🎯 Estatus: RECIBIDA");

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("❌ Error al enviar email de solicitud: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
