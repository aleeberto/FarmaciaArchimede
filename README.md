# Farmacia Archimede

Progetto per il corso Tecnologie Web. L'ambiente di sviluppo è basato su Docker in modo da non dover installare manualmente web server e database.

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
docker compose -f docker/docker-compose.yml down
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
│   ├── Config/
│   ├── Core/
│   ├── Service/
│   ├── View/
│   ├── vendor/
│   └── templates/    
├── composer.lock
├── composer.json
```

Chi si occupa dell’HTML deve modificare i file presenti nella cartella `src/templates`.

Se il browser non mostra il sito assicurati di usare **http** e non https. L'impostazione di Caddy è pensata per consentire a Total Validator di trovare il sito in locale.

Per accedere rapidamente a phpMyAdmin è sufficiente aprire `http://localhost/pma`.

> ⚠️ **Importante — non rimuovere `vendor/`, `composer.json` e `composer.lock`**
>
> * `vendor/` contiene le dipendenze e l’autoloader generato da Composer.
> * `composer.json` elenca le dipendenze del progetto.
> * `composer.lock` blocca le versioni installate, garantendo che ogni ambiente usi lo stesso codice.
>
> Se uno di questi elementi viene cancellato, l’applicazione non troverà le classi necessarie e smetterà di funzionare. In tal caso dovrai rieseguire `composer install` (dentro il container `php-apache`) per ricostruire l’ambiente.
