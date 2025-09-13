<?php
declare(strict_types=1);

namespace App\Core;

final class Image
{
    public const MAX_FILE_BYTES = 1_600_000;
    public const BOX = 700;
    public const PLACEHOLDER_STEM = 'placeholder';
    private const JPG_Q = 82;
    private const WEBP_Q = 80;

    /**
     * @param array{tmp_name:string,name?:string,size?:int,error?:int} $upload
     * @return array{0:bool,1:?string,2:?string}
     */
    public static function store(array $upload, string $targetDir): array
    {
        if (!self::okUpload($upload)) {
            return [false, null, "Errore durante l'upload dell'immagine."];
        }
        $size = (int)($upload['size'] ?? 0);
        if ($size <= 0 || $size > self::MAX_FILE_BYTES) {
            @unlink($upload['tmp_name']);
            return [false, null, 'Dimensione immagine troppo grande. Max: 1.5MB.'];
        }
        if (!self::ensureDir($targetDir)) {
            @unlink($upload['tmp_name']);
            return [false, null, 'Impossibile creare la cartella immagini.'];
        }

        $kind = self::detectKind($upload['tmp_name']); // 'jpeg' | 'webp' | null
        if ($kind === null) {
            @unlink($upload['tmp_name']);
            return [false, null, 'Formato non supportato. Accettati: JPG o WEBP.'];
        }

        $stem = self::uniqueStem($targetDir);

        $src = self::loadSource($upload['tmp_name'], $kind);
        if (!$src instanceof \GdImage) {
            @unlink($upload['tmp_name']);
            return [false, null, 'Immagine non valida.'];
        }
        if ($kind === 'jpeg') {
            $src = self::autoOrient($src, $upload['tmp_name']);
        }

        $canvasWebp = self::fitToCanvas($src, self::BOX, self::BOX, true);
        $canvasJpg  = self::fitToCanvas($src, self::BOX, self::BOX, false);

        $webpPath = self::path($targetDir, $stem, 'webp');
        $jpgPath  = self::path($targetDir, $stem, 'jpg');

        $okWebp = function_exists('imagewebp') ? @imagewebp($canvasWebp, $webpPath, self::WEBP_Q) : false;

        if ($kind === 'jpeg') {
            $okJpg = function_exists('imagejpeg') ? @imagejpeg($canvasJpg, $jpgPath, self::JPG_Q) : false;
            imagedestroy($canvasWebp);
            imagedestroy($canvasJpg);
            imagedestroy($src);
            @unlink($upload['tmp_name']);

            if (!$okWebp || !$okJpg) {
                @unlink($webpPath); @unlink($jpgPath);
                return [false, null, 'Errore nella creazione dei file JPG/WEBP.'];
            }
            return [true, $stem, null];
        }

        $okJpg = true;
        if (function_exists('imagejpeg')) {
            $okJpg = @imagejpeg($canvasJpg, $jpgPath, self::JPG_Q);
        }

        imagedestroy($canvasWebp);
        imagedestroy($canvasJpg);
        imagedestroy($src);
        @unlink($upload['tmp_name']);

        if (!$okWebp) {
            @unlink($webpPath); @unlink($jpgPath);
            return [false, null, 'Errore nella creazione del file WEBP.'];
        }
        if (!$okJpg) { @unlink($jpgPath); }

        return [true, $stem, null];
    }

    /** @return array{webp:string,jpg:string} */
    public static function resolvePictureSources(string $stem, string $dir, string $baseUrl): array
    {
        $s = $stem !== '' ? $stem : self::PLACEHOLDER_STEM;

        $webp = is_file(self::path($dir, $s, 'webp'))
            ? self::url($baseUrl, $s, 'webp')
            : self::url($baseUrl, self::PLACEHOLDER_STEM, 'webp');

        $jpg = is_file(self::path($dir, $s, 'jpg'))
            ? self::url($baseUrl, $s, 'jpg')
            : self::url($baseUrl, self::PLACEHOLDER_STEM, 'jpg');

        return ['webp' => $webp, 'jpg' => $jpg];
    }

    public static function deleteImageVariants(string $stem, string $dir): void
    {
        if ($stem === '' || $stem === self::PLACEHOLDER_STEM) return;
        @unlink(self::path($dir, $stem, 'jpg'));
        @unlink(self::path($dir, $stem, 'webp'));
    }

    private static function okUpload(array $u): bool
    {
        return isset($u['tmp_name'])
            && is_string($u['tmp_name'])
            && (($u['error'] ?? UPLOAD_ERR_OK) === UPLOAD_ERR_OK)
            && @is_uploaded_file($u['tmp_name'])
            && @is_readable($u['tmp_name']);
    }

