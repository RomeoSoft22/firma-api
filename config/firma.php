<?php

return [
    /*
     * Clave de administración para Historial y Configuración (se define en .env).
     * Si está vacía, el acceso de administración queda deshabilitado (nadie puede entrar).
     */
    'clave_admin' => env('ADMIN_CLAVE', ''),

    // Duración de la sesión de administración, renovada con cada uso.
    'sesion_minutos' => (int) env('ADMIN_SESION_MINUTOS', 60),

    // Intentos fallidos permitidos por IP antes de bloquearla durante "bloqueo_minutos".
    'intentos' => (int) env('ADMIN_INTENTOS', 5),
    'bloqueo_minutos' => (int) env('ADMIN_BLOQUEO_MINUTOS', 15),

    // Tope global de intentos fallidos (todas las IP) en la misma ventana: frena ataques distribuidos.
    'intentos_globales' => (int) env('ADMIN_INTENTOS_GLOBALES', 30),
];
