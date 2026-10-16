# **Logistica E2E — Specifica di implementazione del digital twin**

## **1. Scopo del documento**

Questo documento descrive come la Web App Metallica Marcon implementa il processo logistico end-to-end e ne costituisce il digital twin.

È un documento di specifica e knowledge transfer: consente a un analista, sviluppatore, tester o responsabile operativo di comprendere il comportamento della soluzione, gli oggetti applicativi, le responsabilità, le regole di dominio e i dati prodotti.

La descrizione assume la soluzione funzionalmente completa. Non rappresenta lo stato di avanzamento del codice e non distingue le funzionalità in base al momento in cui sono state introdotte. Descrive quindi il **TO-BE target consolidato**, comprendendo sia il perimetro originario della Fase 1 sia le estensioni funzionali emerse per la Fase 2 come componenti già integrate nella medesima soluzione.

Il perimetro comprende:

* ordini di ritiro;
* viaggi, tappe e composizione del convoglio;
* ritiri effettuati dalla flotta Metallica Marcon;
* consegne a fonderie e impianti di conferimento;
* ritiri presso Metallica da parte di operatori esterni;
* conferimenti autonomi dei clienti;
* ritiri eseguiti da terzisti per conto di Metallica;
* censimento del carico e pianificazione dello scarico;
* presa in carico, pesatura, classificazione, selezione e catalogazione;
* messe a terra e trasbordi;
* dashboard, indicatori, audit e riconciliazione dei dati.

La fonte funzionale del processo aziendale è `Logistica E2E Analisi_v03.md`. Il presente documento compie l'operazione complementare: parte dal processo consolidato e descrive la soluzione applicativa che lo rende eseguibile e tracciabile.

### **1.1 Uso normativo della specifica**

Il presente documento costituisce la baseline funzionale e architetturale per evoluzioni, manutenzioni e integrazioni della Web App.

Chi progetta una nuova funzionalità deve:

1. assumere come punto di partenza il modello target qui descritto;
2. integrare la funzionalità negli oggetti, nei cicli di vita e nelle responsabilità del digital twin;
3. verificare che non vengano violate le invarianti di dominio;
4. valutare separatamente gli interventi necessari per portare l'implementazione corrente al target;
5. evitare soluzioni transitorie che incorporino come vincoli permanenti limiti o lacune dello stato corrente.

Il comportamento del codice esistente è rilevante per analisi d'impatto, compatibilità e migrazione, ma non sostituisce questa specifica nella definizione del risultato funzionale. In caso di differenza tra stato corrente e target, la nuova progettazione si conforma al target e rende esplicito il percorso di adeguamento.

Una funzionalità è considerata coerente soltanto se continua a essere valida una volta completato l'intero modello. Non è quindi accettabile un'ottimizzazione costruita esclusivamente sull'AS-IS applicativo quando questa produrrebbe dati, relazioni o flussi già incompatibili con il digital twin consolidato.

## **2. Principi di implementazione**

### **2.1 Digital twin**

La Web App non riduce il processo a un unico stato complessivo. Ordine, viaggio, tappa, movimento, cassone, materiale, dispatch e trasbordo conservano identità e cicli di vita distinti.

Una modifica operativa viene registrata sull'oggetto a cui appartiene e si propaga agli aggregati superiori soltanto attraverso regole esplicite. La chiusura di un viaggio, per esempio, non chiude automaticamente una lavorazione di magazzino o un trasbordo ancora aperto.

### **2.2 Pianificato ed effettivo**

Il sistema conserva separatamente:

* ciò che è stato pianificato;
* ciò che è stato effettivamente eseguito;
* lo scostamento;
* la motivazione dello scostamento;
* l'autore e l'istante della registrazione.

Il dato effettivo non sovrascrive il piano originario. Questa regola si applica a date, tappe, esecutori, materiali, contenitori, posizione sul mezzo, pesi, destinazioni e sequenze di scarico.

### **2.3 Responsabilità esplicite**

Ogni decisione mantiene il responsabile aziendale previsto dal processo. L'applicazione applica autorizzazioni per ruolo e non trasferisce automaticamente responsabilità tra Logistica e magazzino.

### **2.4 Tracciabilità senza attribuzioni fittizie**

