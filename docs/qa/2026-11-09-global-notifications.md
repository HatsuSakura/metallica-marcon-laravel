# Notifiche globali — 2026-11-09

Implementato localmente, non pubblicato in QA. Unificato il feedback del driver
con la coda Vuex già usata dalle altre pagine. Rimosso JourneyExecutionFeedback.

## Contratto e comportamento

Unico host AppNotifications nella radice app.js, presente anche cambiando
layout; AppNotificationStack separa la presentazione da stato e integrazione
Inertia. Rimangono grafica degli alert precedenti, icone, spaziature e pulsante
circolare di chiusura. Posizione sotto l'header, scorrimento se la pila eccede
lo schermo; il contenitore non intercetta i tocchi fuori dai messaggi.

`store.dispatch('flash/queueMessage', {type, text})` resta compatibile con i
consumatori esistenti (magazzino, ordini, clienti, viaggi e Logistica).
Conservati anche addMessageToQueue e removeMessageByIndex. Tipi: success,
info, warning, error; tipo sconosciuto normalizzato a info. Nessuna deduplicazione:
ogni operazione produce una notifica distinta, anche con testo identico.
Rimossa anche la soppressione locale ravvicinata dei messaggi di data magazzino.

ID univoci, nuovi messaggi sotto i precedenti, timer individuali di 3 secondi, seguiti da dissolvenza di 0,5 secondi.
Chiusura manuale per ID e cancellazione del relativo timer: nessuna rimozione
anticipata del vicino. Errori e messaggi con azione restano fino a chiusura;
per gli altri la durata può essere specificata con duration (0 = persistente).

Le azioni possono avere label, run e disabled. «Annulla ripristino» verifica
l'evento corrente e la revisione; un annullamento fallito conserva la notifica.
All'uscita dal workspace vengono eliminate le notifiche con callback del
workspace, senza eliminare i normali messaggi globali. I flash server sono
raccolti dall'host, senza un secondo watcher nel layout principale.

## Non regressione

10 test mirati in tests/Frontend/appNotifications.test.mjs: contratto legacy,
quattro toni, duplicati, ordine, timer indipendenti, raffica di 10 messaggi,
chiusura per indice, isolamento store, azioni e invalidazione, grafica e host
unico. Superati insieme a tutti i precedenti: 12 file frontend. Build Vite
completata; rimane l'avviso preesistente sulle dimensioni del bundle.

Da verificare manualmente su browser/touch prima della promozione in QA:

1. Magazzino, salvataggio cliente e modifica ordine: grafica precedente,
   messaggi visibili una sola volta e chiusura invariata.
2. Driver: spuntare più materiali rapidamente, verificare una notifica per
   operazione e scadenza individuale; chiudere la prima senza accorciare le altre.
3. Ripristino materiale: annullare, verificare fallimento e invalidazione dopo
   una successiva modifica; navigare fuori e non conservare callback obsolete.
4. Errori persistenti e pila scorribile su telefono, senza bloccare il contenuto
   della pagina fuori dagli alert; flash server anche tra layout diversi.

Nessuna verifica manuale su dispositivo reale è inclusa nei risultati automatici.
