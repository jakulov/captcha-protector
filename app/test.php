<?php
// more simple test
// run php -S localhost:8000
// open http://localhost:8000/test.php
// test it!

require_once __DIR__ . '/../vendor/autoload.php';

$successLogin = $processLogin = false;
$error = '';
$protector = new \CaptchaProtector\Protector(__DIR__ .'/../var', 2);
$login = $password = 'test';
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $processLogin = true;
    $protector->protect('test');
    if($protector->isNeedCaptcha() && $protector->isCaptchaShown()) {
        if($protector->isCaptchaResolved(isset($_POST['captcha']) ? $_POST['captcha'] : '')) {
            $processLogin = true;
        }
        else {
            $processLogin = false;
            $error = 'Invalid text from picture';
        }
    }
    if($processLogin) {
        if($_POST['login'] === $login && $_POST['password'] === $password) {
            $protector->forgive('test');
            $successLogin = true;
        }
        else {
            $error = 'Invalid login or password';
        }
    }
}
ob_start();
?>
<h1>Test form example.</h1>
<p>Form will require captcha after 3 incorrect login attempts.</p>
<p><small>Correct login and password: "test:test"</small></p>
<?php if($successLogin):?>
    <p>You've logged in successfully!</p>
    <p><a href="./test.php">Logout</a></p>
<?php else:?>
<form method="post">
    <?php if($error):?><div style="color: red;"><?php echo $error?></div><?php endif?>
    <label>Login: <br><input required type="text" name="login" value="<?php echo ($_POST ? $_POST['login'] : '')?>"></label><br>
    <label>Password: <br><input required type="password" name="password" value="<?php echo ($_POST ? $_POST['password'] : '')?>"></label><br>
    <?php if($protector->isNeedCaptcha()):?>
        <img src="<?php echo $protector->getCaptcha(150, 50)?>"><br>
        <label>Captcha: <br><input type="text" name="captcha" value="" placeholder="Enter text from image above" required></label>
    <?php endif?>
    <br><br>
    <input type="submit" value="Log In">
</form>
<?php endif?>