Il sistema registra il soggetto che ha materialmente eseguito un movimento. Un dato non noto rimane esplicitamente non attribuito e non viene assegnato a un utente convenzionale per far quadrare i conteggi.

### **2.5 Separazione tra operatività e statistica**

Tutte le attività rilevanti sono tracciate, ma non tutte incrementano il KPI `Movimenti`. Viaggi, attività interne di magazzino, scarichi, messe a terra e trasbordi dispongono di propri eventi e indicatori senza essere conteggiati come movimenti logistici esterni.

## **3. Attori e autorizzazioni**

| Attore | Funzioni principali nella Web App |
| ----- | ----- |
| Logistica | Crea e pianifica ordini e viaggi, monitora l'esecuzione, verifica i movimenti, governa il dispatch, decide e pianifica i trasbordi, riconcilia le anomalie. |
| Autista Metallica | Consulta viaggio e tappe, registra avanzamento ed esiti, aggiorna il carico effettivo, comunica eccezioni. |
| Autista occasionale | Opera come esecutore effettivo dei viaggi e movimenti assegnati, con le stesse funzioni operative consentite a un autista. |
| Persona autorizzata alla guida | Può essere indicata come esecutore effettivo pur conservando il proprio ruolo principale di socio o collaboratore. |
| Capo magazzino | Comunica capacità e vincoli, concorda la ricezione con la Logistica, coordina scarico e lavorazioni. |
| Magazziniere | Prende in carico cassoni e materiali, registra scarico, pesatura, classificazione, difformità ed esigenze operative. |
| Operaio | Esegue e consuntiva selezioni e lavorazioni assegnate. |
| Terzista | È registrato come soggetto esecutore dei ritiri svolti per conto di Metallica. L'eventuale accesso diretto è separato dalla sua identificazione anagrafica. |
| Cliente autonomo | È registrato come soggetto esecutore del proprio conferimento presso Metallica. |
| Operatore esterno | È registrato come soggetto che ritira materiale presso Metallica per gestirlo nel proprio circuito. |

Le autorizzazioni distinguono almeno lettura, pianificazione, esecuzione, presa in carico, approvazione, annullamento e correzione. Le operazioni sensibili richiedono utente autenticato e producono audit.

## **4. Modello applicativo**

### **4.1 Relazioni principali**

```text
Cliente ──< Sede
   │
   └──< Ordine ──< Riga materiale
                     │
Viaggio ──< Tappa >──┘
   │          │
   │          └──< Movimento
   │
   ├──< Cassone di viaggio ──< Allocazione carico ──< Istruzione di scarico
   ├──< Censimento del carico
   ├──< Evento di viaggio
   └──< Esigenza di trasbordo >── Viaggio pianificato di trasferimento
```

Il collegamento è adattato alla natura del movimento. Ordine, viaggio, tappa, mezzo e autista sono obbligatori per il ritiro eseguito dalla flotta propria; sono opzionali o non applicabili per i movimenti eseguiti da soggetti esterni.

### **4.2 Ordine**

L'ordine rappresenta una richiesta di ritiro presso una sede del cliente. Contiene:

* cliente e sede;
* data richiesta e data prevista;
* priorità e urgenza;
* referente, note e vincoli del sito;
* righe materiale;
* vuoti a rendere;
* requisiti documentali e ADR;
* storico delle modifiche.

Ogni riga identifica materiale, codice CER, contenitore, numero di contenitori, peso dichiarato o stimato, destinazione prevista ed eventuali esigenze di lavorazione.

Gli stati dell'ordine sono:

```text
creato → pronto → pianificato → eseguito → scaricato → chiuso
```

Lo stato sintetico deriva dalle righe senza nascondere esecuzioni o lavorazioni parziali.

### **4.3 Viaggio**

Il viaggio organizza un'attività della flotta Metallica. Associa:

* date pianificate ed effettive;
* Logistica responsabile;
* autista pianificato ed esecutore effettivo;
* motrice, rimorchio ed eventuali cassoni;
* ordini;
* tappe cliente e tappe tecniche;
* piano ed effettivo del carico;
* piano di scarico;
* eventi ed eccezioni.

Gli stati sono:

```text
creato → attivo → eseguito → chiuso
```

