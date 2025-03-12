<?php
$root = dirname(__DIR__, 2);

$page_index = 3;

require_once($root . "/php/main.php");

$contatti_template = $builder->load_template("contatti.html");
$contatti_template->insert("head", build_head());
$contatti_template->insert("header", build_header());
$contatti_template->insert("footer", build_footer());

echo $contatti_template->build();
?>