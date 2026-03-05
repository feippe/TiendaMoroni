<?php
declare(strict_types=1);

namespace TiendaMoroni\Controllers;

class PrivacidadController
{
    public function show(array $params = []): void
    {
        view('privacidad/show', [
            'pageTitle' => 'Política de Privacidad | TiendaMoroni',
            'metaDesc'  => 'Conocé cómo recopilamos, usamos y protegemos tu información personal en TiendaMoroni, de acuerdo con la Ley N° 18.331 de Uruguay.',
            'canonical' => 'https://tiendamoroni.com/privacidad',
            'noindex'   => false,
        ]);
    }
}
