<?php
declare(strict_types=1);

namespace App\Core\Model;

class ProductDTO
{
    public int $id;
    public string $shortName;
    public string $name;
    public string $manufacturer;
    public string $aicCode;
    public int $productTypeId;       // ID del tipo prodotto (es. 1)
    public string $productType;      // nome tipo prodotto (es. "Medicinale")
    public string $format;           // es. 'compresse', 'crema', ecc.
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