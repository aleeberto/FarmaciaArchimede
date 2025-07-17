<?php
namespace App\Core;

class View {
    public static function renderPartial(string $templatePath, array $data = []): string {
        extract($data);
        ob_start();
        include __DIR__ . '/../View/' . $templatePath . '.php';
        return ob_get_clean();
    }
}
