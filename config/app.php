<?php

declare(strict_types=1);

/**
 * Configuración general de la aplicación.
 *
 * Las versiones de las librerías del navegador se definen solo aquí; el
 * layout (app/Views/layouts/main.php) arma con ellas las direcciones del CDN
 * y la página "Sistema" del administrador las muestra.
 */

return [
    'nombre'     => 'Encuestas Cali',
    'version'    => '1.1.0',
    'zona_horaria' => 'America/Bogota',

    'frontend' => [
        'bootstrap' => '5.3.3',
        'leaflet'   => '1.9.4',
    ],
];
