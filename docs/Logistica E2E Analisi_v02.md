# **Logistica E2E: creazione ordine, viaggio carico/scarico e gestione magazzino materiali**

## **1\. Scopo**

Questo documento descrive il processo logistico end-to-end: dalla creazione dell'ordine di ritiro fino alla gestione dei materiali nei magazzini Metallica e ReLOAD, compresi viaggio, carico, scarico, lavorazioni e trasbordi.

Per ogni fase vengono affiancati:

* il processo operativo **AS-IS**, cioè il modo in cui l'azienda lavora;  
* il modo in cui la webapp Metallica Marcon dovrà supportare e rendere tracciabile quella fase.

L'obiettivo della webapp è costruire un **digital twin del processo aziendale**: attività, decisioni, responsabilità e stati rilevanti devono essere rappresentati in modo fedele e tracciabile. Funzioni applicative più dettagliate del processo corrente sono considerate miglioramenti compatibili solo quando non ne modificano responsabilità o regole.

Il riferimento grafico principale è `processo_scarico_magazzino_REFACTOR.drawio`.

## **2\. Perimetro**

Il processo inizia con la creazione dell'ordine e comprende:

1. creazione dell'ordine di ritiro;  
2. definizione dei materiali da ritirare e dei vuoti a rendere da consegnare;  
3. associazione di ordini e materiali al viaggio;  
4. disposizione del carico su motrice e rimorchio in base a volume e massa;  
5. censimento del carico effettivo;  
6. esecuzione del viaggio con aggiornamento autista-logistica;  
7. valutazione logistica-capo magazzino sulla capacità dei magazzini;  
8. decisione e pianificazione dello scarico;  
9. scarico o messa a terra dei cassoni;  
10. pesatura, classificazione, selezione e catalogazione dei materiali;  
11. stoccaggio e gestione degli eventuali trasbordi;  
12. chiusura e tracciabilità delle operazioni.

Il processo termina quando tutti i materiali sono stati gestiti nel magazzino di scarico oppure sono stati correttamente predisposti e pianificati per un trasbordo.   
Il processo, dalla creazione dell’ordine allo stoccaggio materiali, può interessare diverse giornate e non è sincrona, salvo le operazioni di aggiornamento autista-logistica-magazzino durante il viaggio.

## **3\. Concetti fondamentali**

### **3.1 Ordine**

L'**ordine** rappresenta una richiesta di ritiro presso una specifica sede di un cliente. È il punto di origine delle informazioni operative che accompagneranno i materiali durante viaggio, scarico e lavorazioni di magazzino.

Un ordine identifica almeno:

* cliente;  
* sede presso cui effettuare il ritiro;  
* data richiesta ed eventuale data prevista;  
* priorità o urgenza;  
* note e indicazioni operative;  
* una o più righe di materiale da ritirare;  
* eventuali contenitori vuoti a rendere da consegnare al cliente.

Ogni riga di materiale descrive una quantità omogenea e comprende almeno:

* codice CER;  
* descrizione del materiale;  
* tipologia del contenitore;  
* numero di contenitori;  
* peso dichiarato o stimato;  
* destinazione di magazzino prevista;  
* informazioni ADR, quando applicabili;  
* eventuali note o vincoli di movimentazione.

I vuoti a rendere costituiscono un flusso distinto dai materiali ritirati: devono essere caricati prima della partenza, consegnati presso la sede del cliente e contabilizzati separatamente. La loro quantità può incidere sulla capacità residua del mezzo e sulla pianificazione del viaggio.

L'ordine mantiene la propria identità anche quando:

* viene inserito in un viaggio insieme ad altri ordini;  
* le sue righe vengono distribuite tra motrice e rimorchio;  
* i materiali vengono scaricati in momenti o magazzini diversi;  
* una parte del materiale può richiedere un successivo trasbordo.

Per ogni riga deve quindi rimanere sempre ricostruibile la catena `ordine → cliente e sede → materiale → contenitore → viaggio → cassone → scarico → lavorazioni di magazzino`.

