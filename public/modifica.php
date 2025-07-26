<?php
require __DIR__ . '/../vendor/autoload.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\PageBuilder;
use App\Service\ProductService;

// Proteggi la rotta
Auth::requireLogin();
Auth::requireAdmin();

// Connessione al DB
$db             = Database::getInstance(
    getenv('MARIADB_HOST')     ?: 'mariadb',
    getenv('MARIADB_USER')     ?: 'admin',
    getenv('MARIADB_PASSWORD') ?: 'admin',
    getenv('MARIADB_DATABASE') ?: 'farmacia_archimede'
);
$productService = new ProductService($db);

// Recupera product_id da GET o POST
$product_id = null;
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id']) && ctype_digit($_GET['id'])) {
    $product_id = (int) $_GET['id'];
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id']) && ctype_digit($_POST['product_id'])) {
    $product_id = (int) $_POST['product_id'];
}

// === FASE GET: mostra il form per inserimento o modifica ===
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($product_id) {
        $prod = $productService->getProductByID($product_id);
        if (!$prod) {
            $_SESSION['flash_message'] = [
                'type'    => 'error',
                'message' => 'Prodotto non trovato.',
            ];
            header('Location: /prodotti.php');
            exit;
        }
        $data = [
            'product_type_id' => $prod->productTypeId,
            'short_name'      => $prod->shortName,
            'name'            => $prod->name,
            'manufacturer'    => $prod->manufacturer,
            'aic_code'        => $prod->aicCode,
            'format'          => $prod->format,
            'price'           => $prod->price,
            'availability'    => $prod->availability,
            'description'     => $prod->description,
        ];
        // in Phase GET il service restituisce già il path completo
        $image_path = $prod->imagePath;
    } else {
        $data = array_fill_keys([
            'product_type_id','short_name','name','manufacturer',
            'aic_code','format','price','availability','description'
        ], '');
        $image_path = '';
    }

    PageBuilder::show('modifica.php', [
        ...$data,
        'product_id'           => $product_id ?: '',
        'form_action'          => '/modifica.php?id=' . $product_id,
        'errors'               => [],
        'image_url'            => $image_path,
        'product_type_options' => $productService->renderTypeOptions($data['product_type_id']),
        'format_options'       => $productService->renderFormatOptions($data['format']),
        'meta_title'           => $product_id ? 'Modifica Prodotto' : 'Inserisci Nuovo Prodotto',
        'page_mode'            => $product_id ? 'Modifica'         : 'Inserisci',
        'submit_label'         => $product_id ? 'Modifica'         : 'Inserisci',
    ]);
    exit;
}

// === FASE POST: raccogli dati da form ===
$post = $_POST;
$data = [
    'product_type_id' => trim($post['product_type_id'] ?? ''),
    'short_name'      => trim($post['short_name']      ?? ''),
    'name'            => trim($post['name']            ?? ''),
    'manufacturer'    => trim($post['manufacturer']    ?? ''),
    'aic_code'        => strtoupper(trim($post['aic_code'] ?? '')),
    'format'          => trim($post['format']          ?? ''),
    'price'           => trim($post['price']           ?? ''),
    'availability'    => trim($post['availability']    ?? ''),
    'description'     => trim($post['description']     ?? ''),
];

// === GESTIONE IMMAGINE UNIFICATA ===
// fallback: estrai solo il nome file dal percorso corrente (se in modifica)
if ($product_id) {
    $prod = $productService->getProductByID($product_id);
    // se $prod->imagePath = '/assets/img/frobengola.webp', basename restituisce 'frobengola.webp'
    $image_path = $prod?->imagePath ? basename($prod->imagePath) : '';
} else {
    $image_path = '';
}

if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
    $tmp  = $_FILES['image_file']['tmp_name'];
    $name = basename($_FILES['image_file']['name']);
    $target = __DIR__ . '/../public/assets/img/' . $name;

    if (move_uploaded_file($tmp, $target)) {
        // salvo SEMPRE solo il nome del file
        $image_path = $name;
    } else {
        $errors['image_file'] = 'Errore durante lo spostamento dell\'immagine.';
    }
}

