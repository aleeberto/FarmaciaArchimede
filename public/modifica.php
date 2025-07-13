<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;
use App\Core\PageBuilder;
use App\Service\AuthService;
use App\Core\Auth;

// 1) Verifica che l'utente sia autenticato
Auth::requireLogin();

// 2) Verifica che l'utente abbia ruolo admin
Auth::requireAdmin();

// 3) Connessione al DB e servizio di autenticazione
$db   = Database::getInstance(
    getenv('MARIADB_HOST')     ?: 'mariadb',
    getenv('MARIADB_USER')     ?: 'admin',
    getenv('MARIADB_PASSWORD') ?: 'admin',
    getenv('MARIADB_DATABASE') ?: 'farmacia_archimede'
);


$user = Auth::user();

// 5) Mostra il template di modifica/inserimento prodotto
PageBuilder::show($_SERVER['SCRIPT_NAME'], [
    'user'             => $user,
    'meta_title'       => 'Inserimento Prodotto | Farmacia Archimede',
    'meta_description' => 'Pagina per creare o modificare un prodotto, accessibile solo agli admin',
    'meta_keywords'    => 'prodotto, modifica, farmacia, admin'
]);
