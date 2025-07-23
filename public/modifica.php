<?php
require __DIR__ . '/../vendor/autoload.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\PageBuilder;
use App\Service\ProductService;

// Proteggi la rotta
Auth::requireLogin();
Auth::requireAdmin();

$db = Database::getInstance(
    getenv('MARIADB_HOST')     ?: 'mariadb',
    getenv('MARIADB_USER')     ?: 'admin',
    getenv('MARIADB_PASSWORD') ?: 'admin',
    getenv('MARIADB_DATABASE') ?: 'farmacia_archimede'
);
$productService = new ProductService($db);

// Raccogli i dati dal POST
$post = $_POST;

$errors = [];
$data = [
    'product_type_id' => trim($post['product_type_id'] ?? ''),
    'short_name'      => trim($post['short_name'] ?? ''),
    'name'            => trim($post['name'] ?? ''),
    'manufacturer'    => trim($post['manufacturer'] ?? ''),
    'aic_code'        => strtoupper(trim($post['aic_code'] ?? '')),
    'format'          => trim($post['format'] ?? ''),
    'price'           => trim($post['price'] ?? ''),
    'availability'    => trim($post['availability'] ?? ''),
    'description'     => trim($post['description'] ?? ''),
];
$product_id = isset($post['product_id']) && ctype_digit($post['product_id']) ? (int)$post['product_id'] : '';


if ($data['short_name'] === '' || mb_strlen($data['short_name']) > 128) {
    $errors['short_name'] = 'Nome breve obbligatorio e massimo 128 caratteri.';
}
if ($data['name'] === '' || mb_strlen($data['name']) > 128) {
    $errors['name'] = 'Nome completo obbligatorio e massimo 128 caratteri.';
}
if ($data['manufacturer'] === '' || mb_strlen($data['manufacturer']) > 100) {
    $errors['manufacturer'] = 'Produttore obbligatorio e massimo 100 caratteri.';
}
if (!preg_match('/^[A-Z0-9]{10}$/', $data['aic_code'])) {
    $errors['aic_code'] = 'Codice AIC non valido (10 caratteri alfanumerici).';
}
$formati_validi = ['compresse','capsule','sciroppo','gocce','pomata','crema','spray','polvere','soluzione','gel','granulato','cerotto','altro'];
if (!in_array($data['format'], $formati_validi, true)) {
    $errors['format'] = 'Formato non valido.';
}
if (!is_numeric($data['price']) || (float)$data['price'] <= 0) {
    $errors['price'] = 'Prezzo obbligatorio e maggiore di 0.';
}
if (!ctype_digit($data['availability']) || (int)$data['availability'] < 0) {
    $errors['availability'] = 'Disponibilità obbligatoria e non negativa.';
}

// Gestione immagine
$image_path = null;
if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
    $tmp = $_FILES['image_file']['tmp_name'];
    $name = basename($_FILES['image_file']['name']);
    $target = __DIR__ . '/../images/' . $name;
    if (move_uploaded_file($tmp, $target)) {
        $image_path = '/images/' . $name;
    } else {
        $errors['image_file'] = 'Errore durante il caricamento dell\'immagine.';
    }
} elseif ($product_id) {
    // Se in modifica e nessuna nuova immagine caricata, tieni quella già presente
    $prod = $productService->getProductByID($product_id);
    $image_path = $prod ? $prod->imagePath : '';
}

// SE ERRORI, mostra di nuovo il form con i dati e gli errori
if ($errors) {
    $data['image_url'] = $image_path ?: '';
    PageBuilder::show('modifica.php', [
        ...$data,
        'product_id' => $product_id??'',
        'errors' => $errors,
        'product_type_options' => $productService->renderTypeOptions($data['product_type_id']),
        'format_options' => $productService->renderFormatOptions($data['format']),
        'meta_title' => $product_id ? 'Modifica Prodotto' : 'Inserisci Nuovo Prodotto',
        'page_mode' => $product_id ? 'Modifica' : 'Inserisci',
        'submit_label' => $product_id ? 'Modifica' : 'Inserisci',
    ]);
    exit;
}

// NESSUN ERRORE: inserisci o aggiorna
//if ($product_id) {
//    $productService->updateProduct($product_id, [
//        ...$data,
//        'image_path' => $image_path,
//    ]);
//} else {
//    $productService->insertProduct([
//        ...$data,
//        'image_path' => $image_path,
//    ]);
//}

header('Location: /prodotti.php?msg=success');
exit;
