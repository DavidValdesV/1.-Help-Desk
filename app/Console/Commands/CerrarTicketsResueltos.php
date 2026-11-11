<?php

namespace App\Console\Commands;

use App\Services\TicketService;
use Illuminate\Console\Command;

class CerrarTicketsResueltos extends Command
{
    /**
     * @var string
     */
    protected $signature = 'tickets:cerrar-resueltos';

    /**
     * @var string
     */
    protected $description = 'Cierra automáticamente los tickets resueltos en los que el cliente no respondió dentro del plazo configurado';

    public function handle(TicketService $ticketService): int
    {
        $cerrados = $ticketService->cerrarTicketsResueltosSinRespuesta();

        $this->info("Tickets cerrados automáticamente: {$cerrados}");

        return self::SUCCESS;
    }
}

