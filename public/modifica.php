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
$db = Database::getInstance(
    getenv('MARIADB_HOST')     ?: 'mariadb',
    getenv('MARIADB_USER')     ?: 'admin',
    getenv('MARIADB_PASSWORD') ?: 'admin',
    getenv('MARIADB_DATABASE') ?: 'farmacia_archimede'
);
$productService = new ProductService($db);

// Chiavi di errore
$errorKeys = [
    'product_type_id','short_name','name','manufacturer',
    'aic_code','format','price','availability','image_file'
];
$errors = array_fill_keys($errorKeys, '');

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
        $previewImageUrl = $prod->imagePath;
    } else {
        $data = array_fill_keys([
            'product_type_id','short_name','name','manufacturer',
            'aic_code','format','price','availability','description'
        ], '');
        $previewImageUrl = '';
    }

    $breadcrumb_product = $product_id
        ? sprintf(
            '<li><a href="/prodotto.php?id=%s">%s</a></li>',
            htmlspecialchars($product_id, ENT_QUOTES),
            htmlspecialchars($data['short_name'], ENT_QUOTES)
        )
        : '';

    PageBuilder::show('modifica.php', [
        ...$data,
        'product_id'         => $product_id ?: '',
        'breadcrumb_product' => $breadcrumb_product,
        'form_action'        => '/modifica.php?id=' . $product_id,
        'errors'             => $errors,
        'image_url'          => $previewImageUrl,
        'product_type_options' => $productService->renderTypeOptions($data['product_type_id']),
        'format_options'       => $productService->renderFormatOptions($data['format']),
        'meta_title'         => ($product_id ? 'Modifica' : 'Inserisci') . ' | Prodotti',
        'page_mode'          => $product_id ? 'Modifica'   : 'Inserisci',
        'submit_label'       => $product_id ? 'Modifica'   : 'Inserisci',
    ]);
    exit;
}

// === FASE POST: raccolta dati da form ===
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

// gestione immagine (nome casuale)
if ($product_id) {
    $prod = $productService->getProductByID($product_id);
    $image_path = $prod->imagePath ? basename($prod->imagePath) : '';
} else {
    $image_path = '';
}

if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
    $tmp          = $_FILES['image_file']['tmp_name'];
    $originalName = $_FILES['image_file']['name'];
    $ext = pathinfo($originalName, PATHINFO_EXTENSION);
    try {
        $randomName = bin2hex(random_bytes(16)) . '.' . $ext;
    } catch (Exception $e) {
        $randomName = uniqid('img_', true) . '.' . $ext;
    }
    $target = __DIR__ . '/../public/assets/img/' . $randomName;

    if (move_uploaded_file($tmp, $target)) {
        $image_path = $randomName;
    } else {
        $errors['image_file'] = 'Errore durante lo spostamento dell\'immagine.';
    }
}

$previewImageUrl = $image_path ? '/assets/img/' . $image_path : '';

// === VALIDAZIONE DATI ===
if ($data['product_type_id'] === '') {
    $errors['product_type_id'] = 'Seleziona il tipo di prodotto.';
}
$len = strlen($data['short_name']);
if ($len === 0 || $len > 128) {
    $errors['short_name'] = 'Inserisci un nome breve, composto al massimo da 128 caratteri.';
}
$len = strlen($data['name']);
if ($len === 0 || $len > 128) {
    $errors['name'] = 'Inserisci il nome completo del prodotto, composto al massimo da 128 caratteri.';
}
$len = strlen($data['manufacturer']);
if ($len === 0 || $len > 100) {
    $errors['manufacturer'] = 'Inserisci il nome completo del produttore, composto al massimo da 100 caratteri.';
}
if (!preg_match('/^[0-9]{9}$/', $data['aic_code'])) {
    $errors['aic_code'] = 'Inserisci un codice AIC valido, composto esattamente da 9 cifre numeriche.';
}
if ($errors['aic_code'] === '' && $productService->existsAicCode($data['aic_code'], $product_id)) {
    $errors['aic_code'] = 'Il codice AIC inserito è già presente in un altro prodotto.';
}
$formati_validi = ['compresse','capsule','sciroppo','gocce','pomata','crema','spray','polvere','soluzione','gel','granulato','cerotto','altro'];
if (!in_array($data['format'], $formati_validi, true)) {
    $errors['format'] = 'Seleziona il formato del prodotto.';
}
if (!is_numeric($data['price']) || (float)$data['price'] <= 0) {
    $errors['price'] = 'Inserisci il prezzo del prodotto, indicando un valore numerico maggiore di zero.';
}
if (!ctype_digit($data['availability']) || (int)$data['availability'] < 0) {
    $errors['availability'] = 'Inserisci la quantità disponibile del prodotto, indicando un numero intero uguale o superiore a zero.';
}

$hasErrors = false;
foreach ($errors as $msg) {
    if ($msg !== '') {
        $hasErrors = true;
        break;
    }
}

if ($hasErrors) {
    $breadcrumb_product = $product_id
        ? sprintf(
            '<li><a href="/prodotto.php?id=%s">%s</a></li>',
            htmlspecialchars($product_id, ENT_QUOTES),
            htmlspecialchars($data['short_name'], ENT_QUOTES)
        )
        : '';

    $_SESSION['flash_message'] = [
        'type'    => 'error',
        'message' => 'Alcuni dati inseriti non sono corretti. Verifica i campi evidenziati e invia nuovamente il modulo.',
    ];
    PageBuilder::show('modifica.php', [
        ...$data,
        'product_id'         => $product_id ?: '',
        'breadcrumb_product' => $breadcrumb_product,
        'form_action'        => '/modifica.php?id=' . $product_id,
        'errors'             => $errors,
        'image_url'          => $previewImageUrl,
        'product_type_options' => $productService->renderTypeOptions($data['product_type_id']),
        'format_options'       => $productService->renderFormatOptions($data['format']),
        'meta_title'         => ($product_id ? 'Modifica' : 'Inserisci') . ' | Prodotti',
        'page_mode'          => $product_id ? 'Modifica'   : 'Inserisci',
        'submit_label'       => $product_id ? 'Modifica'   : 'Inserisci',
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
        'message' => 'Il prodotto è stato aggiornato correttamente.',
    ];
} else {
    $productService->insertProduct([
        ...$data,
        'image_path' => $image_path,
    ]);
    $_SESSION['flash_message'] = [
        'type'    => 'success',
        'message' => 'Il nuovo prodotto è stato inserito correttamente.',
    ];
    header('Location: /prodotti.php');
    exit;
}

header('Location: /modifica.php?id=' . $product_id);
exit;
