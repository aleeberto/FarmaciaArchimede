<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Auth;           // se esiste già nel tuo progetto
use App\Core\Database;
use App\Service\AuthService; // <-- importa il servizio
use App\Service\DeleteService;

Auth::requireLogin();
Auth::requireAdmin();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$type = $_POST['type'] ?? '';
$id   = isset($_POST['id']) ? (int)$_POST['id'] : 0;


$db   = Database::getInstance('localhost', 'gbarison', 'SaSoo9chahNguuCh', 'gbarison');
$auth = new AuthService($db);
$svc  = new DeleteService($db);

$currentUserId = $auth->getUserId() ?? 0;

try {
    switch ($type) {
        case 'product':
            $svc->deleteProduct($id);
            break;
        case 'order':
            $svc->deleteOrder($id);
            break;
        case 'user':
            // <<< PRIMA mancava il secondo argomento
            $svc->deleteUser($id, $currentUserId);
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
