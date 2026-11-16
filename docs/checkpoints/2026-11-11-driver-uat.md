# Checkpoint autisti — 2026-11-11, attesa UAT

**Aggiornamento locale 2026-11-13:** [sviluppi post-UAT GLE-127–135](../qa/2026-11-13-driver-uat-followup.md), implementati e verificati nel working tree; migrazione applicata solo in locale. Nessun nuovo deploy QA; tutte le nuove issue In corso, in attesa di rilascio e ricollaudo.

## Stato verificato e ripresa

Codice consolidato, committato e inviato a origin/main:
`89b2b22a411e99cbb260aba9318079b70408d5dc`.
Rilasciato in QA il 2026-11-11 con deploy-qa.sh, PHP residente 8.4.27 e
Laravel 11.57.1, debug e maintenance OFF. Nessun rilascio produzione.
Snapshot preventivo codice/vendor/database verificato gzip e checksum:
`~/deploy-backups/metallica-qa/20261111T165339Z-before-89b2b22` sul server QA.
È un backup sullo stesso host, non una protezione offsite indipendente.
Nessuna nuova migrazione; cache ricostruite e segnale queue:restart inviato.
Smoke: login 200, dashboard senza sessione 302, tre entry asset 200.
Marker remoto storage/app/deployments/qa-release.json e locale .last-qa-deploy.

PO: attendere i collaudi UAT e dichiarare conclusa l'attività solo dopo gli esiti.
Non confondere sviluppo, disponibilità QA, accettazione e produzione.

## Funzionalità raggiunte

Dashboard giorno corrente, date italiane, storico/futuri, convoglio e avanzamento;
workspace responsive condiviso, tappe concluse prima della corrente e sempre
recuperabili. Ordini per CER con descrizioni item, quantità e contenitori.
Referenti sede e ruoli, chiamate separate mobile/fisso da 42 px, contatti di tutte
le tappe e gestione referenti nell'anagrafica. Nessun piano/storico tecnico nel driver.

Baseline immutabile alla prima partenza, esecuzioni dedicate nel relazionale,
comandi con autore/diff/revisione. Modifiche materiali/CER/quantità, tappe,
riapertura con fatti conservati, rilascio immediato degli ordini di tappe saltate.
Motrice/rimorchio fissi dopo partenza; allocazioni per item, split su due righe
visive senza duplicare item e checklist persistente. Ritiri/consegne indipendenti;
residui = consegne pianificate iniziali incluse scorte meno consegne effettive.

Note sede inline dopo i contatti, una sezione per sede distinta: modifica
anagrafica condivisa con permesso ristretto al viaggio assegnato attivo,
concorrenza tramite versione, evento con prima/dopo; snapshot congelati invariati.
OP-001 risolto: nessun riordino del pianificato da autista; «Avvia e riordina»
registra partenza effettiva e apre editor solo dopo successo. Annullare il solo
riordino successivamente non annulla la partenza.

Notifiche unificate nella coda globale già esistente, host unico anche fra
layout; precedente feedback driver rimosso. Contratto flash/queueMessage
compatibile. N operazioni generano N messaggi anche identici; nuovi in basso,
ID e timer individuali. Permanenza 3 s + fade 0,5 s, errori/azioni persistenti,
chiusura indipendente dai timer vicini. Grafica precedente con correzioni mobile:
stessa riga per icona/testo/X, chiusura circolare outline 42 px, padding 6/12 px,
margini laterali minimi 12 px e safe area, animazione ridotta rispettata.
Annulla ripristino mantiene i controlli di validità ed elimina callback all'uscita.

## Test e limiti

Suite pertinente completa: 86 PHP / 586 asserzioni. Ultimo controllo precommit:
13 PHP / 101 asserzioni; 12 file frontend passati. Notifiche: 10 casi mirati
su contratto legacy, ordine, duplicati, timer, chiusure, isolamento e azioni.
Build Vite completata; avviso preesistente dimensioni bundle. Smoke QA positivi,
nessuna accettazione manuale touch/UAT documentata.

## Tracker aggiornato via MCP nativo

Query project: GLE tag: ui-autisti, una pagina da 20, 11 risultati,
hasNextPage=false; rilettura dopo aggiornamenti. Tutte GLE-116–126 in «In corso»,
nessuna risolta. Il workflow ammette solo To do / In Progress / Done: non esiste
«In collaudo». Aggiunto commento a ciascuna con funzionalità, commit, deploy,
verifiche e attesa UAT. Fonte: ../evidence/youtrack-driver-2026-11-11.json.

