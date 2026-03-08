<?php
$sourcePath = __DIR__ . '/img/logo_jwquiz.png';
$iconsDir = __DIR__ . '/img/icons';

if (!file_exists($iconsDir)) {
    mkdir($iconsDir, 0777, true);
}

function resizeImage($file, $w, $h, $output) {
    list($width, $height) = getimagesize($file);
    $src = imagecreatefrompng($file);
    $dst = imagecreatetruecolor($w, $h);

    // Preserve transparency
    imagealphablending($dst, false);
    imagesavealpha($dst, true);
    $transparent = imagecolorallocatealpha($dst, 255, 255, 255, 127);
    imagefilledrectangle($dst, 0, 0, $w, $h, $transparent);

    // Calculate aspect ratio preserving resize
    $ratio = min($w / $width, $h / $height);
    $new_width = $width * $ratio;
    $new_height = $height * $ratio;
    
    // Center the image in the new square canvas
    $x = ($w - $new_width) / 2;
    $y = ($h - $new_height) / 2;

    imagecopyresampled($dst, $src, $x, $y, 0, 0, $new_width, $new_height, $width, $height);
    imagepng($dst, $output);
    imagedestroy($src);
    imagedestroy($dst);
}

if (file_exists($sourcePath)) {
    echo "Creating 192x192 icon...\n";
    resizeImage($sourcePath, 192, 192, $iconsDir . '/icon-192x192.png');
    echo "Creating 512x512 icon...\n";
    resizeImage($sourcePath, 512, 512, $iconsDir . '/icon-512x512.png');
    echo "Icons generated successfully.\n";
} else {
    echo "Source logo not found at $sourcePath\n";
}
?>