Il viaggio non è un movimento. Può contenere zero o più movimenti e attività tecniche che non producono movimenti.

### **4.4 Tappa**

La tappa rappresenta un punto ordinato del viaggio. Può essere:

* `customer`, associata a un cliente, a una sede e agli ordini interessati;
* `technical`, associata a un'azione tecnica e, quando richiesto, a una posizione.

Conserva sequenza pianificata ed effettiva, luogo, orari, note dell'autista, motivo dell'eccezione e stato:

```text
planned → in_progress → done
                    ↘ skipped
                    ↘ cancelled
```

La conclusione di una tappa cliente consolida i movimenti eseguiti in quella tappa. Una tappa saltata o annullata rimane nello storico ma non genera un movimento consuntivo.

### **4.5 Movimento logistico**

Il movimento è la singola azione logistica esterna utilizzata per il consuntivo dei volumi.

| Codice logico | Etichetta | Significato |
| ----- | ----- | ----- |
| `pickup_own_fleet` | Ritiro | Metallica ritira materiale presso un cliente con la propria flotta. |
| `delivery_own_fleet` | Consegna | Metallica trasferisce materiale stoccato a una fonderia o a un impianto. |
| `outbound_external_operator` | Ritiro da Metallica | Un cliente od operatore esterno ritira materiale presso Metallica. |
| `inbound_autonomous_customer` | Conferimento autonomo cliente | Un cliente porta materiale a Metallica con un proprio mezzo. |
| `pickup_third_party` | Ritiro eseguito da terzista | Un terzista ritira presso un cliente per conto di Metallica. |

`MOVIMENTI` è il totale delle cinque categorie e non costituisce una categoria autonoma. La precedente voce aggregata `CLIENTI` è sostituita dalle tipologie `inbound_autonomous_customer` e `pickup_third_party`.

Ogni movimento registra almeno:

* tipo;
* data e ora pianificata, quando disponibili;
* data e ora effettiva;
* stato;
* origine e destinazione;
* soggetto esecutore e ruolo assunto;
* cliente e sede, quando pertinenti;
* ordine, viaggio e tappa, quando pertinenti;
* mezzo e rimorchio, quando pertinenti;
* origine del dato;
* note e documenti collegati;
* autore e audit.

Gli stati sono:

```text
pianificato → eseguito
           ↘ annullato
```

Solo i movimenti `eseguiti` confluiscono nei consuntivi. Lo stato di attribuzione dell'esecutore è un'informazione distinta dallo stato operativo.

### **4.6 Soggetto esecutore**

Il soggetto esecutore può essere:

* autista dipendente;
* autista occasionale o a contratto;
* socio o collaboratore autorizzato;
* terzista;
* cliente autonomo;
* operatore esterno;
* temporaneamente non identificato.

Una persona può essere abilitata alla guida senza assumere il ruolo applicativo permanente di autista. L'abilitazione ha validità temporale e consente di selezionare la persona come esecutore.

Piero è gestito come autista occasionale reale. Riceve esclusivamente i movimenti effettivamente eseguiti. Un esecutore non noto produce una registrazione `non attribuita` con codice motivo, nota, qualità del dato e coda di riconciliazione.

### **4.7 Cassone, censimento e allocazione**

Il cassone di viaggio identifica l'unità fisica collocata su motrice o rimorchio. Per ogni viaggio il sistema mantiene:

* composizione pianificata;
* censimento effettivo per riga materiale;
* numero di contenitori;
* peso totale o stimato;
* allocazioni tra cassoni;
* eccezioni motivate;
* fonte dell'informazione.

La somma delle allocazioni deve corrispondere al censimento effettivo oppure presentare eccezioni esplicite. Il sistema non conferma una baseline incoerente.

### **4.8 Dispatch e istruzioni di scarico**

Il dispatch rappresenta la decisione operativa su dove, come e in quale sequenza scaricare i cassoni e i materiali.

Per ogni allocazione registra:

* magazzino previsto;
* magazzino effettivo assegnato;
* sequenza di scarico;
* modalità `unload` o `drop_only`;
* scarico semplice o doppio;
* eventuale esigenza di trasbordo;
* motivazione delle difformità.

Gli stati del dispatch sono:

```text
pending → in_progress → managed
                    ↘ on_hold → in_progress
                              ↘ managed
```

