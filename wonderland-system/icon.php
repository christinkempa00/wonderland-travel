<?php
/**
 * Dynamic PWA Icon Generator
 * Creates a simple colored icon with "TM" text
 */

$size = (int) ($_GET['size'] ?? 144);
$size = max(16, min(512, $size)); // Limit 16-512

// Create image
$image = imagecreatetruecolor($size, $size);

// Colors
$bgColor = imagecolorallocate($image, 30, 64, 175); // Primary blue
$textColor = imagecolorallocate($image, 255, 255, 255);

// Fill background with rounded corners effect (blue gradient)
imagefilledrectangle($image, 0, 0, $size, $size, $bgColor);

// Add gradient effect
for ($i = 0; $i < $size / 2; $i++) {
    $gradColor = imagecolorallocatealpha($image, 59, 130, 246, (int)(127 * ($i / ($size / 2))));
    imagefilledrectangle($image, $i, $i, $size - $i, $size - $i, $gradColor);
}

// Reset background
imagefilledrectangle($image, 0, 0, $size, $size, $bgColor);

// Add text "TM"
$fontSize = $size * 0.35;
$fontFile = __DIR__ . '/assets/fonts/Inter-Bold.ttf';

// If font file doesn't exist, use built-in font
if (file_exists($fontFile)) {
    $bbox = imagettfbbox($fontSize, 0, $fontFile, 'TM');
    $x = ($size - ($bbox[2] - $bbox[0])) / 2;
    $y = ($size - ($bbox[1] - $bbox[7])) / 2 + ($bbox[7] * -1);
    imagettftext($image, $fontSize, 0, (int)$x, (int)$y, $textColor, $fontFile, 'TM');
} else {
    // Use built-in font (larger sizes use imagestring scale)
    $fontScale = max(1, (int)($size / 40));
    $text = 'TM';
    $fontWidth = imagefontwidth(5) * strlen($text);
    $fontHeight = imagefontheight(5);
    $x = ($size - $fontWidth * $fontScale) / 2;
    $y = ($size - $fontHeight * $fontScale) / 2;
    
    // Scale up the text
    $tempImg = imagecreatetruecolor($fontWidth, $fontHeight);
    imagefill($tempImg, 0, 0, $bgColor);
    imagestring($tempImg, 5, 0, 0, $text, $textColor);
    imagecopyresized($image, $tempImg, (int)$x, (int)$y, 0, 0, $fontWidth * $fontScale, $fontHeight * $fontScale, $fontWidth, $fontHeight);
    imagedestroy($tempImg);
}

// Output
header('Content-Type: image/png');
header('Cache-Control: public, max-age=31536000');
imagepng($image);
imagedestroy($image);
