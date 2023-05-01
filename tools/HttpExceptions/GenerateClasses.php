<?php

$codes = json_decode(file_get_contents(__DIR__."/HttpCodes.json"));

const TAB = "    ";

abstract class Code {
    public string $code;
    public string $phrase;
    public string $description;
    public string $mdn;
}

foreach ($codes as $key => $code) {
    /** @var Code $code */

    if ((!str_starts_with($code->code, "4") && !str_starts_with($code->code, "5"))) {
        continue;
    }

    $namespace = "Tnapf\Router\Exceptions";

    $parsedPhrase = preg_replace("/[^a-zA-Z]/", "", $code->phrase);
    $className = "Http".$parsedPhrase;

    $psrDescription = "";
    $words = explode(" ", $code->description);

    $line = "\"";
    foreach ($words as $word) {
        if (strlen($line) > 50) {
            $psrDescription .= $line . "\" . \n    \"";
            $line = "";
        }

        $line .= $word . " ";
    }

    if (!empty($line)) {
        $psrDescription .= $line;
    }
    $psrDescription = trim($psrDescription);

    ob_start();
    echo "<?php\n"; ?>

namespace <?= $namespace ?>;

class <?= $className ?> extends HttpException
{
    public const CODE = <?= $code->code ?>;
    public const PHRASE = "<?= $code->phrase ?>";
    public const DESCRIPTION = <?= $psrDescription ?>";
    public const HREF = "<?= $code->mdn ?>";
}
<?php

file_put_contents(__DIR__."/../../src/Exceptions/{$className}.php", ob_get_clean());

}