Nove issue funzionali hanno il comportamento principale disponibile in QA;
GLE-116 epica rimane in attesa di accettazione complessiva. GLE-122 non è
integralmente completata rispetto al testo ampio: ripristini disponibili sui
campi materiale, non su consegne/contenitori vuoti, intera struttura/sequenza
o quantitativo di uno split precedente. Residuo esplicitato nel commento MCP;
non cancellato né reinterpretato come accettato. Anche la riconciliazione delle
API documentali operative con la decisione PO sui soli documenti pianificati
resta un tema noto della review, fuori dalle due ultime modifiche implementate.

## Prossima sessione

1. Acquisire esiti UAT per GLE-116–126, registrare difetti e distinguere bug da nuove richieste.
2. Concordare il perimetro residuo GLE-122 e del ciclo documentale senza dichiararlo chiuso.
3. Correggere esiti e rieseguire test pertinenti; dichiarare conclusa l'attività e chiudere le issue solo dopo accettazione.
4. Nessun nuovo sviluppo o deploy automatico è richiesto dalla sola ripresa.

Le conversazioni sulle credenziali Workspace non sono incluse in questo checkpoint.
Dati di test e backup DB restano fuori Git/KB. Reset locale del 159 eseguito il
9 novembre: creato, tre tappe originali, nessuna baseline/esecuzione/evento e
documenti correnti al controllo di allora; non è una lettura dello stato odierno.


## Nuove richieste emerse in UAT

Le azioni sotto sono proposte da correggere/integrarne il perimetro; non attestano implementazione o accettazione.