Ogni conferma genera una baseline versionata. Le modifiche successive incrementano la versione e rimangono confrontabili con il piano precedente.

### **4.9 Materiale e lavorazioni**

La riga materiale conserva il collegamento con ordine, cliente, contenitore, viaggio, movimento di origine, cassone, scarico e lavorazioni.

Il ciclo ordinario è:

```text
creato → caricato → scaricato → lavorazione → classificato → chiuso
```

Il sistema registra pesatura, classificazione, selezione, catalogazione, immagini, non conformità, operatore, tempi ed eventuale scomposizione in componenti mediante esplosioni e ricette.

### **4.10 Messa a terra**

La messa a terra, detta anche appoggio, stoccaggio temporaneo o grounding, registra il deposito del cassone senza svuotamento.

Il sistema memorizza l'istante effettivo e calcola la scadenza a 24 ore per quel viaggio. La dashboard evidenzia cassoni prossimi alla scadenza, scaduti e trasferiti. La messa a terra è tracciata ma non incrementa il KPI Movimenti.

### **4.11 Trasbordo**

Il trasbordo trasferisce successivamente materiale dal magazzino effettivo a quello di destinazione.

Il magazzino registra l'esigenza operativa; la Logistica crea la proposta formale, la approva, la pianifica e la collega al viaggio di trasferimento. La decisione tra gestione locale e trasbordo è assunta dalla Logistica previo accordo con il capo magazzino.

Gli stati sono:

```text
proposed → approved → planned → in_progress → completed
         ↘ cancelled
                    ↘ cancelled
```

Il trasbordo mantiene origine, destinazione, materiale, contenitori, peso stimato, approvatore e viaggio pianificato. È un'attività operativa tracciata, ma non produce un movimento statistico.

## **5. Flussi implementati**

### **5.1 Ritiro con flotta Metallica**

1. La Logistica crea l'ordine e inserisce righe materiale e vuoti a rendere.
2. Il sistema valida i dati minimi e porta l'ordine a `pronto`.
3. La Logistica crea il viaggio, assegna autista, mezzo, tappe e ordini.
4. L'ordine passa a `pianificato` e viene costruito il piano di carico.
5. L'autista esegue le tappe e registra inizio, esito, variazioni e note.
6. La conclusione del ritiro crea o consolida un movimento `pickup_own_fleet`.
7. Autista e Logistica consolidano censimento e disposizione effettiva del carico.
8. Logistica e capo magazzino valutano capacità e vincoli.
9. La Logistica configura e conferma il dispatch.
10. Il magazzino registra scarico o messa a terra e prende in carico i materiali.
11. Pesatura, classificazione, selezione e catalogazione completano il ciclo dei materiali.
12. Viaggio, ordine e materiali si chiudono quando sono soddisfatte le rispettive condizioni, indipendentemente dagli altri cicli ancora aperti.

Un viaggio con tre ritiri completati produce un viaggio e tre movimenti. Uno scarico successivo non produce un quarto movimento.

### **5.2 Consegna a fonderia o impianto**

La Logistica pianifica l'uscita di materiale stoccato, associa destinazione, mezzo, esecutore e materiale, quindi collega l'operazione a un viaggio della flotta.

L'arrivo e il conferimento effettivo generano un movimento `delivery_own_fleet`. Il movimento conserva il collegamento al materiale di magazzino e ai documenti di trasporto. Eventuali tappe tecniche non incrementano il conteggio.

### **5.3 Ritiro presso Metallica da parte di un operatore esterno**

La Logistica o il magazzino autorizzato registra il soggetto esterno, il materiale ritirato, l'origine Metallica, la destinazione, il mezzo quando noto e l'istante effettivo.

La conferma dell'uscita genera un movimento `outbound_external_operator`. Non viene creato un viaggio della flotta Metallica.

### **5.4 Conferimento autonomo del cliente**

All'arrivo del cliente vengono registrati soggetto, sede di provenienza, materiale, mezzo quando noto, data e ora. La presa in carico genera un movimento `inbound_autonomous_customer`.

Il movimento non richiede un autista Metallica né un viaggio della flotta. Le successive attività di pesatura e magazzino rimangono collegate ma non generano ulteriori movimenti.

