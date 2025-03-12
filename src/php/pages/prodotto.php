<?php
//// Recupera l'ID del prodotto dalla query string
//$product_id = $_GET['id'] ?? null;
//
//if ($product_id) {
//    // Recuperiamo i dettagli del prodotto
//    $conn = $db->connect();
//    $stmt = $conn->prepare("SELECT * FROM Prodotto WHERE ID_prodotto = ?");
//    $stmt->bind_param("i", $product_id);
//    $stmt->execute();
//    $result = $stmt->get_result();
//    
//    if ($product = $result->fetch_assoc()) {
//        // Visualizziamo i dettagli del prodotto
//        echo "<h1>" . $product['Nome'] . "</h1>";
//        echo "<p>" . $product['Descrizione'] . "</p>";
//        echo "<p>Prezzo: € " . number_format($product['Prezzo'], 2) . "</p>";
//        echo "<img src='/assets/img/" . $product['Path_immagine'] . "' alt='" . htmlspecialchars($product['Nome'], ENT_QUOTES) . "'>";
//    } else {
//        echo "<p>Prodotto non trovato.</p>";
//    }
//} else {
//    echo "<p>Prodotto non specificato.</p>";
//}
echo "cazzabubbolo"

?>