### **3.2 Viaggio**

Il viaggio raggruppa uno o più ordini, anche di clienti diversi. Ogni ordine comprende uno o più materiali.

La pianificazione del viaggio deve consentire di conoscere o stimare:

* mezzo e autista;  
* ordini e clienti coinvolti;  
* contenitori o cassoni previsti;  
* materiali trasportati;  
* peso del carico;  
* spazio a terra necessario;  
* disposizione del carico su motrice e rimorchio;  
* destinazione prevista dei materiali.

### **3.3 Cassone e materiale**

Il **cassone** è l'unità fisica di riferimento per le operazioni di trasporto, scarico o messa a terra.

A ogni cassone è associata una lista di materiali. Ogni materiale deve mantenere il legame con:

* ordine;  
* cliente;  
* contenitore o cassone;  
* posizione sul mezzo;  
* destinazione prevista;  
* luogo di scarico effettivo;  
* successive lavorazioni di magazzino.

### **3.4 Destinazione prevista e scarico effettivo**

I due concetti sono distinti:

* **destinazione prevista**: magazzino assegnato al materiale durante la preparazione dell'ordine o del viaggio, sulla base del codice CER e dell'esperienza operativa;  
* **magazzino di scarico effettivo**: magazzino nel quale il materiale viene realmente scaricato.

Quando non coincidono, il materiale può essere gestito localmente oppure stoccato in attesa di trasbordo.

### **3.5 Scarico e messa a terra**

* **Scarico**: il cassone viene svuotato e i materiali sono presi in carico dal magazzino.  
* **Messa a terra**: il cassone viene appoggiato senza essere svuotato.

Nel processo aziendale i termini **messa a terra**, **appoggio**, **stoccaggio temporaneo** e **grounding** sono sinonimi.

Un cassone messo a terra deve essere portato a un magazzino entro 24 ore. Il termine decorre dalla data e ora in cui viene registrata la messa a terra per quel viaggio.

### **3.6 Classificazione, selezione e catalogazione**

* **Classificazione**: riconoscimento della tipologia del materiale. Non richiede necessariamente una lavorazione manuale.  
* **Selezione**: intervento manuale sul materiale, ad esempio smontaggio o separazione delle componenti.  
* **Catalogazione**: registrazione strutturata delle tipologie e quantità risultanti dalla classificazione o dalla selezione.

Queste attività devono rimanere concettualmente distinte anche quando avvengono nella stessa fase operativa.

### **3.7 Trasbordo**

Il trasbordo è il trasferimento successivo di un materiale dal magazzino di scarico effettivo al magazzino di destinazione.

Il magazzino può segnalare l'esigenza operativa, ma:

* la proposta formale è di competenza della Logistica;  
* l'approvazione è di competenza della Logistica;  
* la pianificazione del nuovo trasferimento è di competenza della Logistica;  
* l'esecuzione viene collegata a un viaggio successivo.

La decisione di effettuare il trasbordo oppure gestire definitivamente il materiale nel magazzino di scarico spetta, allo stato attuale, alla Logistica previo accordo con il capo magazzino. Questa regola è ancora oggetto di approfondimento organizzativo.

### **3.8 Attuali “Movimenti” tracciati dal processo AS-IS**

\<TBW\>

## **4\. Ruoli e responsabilità**

| Ruolo | Responsabilità |
| ----- | ----- |
| Autista | Gestisce la composizione effettiva del carico, la disposizione su motrice e rimorchio, le informazioni operative sul mezzo e comunica i dati alla logistica prima di iniziare la fase di ritorno del viaggio. Esegue il viaggio e segue l'ordine di carico (“presa”) e scarico concordato. |
| Logistica | Configura e monitora il viaggio; censisce il carico effettivo; concorda la capacità di ricezione con il capo magazzino; stabilisce luogo, modalità e ordine di scarico; propone, approva e pianifica i trasbordi; chiude la gestione logistica. Gestisce le modifiche estemporanee che possono incorrere (richiesta di modifica da parte di un cliente, guasto automezzo, cambio di priorità). |
| Capo magazzino | Comunica disponibilità di spazio e capacità di lavorazione; concorda con la Logistica la ricezione e la gestione del carico; coordina le attività del magazzino. |
| Magazziniere | Prende in carico cassoni e materiali; registra scarico, pesatura e classificazione; segnala difformità ed esigenze operative; predispone i materiali per selezione o stoccaggio. |
| Operaio | Esegue la selezione manuale e le lavorazioni assegnate, registrandone tempi ed esito. |

