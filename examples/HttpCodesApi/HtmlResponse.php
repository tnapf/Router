<!DOCTYPE HTML>
<html lang='en'>
<head>
    <title><?= $code->code ?> - <?= $code->phrase ?></title>
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
        <h1><?= $code->code ?> - <a href='<?= $code->mdn ?>'><?= $code->phrase ?></a></h1>
        <hr>
        <p><?= $code->description ?></p>
    </div>
</body>
</html>
