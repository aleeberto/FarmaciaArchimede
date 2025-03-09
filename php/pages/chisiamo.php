<?php
$root = dirname(__DIR__, 2);

$page_index = 2;

require_once($root . "/php/main.php");

$prodotti_template = $builder->load_template("chisiamo.html");
$prodotti_template->insert("header", build_header());
$prodotti_template->insert("footer", build_footer());

echo $prodotti_template->build();
?>