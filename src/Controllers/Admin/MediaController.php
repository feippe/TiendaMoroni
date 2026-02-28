<?php
declare(strict_types=1);

namespace TiendaMoroni\Controllers\Admin;

use TiendaMoroni\Core\Session;
use TiendaMoroni\Core\Database as DB;

class MediaController
{
    // ── GET /admin/repositorio  →  HTML page ─────────────────────────────────

    public function manager(array $params = []): void
    {
        Session::requireAdmin();

        $folderId = (int) get('folder_id', 0) ?: null;

        $folders = DB::fetchAll(
            'SELECT * FROM media_folders WHERE parent_id ' . ($folderId ? '= ?' : 'IS NULL') . ' ORDER BY name',
            $folderId ? [$folderId] : []
        );

        $files = DB::fetchAll(
            'SELECT * FROM media_files WHERE folder_id ' . ($folderId ? '= ?' : 'IS NULL') . ' ORDER BY created_at DESC',
            $folderId ? [$folderId] : []
        );

        $breadcrumb = $folderId ? self::buildBreadcrumb($folderId) : [];

        // Total counts for stats
        $totalFiles   = (int) DB::fetchOne('SELECT COUNT(*) AS n FROM media_files')['n'];
        $totalFolders = (int) DB::fetchOne('SELECT COUNT(*) AS n FROM media_folders')['n'];
        $totalSize    = (int) (DB::fetchOne('SELECT COALESCE(SUM(size),0) AS n FROM media_files')['n'] ?? 0);

        view('admin/media/index', [
            'pageTitle'    => 'Repositorio de imágenes',
            'folders'      => $folders,
            'files'        => $files,
            'breadcrumb'   => $breadcrumb,
            'folderId'     => $folderId,
            'totalFiles'   => $totalFiles,
            'totalFolders' => $totalFolders,
            'totalSize'    => $totalSize,
        ]);
    }

    // ── GET /admin/media?folder_id=X  →  JSON ────────────────────────────────

    public function index(array $params = []): void
    {
        Session::requireAdmin();

        $folderId = (int) get('folder_id', 0) ?: null;

        $folders = DB::fetchAll(
            'SELECT * FROM media_folders WHERE parent_id ' . ($folderId ? '= ?' : 'IS NULL') . ' ORDER BY name',
            $folderId ? [$folderId] : []
        );

        $files = DB::fetchAll(
            'SELECT * FROM media_files WHERE folder_id ' . ($folderId ? '= ?' : 'IS NULL') . ' ORDER BY created_at DESC',
            $folderId ? [$folderId] : []
        );

        jsonResponse([
            'folders'    => $folders,
            'files'      => $files,
            'breadcrumb' => $folderId ? self::buildBreadcrumb($folderId) : [],
            'folder_id'  => $folderId,
        ]);
    }

    // ── POST /admin/media/subir ───────────────────────────────────────────────

    public function upload(array $params = []): void
    {
        Session::requireAdmin();
        verifyCsrf();

        $folderId = (int) post('folder_id', 0) ?: null;

        if (!isset($_FILES['file']) || $_FILES['file']['error'] === UPLOAD_ERR_NO_FILE) {
            jsonResponse(['success' => false, 'message' => 'No se recibió ningún archivo.'], 400);
        }

        $file = $_FILES['file'];

        $uploadErrors = [
            UPLOAD_ERR_INI_SIZE   => 'El archivo supera el límite de PHP (upload_max_filesize = ' . ini_get('upload_max_filesize') . ').',
            UPLOAD_ERR_FORM_SIZE  => 'El archivo supera el límite definido en el formulario.',
            UPLOAD_ERR_PARTIAL    => 'El archivo se subió de forma incompleta.',
            UPLOAD_ERR_NO_TMP_DIR => 'Falta el directorio temporal en el servidor.',
            UPLOAD_ERR_CANT_WRITE => 'No se pudo escribir el archivo en disco.',
            UPLOAD_ERR_EXTENSION  => 'Una extensión de PHP bloqueó la subida.',
        ];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $msg = $uploadErrors[$file['error']] ?? 'Error en la subida (código ' . $file['error'] . ').';
            jsonResponse(['success' => false, 'message' => $msg], 400);
        }

        if ($file['size'] > UPLOAD_MAX_SIZE) {
            jsonResponse(['success' => false, 'message' => 'El archivo supera el límite de tamaño.'], 400);
        }

        $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        $mime    = mime_content_type($file['tmp_name']);
        if (!in_array($mime, $allowed)) {
            jsonResponse(['success' => false, 'message' => 'Tipo de archivo no permitido. Solo JPEG, PNG, WebP o GIF.'], 400);
        }

        // Build subdirectory
        $subDir = $folderId ? self::getFolderDiskPath($folderId) . '/' : '';

