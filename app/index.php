<?php

require_once __DIR__ . '/../vendor/autoload.php';

$defaultPage = 'index';
$examples = [
    'index' => 'Home',
    'login' => 'Example 1',
    'search' => 'Example 2',
    '404' => 'Not found',
];
$page = isset($_GET['page']) && $_GET['page'] ? $_GET['page'] : $defaultPage;
$uri = explode('?', $_SERVER['REQUEST_URI'])[0];
if(!isset($examples[$page]) || $uri !== '/') {
    $page = '404';
}
$title = $examples[$page];

if($page === '404') {
    header('HTTP/1.0 404 Not Found');
}
ob_start();
?>
<html>
<head>
    <meta charset="utf-8">
    <title><?php echo $title?> &ndash; PHP Captcha protector</title>

    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.4/css/bootstrap.min.css">
    <link href="./public/css/cover.css" rel="stylesheet">

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.4/js/bootstrap.min.js"></script>
</head>
<body>
<div class="site-wrapper">

    <div class="site-wrapper-inner">

        <div class="cover-container">

            <div class="masthead clearfix">
                <div class="inner">
                    <h3 class="masthead-brand">Captcha Protector</h3>
                    <nav>
                        <ul class="nav masthead-nav">
                            <?php foreach($examples as $e => $name):?>
                                <?php if ($e === 404) continue;?>
                                <li <?php if($page === $e):?>class="active"<?php endif?>>
                                    <a href="<?php if($e !== $defaultPage):?>./?page=<?php echo $e?><?php else:?>/<?php endif?>"><?php echo $name?></a>
                                </li>
                            <?php endforeach?>
                        </ul>
                    </nav>
                </div>
            </div>

            <div class="inner cover">
                <?php require __DIR__ .'/../src/examples/'. $page .'.phtml'?>
            </div>

            <div class="mastfoot">
                <div class="inner">
                    <p>
                        PHP Captcha protector library, using
                        <a href="https://github.com/Gregwar/Captcha">Gregwar/Captcha</a>,
                        by <a href="http://jakulov.ru">@jakulov</a>.
                    </p>
                </div>
            </div>

        </div>

    </div>

</div>

<?php
$exampleCode = __DIR__ .'/../src/examples/'. $page .'_code.phtml';
if(file_exists($exampleCode)) {
    require $exampleCode;
}
?>
</body>
</html>
