<?php
$titolo = "Farmacia Archimede";
$descrizione = "TODO TODO TODO";

echo file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . "templates" . DIRECTORY_SEPARATOR . "header.template.html");

echo file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . "templates" . DIRECTORY_SEPARATOR . "index.template.html");

echo file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . "templates" . DIRECTORY_SEPARATOR . "footer.template.html");

?>