<?php
declare(strict_types=1);

// public/area_personale.php
// Front-controller per l'Area Personale, con breadcrumb e PageBuilder

require __DIR__ . '/../vendor/autoload.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\PageBuilder;
use App\Service\AuthService;
use App\Service\AreaPersonaleService;

Auth::requireLogin();

// 2) Inizializza DB e servizi
$db = Database::getInstance(
    'localhost', 'gbarison','SaSoo9chahNguuCh', 'gbarison'
);
$auth = new AuthService($db);
$svc  = new AreaPersonaleService($auth, $db);

// 3) Determina la sezione (menu, dati, ordini, gestione, ecc.) e l'azione
$section = $_GET['section'] ?? 'menu';
$action  = $_GET['action']  ?? 'view'; // non più usato per 'dati' in GET

// 4) Breadcrumb e parametri base
$params = [];
$crumbs = [
    '<a href="index.php" lang="en">Home</a>',
    '<a href="area_personale.php">Area personale</a>',
];

$isAdmin = $auth->getUser()->isAdmin();

// Helper di escaping HTML
$esc = static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

// Helper: merge valori form (flat)
$mergeForm = static function (array $oldSafe, array $safeUser): array {
    return [
        'first_name' => ($oldSafe['first_name'] ?? '') !== '' ? $oldSafe['first_name'] : ($safeUser['first_name'] ?? ''),
        'last_name'  => ($oldSafe['last_name']  ?? '') !== '' ? $oldSafe['last_name']  : ($safeUser['last_name']  ?? ''),
        'email'      => ($oldSafe['email']      ?? '') !== '' ? $oldSafe['email']      : ($safeUser['email']      ?? ''),
        'tax_code'   => ($oldSafe['tax_code']   ?? '') !== '' ? $oldSafe['tax_code']   : ($safeUser['tax_code']   ?? ''),
    ];
};

