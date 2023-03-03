<?php

$codes = json_decode(file_get_contents("./HttpCodes.json"));

define("TAB", "    ");

abstract class Code {
    public string $code;
    public string $phrase;
    public string $description;
    public string $spec_title;
    public string $spec_href;
}

echo TAB.'private static array $catchers = ['.PHP_EOL;

foreach ($codes as $key => $code) {
    /** @var Code $code */

    if ((!str_starts_with($code->code, "4") && !str_starts_with($code->code, "5")) || str_contains($code->code, "x")) {
        continue;
    }

    $parsedPhrase = preg_replace("/[^a-zA-Z]/", "", $code->phrase);

    $namespace = "Tnapf\Router\Exceptions";
    $className = "Http".str_replace(" ", "", $parsedPhrase);

    $code->description = ucfirst(str_replace('"', "", $code->description));
    $code->spec_href = "https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/{$code->code}";

    ob_start(); 
    echo "<?php\n"; ?>

namespace <?= $namespace ?>;

class <?= $className ?> extends HttpException {
    public const CODE = <?= $code->code ?>;
    public const PHRASE = "<?= $code->phrase ?>";
    public const DESCRIPTION = "<?= $code->description ?>";
    public const HREF = "<?= $code->spec_href ?>";
}

<?php 

file_put_contents("../$className.php", ob_get_clean());



echo TAB.TAB."Exceptions\\$className::class => []";
echo ($key !== count($codes)-1) ? ",\n" : "\n";

}

echo TAB."];";

echo "\nBE SURE TO UPDATE 'src/Router.php:32'";