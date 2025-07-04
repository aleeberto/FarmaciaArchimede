# Farmacia Archimede

Progetto per il corso Tecnologie Web. L'ambiente di sviluppo è basato su Docker in modo da non dover installare manualmente web server e database.

## Todo

### FrontEnd
- Fare la pagina Home (Pagina che non richiedono chiamate a php o altro, pagina statica, straight forward)
- Fare la pagina Chi Siamo (Pagina che non richiedono chiamate a php o altro, pagina statica, straight forward)
- Fare la pagina Contatti (Pagina che non richiedono chiamate a php o altro, pagina statica, straight forward)
- Sistemare lo stilde della pagina prodotti (Attenzione che forse le immagini sono forzate tramite php ad una certa dimensione da controllare)
- Sistemare lo stile della pagina prodotto (La pagina che si apre quando si apre un prodotto, ricordarsi che va aggiunto il bottone per aggiungere al carrello carrello)
- Sistemare la pagina di Login 
### BackEnd
- quando loggati sistemare al posto di accedi ce logout e inserire tab carrello
- filtri
- login

## Avvio rapido

1. Installa [Docker](https://docs.docker.com/engine/install/). Se usi Linux segui anche la [guida post installazione](https://docs.docker.com/engine/install/linux-postinstall/).
2. Posizionati nella cartella del progetto.
3. Avvia i servizi con:

```sh
docker compose -f docker/docker-compose.yml up -d
```

La prima esecuzione scaricherà le immagini necessarie.

Servizi forniti:

- **caddy**: espone il sito sulla porta 80.
- **php-apache**: esegue il codice PHP.
- **mariadb**: database inizializzato con i dump in `sql/`.
- **phpmyadmin**: interfaccia web del database raggiungibile su `http://localhost/pma`.

Per fermare tutto l’ambiente:

```sh
docker compose -f docker/docker-compose.yml down -v
```

## Struttura del progetto

Albero delle cartelle (aggiornato all'ultima modifica):

```
.
├── docker/           
│   ├── caddy/
│   ├── mariadb/
│   ├── php-apache/
│   └── phpmyadmin/
├── public/            
│   ├── assets/
│   └── css/
├── sql/              
├── src/
│   ├── Core/
│   ├── Service/
│   ├── View/
│   └── html/        
```

Chi si occupa dell’HTML deve modificare i file presenti nella cartella `src/html`.

Se il browser non mostra il sito assicurati di usare **http** e non https. L'impostazione di Caddy è pensata per consentire a Total Validator di trovare il sito in locale.

Per accedere rapidamente a phpMyAdmin è sufficiente aprire `http://localhost/pma`. Utente e Password == admin

> ⚠️ **Importante — non rimuovere `vendor/`, `composer.json` e `composer.lock`**
>
> * `vendor/` contiene **tutte le dipendenze locali** e l’autoloader generato da Composer.
> * `composer.json` elenca le dipendenze del progetto.
> * `composer.lock` blocca le versioni installate, garantendo che ogni ambiente usi lo stesso codice.
