<?php
session_start();

const CAPTCHA_WIDTH = 350;
const CAPTCHA_HEIGHT = 50;
const CAPTCHA_FONT = __DIR__ . '/assets/fonts/Pinar-Regular.ttf';

function generate_captcha_code(int $length, int $type = 1): string
{
    $letters = $type === 2
        ? '123456789'
        : '123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $maxIndex = strlen($letters) - 1;
    $captcha = '';

    for ($i = 0; $i < $length; $i += 1) {
        $captcha .= $letters[random_int(0, $maxIndex)];
    }

    return $captcha;
}

function output_captcha_image(string $captcha, string $fontPath): void
{
    $image = imagecreatetruecolor(CAPTCHA_WIDTH, CAPTCHA_HEIGHT);
    $background = imagecolorallocate($image, 255, 255, 255);
    imagefill($image, 0, 0, $background);

    $textColors = [
        imagecolorallocate($image, 49, 165, 74),
        imagecolorallocate($image, 26, 213, 32),
        imagecolorallocate($image, 207, 24, 24),
        imagecolorallocate($image, 222, 225, 0),
        imagecolorallocate($image, 225, 124, 0),
        imagecolorallocate($image, 0, 80, 225),
        imagecolorallocate($image, 225, 0, 166),
        imagecolorallocate($image, 0, 225, 183),
        imagecolorallocate($image, 150, 207, 60),
        imagecolorallocate($image, 233, 30, 99),
        imagecolorallocate($image, 255, 235, 59),
        imagecolorallocate($image, 0, 150, 136),
    ];

    for ($i = 0; $i < 16; $i += 1) {
        $lineColor = imagecolorallocate($image, 200, 200, 200);
        imageline(
            $image,
            random_int(0, CAPTCHA_WIDTH),
            random_int(0, CAPTCHA_HEIGHT),
            random_int(0, CAPTCHA_WIDTH * 2),
            random_int(0, CAPTCHA_HEIGHT * 2),
            $lineColor
        );
    }

    $x = 20;
    $y = 36;

    for ($i = 0, $length = strlen($captcha); $i < $length; $i += 1) {
        $angle = random_int(-10, 10);
        $fontSize = random_int(26, 32);
        $letter = substr($captcha, $i, 1);
        $color = $textColors[array_rand($textColors)];
        $coords = imagettftext($image, $fontSize, $angle, $x, $y, $color, $fontPath, $letter);
        $x = $coords[2] + random_int(6, 12);
    }

    header('Content-Type: image/png');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');

    imagepng($image);
    imagedestroy($image);
}

if (!file_exists(CAPTCHA_FONT)) {
    http_response_code(500);
    exit;
}

if (empty($_SESSION['security_code'])) {
    $_SESSION['security_code'] = generate_captcha_code(5, 2);
}

output_captcha_image($_SESSION['security_code'], CAPTCHA_FONT);
