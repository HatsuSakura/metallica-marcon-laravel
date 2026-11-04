# GLE-118 — Riapertura tracciata di una tappa completata

Implementazione locale: 2026-10-31. Riferimento:
[GLE-118](https://metallicamarcon.youtrack.cloud/issue/GLE-118).
La fondazione conteneva già una riapertura; questo incremento ne completa
semantica, guard, visibilità UI e verifica degli effetti successivi.

## Comportamento

Selezionare la tappa conclusa nell'elenco delle tappe concluse e premere
**Riapri tappa**. Il comando non richiede popup ripetuti. La tappa selezionata
ritorna corrente; l'eventuale altra tappa corrente torna pianificata,
conservando i propri dati. Rimane una sola tappa corrente e non cambia la
sequenza del viaggio. Anche il dettaglio mantiene il focus sulla riaperta.

Quantità, pesi, contenitori consegnati, note e identità dei record esecutivi
rimangono conservati. L'ora del primo inizio tappa rimane; completed_at viene
azzerato nello stato corrente, ma il valore precedente rimane nello storico.
Ricompletare la tappa aggiorna gli stessi record senza creare doppi movimenti,
ordini, materiali, consegne o impegni.

L'ordine esecutivo ritorna pending, il suo impegno diventa attivo e lo stato
commerciale di evasione torna pianificato quando l'ordine è ancora disponibile.
Il viaggio terminato ritorna attivo senza una nuova partenza, senza modificare
actual_start_at e senza ricatturare la baseline. actual_end_at viene annullato
nello stato corrente e conservato nel diff precedente. Il conteggio delle
concluse si aggiorna attraverso il read model condiviso.

## Protezioni

- Solo chi può modificare quel viaggio può riaprire una tappa.
- Revisione attesa, lock autista/viaggio e transazione comune dell'esecuzione.
- Blocco se viaggio chiuso, materiali già ricevuti a magazzino, ordine già
  ripianificato/impegnato altrove, sorgente non più disponibile o autista già
  impegnato su un altro viaggio gestito.
- La UI mostra il motivo del blocco e disabilita il comando nei casi rilevati;
  il server ricontrolla sempre sotto lock.
- Una tappa con più ordini viene riaperta interamente o non viene modificata.
- Una richiesta superata non può sovrascrivere una revisione più recente.

La riapertura non annulla ricevimenti, pesature o classificazioni già avvenuti:
le rettifiche dei fatti di magazzino richiedono il successivo processo dedicato.
Il cambio di stato non cancella movimenti fisici e non sblocca ordini già
utilizzati da altri viaggi.

## Tracciatura e codice

`StopReopening` centralizza preview e applicazione. `DriverJourneyReadModel`
espone `reopen_available` e `reopen_unavailable_reason`; il workspace condiviso
presenta il comando e il feedback touch. La mutazione usa l'endpoint esistente:

```json
{"revision": 5, "operation": "reopen", "input": {"stop_id": 24}}
```

Evento execution_changed con operation=reopen: autore, istante, transizioni
prima/dopo, revisione, tappa, stato di provenienza e ID delle tappe correnti
rinviate. Originale e storico precedenti rimangono intatti. Persistenza e
conteggi sono riletti dopo uscita/rientro, non ricostruiti soltanto nella UI.

Test: `JourneyStopReopeningTest.php`; verifica corrente unica, mantenimento di
quantità/pesi/consegne e primi orari, ricompletamento senza duplicazioni,
ricezione, ripianificazione, atomicità multiordine, viaggio terminato/chiuso,
altro viaggio attivo, sorgente archiviato, autorizzazioni e revisioni via API.
Nessuna nuova migrazione.

## Preparazione locale richiesta dal PO

Il 2026-10-31 alle 22:20 Europe/Rome, su richiesta esplicita del PO, il viaggio
159 è stato riportato a creato con partenza prevista adesso: tre tappe
pianificate, orari esecutivi azzerati, sequenza operativa conservata come nuovo
piano, documenti reali dei tre ordini rigenerati e verificati correnti.
I cinque eventi preesistenti sono rimasti; aggiunto evento development_reset.
Backup escluso da Git nel disco local:
`development-backups/journey-159-reset-20261031-212003-663304.json`.

Nessuna partenza automatica eseguita dal reset. Una successiva verifica di
lettura ha trovato il 159 già attivo con contesto esecutivo, motrice 4 e
rimorchio 2: risulta quindi un avvio successivo al reset. Il 159
non è più il record legacy in sola lettura descritto nelle note precedenti.

Verifica finale: **52 test PHP / 315 asserzioni passati** nelle nove suite
mirate, **5 file frontend passati**, build Vite riuscita e diff senza errori.
Inclusi i test del vincolo convoglio prima/dopo partenza; resta l'avviso noto
sulla dimensione del bundle e il collaudo touch reale da effettuare sulla UI.
