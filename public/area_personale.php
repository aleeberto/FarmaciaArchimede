<?php
declare(strict_types=1);

// public/area_personale.php
// Front-controller per l'Area Personale, con breadcrumb e PageBuilder

// 0. Carica l’autoload di Composer (nessun echo/whitespace prima)
require __DIR__ . '/../vendor/autoload.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\PageBuilder;
use App\Service\AuthService;
use App\Service\AreaPersonaleService;

// 1. Verifica login (fa session_start() internamente)
Auth::requireLogin();

// 2. Inizializza DB e AuthService
$db   = Database::getInstance(
    getenv('MARIADB_HOST')     ?: 'mariadb',
    getenv('MARIADB_USER')     ?: 'admin',
    getenv('MARIADB_PASSWORD') ?: 'admin',
    getenv('MARIADB_DATABASE') ?: 'farmacia_archimede'
);
$auth = new AuthService($db);
$svc  = new AreaPersonaleService($auth, $db);

// 3. Determina la sezione (menu, dati, ordini, gestione, ecc.)
$section = $_GET['section'] ?? 'menu';

// 4. Prepara parametri e breadcrumb
$params = [];
$crumbs = ['<a href="/area_personale.php">Home</a>'];
$isAdmin = $auth->getUser()->isAdmin();

switch ($section) {
    case 'dati':
        $params = $svc->getDatiUtente();
        $crumbs[] = 'I miei dati';
        $params['meta_title'] = 'I miei dati | Farmacia Archimede';
        $templateName = 'area_personale/dati';
        break;

    case 'ordini':
        $params = $svc->getOrdiniUtente();
        $crumbs[] = 'I miei ordini';
        $params['meta_title'] = 'I miei ordini | Farmacia Archimede';
        $templateName = 'area_personale/ordini';
        break;

    case 'gestione':
        if (! $isAdmin) {
            header('Location: /area_personale.php'); exit;
        }
        $crumbs[] = 'Gestisci';
        $params['meta_title'] = 'Gestisci | Farmacia Archimede';
        $templateName = 'area_personale/gestione/gestione';
        break;

    case 'gestione_prodotti':
        if (! $isAdmin) {
            header('Location: /area_personale.php'); exit;
        }
        $params = $svc->getProdottiAdmin();
        $crumbs[] = '<a href="?section=gestione">Gestisci</a>';
        $crumbs[] = 'Prodotti';
        $params['meta_title'] = 'Prodotti | Gestisci | Farmacia Archimede';
        $templateName = 'area_personale/gestione/gestione_prodotti';
        break;

    case 'gestione_ordini':
        if (! $isAdmin) {
            header('Location: /area_personale.php'); exit;
        }
        $params = $svc->getOrdiniAdmin();
        $crumbs[] = '<a href="?section=gestione">Gestisci</a>';
        $params['meta_title'] = 'Ordini | Gestisci | Farmacia Archimede';
        $crumbs[] = 'Ordini';
        $templateName = 'area_personale/gestione/gestione_ordini';
        break;

    case 'gestione_utenti':
        if (! $isAdmin) {
            header('Location: /area_personale.php'); exit;
        }
        $params = $svc->getUtentiAdmin();
        $crumbs[] = '<a href="?section=gestione">Gestisci</a>';
        $params['meta_title'] = 'Utenti | Gestisci | Farmacia Archimede';
        $crumbs[] = 'Utenti';
        $templateName = 'area_personale/gestione/gestione_utenti';
        break;

    default:
        // menu principale
        $params = [
            'is_admin'      => $isAdmin,
        ];
        $templateName = 'area_personale/menu';
        $params['meta_title'] = 'Area personale | Farmacia Archimede';
        break;
}

// Genera HTML della breadcrumb
$last = count($crumbs) - 1;
$items = '';
foreach ($crumbs as $i => $crumb) {
    $attrs = $i === $last ? ' aria-current="page"' : '';
    if ($i === $last) {
        $text = strip_tags($crumb);
        $items .= "<li{$attrs}>{$text}</li>";
    } else {
        $items .= "<li{$attrs}>{$crumb}</li>";
    }
}
$params['breadcrumb'] = $items;

// 5. Renderizza con PageBuilder
PageBuilder::show($templateName, $params);
