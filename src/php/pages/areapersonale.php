<?php
$root = dirname(__DIR__, 2);

$page_index = 4;

require_once($root . "/php/main.php");

$areapersonale_template = $builder->load_template("areapersonale.html");
$areapersonale_template->insert("head", build_head());
$areapersonale_template->insert("header", build_header());
$areapersonale_template->insert("footer", build_footer());

echo $areapersonale_template->build();
?>