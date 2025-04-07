# Farmacia Archimede
 
 Progetto per il corso Tecnologie Web 

## TODO

#### SQL

- Popolare il database (file popupate_db.sql)

#### HTML e CSS
- Fare e sistemare pagina Home (file index.html)
- Fare e sistemare pagina Chi Siamo (file chisiamo.html)
- Fare e sistemare pagina Contatti (file contatti.html)
- Sistemare stile della pagina I Nostri Prodotti (prodotti.html)
    - inserire campo di testo nel box di login
    - Sistemare gli item
- Fare e sistemare la pagina del singolo Prodotto (prodotto.html)
    - Se si guarda nel file si capisce come restituisco i vari dati utilizzarli per creare la pagina
    - Ricordarsi di aggiungere tasto di aggiunta al carrello
- Inserire campo di testo in filtri nella pagina Prodotti
- Sistemare la pagina di login (login.html)
- Inserire nell header di fianco a Area personale bottone per carrello

(Sistemare --> CSS Fare --> HTML)

#### PHP

- Gestire login con generazione pagina login / registrazione / AreaPersonale(loggato)
- Gestire Filtri
- Gestire aggiunta al carrello




## Appunti parte di prodotti

Esistono 3 elementi che vanno a compore la parte di prodotti. La pagina prodotti.html presenterà una board in cui verranno appesi tutti i prodootti presi dal server sql ogni prodotto verrà messo all interno di un item. Gli item verranno creatii sempre dal mai.php con luso di prodottiquery.php. Si utilizzerà come template per gli item item.html. Infine prodotto.html sarà la pagina che indica la descrizione codice e tutto il resto del prodotto

## HOW TO DOCKER
1. Installa [Docker](https://docs.docker.com/engine/install/) (se avete linux seguite questa guida dopo averlo installato [Guida](https://docs.docker.com/engine/install/linux-postinstall/))
2. Apri un terminale all interno di Farmacia Archimede (La root del repository Github)
3. Lancia il comando ``` docker-compose up -d ``` la prima volta ci metterà tanto perchè deve scaricare le immagini di php e mysql
4. Digitando localhost:8080 su qualsiasi browser web ci verrà proposto il file index.php
5. Se fate modifiche al file sql dovete rilanciare i comandi. Non è necessario per modifiche ai file php/html
```sh 
docker-compose down
docker-compose up -d 
```
In teoria il down non è necessario ma almeno pulisce tutto