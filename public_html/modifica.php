<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\PageBuilder;
use App\Core\Image;
use App\Service\ProductService;

define('IMG_DIR', rtrim(__DIR__, '/\\') . '/assets/img');
$__scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
define('IMG_BASE_URL', ($__scriptDir === '' || $__scriptDir === '/') ? '/assets/img' : ($__scriptDir . '/assets/img'));
if (!is_dir(IMG_DIR)) { @mkdir(IMG_DIR, 0755, true); }

Auth::requireLogin();
Auth::requireAdmin();

$db = Database::getInstance('localhost', 'gbarison', 'SaSoo9chahNguuCh', 'gbarison');
$productService = new ProductService($db);

$errorKeys = ['product_type_id','short_name','name','manufacturer','aic_code','format','price','availability','image_file'];
$errors = array_fill_keys($errorKeys, '');

$product_id = null;
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id']) && ctype_digit($_GET['id'])) {
    $product_id = (int) $_GET['id'];
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id']) && ctype_digit($_POST['product_id'])) {
    $product_id = (int) $_POST['product_id'];
}

/**
 * GET: mostra form (Inserisci/Modifica)
 */
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($product_id) {
        $prod = $productService->getProductByID($product_id);
        if (!$prod) { header('Location: 404.php'); exit; }

        $data = [
            'product_type_id' => $prod->productTypeId,
            'short_name'      => $prod->shortName,
            'name'            => $prod->name,
            'manufacturer'    => $prod->manufacturer,
            'aic_code'        => $prod->aicCode,
            'format'          => $prod->format,
            'price'           => (string)$prod->price,
            'availability'    => (string)$prod->availability,
            'description'     => $prod->description,
        ];

        $existingPath = (string)($prod->imagePath ?? '');
        $existingStem = $existingPath !== '' ? pathinfo(basename($existingPath), PATHINFO_FILENAME) : '';
        $pic = Image::resolvePictureSources($existingStem, IMG_DIR, IMG_BASE_URL);
        $image_jpg_url  = $pic['jpg'];
        $image_webp_url = $pic['webp'];
    } else {
        $data = array_fill_keys(['product_type_id','short_name','name','manufacturer','aic_code','format','price','availability','description'], '');
        $pic = Image::resolvePictureSources('', IMG_DIR, IMG_BASE_URL);
        $image_jpg_url  = $pic['jpg'];
        $image_webp_url = $pic['webp'];
    }

    $page_mode = $product_id ? 'Modifica' : 'Inserisci';
    $short     = trim($data['short_name'] ?? '');
    $hasName   = ($short !== '');

    $meta_title = $hasName
        ? sprintf('%s prodotto: %s | Farmacia Archimede', $page_mode, $short)
        : sprintf('%s prodotto | Farmacia Archimede', $page_mode);

    $meta_description = $hasName
        ? sprintf('%s i dati del prodotto %s: AIC, formato, prezzo, disponibilità e descrizione nel catalogo di Farmacia Archimede.', $page_mode, $short)
        : sprintf('%s i dati di un prodotto: AIC, formato, prezzo, disponibilità e descrizione nel catalogo di Farmacia Archimede.', $page_mode);

    $meta_keywords = $hasName
        ? sprintf('farmacia archimede, prodotti, %s, %s prodotto, catalogo, gestione', $short, strtolower($page_mode))
        : 'farmacia archimede, prodotti, modifica, inserisci, catalogo, gestione';

    $breadcrumb_product_li = '';
    if ($product_id && ($data['short_name'] ?? '') !== '') {
        $href = 'prodotto.php?id=' . rawurlencode((string)$product_id);
        $text = htmlspecialchars($data['short_name'], ENT_QUOTES);
        $breadcrumb_product_li = sprintf(
            '<li><a href="%s">%s</a><span class="separator" aria-hidden="true">&gt;&gt;</span></li>',
            $href,
            $text
        );
    }

    PageBuilder::show('modifica.php', [
        ...$data,
        'product_id'             => $product_id ?: '',
        'breadcrumb_product_li'  => $breadcrumb_product_li,
        'form_action'            => $product_id ? ('modifica.php?id=' . $product_id) : 'modifica.php',
        'errors'                 => $errors,
        'image_webp_url'         => $image_webp_url,
        'image_jpg_url'          => $image_jpg_url,
        'product_type_options'   => $productService->renderTypeOptions($data['product_type_id']),
        'format_options'         => $productService->renderFormatOptions($data['format']),
        'meta_title'             => $meta_title,
        'meta_description'       => $meta_description,
        'meta_keywords'          => $meta_keywords,
        'page_mode'              => $page_mode,
        'submit_label'           => $page_mode,
    ]);

    exit;
}

/**
 * POST: valida e salva
 */
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

$image_stem = '';
if ($product_id) {
    $prod = $productService->getProductByID($product_id);
    $prevPath   = $prod && $prod->imagePath ? basename((string)$prod->imagePath) : '';
    $image_stem = $prevPath !== '' ? pathinfo($prevPath, PATHINFO_FILENAME) : '';
}

