<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Auth;
use App\Core\Database;
use App\Service\DeleteService;

Auth::requireLogin();
Auth::requireAdmin();

session_start();

$type = $_POST['type'] ?? '';
$id   = isset($_POST['id']) ? (int)$_POST['id'] : 0;

$db = Database::getInstance(
    'localhost', 'gbarison','SaSoo9chahNguuCh', 'gbarison'
);
$svc = new DeleteService($db);

try {
    switch ($type) {
        case 'product':
            $svc->deleteProduct($id);
            break;
        case 'order':
            $svc->deleteOrder($id);
            break;
        case 'user':
            $svc->deleteUser($id, Auth::getUserId());
            break;
        default:
            throw new RuntimeException("Tipo non valido.");
    }

    $_SESSION['flash_message'] = ['type'=>'success','message'=>'Eliminazione completata.'];
} catch (Throwable $e) {
    $_SESSION['flash_message'] = ['type'=>'error','message'=>$e->getMessage()];
}

header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/area_personale.php?section=gestione'));
exit;