| Punto                               | Mia descrizione del file md                                                                                                                                                                                                                                 | Feedback                                                                                                                                                                                                                       | Azione                                                                                                                                                                                                                                                                                                                                                        |
| ----------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Verifica email | Abilitare verifica email: Ripristinare l'invio automatico della verifica email quando viene modificato l'indirizzo di posta elettronica dell'utente. | La richiesta comprende invio automatico al cambio indirizzo e reinvio manuale già annotato. Il nuovo indirizzo deve tornare da verificare; il precedente comportamento automatico non è ancora dimostrato. | Proposta: invalidare la verifica al cambio effettivo di indirizzo, inviare la verifica al nuovo indirizzo e consentirne il reinvio manuale. Riferimento: [GLE-127](https://metallicamarcon.youtrack.cloud/issue/GLE-127). |
| Modifica sede più visibile | Migliorare UI: Rendere più visibile il pulsante di modifica della sede all'interno della scheda cliente. | La richiesta riguarda la modifica dell’anagrafica da parte di Logistica/amministrazione, non il workspace autista. Decisione PO: attività in pausa, fuori dallo sviluppo attuale. | IN PAUSA — Non sviluppare ora. Conservare il requisito di semplificazione del percorso di modifica dati sede e gestione referenti per Logistica/amministrazione. |
| UNDO dopo cancellazione | Migliorare visibilità annulla: in caso di cancellazione elemento come opero un UNDO? | Decisione PO: il materiale rimosso resta visibile come record fantasma recuperabile. Elimina definitivamente indica solo la pulizia della UI: lo storico tecnico rimane conservato. | Mostrare il materiale rimosso come record attenuato, con Ripristina ed Elimina definitivamente. Ripristina recupera il materiale con i relativi dati e controlli di validità; Elimina definitivamente nasconde il record fantasma dalla UI senza cancellare lo storico tecnico. Conservare la scelta di occultamento ai successivi caricamenti. Riferimento: [GLE-128](https://metallicamarcon.youtrack.cloud/issue/GLE-128). |
| Completa tappa anche in alto | Ottimizzare completamento tappa: Inserisci il tasto completa tappa anche nella parte superiore della schermata per migliorare l'accessibilità. | Decisione PO: Salta e Completa devono essere entrambi disponibili sia nella card principale della tappa sia in fondo al dettaglio, dopo il riepilogo. | Mostrare entrambi i comandi Salta e Completa nei due punti. Riutilizzare le stesse azioni, autorizzazioni, conferme e stato di caricamento per evitare differenze di comportamento o doppie operazioni. Riferimento: [GLE-129](https://metallicamarcon.youtrack.cloud/issue/GLE-129). |
| Salva senza modifiche | In creazione viaggio disabilitare salvataggio vuoto: Disabilita o rendi grigio il tasto salva quando non ci sono modifiche effettive da salvare. | Distinguere prima creazione e aggiornamenti: un viaggio nuovo valido deve poter essere creato; uno già salvato e invariato non deve generare aggiornamenti. | Disabilitare Salva quando il viaggio esistente è invariato e impedire invalidazioni documentali per salvataggi senza modifiche effettive. Riferimento: [GLE-130](https://metallicamarcon.youtrack.cloud/issue/GLE-130). |
| Salvataggio e generazione documenti | In creazione viaggio disabilitare salvataggio finchè sta creando i documenti. Il salvataggio successivo alla creazione non dovrebbe essere possibile, tuttavia va gestita l’esperienza di creazione documenti che oggi li annulla se si salva a posteriori. | Priorità proposta alta. Bloccare il pulsante durante la generazione aiuta, ma serve anche evitare invalidazioni dopo salvataggi invariati. Le modifiche reali successive devono seguire le regole concordate di rigenerazione. | Verificare il caso UAT e gestire insieme stato della UI, controlli backend e revisione dei dati usati dalla generazione; preservare i documenti su salvataggio invariato. Riferimento: [GLE-131](https://metallicamarcon.youtrack.cloud/issue/GLE-131). |
| Contatore prodotti da consegnare | Rimuovere contatore superfluo: Elimina il contatore relativo ai prodotti da consegnare per evitare confusione tra gli autisti. | Va identificato il contatore preciso; mantenere le quantità operative dei contenitori e la distinzione fra ritiri e consegne. | Va eliminato il contatore "da consegnare" poichè quello è il mandato. L'autista può solo consegnarne il numero previsto o un numero diverso... Di conseguenza il numero effettivamente consegnato (di default = a da consegnare) è l'unico numero modificabile dal driver Riferimento: [GLE-132](https://metallicamarcon.youtrack.cloud/issue/GLE-132). Chiarimento PO 2026-11-16: consegne effettive complessive non superiori ai vuoti disponibili per tipo. Ammessa redistribuzione fra clienti e rientro con residui; bloccare anche il completamento automatico oltre disponibilità. [Implementazione e verifiche](../qa/2026-11-16-delivery-capacity.md). |
| Apertura testata viaggio | Configurare testata viaggio: Imposta la testata del viaggio affinché risulti aperta automaticamente al primo accesso e chiusa ai successivi. | Decisione PO: il flag Apri all’avvio è specifico del singolo viaggio, inizialmente true; non è una preferenza globale dell’autista. | Aggiungere per singolo viaggio il flag Apri all’avvio, di default true, modificabile dal driver e persistente. All’apertura del workspace usare il flag per decidere se espandere la testata; la semplice apertura/chiusura manuale della testata non cambia il flag. Non chiudere automaticamente ai successivi accessi se il flag resta true. Riferimento: [GLE-133](https://metallicamarcon.youtrack.cloud/issue/GLE-133). |
| Etichette articoli pericolosi | Etichette rosse: recuperare la regola per tematizzare gli articoli pericolosi sulla nuova dashboard per autisti. | Recuperare la regola distinguendo CER pericoloso e presenza ADR, che non coincidono necessariamente. Usare il token semantico error con testo/icona. | Verificare la regola precedente presente in creazione ordine e centralizzare la rappresentazione nella nuova UI autisti, senza colori arbitrari. Creare, all'occorrenza un component o un  composable riutilizzabile Riferimento: [GLE-134](https://metallicamarcon.youtrack.cloud/issue/GLE-134). |
| Visibilità tappe concluse | Ottimizzare interfaccia: Ottimizzare la visibilità del pulsante per mostrare le tappe concluse ingrandendolo. | Richiesta chiara: comando più grande e riconoscibile, con numero di tappe e stato aperto/chiuso evidente. | Aumentare visibilità e area di tocco del comando, mantenendo compattezza mobile e stato accessibile. Riferimento: [GLE-135](https://metallicamarcon.youtrack.cloud/issue/GLE-135). |
| Integrazione formulari              | Verificare integrazione formulari: Verificare con MARIO la possibilità tecnica di integrare la visualizzazione dei formulari nel nuovo software gestionale.                                                                                                 | Approfondimento con Mario: distinguere visualizzazione di allegati e integrazione con un sistema esterno, perché dipendenze e lavoro sono diversi.                                                                             | Da verificare con Mario: sistema sorgente, disponibilità di file o API, autorizzazioni e collegamento dei formulari a ordini/viaggi; poi definire fattibilità e perimetro.                                                                                                                                                                                    |
