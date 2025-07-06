<?php
declare(strict_types=1);

namespace App\Core\Product;

class ProductDTO
{
    public int $id;
    public string $shortName;
    public string $name;
    public string $manufacturer;
    public string $aicCode;
    public string $type;
    public float $price;
    public int $availability;
    public string $description;
    public string $imagePath;

    public function getAvailability(): string
    {
        return $this->availability > 0
            ? (string) $this->availability
            : 'OUT OF STOCK';
    }
}
