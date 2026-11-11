<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cierre automático de tickets resueltos
    |--------------------------------------------------------------------------
    |
    | Minutos que un ticket permanece en estado "resuelto" esperando respuesta
    | del cliente. Si en ese lapso el cliente no publica ningún comentario,
    | el sistema cierra el ticket automáticamente.
    |
    */

    'auto_cierre_minutos' => (int) env('TICKET_AUTO_CIERRE_MINUTOS', 10),

];