### **5.5 Ritiro eseguito da un terzista**

La Logistica assegna a un terzista il ritiro per conto di Metallica, collegandolo a cliente, sede e ordine quando presenti. L'esito effettivo genera un movimento `pickup_third_party`.

Il terzista è il soggetto esecutore. Il movimento può esistere senza viaggio Metallica e non viene attribuito a un autista interno.

### **5.6 Scostamenti ed eccezioni**

Le variazioni rispetto al piano non vengono corrette retroattivamente sul dato originario. Il sistema registra:

* valore pianificato;
* valore effettivo;
* tipo e motivazione dello scostamento;
* autore;
* data e ora;
* eventuale decisione della Logistica.

Rientrano fra le eccezioni: cliente o sede variati, materiale aggiunto, quantità diversa, tappa saltata, guasto del mezzo, cambio di priorità, cambio dell'esecutore, impossibilità di accesso del rimorchio e scarico fuori destinazione.

## **6. Workspace applicativi**

### **6.1 Dashboard Logistica**

La dashboard presenta una vista operativa e una analitica.

La vista operativa mostra:

* ordini da completare o pianificare;
* viaggi programmati, attivi e da chiudere;
* tappe in corso ed eccezioni;
* dispatch da gestire o in attesa;
* messe a terra prossime alla scadenza;
* trasbordi aperti;
* movimenti non attribuiti o da riconciliare.

La vista analitica è descritta nel capitolo 9.

### **6.2 Workspace viaggio**

Riunisce anagrafica del viaggio, convoglio, autista, ordini, mappa, tappe, carico pianificato, censimento effettivo, movimenti prodotti, eventi e documenti.

La Logistica può confrontare pianificato ed effettivo senza perdere le versioni precedenti.

### **6.3 Interfaccia autista**

L'autista visualizza soltanto i viaggi pertinenti e dispone di un flusso ottimizzato per dispositivi mobili:

* avvio del viaggio;
* consultazione della sequenza;
* avvio e conclusione della tappa;
* registrazione dell'esito del ritiro;
* aggiornamento di contenitori e disposizione del carico;
* note e segnalazione di eccezioni;
* conferma della fase di ritorno.

### **6.4 Workspace dispatch**

La Logistica visualizza motrice e rimorchio, cassoni, materiali, destinazioni previste, censimento e capacità comunicata dai magazzini. Configura modalità e sequenze di scarico, registra le motivazioni e conferma la baseline.

### **6.5 Workspace magazzino**

Il magazzino visualizza gli arrivi previsti e le istruzioni confermate. Per ogni cassone e materiale registra:

* presa in carico;
* scarico o messa a terra;
* magazzino effettivo;
* peso lordo, tara e netto;
* difformità, note e fotografie;
* classificazione e selezione;
* catalogazione;
* esigenza di trasbordo.

Il magazzino può segnalare il bisogno di trasferimento ma non proporre formalmente, approvare o schedulare il trasbordo.

## **7. Regole di consistenza**

La Web App applica almeno le seguenti invarianti:

1. Un ordine non diventa `pronto` se mancano i dati minimi.
2. Un ordine modificato dopo la pianificazione richiede rivalutazione del viaggio.
3. Un ritiro della flotta propria richiede ordine, viaggio, tappa, mezzo ed esecutore effettivo oppure una non attribuzione motivata.
4. Una tappa annullata o saltata non produce un movimento eseguito.
5. Ogni azione logistica eseguita produce al massimo un movimento, mediante chiavi e controlli anti-duplicazione.
6. Le allocazioni devono coprire il censimento effettivo oppure contenere eccezioni motivate.
7. Motrice e rimorchio mantengono prese in carico distinte anche quando scaricano nello stesso magazzino.
8. Un doppio scarico indica due magazzini differenti e una sequenza valida.
9. Uno scarico fuori destinazione richiede gestione locale, doppio scarico o esigenza di trasbordo.
10. La proposta, approvazione e pianificazione del trasbordo sono riservate alla Logistica.
11. La scadenza della messa a terra decorre dal relativo istante effettivo.
12. Attività di magazzino e trasbordi non incrementano il KPI Movimenti.
13. Un movimento non attribuito è incluso nel totale e separato dal dettaglio nominativo.
14. Nessun movimento viene attribuito a Piero o ad altro esecutore per convenzione.

