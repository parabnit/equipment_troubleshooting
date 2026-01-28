<?php
session_start();

// Function to generate a random CAPTCHA string with uppercase, lowercase letters, and numbers
function generateCaptchaText($length = 5) {
    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    $captchaText = '';
    for ($i = 0; $i < $length; $i++) {
        $captchaText .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $captchaText;
}

// Generate the CAPTCHA text and store it in the session
$captchaText = generateCaptchaText();
$_SESSION['captcha_code'] = $captchaText;

// Set content type to image
header('Content-Type: image/png');

// Create a blank image
$image = imagecreatetruecolor(150, 40);

// Set background and text colors
$background = imagecolorallocate($image, 255, 255, 255);
$textColor = imagecolorallocate($image, 0, 0, 0);
$lineColor = imagecolorallocate($image, 64, 64, 64);

// Fill background
imagefilledrectangle($image, 0, 0, 150, 40, $background);

// Add random lines for noise
// for ($i = 0; $i < 5; $i++) {
    // imageline($image, rand(0, 150), rand(0, 40), rand(0, 150), rand(0, 40), $lineColor);
// }

// Path to the font file (use an existing font path)
$fontFile = '../assets/font/monofont.ttf';
if (!file_exists($fontFile)) {
    die('Error: Font file not found at ' . $fontFile);
}

// Add the CAPTCHA text to the image
imagettftext($image, 28, 0, 15, 30, $textColor, $fontFile, $captchaText);

// Output the image
imagepng($image);
imagedestroy($image);
?>