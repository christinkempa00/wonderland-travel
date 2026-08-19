<?php
/**
 * Dynamic PWA Icon Generator
 * Serves the company's uploaded logo (resized, transparent background) when
 * one exists; falls back to a generated "TM" placeholder icon otherwise.
 */

define('BASE_PATH', __DIR__);
require_once BASE_PATH . '/config/constants.php';
require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/config/session.php';
Session::start();

$size = (int) ($_GET['size'] ?? 144);
$size = max(16, min(512, $size)); // Limit 16-512

/**
 * Try to render the company's uploaded logo as a size x size PNG icon.
 * Returns true (and sends the image) on success, false if there's no logo
 * or it couldn't be loaded — caller falls back to the generated icon.
 */
function serveCompanyLogoIcon(string $logoPath, int $size): bool {
    $fullPath = UPLOADS_PATH . '/' . $logoPath;
    if (!is_file($fullPath)) {
        return false;
    }

    $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
    switch ($ext) {
        case 'jpg':
        case 'jpeg':
            $src = @imagecreatefromjpeg($fullPath);
            break;
        case 'png':
            $src = @imagecreatefrompng($fullPath);
            break;
        case 'gif':
            $src = @imagecreatefromgif($fullPath);
            break;
        case 'webp':
            $src = function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($fullPath) : false;
            break;
        default:
            $src = false;
    }
    if (!$src) {
        return false;
    }

    $srcW = imagesx($src);
    $srcH = imagesy($src);

    $canvas = imagecreatetruecolor($size, $size);
    imagesavealpha($canvas, true);
    imagealphablending($canvas, false);
    $transparent = imagecolorallocatealpha($canvas, 255, 255, 255, 127);
    imagefill($canvas, 0, 0, $transparent);
    imagealphablending($canvas, true);

    // Scale to fit within the square canvas, preserving aspect ratio.
    $scale = min($size / $srcW, $size / $srcH);
    $newW = max(1, (int) round($srcW * $scale));
    $newH = max(1, (int) round($srcH * $scale));
    $dstX = (int) (($size - $newW) / 2);
    $dstY = (int) (($size - $newH) / 2);

    imagecopyresampled($canvas, $src, $dstX, $dstY, 0, 0, $newW, $newH, $srcW, $srcH);
    imagedestroy($src);

    header('Content-Type: image/png');
    header('Cache-Control: public, max-age=3600');
    imagepng($canvas);
    imagedestroy($canvas);
    return true;
}

if (Session::isLoggedIn() && Session::companyId()) {
    $company = db()->fetchOne("SELECT logo FROM companies WHERE id = ?", [Session::companyId()]);
    if (!empty($company['logo']) && serveCompanyLogoIcon($company['logo'], $size)) {
        exit;
    }
}

// ============================================
// Fallback: generated "TM" placeholder icon
// ============================================

// Create image
$image = imagecreatetruecolor($size, $size);

// Colors
$bgColor = imagecolorallocate($image, 107, 82, 22); // Primary gold
$textColor = imagecolorallocate($image, 255, 255, 255);

// Fill background with rounded corners effect (gold gradient)
imagefilledrectangle($image, 0, 0, $size, $size, $bgColor);

// Add gradient effect
for ($i = 0; $i < $size / 2; $i++) {
    $gradColor = imagecolorallocatealpha($image, 200, 155, 44, (int)(127 * ($i / ($size / 2))));
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
