# Ciclo documentale di ordini e viaggio

Review del 2026-10-31. Fonte delle regole: chiarimenti del PO nella sessione.
Fonte dell'AS-IS: codice del working tree; nessun test di generazione sul DB
reale, nessun job accodato o documento rigenerato per questa verifica.

## Terminologia e rapporto causa/effetto confermati

**Download/ristampa**: recuperare un file già generato e stamparlo di nuovo.
Non genera dati/documenti nuovi e non modifica alcuno stato business.

**Generazione iniziale**: produrre i documenti quando non c'è ancora una
versione valida. Un retry in caso di errore è un tentativo tecnico della
stessa revisione, non un cambiamento dell'ordine o del viaggio.

**Rigenerazione**: produrre una nuova versione dopo una modifica a un ordine
collegato o al viaggio. Entrambi sono trigger espliciti del PO. Non basta
cliccare Genera su dati invariati per autorizzare una nuova revisione documentale.

La catena è:

```text
modifica ordine OPPURE modifica viaggio
    → documenti non più allineati alla revisione corrente
    → rigenerazione abilitata
    → nuovi documenti riferiti alla revisione corrente
```

Non vale la catena inversa: generare un file non deve far diventare nuovamente
creato un ordine eseguito né riportare un viaggio in pianificazione.

## AS-IS verificato

| Operazione | Codice | Effetti osservati |
| --- | --- | --- |
| Elenco file/stato | API_OrderDocumentsController::list/status, JourneyController::documentsStatus | Lettura di stati e file; non riapre ordini o viaggi |
| Download | API_OrderDocumentsController::download | Restituisce il file con autorizzazione view; nessun reset di stato |
| Genera singolo ordine | API_OrderDocumentsController::generate | Controlla autorizzazione update e generazione già attiva/stale; non confronta revisioni ordine/viaggio |
| Genera dal viaggio, singolo o bulk | JourneyController::generateDocuments | Sceglie gli ordini collegati e richiama lo stesso servizio, saltando quelli già in generazione; nessun guard di fase o confronto con revisione documentata |
| Accodamento | OrderDocumentGenerationService::enqueueGeneration, riga 23 | Forza Order.status=creato e documents_status=generating, quindi accoda il job |
| Job | GenerateOrderDocumentsJob | Riceve solo orderId e rilegge i dati correnti al momento del lavoro; nessuna revisione attesa |
| Generazione file | OrderDocumentGenerationService::generate, riga 34 | Crea manifest JSON, XLSX e PDF ADR/HP quando richiesto; assegna nuova versione; porta creato/pronto/pianificato a pronto |
| Errore | markFailed | Segna documents_status=failed e messaggio; non ripristina lo stato business precedente forzato dall'accodamento |
| Modifica ordine | OrderController::update → invalidateAfterReadyOrderEdit, riga 104 servizio | Invalida documenti e può forzare Order.status=creato, senza un confronto esplicito fra contenuto vecchio e nuovo |
| Modifica viaggio | JourneyController::update, righe 596–681 | Invalida per alcuni campi della testata (date, mezzi/cassoni, autista) e per nuovi ordini associati; non c'è un trigger uniforme per tutte le modifiche del viaggio |

Il pannello `JourneyDocumentGenerationPanel.vue` distingue download da Genera.
Il comando Genera è però disponibile senza verificare cambiamenti del contenuto
e il pannello è montato anche in Journey/Show. Non è stato trovato un comando
applicativo dedicato alla stampa fisica: un file generato/scaricato non prova
che la stampante lo abbia stampato.

### Precisazione sul reset a creato

Il reset individuato riguarda **l'ordine**, non direttamente il viaggio.
Esempio dedotto dal codice, non eseguito sul DB: su un viaggio attivo,
rigenerare un ordine può far diventare quest'ultimo creato durante il job
e pronto alla fine, lasciando il viaggio attivo. È una incoerenza tra cicli,
non la prova che una ristampa riporti il viaggio in stato creato.

Il ramo generate tenta di conservare stati più avanzati, ma enqueueGeneration
li ha già forzati a creato. Il motivo storico di tale scelta non è documentato
nel punto di codice: l'effetto è chiaro, l'intenzione originaria non è provata.