        $uploadDir = UPLOAD_PATH . $subDir;
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $baseName = pathinfo($file['name'], PATHINFO_FILENAME);
        $safeName = preg_replace('/[^a-z0-9_-]/', '-', strtolower($baseName));
        $safeName = trim($safeName, '-') ?: 'imagen';
        $filename = $safeName . '_' . substr(uniqid(), -6) . '.' . $ext;
        $diskPath = $uploadDir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $diskPath)) {
            jsonResponse(['success' => false, 'message' => 'Error al guardar el archivo en disco.'], 500);
        }

        $url = UPLOAD_URL . $subDir . $filename;

        DB::query(
            'INSERT INTO media_files (folder_id, filename, url, disk_path, mime_type, size) VALUES (?, ?, ?, ?, ?, ?)',
            [$folderId, $filename, $url, $diskPath, $mime, $file['size']]
        );
        $id = (int) DB::lastInsertId();

        jsonResponse([
            'success' => true,
            'file'    => [
                'id'        => $id,
                'folder_id' => $folderId,
                'filename'  => $filename,
                'url'       => $url,
                'mime_type' => $mime,
                'size'      => $file['size'],
            ],
        ]);
    }

    // ── POST /admin/media/carpeta ─────────────────────────────────────────────

    public function createFolder(array $params = []): void
    {
        Session::requireAdmin();
        verifyCsrf();

        $name     = trim(sanitize(post('name', '')));
        $parentId = (int) post('parent_id', 0) ?: null;

        if (!$name) {
            jsonResponse(['success' => false, 'message' => 'El nombre de la carpeta es obligatorio.'], 400);
        }

        // Create on disk
        $parentPath = $parentId ? self::getFolderDiskPath($parentId) . '/' : '';
        $safeDirName = preg_replace('/[^a-z0-9_-]/', '-', strtolower($name));
        $safeDirName = trim($safeDirName, '-') ?: 'carpeta';

        $diskPath = UPLOAD_PATH . $parentPath . $safeDirName;

        // Disambiguate if exists
        $suffix  = '';
        $counter = 1;
        while (is_dir($diskPath . $suffix)) {
            $suffix = '_' . $counter++;
        }
        $diskPath .= $suffix;
        $safeDirName .= $suffix;

        if (!mkdir($diskPath, 0755, true) && !is_dir($diskPath)) {
            jsonResponse(['success' => false, 'message' => 'No se pudo crear el directorio en disco. Verificá los permisos de la carpeta uploads/.'], 500);
        }

        DB::query(
            'INSERT INTO media_folders (name, parent_id) VALUES (?, ?)',
            [$name, $parentId]
        );
        $id = (int) DB::lastInsertId();

        jsonResponse([
            'success' => true,
            'folder'  => ['id' => $id, 'name' => $name, 'parent_id' => $parentId],
        ]);
    }

    // ── POST /admin/media/eliminar ────────────────────────────────────────────

    public function deleteFile(array $params = []): void
    {
        Session::requireAdmin();
        verifyCsrf();

        $id   = (int) post('id', 0);
        $file = DB::fetchOne('SELECT * FROM media_files WHERE id = ?', [$id]);

        if (!$file) {
            jsonResponse(['success' => false, 'message' => 'Archivo no encontrado.'], 404);
        }

        if (file_exists($file['disk_path'])) {
            unlink($file['disk_path']);
        }

        DB::query('DELETE FROM media_files WHERE id = ?', [$id]);

        jsonResponse(['success' => true]);
    }

    // ── POST /admin/media/carpeta/eliminar ───────────────────────────────────

    public function deleteFolder(array $params = []): void
    {
        Session::requireAdmin();
        verifyCsrf();

        $id     = (int) post('id', 0);
        $folder = DB::fetchOne('SELECT * FROM media_folders WHERE id = ?', [$id]);

        if (!$folder) {
            jsonResponse(['success' => false, 'message' => 'Carpeta no encontrada.'], 404);
        }

        // Delete all files inside (recursively via FK CASCADE on folders,
        // but we also clean disk files manually)
        self::deleteFolderRecursive($id);

        DB::query('DELETE FROM media_folders WHERE id = ?', [$id]);

        jsonResponse(['success' => true]);
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private static function deleteFolderRecursive(int $folderId): void
    {
        // Delete disk files inside this folder
        $files = DB::fetchAll('SELECT disk_path FROM media_files WHERE folder_id = ?', [$folderId]);
        foreach ($files as $f) {
            if (file_exists($f['disk_path'])) {
                unlink($f['disk_path']);
            }
        }
        DB::query('DELETE FROM media_files WHERE folder_id = ?', [$folderId]);

        // Recurse into subfolders
        $subFolders = DB::fetchAll('SELECT id FROM media_folders WHERE parent_id = ?', [$folderId]);
        foreach ($subFolders as $sub) {
            self::deleteFolderRecursive((int) $sub['id']);
        }
    }

    private static function getFolderDiskPath(int $folderId): string
    {
        $folder = DB::fetchOne('SELECT * FROM media_folders WHERE id = ?', [$folderId]);
        if (!$folder) {
            return 'folder_' . $folderId;
        }

        $safe = preg_replace('/[^a-z0-9_-]/', '-', strtolower($folder['name']));
        $safe = trim($safe, '-') ?: 'carpeta';
        $segment = $safe . '_' . $folderId;

        if ($folder['parent_id']) {
            return self::getFolderDiskPath((int) $folder['parent_id']) . '/' . $segment;
        }

        return $segment;
    }

    private static function buildBreadcrumb(int $folderId): array
    {
        $crumbs  = [];
        $current = $folderId;

        while ($current) {
            $folder = DB::fetchOne('SELECT * FROM media_folders WHERE id = ?', [$current]);
            if (!$folder) break;
            array_unshift($crumbs, ['id' => (int) $folder['id'], 'name' => $folder['name']]);
            $current = (int) ($folder['parent_id'] ?? 0);
        }

        return $crumbs;
    }
}