Il magazzino è responsabile della corretta presa in carico, pesatura e messa in stoccaggio. Per il materiale da trasbordare, la responsabilità di approvazione e schedulazione passa alla Logistica.

## **5\. Processo operativo e soluzione applicativa**

### **5.1 Creazione dell'ordine di ritiro**

#### **Processo aziendale**

L'operatore seleziona il cliente e la sede presso cui eseguire il ritiro, concorda la richiesta e inserisce nell'ordine le righe di materiale previste.

Per ciascuna riga definisce codice CER, contenitore, quantità, peso dichiarato o stimato, destinazione di magazzino ed eventuali informazioni ADR. Registra inoltre i vuoti a rendere che dovranno essere consegnati durante il ritiro.

Prima che l'ordine possa essere pianificato, le informazioni devono essere sufficienti per:

* identificare senza ambiguità cliente e luogo del ritiro;  
* conoscere materiali e contenitori attesi;  
* verificare i requisiti documentali e ADR;  
* stimare peso, volume e mezzi necessari;  
* individuare il magazzino normalmente competente per ogni materiale;  
* conoscere il numero e la tipologia dei vuoti a rendere;  
* evidenziare urgenze, vincoli del sito e note operative.

L'ordine diventa quindi disponibile alla Logistica per l'inserimento in un viaggio. Eventuali modifiche successive devono rimanere tracciabili, soprattutto se intervengono dopo la pianificazione.

#### **Soluzione applicativa prevista**

La webapp dovrà guidare la creazione dell'ordine a partire dall'anagrafica cliente e da una delle sue sedi, evitando duplicazioni e incongruenze anagrafiche.

Per ogni ordine dovrà:

* acquisire date, priorità, referente e note operative;  
* consentire l'inserimento di più righe materiale;  
* validare codice CER, contenitore, quantità, peso e destinazione prevista;  
* raccogliere e validare le informazioni ADR necessarie;  
* registrare separatamente i vuoti a rendere da consegnare;  
* calcolare o rendere disponibili peso e ingombro complessivi per la pianificazione;  
* segnalare dati mancanti o incompatibili prima dell'assegnazione a un viaggio;  
* conservare lo storico delle modifiche e dell'utente che le ha effettuate;  
* mantenere il collegamento tra ogni riga e tutte le successive operazioni logistiche e di magazzino.  
* produrre le schede da stampare per essere apposte su casse e contenitori così da agevolare le operazioni in magazzino

L'output della fase è un ordine pianificabile, composto da righe materiali e vuoti a rendere chiaramente identificati, sul quale il viaggio potrà operare senza perdere la granularità originaria.

### **5.2 Configurazione del viaggio**

#### **Processo aziendale**

La Logistica crea il viaggio e associa:

* autista e mezzo;  
* motrice ed eventuale rimorchio;  
* ordini dei clienti;  
* materiali e contenitori;  
* destinazione prevista di ogni materiale;  
* collocazione prevista su motrice, rimorchio o spazio a riempimento.

