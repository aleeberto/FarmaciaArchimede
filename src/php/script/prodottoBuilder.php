<?php

$root = dirname(__DIR__, 2);

$page_index = 0;

require_once($root . "/php/main.php");

$db = Database::getInstance("farmacia_mysql", "root", "root_password", "farmacia_archimede");
$conn = $db->connect();

class ProdottoDTO {
    public int $id;
    public string $nome;
    public string $produttore;
    public string $codice_aic;
    public string $tipo;
    public float $prezzo;
    public int $disponibilita;
    public string $descrizione;
}

class ImmagineDTO {
    public int $prodotto_id;
    public string $alt;
    public string $path;
}

class ProdottoService {
    private Database $db;

    public function __construct(Database $db) {
        $this->db = $db;
    }

    public function getAllProducts(): array {
        $conn = $this->db->connect();
        $result = $conn->query("SELECT * FROM Prodotto");

        if (!$result) {
            die("Errore nella query: " . $conn->error);
        }

        $prodotti = [];
        while ($row = $result->fetch_assoc()) {
            $prodotto = new ProdottoDTO();
            $prodotto->id = $row["ID_prodotto"];
            $prodotto->nome = $row["Nome"];
            $prodotto->produttore = $row["Produttore"];
            $prodotto->codice_aic = $row["Codice_AIC"];
            $prodotto->tipo = $row["Tipo"];
            $prodotto->prezzo = (float) $row["Prezzo"];
            $prodotto->disponibilita = (int) $row["Disponibilita"];
            $prodotto->descrizione = $row["Descrizione"];
            $prodotti[] = $prodotto;
        }

        return $prodotti;
    }

    public function getProductImage(int $productId): ?ImmagineDTO {
        $conn = $this->db->connect();
        $stmt = $conn->prepare("SELECT * FROM Immagine WHERE Prodotto = ?");
        
        if (!$stmt) {
            die("Errore nella preparazione della query: " . $conn->error);
        }

        $stmt->bind_param("i", $productId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $img = new ImmagineDTO();
            $img->prodotto_id = $row["Prodotto"];
            $img->alt = $row["Alt"];
            $img->path = "/assets/img/" . $row["Path"];
            return $img;
        }
        return null;
    }
}
?>