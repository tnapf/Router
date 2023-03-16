<?php
/**
 * @var string $title
 * @var string $phrase
 * @var string $href
 */
?>
<!DOCTYPE HTML>
<html lang="en">
<head>
    <title><?php echo $title ?></title>
</head>
<body>
    <style>
        * {
            font-family: Arial, Helvetica, sans-serif;
            text-align: center;
        }

        body {
            background: #1b1c1d;
            color: white;
            padding-top: calc(50vh - 95px);
        }

        body>div {
            max-width: 90%;
            margin: auto;
            width: fit-content;
        }
    </style>
    <div>
        <h1>
            <?= $code ?> - <?= !empty($href) ? "<a href=\"{$href}\">{$phrase}</a>" : $phrase; ?>
        </h1>
        <hr>
        <p><?= $description ?></p>
    </div>
</body>

</html>
