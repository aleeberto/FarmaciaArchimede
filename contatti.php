<?php
$root = ".";

$page_index = 3;

require_once($root . "/php/main.php");

$prodotti_template = $builder->load_template("contatti.html");
$prodotti_template->insert("header", build_header());
$prodotti_template->insert("footer", build_footer());

echo $prodotti_template->build();
?>