    private static function detectKind(string $path): ?string
    {
        if (function_exists('exif_imagetype')) {
            $t = @exif_imagetype($path);
            if ($t === IMAGETYPE_JPEG) return 'jpeg';
            if (defined('IMAGETYPE_WEBP') && $t === IMAGETYPE_WEBP) return 'webp';
        }
        $info = @getimagesize($path);
        if (is_array($info) && isset($info[2])) {
            if ($info[2] === IMAGETYPE_JPEG) return 'jpeg';
            if (defined('IMAGETYPE_WEBP') && $info[2] === IMAGETYPE_WEBP) return 'webp';
        }
        if (function_exists('mime_content_type')) {
            $m = @mime_content_type($path);
            if ($m === 'image/jpeg') return 'jpeg';
            if ($m === 'image/webp') return 'webp';
        }
        $b = self::peek($path, 12);
        if ($b !== '') {
            if (strlen($b) >= 3 && $b[0] === "\xFF" && $b[1] === "\xD8" && $b[2] === "\xFF") return 'jpeg';
            if (strlen($b) >= 12 && substr($b, 0, 4) === 'RIFF' && substr($b, 8, 4) === 'WEBP') return 'webp';
        }
        return null;
    }

    private static function loadSource(string $path, string $kind): ?\GdImage
    {
        if ($kind === 'jpeg' && function_exists('imagecreatefromjpeg')) return @imagecreatefromjpeg($path) ?: null;
        if ($kind === 'webp'  && function_exists('imagecreatefromwebp')) return @imagecreatefromwebp($path) ?: null;
        return null;
    }

    private static function autoOrient(\GdImage $img, string $path): \GdImage
    {
        if (!function_exists('exif_read_data')) return $img;
        try {
            $exif = @exif_read_data($path);
            $o = isset($exif['Orientation']) ? (int)$exif['Orientation'] : 0;
            if ($o === 3 || $o === 6 || $o === 8) {
                $angle = ($o === 3) ? 180 : (($o === 6) ? -90 : 90);
                $rot = @imagerotate($img, $angle, 0);
                if ($rot instanceof \GdImage) $img = $rot;
            }
        } catch (\Throwable) {}
        return $img;
    }

    private static function fitToCanvas(\GdImage $src, int $w, int $h, bool $alpha): \GdImage
    {
        $sw = imagesx($src); $sh = imagesy($src);
        $scale = min($w / max(1, $sw), $h / max(1, $sh), 1.0);
        $nw = (int) floor($sw * $scale); $nh = (int) floor($sh * $scale);
        $dx = (int) floor(($w - $nw) / 2); $dy = (int) floor(($h - $nh) / 2);

        $canvas = imagecreatetruecolor($w, $h);
        if ($alpha) {
            imagealphablending($canvas, false);
            imagesavealpha($canvas, true);
            $t = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
            imagefilledrectangle($canvas, 0, 0, $w, $h, $t);
        } else {
            $white = imagecolorallocate($canvas, 255, 255, 255);
            imagefilledrectangle($canvas, 0, 0, $w, $h, $white);
        }
        imagealphablending($canvas, true);
        imagecopyresampled($canvas, $src, $dx, $dy, 0, 0, $nw, $nh, $sw, $sh);
        return $canvas;
    }

    private static function ensureDir(string $dir): bool
    {
        return is_dir($dir) || @mkdir($dir, 0755, true);
    }

    private static function uniqueStem(string $dir): string
    {
        for ($i = 0; $i < 6; $i++) {
            $c = self::token();
            if (!is_file(self::path($dir, $c, 'jpg')) && !is_file(self::path($dir, $c, 'webp'))) return $c;
        }
        return self::token();
    }

    private static function token(): string
    {
        try { return bin2hex(random_bytes(16)); }
        catch (\Throwable) { return uniqid('img_', true); }
    }

    private static function path(string $dir, string $stem, string $ext): string
    {
        return rtrim($dir, '/\\') . DIRECTORY_SEPARATOR . $stem . '.' . $ext;
    }

    private static function url(string $base, string $stem, string $ext): string
    {
        return rtrim($base, '/') . '/' . $stem . '.' . $ext;
    }

    private static function peek(string $path, int $n): string
    {
        $fh = @fopen($path, 'rb'); if (!$fh) return '';
        $buf = @fread($fh, $n); @fclose($fh);
        return $buf !== false ? $buf : '';
    }
}