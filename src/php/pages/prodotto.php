<?php

$root = dirname(__DIR__, 2);

$page_index = 2;

require_once($root . "/php/main.php");

$prodotto_template = $builder->load_template("prodotto.html");
$prodotto_template->insert("head", build_head());
$prodotto_template->insert("header", build_header());
$prodotto_template->insert("footer", build_footer());

$id = $_GET["id"];

$prodottoService = new ProdottoService($db);
$prodotto = $prodottoService->getProductByID($id);

// Recupera immagine prodotto
$immagine = $prodottoService->getProductImage($prodotto->id);
$imagePath = $immagine ? $immagine->path : "/assets/img/default.jpg";
$imageAlt = $immagine ? $immagine->alt : "Immagine non disponibile";

$prodotto_template->insert_all(array(
    "shortNome" => $prodotto->shortNome,
    "nome" => $prodotto->nome,
    "tipo" => $prodotto->tipo,
    "descrizone" => $prodotto->descrizione,
    "produttore" => $prodotto->produttore,
    "codice" => $prodotto->codice_aic,
    "disponibilita" => $prodotto->disponibilita,
    "prezzo" => number_format($prodotto->prezzo, 2, ',', '.') . "€",
    "immagine" => "<img src='{$imagePath}' alt='{$imageAlt}' width='100' height='100'>",
    
));

echo $prodotto_template->build();

?>
