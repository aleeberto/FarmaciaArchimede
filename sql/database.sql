CREATE DATABASE IF NOT EXISTS farmacia_archimede;
use farmacia_archimede;

DROP TABLE IF EXISTS Ordinazioni;
DROP TABLE IF EXISTS Ordine;
DROP TABLE IF EXISTS Immagine;
DROP TABLE IF EXISTS Prodotto;
DROP TABLE IF EXISTS Lista_carte;
DROP TABLE IF EXISTS Carta_di_credito;
DROP TABLE IF EXISTS Lista_indirizzi_spedizione;
DROP TABLE IF EXISTS Indirizzo;
DROP TABLE IF EXISTS Utente;

create table Utente(
	Email varchar(30) PRIMARY KEY,
    Psw char(64) NOT NULL,
    Nome varchar(20) NOT NULL,
    Cognome varchar(20) NOT NULL,
    CF char(16) NOT NULL
);

create table Prodotto(
	ID_prodotto SMALLINT UNSIGNED PRIMARY KEY,
    Nome varchar(30) NOT NULL,
    Produttore varchar(30) NOT NULL,
	Codice_AIC char(10) NOT NULL,
    Tipo varchar(20) NOT NULL,
    Prezzo SMALLINT UNSIGNED  NOT NULL,
    Disponibilità SMALLINT UNSIGNED  NOT NULL,
    Descrizione varchar(50) NOT NULL,
    
    check(Disponibilità > 0),
    check(Prezzo > 0)
);

create table Immagine(
	Prodotto SMALLINT UNSIGNED PRIMARY KEY,
    Alt varchar(20) NOT NULL,
    Path varchar(30) NOT NULL,
    
    FOREIGN KEY(Prodotto) REFERENCES Prodotto(ID_prodotto)
);

create table Indirizzo(
	ID_indirizzo SMALLINT UNSIGNED PRIMARY KEY,
	Città varchar(20) NOT NULL,
    Provincia varchar(20) NOT NULL,
    Via varchar(30) NOT NULL,
    Civico TINYINT UNSIGNED NOT NULL,
    Tipo char(12) NOT NULL,
	check (Tipo in("spedizione","fatturazione"))
);

create table Lista_indirizzi_spedizione(
	Cliente varchar(30),
    Indirizzo_spedizione SMALLINT UNSIGNED,
    
    PRIMARY KEY(Cliente,Indirizzo_spedizione),
    
    FOREIGN KEY(Cliente) REFERENCES Utente(Email),
    FOREIGN KEY(Indirizzo_spedizione) REFERENCES Indirizzo(ID_indirizzo)
);

create table Carta_di_credito(
	Numero char(19) PRIMARY KEY,
    CVV char(3) NOT NULL,
    Scadenza SMALLINT UNSIGNED NOT NULL,
    Indirizzo_fatturazione SMALLINT UNSIGNED NOT NULL,
    FOREIGN KEY (Indirizzo_fatturazione) REFERENCES Indirizzo(ID_indirizzo)
);

create table Lista_carte(
	Cliente varchar(30),
    Carta char(19),
    
	PRIMARY KEY(Cliente,Carta),
    FOREIGN KEY (Cliente) REFERENCES Utente(Email),
    FOREIGN KEY (Carta) REFERENCES Carta_di_credito(Numero)
);

create table Ordine(
	ID_ordine SMALLINT UNSIGNED PRIMARY KEY,
    Cliente varchar(30) NOT NULL,
    Carta char(19) NOT NULL,
    Indirizzo_spedizione SMALLINT UNSIGNED NOT NULL,
    Orario TIMESTAMP NOT NULL,
    
    FOREIGN KEY (Cliente) REFERENCES Utente(Email),
	FOREIGN KEY (Carta) REFERENCES Carta_di_credito(Numero),
	FOREIGN KEY (Indirizzo_spedizione) REFERENCES Indirizzo(ID_indirizzo)
);

create table Ordinazioni(
	Ordine SMALLINT UNSIGNED,
    Prodotto SMALLINT UNSIGNED,
    Quantità SMALLINT UNSIGNED NOT NULL,
    
    PRIMARY KEY(Ordine,Prodotto),
    FOREIGN KEY (Ordine) REFERENCES Ordine(ID_ordine),
	FOREIGN KEY (Prodotto) REFERENCES Prodotto(ID_prodotto),
    
    check(Quantità > 0)
);

