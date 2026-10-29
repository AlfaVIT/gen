<?php

function uploadsDirReal()
{
    $dir = cfg('uploads_dir');
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
    return $dir;
}

function ensureTreeUploadDir($treeId)
{
    $dir = uploadsDirReal() . '/t' . (int)$treeId;
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
    if (!is_dir($dir . '/thumb')) {
        mkdir($dir . '/thumb', 0775, true);
    }
    return $dir;
}

function mapMimeToKind($mime)
{
    if (strpos($mime, 'image/') === 0) return 'image';
    if (strpos($mime, 'video/') === 0) return 'video';
    if (strpos($mime, 'audio/') === 0) return 'audio';
    if ($mime === 'application/pdf') return 'pdf';
    return null;
}

function mediaUploadFromPost($treeId, $field)
{
    if (empty($_FILES[$field]) || !is_uploaded_file($_FILES[$field]['tmp_name'])) {
        throw new RuntimeException('Файл не был загружен.');
    }
    $up = $_FILES[$field];
    if ($up['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Ошибка загрузки файла (код ' . $up['error'] . ').');
    }
    if ($up['size'] > (int)cfg('upload_max_bytes')) {
        throw new RuntimeException('Файл слишком большой. Максимум ' . round(cfg('upload_max_bytes') / 1048576) . ' МБ.');
    }
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = (string)$finfo->file($up['tmp_name']);
    $allowed = cfg('allowed_mime');
    if (!isset($allowed[$mime])) {
        throw new RuntimeException('Недопустимый тип файла.');
    }
    $ext = $allowed[$mime];
    $treeDir = ensureTreeUploadDir($treeId);
    $name = bin2hex(random_bytes(12)) . '.' . $ext;
    if (!move_uploaded_file($up['tmp_name'], $treeDir . '/' . $name)) {
        throw new RuntimeException('Не удалось сохранить файл.');
    }
    $kind = mapMimeToKind($mime);
    $rel = 'uploads/t' . (int)$treeId . '/' . $name;
    if ($kind === 'image') {
        makeThumb($rel);
    }
    return [
        'file' => $rel,
        'original' => (string)$up['name'],
        'mime' => $mime,
        'size' => (int)$up['size'],
        'kind' => $kind,
    ];
}

function mediaFileReal($rel)
{
    $root = realpath(dirname(__DIR__, 2)) ?: dirname(__DIR__, 2);
    $path = realpath($root . '/' . $rel);
    $up = realpath(uploadsDirReal());
    if (!$path || !$up || strpos($path, $up) !== 0) {
        return false;
    }
    return $path;
}

function deleteMediaFile($rel)
{
    $real = mediaFileReal($rel);
    if ($real && is_file($real)) {
        @unlink($real);
        $thumb = preg_replace('/\.[a-z0-9]+$/i', '_th.jpg', $real);
        if (is_file($thumb)) {
            @unlink($thumb);
        }
    }
}

function makeThumb($rel)
{
    $real = mediaFileReal($rel);
    if (!$real) return null;
    $info = @getimagesize($real);
    if (!$info || !in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_WEBP], true)) {
        return null;
    }
    $thumbFile = preg_replace('/\.[a-z0-9]+$/i', '_th.jpg', $real);
    $src = @imagecreatefromstring(file_get_contents($real));
    if (!$src) return null;
    $w = imagesx($src);
    $h = imagesy($src);
    $mw = 420;
    if ($w > $mw) {
        $nh = (int)round($h * $mw / $w);
        $dst = imagecreatetruecolor($mw, $nh);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $mw, $nh, $w, $h);
        imagedestroy($src);
        $src = $dst;
    }
    imagejpeg($src, $thumbFile, 82);
    imagedestroy($src);
    return $thumbFile ? str_replace('\\', '/', substr($thumbFile, strlen(dirname($thumbFile)))) : null;
}

function mediaViewUrl($rel)
{
    return basePath() . '/' . ltrim(str_replace('\\', '/', $rel), '/');
}

function gdOpenImage($realPath)
{
    if (!function_exists('imagecreatefromstring')) {
        return null;
    }
    $data = @file_get_contents($realPath);
    if ($data === false) {
        return null;
    }
    $img = @imagecreatefromstring($data);
    return $img ?: null;
}

function gdSaveJpeg($img, $dst, $quality = 88)
{
    imagejpeg($img, $dst, $quality);
}

function avatarResizeToMax($srcReal, $dstReal, $maxSide)
{
    $img = gdOpenImage($srcReal);
    if (!$img) {
        throw new RuntimeException('Не удалось прочитать изображение.');
    }
    $w = imagesx($img);
    $h = imagesy($img);
    if ($w <= 0 || $h <= 0) {
        imagedestroy($img);
        throw new RuntimeException('Некорректное изображение.');
    }
    $scale = 1;
    $minSide = min($w, $h);
    if ($minSide > $maxSide) {
        $scale = $maxSide / $minSide;
    }
    $nw = (int)round($w * $scale);
    $nh = (int)round($h * $scale);
    $out = imagecreatetruecolor($nw, $nh);
    imagecopyresampled($out, $img, 0, 0, 0, 0, $nw, $nh, $w, $h);
    gdSaveJpeg($out, $dstReal, 90);
    imagedestroy($out);
    imagedestroy($img);
}

function avatarCropSquareTo($srcReal, $dstReal, $x, $y, $size, $outSize)
{
    $img = gdOpenImage($srcReal);
    if (!$img) {
        throw new RuntimeException('Не удалось прочитать изображение.');
    }
    $w = imagesx($img);
    $h = imagesy($img);
    $x = max(0, min($w - 1, (int)$x));
    $y = max(0, min($h - 1, (int)$y));
    $max = max(1, min($w - $x, $h - $y));
    $size = max(1, min($max, (int)$size));
    $crop = imagecreatetruecolor($size, $size);
    imagecopyresampled($crop, $img, 0, 0, $x, $y, $size, $size, $size, $size);
    $out = imagecreatetruecolor($outSize, $outSize);
    imagecopyresampled($out, $crop, 0, 0, 0, 0, $outSize, $outSize, $size, $size);
    gdSaveJpeg($out, $dstReal, 92);
    imagedestroy($out);
    imagedestroy($crop);
    imagedestroy($img);
}

function avatarTreeDir($treeId)
{
    $dir = uploadsDirReal() . '/t' . (int)$treeId;
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
    return $dir;
}

function avatarUploadFromPost($treeId, $personId, $field)
{
    if (empty($_FILES[$field]) || !is_uploaded_file($_FILES[$field]['tmp_name'])) {
        throw new RuntimeException('Файл не выбран.');
    }
    $up = $_FILES[$field];
    if ($up['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Ошибка загрузки файла.');
    }
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = (string)$finfo->file($up['tmp_name']);
    $allowed = cfg('allowed_mime');
    if (!isset($allowed[$mime]) || strpos($mime, 'image/') !== 0) {
        throw new RuntimeException('Допустимы только изображения (JPG/PNG/GIF/WebP).');
    }
    $dir = avatarTreeDir($treeId);
    $name = 'avfull_' . (int)$personId . '_' . bin2hex(random_bytes(8)) . '.jpg';
    $dst = $dir . '/' . $name;
    try {
        avatarResizeToMax($up['tmp_name'], $dst, 800);
    } catch (RuntimeException $ex) {
        @unlink($dst);
        throw $ex;
    }
    return 'uploads/t' . (int)$treeId . '/' . $name;
}
