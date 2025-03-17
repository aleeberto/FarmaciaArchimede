use farmacia_archimede;

INSERT INTO `Prodotto` (`ID_prodotto`,`ShortNome`,  `Nome`, `Produttore`, `Codice_AIC`, `Tipo`, `Prezzo`, `Disponibilita`, `Descrizione`) 
VALUES    
(1, 'Aspirina C Antidolorifico e Antinfiammatorio', 'Aspirina C Antinfiammatorio Antidolorifico per Influenza Raffreddore e Febbre con Vitamina C 40 cpr', 'Bayer', '004763619', 'Medicinale', 13.05, 500, '40 compresse. Aspirina C Compresse Effervescenti si usa nella terapia sintomatica degli stati febbrili e delle sindromi influenzali e da raffreddamento e nel trattamento sintomatico di mal di testa e di denti, nevralgie, dolori mestruali, dolori reumatici e muscolari.'),
(2, 'Okitask 40mg Senza Acqua', 'OKITask 40mg granulato senza acqua 30 bustine orosolubili', 'Dompè', '042028050', 'Medicinale', 10.49, 1000, '30 bustine orosolubili. Okitask in bustine orosolubili appartiene alla categoria dei farmaci antinfiammatori e antireumatici.');

INSERT INTO `Immagine` (`Prodotto`, `Alt`, `Path`)
VALUES
(1, 'Immagine Aspirina', 'aspirinac_antinfiammatorio.webp' ),
(2, 'Immagine Oki', 'okitask_granulatonoacqua.webp');

INSERT INTO `Utente` (`Email`, `Psw`, `Nome`, `Cognome`, `CF`)
VALUES
('utente@utente.it', '123stella', 'utente', 'utente', 'utenteutente');