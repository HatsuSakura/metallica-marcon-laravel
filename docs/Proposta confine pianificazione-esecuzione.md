# Review: pianificazione, partenza ed esecuzione

Data: 2026-10-31. Regole funzionali dichiarate dal PO; proposta tecnica da
riesaminare insieme prima della relativa implementazione. Non descrive
codice già realizzato per GLE-122.

## Regola funzionale confermata

| Momento | Oggetto modificato | Responsabile operativo | Tracciatura |
| --- | --- | --- | --- |
| Prima della prima partenza | Piano in preparazione: viaggio e ordini | Solo Logistica | Storico delle revisioni di pianificazione |
| Prima partenza | Ultimo piano valido, completo e coerente | Comando di avvio autorizzato | Congelamento unico dell'originale e apertura esecuzione |
| Dopo la prima partenza | Viaggio operativo e contenuti operativi degli ordini | Autista assegnato e Logistica | Differenze dall'originale e dalla revisione operativa precedente |

L'originale è **il piano alla partenza**, non la prima bozza del viaggio,
la prima versione dell'ordine o la versione stampata in un momento precedente.
Le revisioni prima della partenza aggiornano quindi legittimamente il piano
che diventerà originale. Le modifiche della Logistica dopo la partenza sono
esecutive tanto quanto quelle dell'autista.

Riapertura di tappa, correzione di un orario, rigenerazione documenti o
correzione di un esito non riaprono la pianificazione e non sostituiscono
l'originale. Il campo di stato non deve essere l'unico criterio: il fatto
che la prima partenza sia avvenuta deve rimanere identificabile e immutabile.

## Perimetro di produzione dichiarato dal PO

Il PO riferisce che l'uso corrente di produzione arriva alla composizione
dei viaggi e alla produzione dei documenti cartacei. Non risultano utilizzate
le attività digitali di esecuzione e di magazzino a valle. Non è stata svolta
una verifica live di produzione durante questa review.

Il post-partenza può pertanto essere ridisegnato organicamente, senza
adattatori per conservare un workflow non utilizzato. Questo non significa
eliminare dati o documenti, modificare la produzione o interrompere il ciclo
di pianificazione/stampa già utilizzato. Nessuna di tali operazioni è inclusa
in questa proposta.

## AS-IS: differenze accertate

La [ricognizione dei meccanismi esistenti](Verifica%20piano%20viaggio%20e%20tracciatura.md)
rimane l'evidenza di partenza: Versionable, audit OwenIt, journey_events e
separazioni planned/actual sono presenti e non vanno ignorati.

| Percorso attuale | Comportamento osservato nel codice | Differenza rispetto al TO-BE |
| --- | --- | --- |
| OrderPolicy create/update, JourneyPolicy update | Autista abilitato per creazione ordine o modifica di risorse assegnate senza distinzione temporale | Il ruolo e l'assegnazione non bastano: prima dell'avvio nessuna modifica di pianificazione da autista |
| JourneyController::update | Modifica solo in stato creato; elimina/ricrea tappe e associazioni | È un percorso di pianificazione, non il comando di modifica di un viaggio già attivo |
| Avvio in API_DriverJourneyStopsController | Avvia, registra ora, inizializza carichi e allocazioni planned, apre una tappa, emette evento | Manca il congelamento completo e atomico dell'aggregato |
| Avvio alternativo in API_DriverJourneyUpdateController e JourneyController::updateState | Transizione di stato con controlli propri | Possono saltare inizializzazione ed effetti dell'altro percorso; occorre un unico comando |
| Riordino/aggiunta sosta/salto | Autorizzazione all'assegnatario, sequenze ed eventi; alcuni campi aggiornati in massa | Non applicano un guard comune di fase; non c'è una revisione operativa unica; il salto rimuove associazioni |
| Modifica ordini | Scrive nelle stesse righe ordine/materiali/contenitori; alcuni ingressi usano query di massa | La mutazione va indirizzata alla pianificazione o all'esecuzione secondo la prima partenza, non secondo il controller usato |
| plan_version | Incrementato da un servizio del piano dispatch | Non identifica tutte le revisioni strutturali di viaggio e ordini |
| Censimento e allocazioni dispatch | Stato corrente separato per source planned/actual, aggiornato sul posto | Alcune revisioni sono perse e planned può essere aggiornato; non è l'originale congelato |
| Documenti | Generazione e invalidazione possono cambiare Order.status; nuova generazione non subordinata a cambiamenti di ordine/viaggio | Validità documentale e fase operativa vanno separate; ordine o viaggio cambiati sono i trigger |

