<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Gestione immagini prodotto con doppio formato (JPG + WEBP),
 * normalizzazione su canvas 700×700 senza upscaling.
 *
 * Regole:
 * - Input JPEG  => genera SEMPRE .jpg + .webp (entrambi obbligatori)
 * - Input WEBP  => genera .webp (obbligatorio) e prova anche .jpg (best-effort)
 * - Canvas: per JPG fondo bianco, per WEBP trasparente
 */
final class Image
{
    /** Limite dimensione upload (byte) */
    public const MAX_FILE_BYTES = 1_600_000;

    /** Canvas di destinazione */
    public const TARGET_WIDTH  = 700;
    public const TARGET_HEIGHT = 700;

    /** Placeholder stem (senza estensione) */
    public const PLACEHOLDER_STEM = 'placeholder';

    /** Qualità di salvataggio */
    private const JPG_QUALITY  = 82;
    private const WEBP_QUALITY = 80;

    /**
     * Salva l'upload in `$targetDir` generando immagini 700×700.
     */
    public static function store(array $upload, string $targetDir): array
    {
        if (!self::isValidUpload($upload)) {
            return [false, null, "Errore durante l'upload dell'immagine."];
        }

        $tmp  = $upload['tmp_name'];
        $size = (int)($upload['size'] ?? 0);
        if ($size <= 0 || $size > self::MAX_FILE_BYTES) {
            @unlink($tmp);
            return [false, null, 'Dimensione immagine troppo grande. Max: 1.6MB.'];
        }

        $type = self::detectType($tmp); // 'jpg' | 'webp' | null
        if ($type === null) {
            @unlink($tmp);
            return [false, null, 'Formato non supportato. Accettati: JPG o WEBP.'];
        }

        if (!self::ensureDirectoryExists($targetDir)) {
            @unlink($tmp);
            return [false, null, 'Impossibile creare la cartella immagini.'];
        }

        $stem = self::generateUniqueStem($targetDir);
        $jpgPath  = self::buildPath($targetDir, $stem, 'jpg');
        $webpPath = self::buildPath($targetDir, $stem, 'webp');

        // Carica sorgente
        $src = ($type === 'jpg') ? @imagecreatefromjpeg($tmp) : @imagecreatefromwebp($tmp);
        if (!$src instanceof \GdImage) {
            @unlink($tmp);
            return [false, null, 'Immagine non valida.'];
        }

        // Auto-orient solo per JPEG (EXIF)
        if ($type === 'jpg') {
            $src = self::autoOrientJpeg($src, $tmp);
        }

        // Prepara i due canvas
        $canvasForWebp = self::toCanvas($src, self::TARGET_WIDTH, self::TARGET_HEIGHT, true);   // trasparente
        $canvasForJpg  = self::toCanvas($src, self::TARGET_WIDTH, self::TARGET_HEIGHT, false);  // bianco

        // Salvataggi secondo le regole
        if ($type === 'jpg') {
            // Entrambi obbligatori
            if (!self::canSaveJpg() || !self::canSaveWebp()) {
                self::cleanupTemps($tmp, $src, $canvasForWebp, $canvasForJpg);
                return [false, null, 'GD senza supporto JPEG/WEBP sufficiente.'];
            }
            $okJ = @imagejpeg($canvasForJpg,  $jpgPath,  self::JPG_QUALITY);
            $okW = @imagewebp($canvasForWebp, $webpPath, self::WEBP_QUALITY);

            self::cleanupTemps($tmp, $src, $canvasForWebp, $canvasForJpg);

            if (!$okJ || !$okW) {
                @unlink($jpgPath); @unlink($webpPath);
                return [false, null, 'Errore nella creazione dei file JPG/WEBP.'];
            }
            return [true, $stem, null];
        }

        // type === 'webp'
        if (!self::canSaveWebp()) {
            self::cleanupTemps($tmp, $src, $canvasForWebp, $canvasForJpg);
            return [false, null, 'GD senza supporto WEBP per il ridimensionamento.'];
        }

        $okWebp = @imagewebp($canvasForWebp, $webpPath, self::WEBP_QUALITY);
        $okJpg  = true; // best-effort
        if (self::canSaveJpg()) {
            $okJpg = @imagejpeg($canvasForJpg, $jpgPath, self::JPG_QUALITY);
        }

        self::cleanupTemps($tmp, $src, $canvasForWebp, $canvasForJpg);

        if (!$okWebp) {
            @unlink($jpgPath); @unlink($webpPath);
            return [false, null, 'Errore nella creazione del file WEBP 700×700.'];
        }

        // Se JPEG best-effort fallisce, non è un errore bloccante.
        if (!$okJpg) { @unlink($jpgPath); }

        return [true, $stem, null];
    }

    /**
     * Restituisce gli URL per il tag <picture>, con fallback al placeholder.
     * @return array{webp:string,jpg:string}
     */
    public static function resolvePictureSources(string $stem, string $dir, string $baseUrl): array
    {
        $s = $stem !== '' ? $stem : self::PLACEHOLDER_STEM;

        $webp = is_file(self::buildPath($dir, $s, 'webp'))
            ? self::buildUrl($baseUrl, $s, 'webp')
            : self::buildUrl($baseUrl, self::PLACEHOLDER_STEM, 'webp');

        $jpg = is_file(self::buildPath($dir, $s, 'jpg'))
            ? self::buildUrl($baseUrl, $s, 'jpg')
            : self::buildUrl($baseUrl, self::PLACEHOLDER_STEM, 'jpg');

        return ['webp' => $webp, 'jpg' => $jpg];
    }

