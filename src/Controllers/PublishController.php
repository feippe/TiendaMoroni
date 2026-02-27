<?php
declare(strict_types=1);

namespace TiendaMoroni\Controllers;

use TiendaMoroni\Core\Database as DB;
use TiendaMoroni\Core\Mailer;
use TiendaMoroni\Core\Session;

class PublishController
{
    // ── GET /publicar-gratis ──────────────────────────────────────────────────

    public function show(array $params = []): void
    {
        view('publish/show', [
            'pageTitle' => 'Publicá tus artesanías gratis | TiendaMoroni',
            'metaDesc'  => '¿Creás productos artesanales inspirados en la fe? Publicá gratis en TiendaMoroni y llegá a toda la comunidad en Uruguay.',
            'canonical' => 'https://tiendamoroni.com/publicar-gratis',
            'errors'    => [],
            'old'       => [],
        ]);
    }

    // ── POST /publicar-gratis ─────────────────────────────────────────────────

    public function store(array $params = []): void
    {
        verifyCsrf();

        $name     = sanitize(post('name'));
        $lastname = sanitize(post('lastname'));
        $phone    = sanitize(post('phone'));
        $email    = sanitize(post('email'));
        $comments = sanitize(post('comments'));

        $errors = [];

        if ($name === '')      $errors['name']     = 'El nombre es obligatorio.';
        if ($lastname === '')  $errors['lastname']  = 'El apellido es obligatorio.';
        if ($phone === '')     $errors['phone']     = 'El número de contacto es obligatorio.';
        if ($email === '')     $errors['email']     = 'El email es obligatorio.';
        elseif (!filter_var($email, FILTER_VALIDATE_EMAIL))
                               $errors['email']     = 'El email no tiene un formato válido.';

        if ($errors) {
            view('publish/show', [
                'pageTitle' => 'Publicá tus artesanías gratis | TiendaMoroni',
                'metaDesc'  => '¿Creás productos artesanales inspirados en la fe? Publicá gratis en TiendaMoroni y llegá a toda la comunidad en Uruguay.',
                'canonical' => 'https://tiendamoroni.com/publicar-gratis',
                'errors'    => $errors,
                'old'       => compact('name', 'lastname', 'phone', 'email', 'comments'),
            ]);
            return;
        }

        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? null;

        // Save to DB
        DB::query(
            'INSERT INTO vendor_contacts (name, lastname, phone, email, comments, ip_address)
             VALUES (:name, :lastname, :phone, :email, :comments, :ip)',
            [
                ':name'     => $name,
                ':lastname' => $lastname,
                ':phone'    => $phone,
                ':email'    => $email,
                ':comments' => $comments ?: null,
                ':ip'       => $ip,
            ]
        );

        // Send notification email
        $date    = date('d/m/Y H:i');
        $htmlBody = "
<h2>Nuevo artesano interesado — TiendaMoroni</h2>
<table cellpadding='6' style='font-family:sans-serif;font-size:14px;'>
  <tr><td><strong>Nombre:</strong></td><td>{$name} {$lastname}</td></tr>
  <tr><td><strong>Teléfono:</strong></td><td>{$phone}</td></tr>
  <tr><td><strong>Email:</strong></td><td>{$email}</td></tr>
  <tr><td><strong>Comentarios:</strong></td><td>" . nl2br(htmlspecialchars($comments)) . "</td></tr>
  <tr><td><strong>Fecha:</strong></td><td>{$date}</td></tr>
  <tr><td><strong>IP:</strong></td><td>{$ip}</td></tr>
</table>";

        $textBody = "Nuevo artesano interesado — TiendaMoroni\n\n"
            . "Nombre: {$name} {$lastname}\n"
            . "Teléfono: {$phone}\n"
            . "Email: {$email}\n"
            . "Comentarios: {$comments}\n"
            . "Fecha: {$date}\n"
            . "IP: {$ip}";

        Mailer::send(
            'gabriel@feippe.com',
            'Gabriel Feippe',
            'Nuevo artesano interesado — TiendaMoroni',
            $htmlBody,
            $textBody
        );

        Session::flash('success', '¡Gracias! Te contactaremos pronto.');
        redirect('/publicar-gratis#formulario');
    }
}
