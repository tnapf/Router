<?php

$codes = json_decode(file_get_contents("./HttpCodes.json"));
ob_start();
?>

## Available HttpExceptions

| Code | Phrase | ClassName |
|------|--------|-----------|
<?php
foreach ($codes as $code) { 
    if ((!str_starts_with($code->code, "4") && !str_starts_with($code->code, "5"))) {
        continue;
    } 
    
    $className = "Http".preg_replace("/[^a-zA-Z]/", "", $code->phrase);
?>
|<?= $code->code ?>|[<?= $code->phrase ?>](<?= $code->mdn ?>)|[<?= $className ?>](https://github.com/tnapf/Router/blob/main/src/Exceptions/<?= $className ?>.php)
<?php }

file_put_contents("./HttpExceptions.md", ob_get_clean());