## **8. Audit, versionamento e qualità del dato**

Gli eventi di dominio sono immutabili o comunque completamente auditabili. L'audit registra:

* entità e identificativo;
* tipo di evento;
* valori precedenti e successivi;
* autore;
* data e ora;
* origine dell'operazione;
* motivo, quando richiesto;
* viaggio, tappa o movimento correlati, quando pertinenti.

Sono auditati almeno creazione e modifica degli ordini, variazioni del piano, avanzamento delle tappe, conferma o annullamento dei movimenti, cambio dell'esecutore, censimento, allocazioni, baseline di dispatch, eccezioni di magazzino, pesature, trasbordi e chiusure.

Ogni dato statistico espone la propria qualità:

* `exact`: derivato da un evento effettivo completo;
* `reconciled`: corretto e validato successivamente;
* `estimated`: derivato da una stima esplicita;
* `incomplete`: mancano una o più informazioni;
* `legacy`: importato da fonti storiche con granularità ridotta.

Le attribuzioni storiche a Piero vengono conservate come dato originario e marcate `legacy` quando non è possibile identificare l'esecutore reale.

## **9. Dashboard analitica**

### **9.1 Competenza temporale**

I consuntivi utilizzano la data e ora effettiva del singolo movimento. Se temporaneamente assente, il sistema usa la data del viaggio o della registrazione evidenziando la minore qualità del dato.

I raggruppamenti disponibili sono:

* giorno;
* settimana ISO, da lunedì a domenica;
* mese;
* trimestre;
* quadrimestre;
* semestre;
* anno;
* intervallo personalizzato.

Mesi e periodi superiori sono calcolati dalla data di ciascun movimento. Una settimana a cavallo tra due mesi non viene assegnata interamente al mese del lunedì.

### **9.2 KPI**

La dashboard espone almeno:

* movimenti totali;
* ritiri della flotta propria;
* consegne;
* ritiri presso Metallica;
* conferimenti autonomi;
* ritiri dei terzisti;
* viaggi totali;
* movimenti medi per viaggio;
* movimenti attribuiti e non attribuiti;
* chilometri per mezzo, viaggio e autista, con qualità dell'associazione;
* messe a terra e rispetto delle 24 ore;
* trasbordi per stato;
* scostamenti tra pianificato ed effettivo.

### **9.3 Filtri e navigazione**

I dati sono filtrabili per periodo, categoria, esecutore, natura del soggetto, mezzo, cliente, sede, origine, destinazione, magazzino, stato e qualità del dato.

Ogni aggregato consente il drill-down:

```text
Periodo → categoria → soggetto → movimento → viaggio/tappa/ordine
```

Quando non esiste un viaggio, il dettaglio mostra comunque soggetto, data, luoghi, causale e documenti. Le viste possono essere salvate ed esportate in CSV o XLSX nel rispetto delle autorizzazioni.

### **9.4 Chilometri**

I chilometri provengono dal sistema di geolocalizzazione a fine giornata, quando il mezzo è fermo. Sono memorizzati separatamente dai movimenti e riconciliati per mezzo e data.

Il sistema non distribuisce automaticamente i chilometri tra movimenti o viaggi senza una regola approvata. Ogni KPI chilometrico indica se l'associazione è esatta, riconciliata o ripartita.

## **10. Chiusura dei cicli di vita**

La chiusura avviene per oggetto:

* il movimento è consuntivato quando è `eseguito`;
* la tappa è conclusa quando registra esito e orario;
* il viaggio è eseguito quando il trasporto originario è terminato;
* il dispatch è gestito quando tutte le decisioni di scarico sono state assunte;
* l'ordine è scaricato quando tutte le sue righe sono state scaricate o messe a terra;
* il materiale è chiuso quando le lavorazioni obbligatorie sono concluse;
* il trasbordo è chiuso all'arrivo nel magazzino di destinazione.

La messa a terra o un trasbordo pendente non riaprono il trasporto originario, ma rimangono attività visibili e assegnate. Nessuna chiusura elimina o nasconde attività residue.

## **11. Gestione degli errori e riconciliazione**

Le operazioni ripetute dall'interfaccia o da integrazioni sono idempotenti: non creano duplicati quando rappresentano lo stesso evento aziendale.

