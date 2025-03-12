# Farmacia Archimede

## TODO

- fare main.css esiste main.temp.css come spunto

- [DONE] inserire  Logo in template header.html logo in assets/img/logo.png

- [] mettere in tabella gli orari in footer.html 

- [DONE] fare il form di login (pagina html)

- [DONE] index.html inserire keywords e descrizione

- fare html di base per singolo prodotto (quello che si vedrà nella lista dei prodotti, poi verrà generata la lista completa con php nella pagina prodotti.html (per capirci la foto dell integratore mandato da @-- su telegram))

- fare pagina home, chi siamo e contatti

- popolare il database i link ai medicinali sono disponibili nel file condiviso

- Civico non è small int è un char

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