// Upload immagine (genera sempre .jpg + .webp, cancella varianti vecchie se cambia lo stem)
if (isset($_FILES['image_file']) && ($_FILES['image_file']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
    [$ok, $newStem, $err] = Image::store($_FILES['image_file'], IMG_DIR);
    if ($ok && is_string($newStem) && $newStem !== '') {
        if ($image_stem !== '' && $image_stem !== $newStem) {
            Image::deleteImageVariants($image_stem, IMG_DIR);
        }
        $image_stem = $newStem;
    } else {
        $errors['image_file'] = $err ?? 'Errore durante l\'elaborazione dell\'immagine.';
    }
}

$pic = Image::resolvePictureSources($image_stem, IMG_DIR, IMG_BASE_URL);
$image_jpg_url  = $pic['jpg'];
$image_webp_url = $pic['webp'];

/* =========================
   Normalizzazione numeri
   ========================= */
$price = $data['price'];
if ($price !== '') {
    if (strpos($price, ',') !== false && strpos($price, '.') !== false) {
        $price = str_replace('.', '', $price); // rimuovi separatori migliaia
    }
    $price = str_replace(',', '.', $price);
}
$data['price'] = $price;

// Disponibilità: solo intero non negativo
$data['availability'] = trim($data['availability']);

/* =========================
   Validazione campi
   ========================= */
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


if ($data['price'] === '' || !is_numeric($data['price']) || (float)$data['price'] <= 0) {
    $errors['price'] = 'Inserisci il prezzo del prodotto, indicando un valore numerico maggiore di zero.';
} else {
    $data['price'] = number_format((float)$data['price'], 2, '.', '');
}


$availabilityInt = filter_var($data['availability'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
if ($availabilityInt === false) {
    $errors['availability'] = 'Inserisci la quantità disponibile del prodotto, indicando un numero intero uguale o superiore a zero.';
} else {
    $data['availability'] = (string)$availabilityInt;
}


$hasErrors = false;
foreach ($errors as $msg) { if ($msg !== '') { $hasErrors = true; break; } }

if ($hasErrors) {
    $page_mode = $product_id ? 'Modifica' : 'Inserisci';
    $short     = trim($data['short_name'] ?? '');
    $hasName   = ($short !== '');

    $meta_title = $hasName
        ? sprintf('%s prodotto: %s | Farmacia Archimede', $page_mode, $short)
        : sprintf('%s prodotto | Farmacia Archimede', $page_mode);

    $meta_description = $hasName
        ? sprintf('%s i dati del prodotto %s. Alcuni campi non sono validi: correggi e invia di nuovo.', $page_mode, $short)
        : sprintf('%s i dati del prodotto. Alcuni campi non sono validi: correggi e invia di nuovo.', $page_mode);

    $meta_keywords = $hasName
        ? sprintf('farmacia archimede, prodotti, %s, %s prodotto, errori form, validazione', $short, strtolower($page_mode))
        : 'farmacia archimede, prodotti, modifica, inserisci, errori form, validazione';


    $breadcrumb_product_li = '';
    if ($product_id && ($data['short_name'] ?? '') !== '') {
        $href = 'prodotto.php?id=' . rawurlencode((string)$product_id);
        $text = htmlspecialchars($data['short_name'], ENT_QUOTES);
        $breadcrumb_product_li = sprintf(
            '<li><a href="%s">%s</a><span class="separator" aria-hidden="true">&gt;&gt;</span></li>',
            $href,
            $text
        );
    }

    PageBuilder::show('modifica.php', [
        ...$data,
        'product_id'             => $product_id ?: '',
        'breadcrumb_product_li'  => $breadcrumb_product_li,
        'form_action'            => $product_id ? ('modifica.php?id=' . $product_id) : 'modifica.php',
        'errors'                 => $errors,
        'image_webp_url'         => $image_webp_url,
        'image_jpg_url'          => $image_jpg_url,
        'product_type_options'   => $productService->renderTypeOptions($data['product_type_id']),
        'format_options'         => $productService->renderFormatOptions($data['format']),
        'meta_title'             => $meta_title,
        'meta_description'       => $meta_description,
        'meta_keywords'          => $meta_keywords,
        'page_mode'              => $page_mode,
        'submit_label'           => $page_mode,
    ]);
    exit;
}

// Salvataggio
if ($product_id) {
    $productService->updateProduct($product_id, [
        ...$data,
        'image_path' => $image_stem,
        // cast consigliati
        'price' => (float)$data['price'],
        'availability' => (int)$data['availability'],
    ]);
    $_SESSION['flash_message'] = ['type'=>'success','message'=>'Il prodotto è stato aggiornato correttamente.'];
    header('Location: modifica.php?id=' . $product_id);
    exit;
}

$productService->insertProduct([
    ...$data,
    'image_path' => $image_stem,
    'price' => (float)$data['price'],
    'availability' => (int)$data['availability'],
]);
$_SESSION['flash_message'] = ['type'=>'success','message'=>'Il nuovo prodotto è stato inserito correttamente.'];
header('Location: prodotti.php');
exit;
