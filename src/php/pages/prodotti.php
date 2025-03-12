<?php
$root = dirname(__DIR__, 2);

$page_index = 1;

require_once($root . "/php/main.php");

// Usa il nome corretto della variabile
$prodottoService = new ProdottoService($db);
$prodotti = $prodottoService->getAllProducts();

$prodotti_template = $builder->load_template("prodotti.html");
$prodotti_template->insert("head", build_head());
$prodotti_template->insert("header", build_header());
$prodotti_template->insert("footer", build_footer());

function build_item(ProdottoDTO $prodotto): string {
    global $builder;
    global $prodottoService; // Aggiunto per recuperare le immagini

    $prodotti_template = $builder->load_template("item.html");

    // Recupera immagine prodotto
    $immagine = $prodottoService->getProductImage($prodotto->id);
    $imagePath = $immagine ? $immagine->path : "/assets/img/default.jpg";
    $imageAlt = $immagine ? $immagine->alt : "Immagine non disponibile";

    $prodotti_template->insert_all(array(
        "nome" => $prodotto->nome,
        "prezzo" => number_format($prodotto->prezzo, 2, ',', '.') . "€",
        "immagine" => "<img src='{$imagePath}' alt='{$imageAlt}' width='100' height='100'>",
        "url_farmaco" => "/php/pages/prodotto.php?id={$prodotto->id}",
    ));
    return $prodotti_template->build();
}

$items = "";
foreach ($prodotti as $prodotto) {
    $items .= build_item($prodotto);
}

$prodotti_template->insert("items", $items);
echo $prodotti_template->build();
?>
