# GLE-122 — Modifiche operative e ripristini

Implementazione locale: 2026-10-31. Issue verificata tramite YouTrack MCP:
[GLE-122](https://metallicamarcon.youtrack.cloud/issue/GLE-122), «Modifica del
piano corrente e ripristino dei valori tracciato». Al controllo lo stato del
tracker è **Da fare**, distinto dallo sviluppo locale; nessuna chiusura implicita.

## Significato di pianificato e operativo

Chiarimento del PO: l'operativo è l'esecuzione del pianificato. Inizialmente
coincide con i contenuti del piano congelato alla prima partenza. Se non ci
sono variazioni, i contenuti finali corrispondono al piano; stati, orari e
registrazioni di completamento si aggiungono comunque. Se ci sono variazioni,
il risultato operativo differisce a livello di Journey, Order e OrderItem.
«Effettivo» descrive i fatti e il risultato nell'esecuzione: non è una terza
copia indipendente del viaggio.

Fondazione: [piano operativo](Piano%20operativo%20esecuzione%20viaggi.md), con
baseline immutabile, esecuzioni dedicate e comandi con revisione attesa.

## Completamento di questo incremento

- Menu touch «Ripristina» riutilizzabile: valore salvato attuale, precedente
  e originale visibili prima di scegliere l'azione.
- Una sola azione quando precedente e originale coincidono; il comando
  «precedente» avanza correttamente la storia degli annullamenti.
- Originale già corrente senza pulsante inutile; originale assente sui
  materiali aggiunti dichiarato esplicitamente. Zero effettivo distinto da
  valore non ancora registrato.
- Applicazione immediata senza popup Sì/No. Feedback vicino al bordo inferiore,
  raggiungibile su telefono portrait anche dopo aver scorso l'ordine;
  «Annulla ripristino» rimane disponibile fino alla successiva operazione
  o alla chiusura del messaggio, senza una scadenza troppo breve.
- Annullamento del ripristino come nuova revisione audita, che ripristina
  anche la disponibilità dell'azione precedente nel relativo storico.
- Rifiuto dell'annullamento se un'altra operazione ha già cambiato il viaggio;
  nessuna sovrascrittura silenziosa. Stessa autorizzazione dell'esecuzione.
- Campi non salvati di altri materiali mantenuti durante gli aggiornamenti.
  Su un materiale con modifiche locali il ripristino richiede prima di
  salvarle o scartarle esplicitamente, senza popup o perdita dei campi.
- Materiale rimosso ancora consultabile e ripristinabile, con anteprima
  «presente/rimosso»; nessuna cancellazione fisica della storia.

Campi coperti dal menu materiali: quantità corrente da ritirare, peso corrente,
descrizione, CER, tipo contenitore, sfuso, quantità e peso effettivamente
ritirati, zona di carico e rimozione. Riapertura, riordino e casse vuote
continuano a usare i relativi comandi coordinati delle altre GLE.

## Struttura e contratto

`ExecutionRestoration` centralizza selezione dei valori e logica di annullamento:
la preview e il comando utilizzano la stessa baseline e la stessa storia.
La UI non calcola il valore di destinazione dall'ordine commerciale corrente.
Il server ricalcola e applica il ripristino sotto lock del viaggio e revisione
attesa; il client non invia né impone il valore sostitutivo.

Il read model aggiunge `restore_options` ai materiali esecutivi modificabili,
compresi quelli rimossi. Per campo: attuale, originale/precedente con etichetta,
disponibilità, motivazione e indicazione dei valori coincidenti. Non trasmette
le copie interne dei metadati usati dal servizio per ripristinare CER e tipi.

Comandi sull'endpoint esistente `POST /api/journeys/{id}/execution/commands`:

```json
{"revision": 3, "operation": "restore_previous", "input": {"item_id": 7, "field": "holder_quantity"}}
```

La risposta contiene il read model aggiornato e, per un ripristino eseguito,
`undo: {event_id, revision}`. Per annullarlo:

```json
{"revision": 4, "operation": "undo_restore", "input": {"event_id": 18}}
```

`event_id` deve identificare quel ripristino sullo stesso viaggio e sulla
revisione corrente. `undo_restore` conserva un evento con
`cancels_restore_event_id`; il ripristino originale rimane nella storia.
I diff distinguono valore assente e valore presente nullo per ricostruire
correttamente anche la struttura dei dati durante un annullamento.

UI: `JourneyRestoreMenu.vue`, `JourneyExecutionFeedback.vue`, editor condiviso
`JourneyExecutionOrderEditor.vue`; helper puri per azioni e bozze locali.
Colori semantici DaisyUI e bersagli touch di almeno 48 px.

## Verifica e disponibilità

Test dedicati: `JourneyRestorationTest.php`, `journeyRestore.test.mjs` e
`journeyRestoreMenu.test.mjs`, oltre alle suite della fondazione. Comprendono
anteprime coerenti, duplicati, originali assenti, zero, rimozioni, annullamenti
successivi, annullamento del ripristino, conflitti, API/storico e rendering Vue
del menu. Le prove di rendering non sostituiscono il collaudo touch autenticato.
Nessuna nuova migrazione necessaria per questo incremento.

La UI di modifica è disponibile su viaggi avviati con baseline. Il 159 era
inizialmente legacy in sola lettura; il successivo reset esplicitamente
richiesto dal PO e il nuovo avvio dalla UI lo hanno portato nel nuovo contesto
esecutivo. Vedi `GLE-118 Riapertura tappa.md` per la preparazione registrata.
Per il 40, strutturalmente valido, aggiornare i documenti dalla Logistica e
poi avviare dalla UI autista. Questo incremento non avvia
automaticamente viaggi né riscrive originali dei viaggi legacy.

Controllo aggiuntivo richiesto dal PO: ordine `MMM_2506_0004`, item 7 e 8,
descrizioni specifiche entrambe NULL nel DB locale. Le descrizioni anagrafiche
CER sono presenti e distinte; nessuna descrizione inventata o sostituita nei dati.

Esito verifiche dell'incremento: **41 test PHP / 232 asserzioni passati** nelle
sette suite mirate, **5 file di test frontend passati**, build Vite riuscita,
sintassi PHP e diff senza errori. Resta l'avviso già noto sulla dimensione del
bundle principale; nessun collaudo touch autenticato o deploy eseguito qui.

## UX dei materiali nell'ordine — azioni esplicite

Chiarimento del PO: «Modifica movimentazioni» non rende evidente l'inserimento
di materiali/CER non previsti e la modifica degli OrderItem nell'esecuzione.
Quel titolo apriva un pannello, senza registrare nulla finché non veniva
premuto un pulsante di salvataggio. Il contenitore generico è stato rimosso.

La scheda ordine ora presenta:

1. **Aggiungi materiale / CER**: apre i campi per un nuovo materiale associato
   a un CER del catalogo; contenitore, quantità, peso, descrizione e zona di
   carico. **Aggiungi all’ordine** registra la nuova riga esecutiva.
2. **Modifica materiale** su ogni item, vicino a numero/tipo contenitore,
   peso e descrizione: consente di modificare anche il CER dell'item esistente.
   **Salva materiale** applica solo i campi effettivamente cambiati.
3. **Quantità effettive registrate**: se presenti, la sezione è aperta e
   mostra i valori registrati, incluso zero. Se assenti, il completamento
   della tappa conferma le quantità correnti secondo il contratto esistente.
4. Note ordine/zona di carico e contenitori in consegna hanno azioni distinte;
   non precedono più in un pannello generico i comandi richiesti sui materiali.

Il CER viene selezionato dal catalogo: si aggiunge un materiale non previsto
in questo ordine, non si inventa un nuovo codice CER anagrafico.

Una sola rappresentazione degli item, con la stessa gerarchia CER/item già
concordata: `JourneyOrderMaterials` espone slot per le azioni e l'editor in
linea. `JourneyExecutionMaterialFields` condivide i campi tra inserimento e
modifica. Nessuna lista di lettura duplicata sotto una seconda lista di editor.
A salvataggio riuscito il form si chiude; errori lasciano i dati compilati e
sono visibili nel feedback mobile condiviso. Le bozze di altri item restano
conservate. Il salvataggio parziale evita di introdurre valori di default,
come una zona di carico diversa, quando si modifica soltanto la descrizione.

Corretto anche il comando add_item: eventuali quantità/pesi effettivi ammessi
dalla validazione vengono conservati durante l'inserimento, invece di essere
ignorati; zero resta distinto da non registrato. Pianificato e OrderItem
commerciali rimangono invariati. Le tappe concluse richiedono «Riapri tappa»
prima di modificare i materiali, con i guard della GLE-118.

Prove aggiunte: `JourneyExecutionMaterialsTest.php` per inserimento CER e
modifica di item via API, rilettura, originali invariati, audit, zero effettivo
e blocco sulle tappe concluse; `journeyExecutionOrderEditor.test.mjs` per
visibilità iniziale delle azioni, lista unica, campi e feedback degli errori.

Verifica di questo aggiornamento: **54 test PHP / 346 asserzioni passati**,
**6 file di test frontend passati**, build Vite riuscita e diff senza errori.
I test di rendering verificano le azioni visibili; il collaudo touch reale
rimane distinto dalle prove automatizzate. Nessuna modifica diretta al DB
locale o migrazione è stata necessaria per questo aggiornamento.

### Comandi compatti di modifica del materiale

Nella riga del materiale, la matita circolare apre l'editor. Durante la modifica
viene sostituita da due pulsanti circolari: X per scartare i valori non salvati
e chiudere, floppy per salvare e chiudere. Il salvataggio resta disabilitato
finché non cambia un valore e usa il form associato per mantenere la validazione
HTML. Rimossi puntino arancione e pulsanti interni duplicati. Più item possono
restare aperti senza perdere le rispettive bozze. Il ripristino della storia
persistita rimane nel menu Ripristina ed è distinto dall'annullamento locale.


## Feedback e riepiloghi contenitori — 2026-11-02

Su indicazione PO, feedback fixed in alto sotto l'header, chiusura X circolare
visibile e messaggi soltanto sull'esito, con codice CER del materiale salvato.
Rimosse le spiegazioni del piano conservato e della tracciatura dai messaggi.
Per ritiri/consegne introdotto `JourneyContainerQuantities`: tessere flex con
numero grande sopra e tipo sotto, centrate, riutilizzate anche nei materiali.
Restano invariati conteggi, differenza previsto/effettivo, zero e dato assente.
Verifica: 7 file frontend e build passati.


## Destinatari dello storico — 2026-11-02

Il PO chiarisce che il blocco completo Piano originale/storico e i confronti
generali dell'ordine sono per Logistica e ruoli di controllo developer/admin,
non per l'autista. `JourneyPolicy::viewExecutionHistory` centralizza il
permesso, esposto come capability alla UI e verificato dall'API dello storico.
L'autista mantiene correzioni e ripristini puntuali; tracciatura e originale
rimangono conservati senza ingombrare l'interfaccia operativa.