Le incongruenze vengono collocate in code operative dedicate, fra cui:

* movimento senza esecutore;
* censimento non coperto dalle allocazioni;
* ordine modificato dopo la pianificazione;
* movimento privo di data effettiva;
* scarico fuori destinazione senza decisione;
* messa a terra scaduta;
* trasbordo senza viaggio pianificato;
* chilometraggio non riconciliato.

La correzione non cancella l'informazione precedente: genera un evento di audit e aggiorna lo stato di qualità del dato.

## **12. Selezione richiesta dalla Logistica**

La Logistica può richiedere la selezione nel magazzino di scarico anche quando la destinazione finale è un altro magazzino. La richiesta può riguardare solo una parte dei contenitori.

Il sistema registra richiedente, quantità interessata, lavorazione richiesta, magazzino esecutore ed esito. Il materiale può essere successivamente trasbordato già selezionato e catalogato. La richiesta non trasferisce al magazzino la competenza sulla decisione o pianificazione del trasbordo.

## **13. Criteri funzionali di accettazione**

| Scenario | Risultato atteso |
| ----- | ----- |
| Viaggio Metallica con tre ritiri | Un viaggio, tre movimenti `pickup_own_fleet`. |
| Tappa cliente annullata | Tappa auditata, nessun movimento eseguito. |
| Consegna a fonderia | Un viaggio e un movimento `delivery_own_fleet`. |
| Cliente che conferisce con mezzo proprio | Un movimento `inbound_autonomous_customer`, nessun autista Metallica. |
| Terzista che ritira per Metallica | Un movimento `pickup_third_party` attribuito al terzista. |
| Operatore che ritira plastica presso Metallica | Un movimento `outbound_external_operator`. |
| Scarico del viaggio in magazzino | Attività tracciata, nessun nuovo movimento. |
| Trasbordo tra magazzini | Trasbordo e viaggio tracciati, nessun nuovo movimento statistico. |
| Socio alla guida per un'urgenza | Movimento attribuito al socio autorizzato. |
| Esecutore non noto | Movimento incluso nei totali e mostrato tra i non attribuiti con motivazione. |
| Piero esegue realmente un ritiro | Movimento attribuito a Piero come autista occasionale. |
| Movimento non eseguito attribuito storicamente a Piero | Dato storico conservato e marcato con qualità ridotta; nessuna nuova attribuzione fittizia. |
| Settimana a cavallo tra due mesi | Ogni movimento confluisce nel mese della propria data effettiva. |
| Messa a terra | Scadenza calcolata a 24 ore dall'istante effettivo e nessun incremento dei movimenti. |
| Correzione di un consuntivo | Valore aggiornato, precedente conservato nell'audit. |

## **14. Glossario applicativo**

| Termine | Definizione |
| ----- | ----- |
| Ordine | Richiesta di ritiro presso una sede cliente. |
| Viaggio | Aggregato operativo della flotta composto da mezzo, esecutore, tappe e carico. |
| Tappa | Punto ordinato cliente o tecnico appartenente a un viaggio. |
| Movimento | Singola azione logistica esterna conteggiabile. |
| Cassone di viaggio | Unità fisica di carico associata a motrice o rimorchio. |
| Censimento | Rilevazione della composizione effettiva del carico. |
| Allocazione | Quota di una riga materiale assegnata a un cassone. |
| Dispatch | Processo decisionale e piano operativo dello scarico. |
| Messa a terra | Deposito temporaneo del cassone senza svuotamento. Sinonimi: appoggio, stoccaggio temporaneo, grounding. |
| Trasbordo | Trasferimento successivo tra magazzini, escluso dal KPI Movimenti. |
| Non attribuito | Movimento valido il cui esecutore non è temporaneamente identificabile. |
| Baseline | Versione confermata di un piano, conservata per il confronto con modifiche successive. |

## **15. Documenti collegati**

* Analisi funzionale del processo: `Logistica E2E Analisi_v03.md`
* Piano della dashboard: `Piano sviluppo dashboard logistica.md`
* Diagramma di processo: `processo_scarico_magazzino_REFACTOR.drawio`
* Fonte storica dei volumi: `Dashboard viaggi autisti.xlsx`