    /** Elimina `stem.{jpg,webp}` se non è il placeholder. */
    public static function deleteImageVariants(string $stem, string $dir): void
    {
        if ($stem === '' || $stem === self::PLACEHOLDER_STEM) { return; }
        @unlink(self::buildPath($dir, $stem, 'jpg'));
        @unlink(self::buildPath($dir, $stem, 'webp'));
    }

    /** Upload valido/leggibile. */
    private static function isValidUpload(array $u): bool
    {
        return isset($u['tmp_name']) && is_string($u['tmp_name'])
            && ($u['error'] ?? UPLOAD_ERR_OK) === UPLOAD_ERR_OK
            && @is_uploaded_file($u['tmp_name'])
            && @is_readable($u['tmp_name']);
    }

    /**
     * Rileva tipo immagine tramite funzioni base:
     * - preferisce exif_imagetype / getimagesize (magic numbers)
     * @return 'jpg'|'webp'|null
     */
    private static function detectType(string $path): ?string
    {
        if (function_exists('exif_imagetype')) {
            $t = @exif_imagetype($path);
            if ($t === IMAGETYPE_JPEG) return 'jpg';
            if (defined('IMAGETYPE_WEBP') && $t === IMAGETYPE_WEBP) return 'webp';
        }
        $info = @getimagesize($path);
        if (is_array($info) && isset($info[2])) {
            if ($info[2] === IMAGETYPE_JPEG) return 'jpg';
            if (defined('IMAGETYPE_WEBP') && $info[2] === IMAGETYPE_WEBP) return 'webp';
        }
        // Fallback minimale: magic bytes
        $b = self::readBytes($path, 12);
        if ($b !== '') {
            if (strlen($b) >= 3 && ord($b[0]) === 0xFF && ord($b[1]) === 0xD8 && ord($b[2]) === 0xFF) return 'jpg';
            if (strlen($b) >= 12 && substr($b, 0, 4) === 'RIFF' && substr($b, 8, 4) === 'WEBP') return 'webp';
        }
        return null;
    }

    /** Canvas 700×700 centrato, senza upscaling; alpha opzionale. */
    private static function toCanvas(\GdImage $src, int $w, int $h, bool $alpha): \GdImage
    {
        $sw = imagesx($src); $sh = imagesy($src);
        $scale = min($w / max(1, $sw), $h / max(1, $sh), 1.0);
        $nw = (int) floor($sw * $scale); $nh = (int) floor($sh * $scale);

        $canvas = imagecreatetruecolor($w, $h);
        if ($alpha) {
            imagealphablending($canvas, false);
            imagesavealpha($canvas, true);
            $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
            imagefilledrectangle($canvas, 0, 0, $w, $h, $transparent);
        } else {
            $white = imagecolorallocate($canvas, 255, 255, 255);
            imagefilledrectangle($canvas, 0, 0, $w, $h, $white);
        }

        $dx = (int) floor(($w - $nw) / 2);
        $dy = (int) floor(($h - $nh) / 2);
        imagealphablending($canvas, true);
        imagecopyresampled($canvas, $src, $dx, $dy, 0, 0, $nw, $nh, $sw, $sh);

        return $canvas;
    }

    /** Auto-orienta JPEG via EXIF (se presente). */
    private static function autoOrientJpeg(\GdImage $img, string $path): \GdImage
    {
        if (!function_exists('exif_read_data')) { return $img; }
        try {
            $exif = @exif_read_data($path);
            if (!$exif || empty($exif['Orientation'])) { return $img; }
            $o = (int)$exif['Orientation'];
            if ($o === 3 || $o === 6 || $o === 8) {
                $angle = ($o === 3) ? 180 : (($o === 6) ? -90 : 90);
                $rot = @imagerotate($img, $angle, 0);
                if ($rot instanceof \GdImage) { $img = $rot; }
            }
        } catch (\Throwable) { /* noop */ }
        return $img;
    }

    /** Pulizia risorse temporanee. */
    private static function cleanupTemps(string $tmp, \GdImage ...$imgs): void
    {
        foreach ($imgs as $i) { if ($i instanceof \GdImage) { imagedestroy($i); } }
        @unlink($tmp);
    }

    /** Lettura rapida byte iniziali. */
    private static function readBytes(string $path, int $n): string
    {
        $fh = @fopen($path, 'rb'); if (!$fh) { return ''; }
        $buf = @fread($fh, $n); @fclose($fh);
        return $buf !== false ? $buf : '';
    }

    /** Directory esistente/creata. */
    private static function ensureDirectoryExists(string $dir): bool
    {
        return is_dir($dir) || @mkdir($dir, 0755, true);
    }

    /** Path/URL helpers. */
    private static function buildPath(string $dir, string $stem, string $ext): string
    {
        return rtrim($dir, '/\\') . DIRECTORY_SEPARATOR . $stem . '.' . $ext;
    }
    private static function buildUrl(string $base, string $stem, string $ext): string
    {
        return rtrim($base, '/') . '/' . $stem . '.' . $ext;
    }

    /** Stem univoco. */
    private static function generateUniqueStem(string $dir): string
    {
        for ($i = 0; $i < 6; $i++) {
            $c = self::randomStem();
            if (!is_file(self::buildPath($dir, $c, 'jpg')) && !is_file(self::buildPath($dir, $c, 'webp'))) {
                return $c;
            }
        }
        return self::randomStem();
    }
    private static function randomStem(): string
    {
        try { return bin2hex(random_bytes(16)); }
        catch (\Throwable) { return uniqid('img_', true); }
    }

    /** Verifiche rapide capacità GD. */
    private static function canSaveWebp(): bool
    {
        return extension_loaded('gd') && function_exists('imagewebp') && function_exists('imagecreatetruecolor');
    }
    private static function canSaveJpg(): bool
    {
        return extension_loaded('gd') && function_exists('imagejpeg') && function_exists('imagecreatetruecolor');
    }
}