### Trigger viaggio non uniforme

`journeyExecutionContextChanged` confronta date, mezzo/rimorchio, cassoni e
autista. La sola modifica di note o composizione/sequenza tappe non passa
da quel confronto. `applyOrdersToJourney` invalida un ordine appena associato,
ma non uno già associato che cambia solo cargo_location. I comandi operativi
di riordino/carico hanno ancora percorsi propri. La nuova regola richiede
una dipendenza documentale dalla revisione del viaggio, non solo questi if.

## Correzione organica proposta

1. Documento legato a una revisione ordine **e** a una revisione viaggio;
   numero documento e revisioni business sono identificativi diversi.
2. Prima generazione o assenza di documento valido: generazione consentita.
   Se entrambe le revisioni coincidono con quelle documentate: usare il file
   esistente. Se cambia una delle due: abilitare rigenerazione.
3. Download/ristampa sempre in sola lettura. Nessun evento di congelamento
   del piano legato alla stampa; il congelamento resta alla prima partenza.
4. Stato documentale tecnico separato dal ciclo ordine/viaggio: in coda,
   generato, non allineato o fallito non devono far retrocedere l'esecuzione.
5. Prima della partenza una modifica invalida la preparazione documentale,
   non elimina i file storici. Dopo, nuovi documenti riflettono l'esecutivo
   senza sostituire la baseline originale.
6. Un solo controllo condiviso per endpoint ordine e viaggio, con protezione
   dalla doppia generazione e pubblicazione condizionata alla revisione attesa.
   Il job non può certificare pronto un piano diverso da quello documentato.
7. Distinguere le modifiche business effettive da semplice updated_at,
   polling, download e avanzamenti puramente tecnici della generazione: questi
   ultimi non devono autoattivare un ciclo infinito di invalidazione.

La granularità documentale resta quella dei file per ordine nel contesto
del viaggio. Una modifica al viaggio rende necessari nuovi riferimenti
documentali di viaggio per gli ordini collegati; non va ignorata perché il
record ordine non è cambiato fisicamente.

## Altre verifiche necessarie nel ridisegno

- Il job conserva soltanto l'ID: va ancorato alla revisione o a input congelati.
- Controllo generating e scelta del prossimo numero versione non sono
  coordinati da un lock unico: richieste concorrenti devono essere gestite.
- Il timeout usa updated_at dell'ordine, non un istante di avvio dedicato.
- listDocuments sceglie il file più recente per template tramite mtime:
  modulo e ADR/HP possono non appartenere alla stessa versione; una vecchia
  etichetta potrebbe esistere anche se oggi non è più richiesta.
- Dopo invalidazione i file precedenti rimangono sul disco: disponibilità
  fisica non significa validità per la revisione corrente. La UI deve
  distinguere documento corrente e storico consultabile.

Sono lacune o rischi derivati dal percorso del codice, non incidenti osservati
in produzione. Nessuna correzione del servizio documentale è stata applicata
durante questa review.

## Test richiesti prima dell'implementazione definitiva

- generazione iniziale, fallimento e retry senza cambiare revisione business;
- nessuna rigenerazione su contenuti invariati e nessun effetto da download;
- cambio solo ordine e cambio solo viaggio: entrambi abilitano rigenerazione;
- modifica di composizione tappe, sequenza o carico riconosciuta correttamente;
- documento nuovo prima dell'avvio e documento nuovo su esecuzione avviata;
- ordine eseguito e viaggio attivo non retrocedono per generare file;
- doppia richiesta, job vecchio e modifica durante la generazione;
- modulo/ADR/HP coerenti con la stessa revisione, inclusa etichetta non più
  necessaria e documento storico ancora presente;
- avvio consentito solo quando la preparazione della revisione corrente è valida.

## Attuazione locale successiva alla review — 2026-10-31

Le lacune sopra descrivono l'as-is precedente alla modifica. Il nuovo ciclo è
implementato: fingerprint di ordine e viaggio, job vincolato agli input,
istante dedicato alla generazione, selezione dei file della stessa versione e
nessuna retrocessione dello stato business. Il dettaglio, le verifiche e i
limiti sono nel [piano operativo](Piano%20operativo%20esecuzione%20viaggi.md).