// 5) Router
switch ($section) {
    case 'dati': {
        // Dati base utente
        $base = $svc->getDatiUtente();        // ['user' => ['first_name','last_name','email','tax_code']]
        $user = $base['user'] ?? [];

        $metaDesc = 'Aggiorna nome, cognome, email e codice fiscale. Modifica password in sicurezza nell’Area Personale di Farmacia Archimede.';
        $metaKeys = 'area personale, dati utente, modifica profilo, cambio password, sicurezza, farmacia archimede';


        // Versione "safe" per il template
        $safeUser = [
            'first_name' => $esc($user['first_name'] ?? ''),
            'last_name'  => $esc($user['last_name']  ?? ''),
            'email'      => $esc($user['email']      ?? ''),
            'tax_code'   => $esc($user['tax_code']   ?? ''),
        ];

        // CSRF token
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
        }
        $csrf = $_SESSION['csrf_token'];

        // Chiavi errore per il form
        $errorKeys = [
            'first_name','last_name','email','tax_code',
            'current_password','new_password','new_password_confirm','confirm_with_password'
        ];
        $errors = array_fill_keys($errorKeys, '');

        // === GET: mostra SUBITO il form precompilato ===
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $form = $mergeForm([], $safeUser);
            $params = array_merge($form, [
                'errors'     => $errors,
                'csrf_token' => $csrf,
                'meta_title' => 'Modifica dati personali | Farmacia Archimede',
                'meta_description' => $metaDesc,
                'meta_keywords'    => $metaKeys,
            ]);
            $crumbs[] = 'Modifica dati personali';
            $templateName = 'area_personale/dati_personali'; // il template con il form
            break;
        }

        // === POST (update) ===
        $post = $_POST;
        $data = [
            'first_name'            => trim($post['first_name'] ?? ''),
            'last_name'             => trim($post['last_name'] ?? ''),
            'email'                 => trim($post['email'] ?? ''),
            'tax_code'              => strtoupper(trim($post['tax_code'] ?? '')),
            'current_password'      => $post['current_password'] ?? '',
            'new_password'          => $post['new_password'] ?? '',
            'new_password_confirm'  => $post['new_password_confirm'] ?? '',
            'confirm_with_password' => $post['confirm_with_password'] ?? '',
            'csrf_token'            => $post['csrf_token'] ?? '',
        ];

        // oldSafe per ripresentare i valori (mai password)
        $oldSafe = [
            'first_name' => $esc($data['first_name']),
            'last_name'  => $esc($data['last_name']),
            'email'      => $esc($data['email']),
            'tax_code'   => $esc($data['tax_code']),
        ];

        // CSRF
        if (!hash_equals($csrf, $data['csrf_token'])) {
            $_SESSION['flash_message'] = [
                'type'    => 'error',
                'message' => 'Sessione scaduta. Ricarica la pagina e riprova.',
            ];
            $form = $mergeForm($oldSafe, $safeUser);
            $params = array_merge($form, [
                'errors'     => $errors,
                'csrf_token' => $csrf,
                'meta_title' => 'Modifica dati personali | Farmacia Archimede',
                'meta_description' => $metaDesc,
                'meta_keywords'    => $metaKeys,
            ]);
            $crumbs[] = 'Modifica dati personali';
            $templateName = 'area_personale/dati_personali';
            break;
        }

        // --- Rileva cambi ai dati profilo (confronto con i valori correnti) ---
        $profileChanged =
            ($data['first_name'] !== ($user['first_name'] ?? '')) ||
            ($data['last_name']  !== ($user['last_name']  ?? '')) ||
            ($data['email']      !== ($user['email']      ?? '')) ||
            ($data['tax_code']   !== (isset($user['tax_code']) ? strtoupper((string)$user['tax_code']) : ''));

        // Cambio password opzionale
        $wantsPwChange = ($data['new_password'] !== '' || $data['new_password_confirm'] !== '');

        // ===== Caso: nessuna modifica =====
        if (!$profileChanged && !$wantsPwChange) {
            $_SESSION['flash_message'] = [
                'type'    => 'info',
                'message' => 'Nessuna modifica apportata.',
            ];
            header('Location: /area_personale.php?section=dati');
            exit;
        }

        // Validazioni base (solo se ci sono modifiche ai dati)
        if ($profileChanged) {
            if ($data['first_name'] === '') {
                $errors['first_name'] = 'Inserisci il nome.';
            }
            if ($data['last_name'] === '') {
                $errors['last_name'] = 'Inserisci il cognome.';
            }
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Inserisci un’email valida.';
            }
            if (!preg_match('/^[A-Z0-9]{16}$/', $data['tax_code'])) {
                $errors['tax_code'] = 'Codice fiscale non valido (16 caratteri alfanumerici).';
            }
        }

        // Validazioni cambio password (se richiesto)
        if ($wantsPwChange) {
            if ($data['current_password'] === '') {
                $errors['current_password'] = 'Inserisci la password attuale.';
            }
            if (strlen($data['new_password']) < 8) {
                $errors['new_password'] = 'La nuova password deve avere almeno 8 caratteri.';
            }
            if ($data['new_password'] !== $data['new_password_confirm']) {
                $errors['new_password_confirm'] = 'Le password non coincidono.';
            }
        }

        // Conferma con password: richiesta SOLO se ci sono modifiche (profilo o password)
        if ($profileChanged || $wantsPwChange) {
            if ($data['confirm_with_password'] === '') {
                $errors['confirm_with_password'] = 'Inserisci la tua password per confermare le modifiche.';
            }
        }

        // Ci sono errori?
        $hasErrors = false;
        foreach ($errors as $msg) {
            if ($msg !== '') { $hasErrors = true; break; }
        }

        if ($hasErrors) {
            $_SESSION['flash_message'] = [
                'type'    => 'error',
                'message' => 'Alcuni dati non sono corretti. Verifica i campi evidenziati e invia nuovamente il modulo.',
            ];
            $form = $mergeForm($oldSafe, $safeUser);
            $params = array_merge($form, [
                'errors'     => $errors,
                'csrf_token' => $csrf,
                'meta_title' => 'Modifica dati personali | Farmacia Archimede',
                'meta_description' => $metaDesc,
                'meta_keywords'    => $metaKeys,
            ]);
            $crumbs[] = 'Modifica dati personali';
            $templateName = 'area_personale/dati_personali';
            break;
        }

        // ===== Salvataggio =====
        try {
            // Conferma con password (arriva qui solo se c'erano modifiche)
            $svc->verifyPassword($data['confirm_with_password']);

            // Cambio password se richiesto
            if ($wantsPwChange) {
                $svc->changePassword($data['current_password'], $data['new_password']);
            }

            // Aggiorna profilo se cambiano i dati
            if ($profileChanged) {
                $svc->updateProfile([
                    'first_name' => $data['first_name'],
                    'last_name'  => $data['last_name'],
                    'email'      => $data['email'],
                    'tax_code'   => $data['tax_code'],
                ]);
            }

            $_SESSION['flash_message'] = [
                'type'    => 'success',
                'message' => 'Modifiche salvate correttamente.',
            ];
            header('Location: area_personale.php?section=dati'); // torna al form
            exit;

        } catch (\Throwable $e) {
            $msg = $e->getMessage();

            if (stripos($msg, 'attuale') !== false) {
                $errors['current_password'] = $msg;
            } elseif (stripos($msg, 'conferma') !== false) {
                $errors['confirm_with_password'] = $msg;
            } elseif (stripos($msg, 'email') !== false) {
                $errors['email'] = $msg;
            } else {
                $_SESSION['flash_message'] = [
                    'type'    => 'error',
                    'message' => $msg,
                ];
            }

            $form = $mergeForm($oldSafe, $safeUser);
            $params = array_merge($form, [
                'errors'     => $errors,
                'csrf_token' => $csrf,
                'meta_title' => 'Modifica dati personali | Farmacia Archimede',
                'meta_description' => $metaDesc,
                'meta_keywords'    => $metaKeys,
            ]);
            $crumbs[] = 'Modifica dati personali';
            $templateName = 'area_personale/dati_personali';
            break;
        }
    }

    case 'ordini': {
        $params = $svc->getOrdiniUtente();
        $crumbs[] = 'I miei ordini';
        $params['meta_title'] = 'I miei ordini | Farmacia Archimede';
        $params['meta_description'] = 'Consulta lo storico ordini, dettagli, stato spedizione e ricevute nell’Area Personale di Farmacia Archimede.';
        $params['meta_keywords']    = 'ordini, storico acquisti, tracciamento, ricevute, area personale, farmacia archimede';
        $templateName = 'area_personale/ordini';
        break;
    }

    case 'gestione': {
        if (!$isAdmin) { header('Location: area_personale.php'); exit; }
        $crumbs[] = 'Gestisci';
        $params['meta_title'] = 'Gestisci | Farmacia Archimede';
        $params['meta_description'] = 'Pannello di amministrazione: accesso rapido a prodotti, ordini e utenti della Farmacia Archimede.';
        $params['meta_keywords']    = 'admin, gestione, prodotti, ordini, utenti, farmacia archimede';
        $templateName = 'area_personale/gestione/gestione';
        break;
    }

    case 'gestione_prodotti': {
        if (!$isAdmin) { header('Location: area_personale.php'); exit; }
        $params = $svc->getProdottiAdmin();
        $crumbs[] = '<a href="?section=gestione">Gestisci</a>';
        $crumbs[] = 'Prodotti';
        $params['meta_title'] = 'Prodotti | Gestisci | Farmacia Archimede';
        $params['meta_description'] = 'Gestione prodotti: crea, modifica, filtra e aggiorna disponibilità e prezzi nel catalogo Farmacia Archimede.';
        $params['meta_keywords']    = 'gestione prodotti, catalogo, prezzi, disponibilità, farmacia archimede';
        $templateName = 'area_personale/gestione/gestione_prodotti';
        break;
    }

    case 'gestione_utenti': {
        if (!$isAdmin) { header('Location: area_personale.php'); exit; }
        $params = $svc->getUtentiAdmin();
        $crumbs[] = '<a href="?section=gestione">Gestisci</a>';
        $crumbs[] = 'Utenti';
        $params['meta_title'] = 'Utenti | Gestisci | Farmacia Archimede';
        $params['meta_description'] = 'Gestione utenti: visualizza e aggiorna profili, ruoli e attività degli account registrati.';
        $params['meta_keywords']    = 'gestione utenti, profili, ruoli, account, attività, farmacia archimede';
        $templateName = 'area_personale/gestione/gestione_utenti';
        break;
    }

    default: {
        // Menu principale
        $params = [
            'is_admin'  => $isAdmin,
            'meta_description' => 'Accedi a dati personali, ordini e, se admin, strumenti di gestione della Farmacia Archimede.',
            'meta_keywords'    => 'area personale, profilo, ordini, admin, gestione, farmacia archimede',
        ];
        $templateName = 'area_personale/menu';
        $params['meta_title'] = 'Area personale | Farmacia Archimede';

        break;
    }
}

// 6) Costruzione breadcrumb HTML con separatore >>
$lastIndex = count($crumbs) - 1;
$items = '';

foreach ($crumbs as $i => $crumb) {
    if ($i === $lastIndex) {
        // Ultimo: solo testo, aria-current
        $items .= '<li aria-current="page">' . strip_tags($crumb) . '</li>';
    } else {
        // Intermedi: link + separatore visivo non letto dagli screen reader
        $items .= '<li>' . $crumb . ' <span class="separator" aria-hidden="true">&gt;&gt;</span></li>';
    }
}

$params['breadcrumb'] = $items;
$params['is_admin']   = $isAdmin;

// 7) Render con PageBuilder
PageBuilder::show($templateName, $params);
