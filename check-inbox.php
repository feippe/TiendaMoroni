<?php
// Verifica el buzón IMAP buscando bounces
$host = 'priva10.privatednsorg.com';
$port = 993;
$user = 'hola@tiendamoroni.com';
$pass = 'TiendaMoroni.1365';

$ctx = stream_context_create(['ssl' => ['verify_peer' => false, 'verify_peer_name' => false]]);
$conn = stream_socket_client("ssl://{$host}:{$port}", $err, $errstr, 10, STREAM_CLIENT_CONNECT, $ctx);

if (!$conn) {
    echo "No conecta IMAP: {$errstr}\n";
    exit(1);
}

$read = function() use ($conn) {
    $out = '';
    while ($line = fgets($conn, 1024)) {
        $out .= $line;
        if (preg_match('/^[A-Z]\d+ (OK|NO|BAD)/m', $line)) break;
    }
    return $out;
};

$write = function($cmd) use ($conn) {
    fwrite($conn, $cmd . "\r\n");
};

echo "Greeting: " . fgets($conn, 512);

$write("A1 LOGIN {$user} {$pass}");
$resp = $read();
echo "Login: " . trim($resp) . "\n";

if (strpos($resp, 'OK') === false) {
    echo "Error de login\n";
    exit(1);
}

$write("A2 SELECT INBOX");
$inbox = $read();
preg_match('/\* (\d+) EXISTS/', $inbox, $m);
echo "Mensajes en inbox: " . ($m[1] ?? '?') . "\n";

// Buscar cualquier email de los últimos 7 días
$write("A3 SEARCH SINCE " . date('d-M-Y', strtotime('-7 days')));
$search = $read();
echo "Búsqueda reciente:\n" . $search;

// Fetch asunto de los últimos 5 mensajes si hay
if (!empty($m[1]) && (int)$m[1] > 0) {
    $total = (int)$m[1];
    $from  = max(1, $total - 4);
    $write("A4 FETCH {$from}:{$total} (BODY[HEADER.FIELDS (SUBJECT FROM DATE)])");
    $headers = $read();
    echo "\nÚltimos mensajes:\n" . $headers;
}

$write("A5 LOGOUT");
fclose($conn);
