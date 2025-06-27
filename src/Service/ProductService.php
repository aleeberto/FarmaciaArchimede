<?php
declare(strict_types=1);

namespace App\Service;

use App\Core\Database;

class ProdottoDTO
{
    public int $id;
    public string $shortNome;
    public string $nome;
    public string $produttore;
    public string $codice_aic;
    public string $tipo;
    public float $prezzo;
    public int $disponibilita;
    public string $descrizione;
}

class ImmagineDTO
{
    public int $prodotto_id;
    public string $alt;
    public string $path;
}

class ProductService
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function getAllProducts(): array
    {
        $conn = $this->db->connect();
        $result = $conn->query('SELECT * FROM Prodotto');
        if (! $result) {
            throw new \RuntimeException('Errore nella query: ' . $conn->error);
        }

        $prodotti = [];
        while ($row = $result->fetch_assoc()) {
            $dto = new ProdottoDTO();
            $dto->id = (int) $row['ID_prodotto'];
            $dto->shortNome = (string) $row['ShortNome'];
            $dto->nome = (string) $row['Nome'];
            $dto->produttore = (string) $row['Produttore'];
            $dto->codice_aic = (string) $row['Codice_AIC'];
            $dto->tipo = (string) $row['Tipo'];
            $dto->prezzo = (float) $row['Prezzo'];
            $dto->disponibilita = (int) $row['Disponibilita'];
            $dto->descrizione = (string) $row['Descrizione'];
            $prodotti[] = $dto;
        }

        return $prodotti;
    }

    public function getProductByID(int $id): ?ProdottoDTO
    {
        $conn = $this->db->connect();
        $stmt = $conn->prepare('SELECT * FROM Prodotto WHERE ID_prodotto = ?');
        if (! $stmt) {
            throw new \RuntimeException('Errore nella preparazione: ' . $conn->error);
        }
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $dto = new ProdottoDTO();
            $dto->id = (int) $row['ID_prodotto'];
            $dto->shortNome = (string) $row['ShortNome'];
            $dto->nome = (string) $row['Nome'];
            $dto->produttore = (string) $row['Produttore'];
            $dto->codice_aic = (string) $row['Codice_AIC'];
            $dto->tipo = (string) $row['Tipo'];
            $dto->prezzo = (float) $row['Prezzo'];
            $dto->disponibilita = (int) $row['Disponibilita'];
            $dto->descrizione = (string) $row['Descrizione'];
            return $dto;
        }
        return null;
    }

    public function getProductImage(int $productId): ?ImmagineDTO
    {
        $conn = $this->db->connect();
        $stmt = $conn->prepare('SELECT * FROM Immagine WHERE Prodotto = ?');
        if (! $stmt) {
            throw new \RuntimeException('Errore nella preparazione: ' . $conn->error);
        }
        $stmt->bind_param('i', $productId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $dto = new ImmagineDTO();
            $dto->prodotto_id = (int) $row['Prodotto'];
            $dto->alt = (string) $row['Alt'];
            $dto->path = '/assets/img/' . $row['Path'];
            return $dto;
        }
        return null;
    }
}