La configurazione viene utilizzata per valutare peso e “spazio a terra” (spazio occupabile nel pianale/cassone richiesto.

#### **Soluzione applicativa prevista**

La webapp dovrà gestire viaggi, ordini, materiali, mezzo, autista, motrice, rimorchio e collocazione del carico. Le posizioni previste saranno rappresentate come motrice, rimorchio e riempimento.

Il software dovrà conservare la pianificazione iniziale separata dai dati effettivi rilevati durante il viaggio. Questa separazione permetterà di misurare gli scostamenti senza sovrascrivere il piano originario.

### **5.3 Censimento del carico effettivo**

#### **Processo aziendale**

L'autista comunica alla Logistica:

* numero effettivo dei contenitori;  
* disposizione dei materiali su motrice e rimorchio;  
* eventuali differenze rispetto al piano;  
* possibilità di accesso del rimorchio e sua configurazione scarrabile.

Questa necessità è non negoziabile poichè spesso la realtà del carico differisce rispetto a quanto concordato: materiali in più, numero diverso di contenitori, richiesta di aggiunta materiale estempoanea ecc. Attualmente la comunicazione avviene telefonicamente.

#### **Soluzione applicativa prevista**

Il workspace dovrà distinguere:

* carico pianificato;  
* censimento effettivo;  
* allocazioni effettive dei contenitori tra motrice e rimorchio;  
* eccezioni e relative motivazioni;  
* origine dell'informazione: telefono, interfaccia autista o interfaccia magazzino.

L'acquisizione potrà avvenire per registrazione telefonica da parte della Logistica oppure direttamente tramite applicazione o tablet dell'autista.

Il sistema dovrà impedire la conferma di una distribuzione nella quale i contenitori allocati non corrispondano al censimento effettivo, ovvero consentire all’autista di aggiornare la reale configurazione del carico.

### **5.4 Valutazione della capacità dei magazzini**

#### **Processo aziendale**

Prima dello scarico, Logistica e capo magazzino si confrontano telefonicamente. Il capo magazzino comunica:

* spazio disponibile;  
* capacità di smaltire e lavorare il carico in arrivo;  
* eventuali vincoli operativi;  
* possibilità di ricevere motrice e rimorchio.

La valutazione tiene conto di destinazione prevista, tipologia dei materiali, configurazione del carico e necessità di eventuali trasbordi.

#### **Soluzione applicativa prevista**

La webapp dovrà esporre alla Logistica i viaggi in corso, la composizione del convoglio, le destinazioni previste, le messe a terra e i trasbordi. Il confronto con il capo magazzino potrà continuare a svolgersi telefonicamente, ma il suo esito dovrà essere registrato nel sistema.

La disponibilità e la capacità operativa continueranno a essere valutate dalle persone responsabili; la webapp dovrà fornire le informazioni necessarie, registrare l'esito del confronto e rendere esplicita la motivazione della decisione.

### **5.5 Pianificazione dello scarico**

#### **Processo aziendale**

Al termine della valutazione, la Logistica stabilisce per motrice e rimorchio:

* magazzino di scarico effettivo;  
* sequenza di scarico;  
* scarico completo oppure messa a terra.

Le configurazioni principali sono:

* scarico completo presso Metallica;  
* scarico completo presso ReLOAD;  
* doppio scarico, prima Metallica e poi ReLOAD;  
* doppio scarico, prima ReLOAD e poi Metallica;  
* messa a terra di uno o più cassoni.

L'ordine deve rispettare la disposizione fisica del carico e i vincoli di accesso.

#### **Soluzione applicativa prevista**

La webapp dovrà registrare, separatamente per motrice e rimorchio:

* attivazione della parte del convoglio;  
* magazzino assegnato;  
* ordine di scarico 1 o 2;  
* modalità `unload` o `drop_only`;  
* scarico semplice o doppio.

Le istruzioni applicative dovranno distinguere scarico semplice, doppio scarico e solo appoggio. La motrice dovrà essere sempre configurata; il rimorchio sarà opzionale.

Ogni modifica della pianificazione dovrà incrementare la versione del piano e generare un evento di audit.

### **5.6 Conferma del piano di scarico**

#### **Processo aziendale**

La Logistica verifica che tutto il carico censito abbia una destinazione operativa e conferma il piano concordato.

#### **Soluzione applicativa prevista**

Prima della conferma, il sistema dovrà controllare almeno che:

* tutti i contenitori censiti siano allocati;  
* i cassoni utilizzati abbiano modalità e magazzino coerenti;  
* ogni allocazione abbia istruzioni di scarico;  
* gli scostamenti tra destinazione prevista e scarico effettivo abbiano una decisione;  
* un doppio scarico indichi un secondo magazzino diverso dal primo.

La conferma dovrà produrre una baseline tracciata. Le modifiche successive dovranno restare auditabili.

### **5.7 Arrivo e scarico al magazzino**

#### **Processo aziendale**

All'arrivo del mezzo, il capo magazzino coordina l'operazione. Il magazziniere prende in carico ogni cassone e verifica la lista dei materiali collegati a ordini e clienti.

In caso di scarico, il cassone viene svuotato. In caso di messa a terra, viene registrata data e ora dell'appoggio e inizia il termine di 24 ore per il trasferimento a un magazzino.

Eventuali differenze tra materiale previsto e materiale ricevuto devono essere segnalate.

#### **Soluzione applicativa prevista**

La webapp dovrà rappresentare:

* cassone di motrice o rimorchio;  
* magazzino previsto ed effettivo;  
* operatore e data/ora dello scarico;  
* materiali non trovati o aggiunti dal magazzino;  
* non conformità e note;  
* fotografie;  
* eventi ed eccezioni operative.

La webapp dovrà registrare l'istante della messa a terra, calcolare automaticamente la scadenza delle 24 ore ed evidenziare cassoni prossimi alla scadenza o in ritardo fino all'avvenuto trasferimento.

### **5.8 Pesatura**

#### **Processo aziendale**

Il magazziniere pesa il materiale e registra peso lordo, tara e netto. La pesatura mantiene il collegamento con materiale, ordine, cliente e cassone di provenienza.

#### **Soluzione applicativa prevista**

La webapp dovrà acquisire:

* peso dichiarato;  
* peso lordo;  
* tara;  
* peso netto;  
* operatore della pesatura;  
* data e ora della pesatura.

Il workspace logistico potrà inoltre stimare il peso delle quote allocate in proporzione ai contenitori censiti. La stima non dovrà sostituire la pesatura effettiva.

### **5.9 Classificazione, selezione e catalogazione**

#### **Processo aziendale**

Dopo la pesatura, il materiale viene classificato quando possibile. Se richiede lavorazione manuale, viene assegnato alla selezione.

La selezione può comportare smontaggio o divisione del materiale. L'esito produce materiali riconosciuti e catalogati. Attualmente parte delle informazioni può essere riportata attraverso un report cartaceo.

#### **Soluzione applicativa prevista**

La webapp dovrà registrare:

* necessità di selezione;  
* operatore;  
* data e ora;  
* durata;  
* classificazione e stato del materiale;  
* scomposizione del materiale in componenti catalogate tramite esplosioni e ricette;  
* peso e valorizzazione riconosciuti.

Queste informazioni dovranno confluire nello stesso flusso end-to-end iniziato dall'ordine e dal viaggio, senza richiedere ricostruzioni manuali tra moduli separati.

### **5.10 Materiale scaricato fuori destinazione**

#### **Processo aziendale**

Quando il magazzino effettivo non coincide con la destinazione prevista, Logistica e capo magazzino valutano due possibilità:

1. il materiale viene gestito definitivamente nel magazzino di scarico;  
2. il materiale viene pesato, stoccato e predisposto per un successivo trasbordo.

Il materiale mantiene sempre il riferimento alla destinazione originaria.

Il magazzino completa la presa in carico e lo stoccaggio. La proposta formale, l'approvazione e la pianificazione del trasbordo competono alla Logistica.

#### **Soluzione applicativa prevista**

Il software dovrà confrontare destinazione pianificata e magazzino effettivo. In caso di difformità richiederà una decisione:

* doppio scarico;  
* messa a terra/stoccaggio con esigenza di trasbordo.

La messa a terra fuori destinazione potrà generare automaticamente un'esigenza di trasbordo da sottoporre alla Logistica. Il trasbordo dovrà conservare origine, destinazione, materiale, numero di contenitori e peso stimato.

Il sistema dovrà consentire al magazzino di registrare l'esigenza o un'eccezione operativa, riservando alla Logistica la proposta formale, l'approvazione e la pianificazione del trasbordo.

### **5.11 Approvazione e pianificazione del trasbordo**

#### **Processo aziendale**

La Logistica:

1. valuta l'esigenza con il capo magazzino;  
2. decide se effettuare il trasbordo o autorizzare la gestione locale;  
3. approva il trasferimento;  
4. lo pianifica in un viaggio successivo;  
5. ne monitora l'esecuzione fino all'arrivo a destinazione.

#### **Soluzione applicativa prevista**

Il ciclo applicativo previsto per il trasbordo è:

`proposto → approvato → pianificato → in trasferimento → completato`

Il trasbordo potrà anche essere annullato prima del completamento. Dovrà essere collegato al viaggio che ne effettuerà il trasferimento.

La webapp dovrà supportare l'intero ciclo, impedire transizioni non valide e mantenere il collegamento tra esigenza originaria, approvazione e viaggio di trasferimento.

### **5.12 Chiusura**

#### **Processo aziendale**

La gestione del viaggio può essere chiusa quando:

* tutti i cassoni risultano scaricati o messi a terra;  
* tutti i materiali sono stati presi in carico;  
* le pesature disponibili sono registrate;  
* classificazioni e selezioni sono concluse o pianificate;  
* i materiali fuori destinazione sono gestiti localmente oppure associati a un trasbordo;  
* non rimangono decisioni operative prive di responsabile.

La messa a terra o l'attesa di trasbordo non impediscono di considerare completato il trasporto originario, purché le attività residue siano correttamente tracciate.

#### **Soluzione applicativa prevista**

Il dispatch applicativo dovrà utilizzare gli stati:

`Da gestire → In gestione → Gestito`

Sarà inoltre possibile porre temporaneamente il processo `In attesa` e successivamente riprenderlo.

La chiusura dovrà congelare l'audit del dispatch. Gli stati di viaggio, ordine, cassone, materiale e trasbordo rimarranno distinti perché descrivono cicli di vita diversi.

La webapp dovrà applicare criteri di chiusura espliciti e impedire la conclusione quando rimangono decisioni obbligatorie o attività prive di responsabile. La chiusura del trasporto originario non dovrà eliminare o nascondere le attività residue di magazzino e trasbordo.

## **6\. Regole decisionali**

| Condizione | Regola |
| ----- | ----- |
| Destinazione prevista e scarico effettivo coincidono | Il magazzino procede con la normale gestione del materiale. |
| Destinazione prevista e scarico effettivo non coincidono | È obbligatoria una decisione esplicita tra gestione locale, doppio scarico o stoccaggio per trasbordo. |
| È scelto il trasbordo | Il magazzino pesa e stocca; la Logistica propone formalmente, approva e pianifica il trasferimento. |
| Il cassone viene messo a terra | Deve essere trasferito a un magazzino entro 24 ore dalla data/ora della messa a terra. |
| Sono coinvolti entrambi i magazzini | La Logistica definisce la sequenza di scarico. |
| Motrice e rimorchio scaricano nello stesso magazzino | È sufficiente un solo magazzino effettivo, mantenendo distinta la presa in carico dei due cassoni. |
| Motrice e rimorchio scaricano in magazzini diversi | Devono essere indicate sequenze e istruzioni coerenti per entrambi. |
| Il rimorchio non può accedere | Il piano deve prevedere la gestione con la sola motrice o una diversa sequenza operativa. |
| Censimento e allocazioni non coincidono | Il piano non può essere confermato. |
| L'ordine non contiene i dati minimi richiesti | L'ordine non può diventare pronto né essere inserito in un viaggio. |
| Un ordine pianificato viene modificato | La modifica deve essere tracciata e la compatibilità del viaggio deve essere rivalutata. |

## **7\. Stati del digital twin**

### **7.1 Ordine**

`creato → pronto → pianificato → eseguito → scaricato → chiuso`

* **Creato**: l'ordine è in compilazione e può essere incompleto.  
* **Pronto**: contiene le informazioni necessarie per la pianificazione logistica.  
* **Pianificato**: è stato assegnato a un viaggio.  
* **Eseguito**: il ritiro presso il cliente è stato effettuato.  
* **Scaricato**: tutti i materiali dell'ordine sono stati scaricati o messi a terra; eventuali trasbordi restano attività successive tracciate.  
* **Chiuso**: tutte le righe materiale hanno completato le lavorazioni richieste e non rimangono attività obbligatorie aperte.

Lo stato dell'ordine deriva dall'avanzamento delle sue righe e non deve nascondere situazioni parziali: uno stesso ordine può avere materiali su cassoni differenti, scaricati in magazzini diversi o ancora coinvolti in lavorazioni e trasbordi.

### **7.2 Viaggio e cassone**

`creato → attivo → eseguito → chiuso`

Un viaggio o cassone può essere considerato eseguito anche quando il cassone è stato messo a terra o il materiale è in attesa di trasbordo, perché il trasporto originario è terminato.

### **7.3 Materiale**

Flusso ordinario:

`creato → caricato → scaricato → lavorazione → classificato → chiuso`

Flusso con trasbordo:

`scaricato → trasbordo → caricato`

Il secondo caricamento appartiene al viaggio utilizzato per il trasferimento verso il magazzino di destinazione.

### **7.4 Dispatch**

`pending → in_progress → managed`

Da `in_progress` è possibile passare a `on_hold`, quindi riprendere la gestione oppure concluderla.

### **7.5 Trasbordo**

`proposed → approved → planned → in_progress → completed`

Il trasbordo può passare a `cancelled` dagli stati non conclusivi previsti.

## **8\. Tracciabilità minima**

Per ogni materiale il digital twin deve consentire di ricostruire:

* viaggio originario;  
* ordine e cliente;  
* codice CER e descrizione;  
* contenitore o cassone;  
* quantità prevista ed effettiva;  
* posizione prevista ed effettiva su motrice o rimorchio;  
* destinazione prevista;  
* magazzino di scarico effettivo;  
* modalità e sequenza di scarico;  
* eventuale messa a terra e relativa data/ora;  
* operatori e tempi di scarico, pesatura e selezione;  
* pesi dichiarato, lordo, tara e netto;  
* classificazione e catalogazione;  
* non conformità, fotografie e note;  
* stato di stoccaggio;  
* eventuale decisione e ciclo di trasbordo;  
* utenti che hanno assunto o modificato le decisioni.

## **9\. Eventi e audit**

Le decisioni rilevanti devono essere registrate come eventi immutabili o comunque auditabili. Tra queste:

* creazione e modifica dell'ordine;  
* passaggio dell'ordine a pronto e assegnazione al viaggio;  
* modifica di un ordine già pianificato;  
* aggiornamento del piano;  
* modifica della composizione del convoglio;  
* conferma della baseline;  
* messa in attesa e ripresa;  
* registrazione di un'eccezione di magazzino;  
* proposta, approvazione o annullamento di un trasbordo;  
* chiusura del dispatch.

L'audit deve indicare almeno autore, data/ora, viaggio, entità interessate e contenuto della modifica.

## **10\. Matrice sintetica della soluzione applicativa**

| Fase del processo | Funzione richiesta alla webapp | Risultato atteso |
| ----- | ----- | ----- |
| Creazione ordine | Gestione cliente, sede, righe materiale, CER, contenitori, pesi, destinazioni, ADR e vuoti a rendere | Ordine completo, validato e pianificabile |
| Configurazione viaggio | Associazione di ordini, mezzo, autista, motrice e rimorchio | Piano di viaggio iniziale |
| Disposizione prevista del carico | Allocazione di materiali e contenitori tra motrice, rimorchio e riempimento | Configurazione prevista del convoglio |
| Censimento del carico effettivo | Acquisizione telefonica o diretta e confronto con il piano | Composizione effettiva e scostamenti tracciati |
| Valutazione dei magazzini | Quadro informativo condiviso e registrazione dell'esito del confronto | Decisione motivata sullo scarico |
| Pianificazione scarico/appoggio | Magazzino, sequenza e modalità per ciascun cassone | Istruzioni operative coerenti |
| Conferma della baseline | Controlli su quantità, allocazioni, magazzini e difformità | Piano confermato e versionato |
| Scarico o messa a terra | Presa in carico, operatori, tempi, eccezioni e termine di 24 ore | Cassoni e materiali localizzati e tracciati |
| Pesatura | Lordo, tara, netto, operatore e data/ora | Peso effettivo associato al materiale |
| Classificazione e selezione | Attività, operatori, tempi ed esiti | Materiale riconosciuto e lavorato |
| Catalogazione | Scomposizione in componenti e quantità risultanti | Inventario strutturato del materiale ottenuto |
| Materiale fuori destinazione | Confronto tra previsto ed effettivo e registrazione della decisione | Gestione locale o esigenza di trasbordo esplicita |
| Trasbordo | Proposta, approvazione, pianificazione, viaggio ed esecuzione | Trasferimento completamente tracciato |
| Audit | Versioni ed eventi con autore e data/ora | Ricostruzione completa delle decisioni |
| Chiusura | Verifica delle condizioni per ogni ciclo di vita | Conclusione senza attività orfane o non assegnate |

## **11\. Vincoli e punti di attenzione per l'implementazione**

### **11.1 Proposta di trasbordo**

Solo la Logistica potrà creare la proposta formale, approvarla e pianificarla. Il magazzino potrà registrare l'esigenza o un'eccezione operativa. Autorizzazioni, interfacce e regole applicative dovranno rispettare questa distinzione.

### **11.2 Termine della messa a terra**

Il vincolo delle 24 ore decorre dalla data e ora di messa a terra del viaggio. La webapp deve conservare questo istante e rendere riconoscibili scadenza, ritardo e avvenuto trasferimento.

### **11.3 Terminologia**

Messa a terra, appoggio, stoccaggio temporaneo e grounding indicano la stessa operazione aziendale. La terminologia mostrata agli utenti dovrebbe essere uniforme, pur potendo mantenere nomi tecnici interni.

### **11.4 Integrazione con la gestione di magazzino**

Workspace logistico, pesatura, selezione e catalogazione dovranno costituire un unico percorso applicativo. Il passaggio tra le fasi non dovrà interrompere la tracciabilità iniziata dall'ordine né richiedere riconciliazioni manuali tra moduli.

## **12\. Punto da approfondire: selezione forzata**

Il diagramma contempla la possibilità che la Logistica richieda la selezione nel magazzino di scarico anche per materiale destinato a un altro magazzino.

È stato chiarito preliminarmente che:

* l'iniziativa spetterebbe alla Logistica;  
* il materiale potrebbe essere successivamente trasbordato già selezionato e catalogato;  
* la richiesta potrebbe interessare solo una parte dei contenitori.

La regola non viene ancora considerata definitiva e non rientra nei requisiti consolidati di questo documento. Devono essere approfonditi con la Logistica responsabilità, autorizzazioni, quantità, stati e modalità operative. Fino a tale chiarimento, la selezione forzata non deve essere usata per valutare la conformità 1:1 del digital twin.

## **13\. Decisione organizzativa da approfondire**

La scelta finale tra trasbordo e gestione definitiva nel magazzino di scarico è attualmente attribuita alla Logistica previo accordo con il capo magazzino. La regola deve essere validata e formalizzata, specificando:

* chi avvia il confronto;  
* chi registra la decisione;  
* quali motivazioni sono obbligatorie;  
* se e come la decisione possa essere modificata;  
* quando termina la responsabilità del magazzino;  
* quali condizioni consentono la chiusura del materiale senza trasbordo.

## **14\. Documenti collegati**

* Diagramma principale: `processo_scarico_magazzino_REFACTOR.drawio`

