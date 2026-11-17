# Elenco ordini: nuovi record e filtro cliente — 2026-11-17

Hotfix preparata su main per il rilascio QA; deploy e collaudo QA da eseguire.
Indipendente dallo sviluppo notifiche non pubblicato.

## Causa verificata nel codice

OrderController.index caricava 25 ordini senza ordinamento esplicito e il
frontend non mostrava i collegamenti del paginator restituito dal server.
I nuovi ordini potevano trovarsi oltre la prima pagina, irraggiungibile dalla UI.
La diagnosi è riprodotta con fixture SQLite, senza modificare dati QA.

## Correzione

- Ordinamento created_at decrescente, poi id decrescente per date uguali.
- Paginazione visibile sotto l'elenco; tutti gli ordini restano consultabili.
- Filtro inline “Filtra per Cliente”, automatico dopo 300 ms, Invio immediato;
  nuovo filtro riparte dalla prima pagina, azzeramento ripristina l'elenco.
- Ricerca lato server prima della paginazione: S.A.I.L. trovato con sail e
  B.B.R. Electronics con bre; termini multipli, maiuscole/accenti/punteggiatura
  normalizzati. Support CustomerNameSearch riutilizzabile; scorre solo nomi/ID
  del catalogo clienti, senza caricare tutti gli ordini o richiedere funzioni
  SQL specifiche di MySQL.
- Card su colonna singola mobile e doppia da md; intestazione a capo quando
  necessario. Relazioni cliente/sede assenti non bloccano il rendering.

## Verifiche

- OrderIndexTest: 2 test, 20 asserzioni. Fixture di 34 ordini: nuovo record in
  cima, spareggio ID, pagina successiva, ricerca oltre i primi 25, filtro
  mantenuto nei link, risultato vuoto e azzeramento.
- Test frontend customerNameSearch passato.
- Build Vite riuscita; warning dimensioni chunk già presente.
- Chrome su fixture sintetiche con componenti reali: 320/390/768/1440 px,
  entrambi i temi (8 combinazioni), senza overflow/errori. Verificati invio del
  filtro al server e azzeramento. Non è un collaudo end-to-end su QA.

Nel prossimo rilascio controllare un nuovo ordine reale in prima posizione,
la ricerca dei clienti con sigle e la seconda pagina mantenendo il filtro.
Non occorrono migrazioni per questa correzione.