Le autorizzazioni tecniche attuali includono ruoli di controllo e bypass
amministrativo. La mappatura del termine aziendale Logistica va mantenuta
esplicita: i permessi tecnici non possono autorizzare la riscrittura di un
originale congelato. Anche un amministratore usa le regole di fase.

## Modello proposto: un originale, un operativo, fatti eseguiti

### 1. Pianificazione prima della partenza

Gli ordini rimangono richieste pianificabili con righe materiali e contenitori
da consegnare. Il viaggio compone tappe, sequenze, ordini e carico. I comandi
Logistica aggiornano questo piano e la sua revisione. Le modifiche non
diventano scostamenti esecutivi finché il viaggio non è partito.

Le versioni e l'audit esistenti mantengono lo storico delle bozze. Non serve
rendere immutabile ogni bozza chiamandola piano originale.

### 2. Avvio unico e transazionale

Un solo comando di partenza deve essere usato da ogni route o UI autorizzata.
Nella stessa transazione:

1. blocca il viaggio e coordina gli aggiornamenti agli ordini collegati;
2. verifica dati e validità documentale della revisione corrente;
3. congela la baseline completa con riferimento alle revisioni di origine;
4. inizializza lo stato operativo dal piano congelato;
5. registra prima partenza, autore, istante e revisione iniziale;
6. apre l'esecuzione e la prima tappa.

Il comando è idempotente: una seconda richiesta non produce un'altra baseline,
non ricopia i dati e non riapre una tappa già eseguita. Il guard di congelamento
rimane vero anche se viene corretto l'orario effettivo.

Il blocco sul viaggio è efficace solo se anche modifiche ordini/viaggio
seguono lo stesso protocollo di concorrenza. Avvio e modifica del piano non
devono poter produrre un originale misto di revisioni diverse.

### 3. Baseline completa e autonoma

La baseline deve poter rispondere a “cosa era previsto alla partenza?” senza
leggere come originali righe correnti che potrebbero essere cambiate.
Comprende almeno:

- testata, mezzo/convoglio e assegnazioni;
- identità e ordine delle tappe, sedi operative e associazioni agli ordini;
- gruppi CER, materiali, contenitori, quantità e pesi dichiarati;
- vuoti da consegnare, indipendenti dai pieni da ritirare;
- collocazioni/allocazioni pianificate e indicazioni operative rilevanti;
- riferimenti alle revisioni e ai documenti validi alla partenza.

Sono riutilizzabili le versioni esistenti dei record. Devono però essere
collegate alla stessa baseline e integrate per le relazioni e le entità non
versionate. La scelta fra snapshot completo versionato e manifest di versioni
più snapshot delle parti mancanti va fatta nel disegno fisico, non dedotta
dalla sola disponibilità di un trait.

### 4. Esecuzione isolata dalle richieste originali

Propongo un contesto esecutivo degli ordini specifico del viaggio. Deve
conservare il riferimento all'ordine/riga di origine ma non dipendere dalla
sua futura versione corrente per ricostruire un ritiro già avvenuto.

Questo è particolarmente importante quando una tappa viene saltata e un
ordine torna pianificabile su un altro viaggio: modificare quel nuovo piano
non deve cambiare retroattivamente l'esecuzione del primo viaggio. Una riga
aggiunta sul posto può non avere una riga originaria; una rimossa mantiene
identità, baseline e storia.

