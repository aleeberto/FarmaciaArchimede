<?php
$root = dirname(__DIR__, 2);

$page_index = 2;

require_once($root . "/php/main.php");

$chisiamo_template = $builder->load_template("chisiamo.html");
$chisiamo_template->insert("head", build_head());
$chisiamo_template->insert("header", build_header());
$chisiamo_template->insert("footer", build_footer());

echo $chisiamo_template->build();
?>