<?php
$root = ".";

$page_index = 0;

require_once($root . "/php/main.php");

$index_template = $builder->load_template("index.html");
$index_template->insert("header", build_header());
$index_template->insert("footer", build_footer());

echo $index_template->build();
?>
