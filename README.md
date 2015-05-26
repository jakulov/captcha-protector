# captcha-protector
PHP Captcha Protector tiny library

This library is useful, when you need a simple captcha protection for any of you website part. It could be login form, or search form.
Captcha Protector will help you protect your project from brute-force attacks or spam.

# Installation
You can install Captcha Protector via composer

    require jakulov/captcha-protector

# Live examples
Live examples presented at [http://cp.jakulov.ru](http://cp.jakulov.ru)
The code of examples can be found in this repo: [src/examples](https://github.com/jakulov/captcha-protector/tree/master/src/examples)

You can run examples on your machine, if you have installed PHP.
To do this, just clone this repo. Than in console do:

    composer install
    cd app
    php -S localhost:8000

And now open [http://localhost:8000/app/](http://localhost:8000/app/)

If you don't have composer, visit [https://getcomposer.org/download/](https://getcomposer.org/download/)

# TODO
1. Request new captcha image without page reload