Le strutture operative possono riusare o sostituire le attuali tabelle di
tappe/censimento/allocazioni. L'obiettivo non è duplicare ogni tabella per
abitudine, ma avere una sola fonte autorevole per ciascuno di:

- originale congelato;
- composizione operativa corrente;
- fatti registrati di ritiro/consegna/esito.

Le allocazioni planned esistenti possono essere proiezioni dell'originale,
non un secondo piano originale editabile. La quantità da consegnare non è
vincolata alla quantità ritirata. La posizione del materiale sul convoglio
deve poter essere rappresentata alla granularità necessaria, non soltanto
tramite una singola posizione sull'intero ordine.

Una modifica alla previsione di una tappa futura è diversa dalla registrazione
di un fatto già avvenuto. Entrambe sono esecutive dopo la partenza e vanno
distinte nel comando e nella UI; non producono automaticamente un movimento
statistico soltanto perché è cambiata una quantità.

### 5. Revisione operativa ed eventi coordinati

Autista e Logistica invocano gli stessi servizi di dominio per le stesse
operazioni, con permessi diversi secondo la funzione. I controller non
implementano copie divergenti delle regole.

Ogni comando produce una revisione operativa e un evento leggibile con autore,
origine, istante, soggetti interessati e correlazione ai diff. Le revisioni
consentono sia confronto con la baseline sia confronto con la precedente.
Il client invia la revisione attesa: una modifica concorrente viene gestita
esplicitamente e non sovrascritta silenziosamente.

Ruoli dei meccanismi già presenti:

- Versionable: storico degli attributi dove utile e già presente;
- OwenIt: diff tecnici e attori, estendendo la copertura dei model necessari;
- journey_events: comandi/eventi di processo e correlazione alla revisione;
- baseline e proiezioni planned/actual: significato del dato nel dominio.

Non si introduce un quarto registro parallelo che duplichi senza criterio
gli stessi dati. Le scritture di massa che aggirano il tracciamento vanno
eliminate, oppure devono avere un diff di aggregato esplicito e verificabile.

“Ripristina originale” riprende il valore della baseline; “Annulla ultima
modifica” riprende il precedente operativo. Entrambi sono nuove modifiche
esecutive e non eliminano eventi o riscrivono la baseline. La disponibilità
tecnica di Version::revert non basta a ripristinare coerentemente un aggregato.

## Documenti: due trigger e nessuna inversione causa/effetto

Regola chiarita dal PO: una modifica a un ordine collegato **oppure** una
modifica al viaggio abilita la rigenerazione documentale. Il solo passaggio
di tempo, salvataggio senza variazioni o download non la abilita.

Il documento deve essere legato alla coppia di revisioni ordine/viaggio
pertinente. Il proprio numero di versione documentale non sostituisce quelle
revisioni. La rigenerazione fotografa la revisione corrente, non la crea.

- Prima della partenza: documenti del piano corrente; modifiche a ordine o
  viaggio li rendono da aggiornare. L'avvio richiede documenti coerenti con
  il piano finale, secondo il gate di preparazione concordato.
- Dopo la partenza: modifiche esecutive di ordine o viaggio abilitano nuovi
  documenti riferiti all'esecutivo. Gli originali rimangono consultabili.
- Download/ristampa: usa il file già esistente, senza modifiche di stato.
- Prima generazione e retry di una generazione fallita non presuppongono
  un precedente documento valido; il retry non è una nuova revisione business.

La lettura non riporta l'ordine a creato; la generazione non riporta viaggio
o ordine in pianificazione. Lo stato tecnico dei documenti va disaccoppiato
dalla fase operativa. La produzione dei file non prova la stampa fisica.

L'analisi dei percorsi attuali è in
[Ciclo documenti viaggio](Ciclo%20documenti%20viaggio.md).

## Impatto a valle e direzione realizzativa

Dispatch e magazzino devono consumare ciò che è stato eseguito/caricato e la
sua revisione operativa, con link alla baseline per confronto. Non devono
ricostruire l'eseguito da OrderItem correnti o diventare proprietari di un
altro originale. Ricezione/pesatura possono aggiungere fatti e correzioni
tracciate senza riscrivere il piano di partenza o riaprire il viaggio.