// === VALIDAZIONE DATI ===
$errorKeys = [
    'product_type_id','short_name','name','manufacturer',
    'aic_code','format','price','availability','image_file'
];
$errors = array_fill_keys($errorKeys, '');

// 1) Tipo prodotto
if ($data['product_type_id'] === '' || !ctype_digit($data['product_type_id'])) {
    $errors['product_type_id'] = 'Tipo prodotto obbligatorio.';
}

// 2) Nome breve
$len = strlen($data['short_name']);
if ($len === 0 || $len > 128) {
    $errors['short_name'] = 'Nome breve obbligatorio e massimo 128 caratteri.';
}

// 3) Nome completo
$len = strlen($data['name']);
if ($len === 0 || $len > 128) {
    $errors['name'] = 'Nome completo obbligatorio e massimo 128 caratteri.';
}

// 4) Produttore
$len = strlen($data['manufacturer']);
if ($len === 0 || $len > 100) {
    $errors['manufacturer'] = 'Produttore obbligatorio e massimo 100 caratteri.';
}

// 5) Codice AIC (9 cifre)
if (!preg_match('/^[0-9]{9}$/', $data['aic_code'])) {
    $errors['aic_code'] = 'Codice AIC non valido (9 cifre numeriche).';
}

// 5-bis) Unicità AIC
if ($errors['aic_code'] === ''
    && $productService->existsAicCode($data['aic_code'], $product_id)
) {
    $errors['aic_code'] = 'Questo codice AIC esiste già per un altro prodotto.';
}

// 6) Formato
$formati_validi = [
    'compresse','capsule','sciroppo','gocce','pomata',
    'crema','spray','polvere','soluzione','gel',
    'granulato','cerotto','altro'
];
if (!in_array($data['format'], $formati_validi, true)) {
    $errors['format'] = 'Formato non valido.';
}

// 7) Prezzo
if (!is_numeric($data['price']) || (float)$data['price'] <= 0) {
    $errors['price'] = 'Prezzo obbligatorio e maggiore di 0.';
}

// 8) Disponibilità
if (!ctype_digit($data['availability']) || (int)$data['availability'] < 0) {
    $errors['availability'] = 'Disponibilità obbligatoria e non negativa.';
}

// Controllo errori
$hasErrors = false;
foreach ($errors as $msg) {
    if ($msg !== '') {
        $hasErrors = true;
        break;
    }
}

if ($hasErrors) {
    PageBuilder::show('modifica.php', [
        ...$data,
        'product_id'           => $product_id ?: '',
        'form_action'          => '/modifica.php?id=' . $product_id,
        'errors'               => $errors,
        'image_url'            => $image_path,
        'product_type_options' => $productService->renderTypeOptions($data['product_type_id']),
        'format_options'       => $productService->renderFormatOptions($data['format']),
        'meta_title'           => $product_id ? 'Modifica Prodotto' : 'Inserisci Nuovo Prodotto',
        'page_mode'            => $product_id ? 'Modifica'         : 'Inserisci',
        'submit_label'         => $product_id ? 'Modifica'         : 'Inserisci',
    ]);
    exit;
}

// === SALVATAGGIO e REDIRECT ===
if ($product_id) {
    $productService->updateProduct($product_id, [
        ...$data,
        'image_path' => $image_path,
    ]);
    $_SESSION['flash_message'] = [
        'type'    => 'success',
        'message' => "Hai correttamente modificato il prodotto",
    ];
} else {
    $productService->insertProduct([
        ...$data,
        'image_path' => $image_path,
    ]);
    $_SESSION['flash_message'] = [
        'type'    => 'success',
        'message' => "Hai correttamente inserito un nuovo prodotto",
    ];
}

header('Location: /prodotti.php');
exit;