La mancata adozione post-partenza dichiarata dal PO consente di sostituire
gli ingressi incoerenti anziché affiancare servizi nuovi a quelli vecchi.
Gli ID e i riferimenti già usati nei documenti del pianificato devono restare
risolvibili verso gli oggetti di origine e le nuove entità operative.

Sequenza di implementazione proposta:

1. consolidare modello e comandi di pianificazione/partenza, autorizzazioni
   e ciclo documentale;
2. introdurre baseline e contesto esecutivo, eliminando gli avvii alternativi;
3. collegare la UI mobile a modifica materiali/CER/contenitori, tappe e carico;
4. aggiungere ripristini e riapertura tappe con lo stesso protocollo;
5. riallineare dispatch e magazzino al modello operativo unico.

GLE-122 coordina la fondazione con GLE-26/95–99. GLE-118/120/126 la usano;
GLE-117/125 leggono dati correttamente attribuiti al loro piano/esecuzione.
Non si dichiarano completate issue sulla base di questo documento.

## Criteri di accettazione del ridisegno

| Scenario | Risultato atteso |
| --- | --- |
| Autista tenta modifica prima di partire | Nessuna modifica al piano, anche tramite chiamata diretta alle API |
| Logistica cambia materiali o tappe prima dell'avvio | Piano corrente aggiornato; documenti da aggiornare; nessuno scostamento esecutivo |
| Salvataggio senza variazioni | Nessuna revisione business o invalidazione documentale artificiale |
| Avvio insieme a una modifica | Piano congelato interamente prima o interamente dopo; mai combinazione incoerente |
| Due richieste di avvio | Una sola baseline e una sola inizializzazione |
| Modifica post-partenza da entrambi gli attori | Originale invariato e aggiornamento esecutivo tracciato |
| Riordino, nuova tappa o esclusione | Identità/storia conservate, differenze leggibili e prossimo punto coerente |
| Materiale aggiunto/rimosso/cambiato | Collegamenti e dati originali preservati; nessun CER originario riscritto |
| Ritiro 4/consegna 2 o 5 | Quantità indipendenti, disponibilità e redistribuzioni coerenti |
| Tappa saltata e ordine riassegnato | Primo viaggio continua a mostrare la propria storia, non il nuovo piano |
| Correzione orario o riapertura tappa | Nessun ritorno alla pianificazione e nessuna seconda baseline |
| Ripristino originale/precedente | Nuova revisione; storico completo e nessuna oscillazione accidentale |
| Download/ristampa | Nessuna transizione business o documentale |
| Cambio ordine oppure viaggio | Rigenerazione abilitata per documenti non più allineati |
| Job documentale di revisione superata | Non può certificare come corrente o pronto un piano diverso |
| Ricezione o pesatura successiva | Fatti di magazzino collegati all'esecuzione, senza alterare l'originale |

Restano da definire nel successivo disegno di dettaglio fine viaggio, correzioni
dopo chiusura e configurazione dei ruoli tecnici di controllo. Nessuno di
questi punti modifica il confine funzionale della prima partenza già deciso.

## Attuazione — 2026-10-31

Il PO ha autorizzato piano e implementazione locale senza ulteriori conferme.
Il [piano operativo](Piano%20operativo%20esecuzione%20viaggi.md) documenta il
modello realizzato, gli ingressi, le prove e i limiti. Questa proposta resta
la provenienza della decisione; le indicazioni precedenti di «nessuna modifica
applicata» si riferiscono al momento della review.


## Punto aperto sulla fase prima della partenza — 2026-10-31

Il PO richiede di chiarire con Metallica Marcon se l'autista possa riordinare
le tappe prima di partire e se occorra conservare anche l'assegnazione iniziale
della Logistica. Vedi [OP-001](open-points.md). In attesa della call restano
regole e autorizzazioni correnti: nessuna nuova modifica del pianificato
abilitata all'autista.
