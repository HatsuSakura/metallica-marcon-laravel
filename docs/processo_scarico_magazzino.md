# Processo di scarico e gestione dei materiali a magazzino

## 1. Scopo

Questo documento descrive il processo aziendale di pianificazione dello scarico, ricezione e gestione dei materiali nei magazzini Metallica e ReLOAD.

Per ogni fase vengono affiancati:

- il processo operativo **AS-IS**, cioè il modo in cui l'azienda lavora;
- la relativa copertura applicativa, già disponibile o prevista nella webapp Metallica Marcon.

L'obiettivo della webapp è costruire un **digital twin del processo aziendale**: attività, decisioni, responsabilità e stati rilevanti devono essere rappresentati in modo fedele e tracciabile. Funzioni applicative più dettagliate del processo corrente sono considerate miglioramenti compatibili solo quando non ne modificano responsabilità o regole.

Il riferimento grafico principale è [`processo_scarico_magazzino_REFACTOR.drawio`](processo_scarico_magazzino_REFACTOR.drawio).

## 2. Perimetro

Il processo inizia con la configurazione del viaggio e comprende:

1. associazione di ordini e materiali al viaggio;
2. disposizione del carico su motrice e rimorchio;
3. censimento del carico effettivo;
4. valutazione della capacità dei magazzini;
5. pianificazione dello scarico;
6. scarico o messa a terra dei cassoni;
7. pesatura, classificazione, selezione e catalogazione dei materiali;
8. stoccaggio e gestione degli eventuali trasbordi;
9. chiusura e tracciabilità delle operazioni.

Il processo termina quando tutti i materiali sono stati gestiti nel magazzino di scarico oppure sono stati correttamente predisposti e pianificati per un trasbordo.

## 3. Concetti fondamentali

### 3.1 Viaggio

Il viaggio raggruppa uno o più ordini, anche di clienti diversi. Ogni ordine comprende uno o più materiali.

La pianificazione del viaggio deve consentire di conoscere o stimare:

- mezzo e autista;
- ordini e clienti coinvolti;
- contenitori o cassoni previsti;
- materiali trasportati;
- peso del carico;
- spazio a terra necessario;
- disposizione del carico su motrice e rimorchio;
- destinazione prevista dei materiali.

### 3.2 Cassone e materiale

Il **cassone** è l'unità fisica di riferimento per le operazioni di trasporto, scarico o messa a terra.

A ogni cassone è associata una lista di materiali. Ogni materiale deve mantenere il legame con:

- ordine;
- cliente;
- contenitore o cassone;
- posizione sul mezzo;
- destinazione prevista;
- luogo di scarico effettivo;
- successive lavorazioni di magazzino.

### 3.3 Destinazione prevista e scarico effettivo

I due concetti sono distinti:

- **destinazione prevista**: magazzino assegnato al materiale durante la preparazione dell'ordine o del viaggio, sulla base del codice CER e dell'esperienza operativa;
- **magazzino di scarico effettivo**: magazzino nel quale il materiale viene realmente scaricato.

Quando non coincidono, il materiale può essere gestito localmente oppure stoccato in attesa di trasbordo.

### 3.4 Scarico e messa a terra

- **Scarico**: il cassone viene svuotato e i materiali sono presi in carico dal magazzino.
- **Messa a terra**: il cassone viene appoggiato senza essere svuotato.

Nel processo aziendale i termini **messa a terra**, **appoggio**, **stoccaggio temporaneo** e **grounding** sono sinonimi.

Un cassone messo a terra deve essere portato a un magazzino entro 24 ore. Il termine decorre dalla data e ora in cui viene registrata la messa a terra per quel viaggio.

### 3.5 Classificazione, selezione e catalogazione

- **Classificazione**: riconoscimento della tipologia del materiale. Non richiede necessariamente una lavorazione manuale.
- **Selezione**: intervento manuale sul materiale, ad esempio smontaggio o separazione delle componenti.
- **Catalogazione**: registrazione strutturata delle tipologie e quantità risultanti dalla classificazione o dalla selezione.

Queste attività devono rimanere concettualmente distinte anche quando avvengono nella stessa fase operativa.

### 3.6 Trasbordo

Il trasbordo è il trasferimento successivo di un materiale dal magazzino di scarico effettivo al magazzino di destinazione.

Il magazzino può segnalare l'esigenza operativa, ma:

- la proposta formale è di competenza della Logistica;
- l'approvazione è di competenza della Logistica;
- la pianificazione del nuovo trasferimento è di competenza della Logistica;
- l'esecuzione viene collegata a un viaggio successivo.

La decisione di effettuare il trasbordo oppure gestire definitivamente il materiale nel magazzino di scarico spetta, allo stato attuale, alla Logistica previo accordo con il capo magazzino. Questa regola è ancora oggetto di approfondimento organizzativo.

## 4. Ruoli e responsabilità

| Ruolo | Responsabilità |
| --- | --- |
| Autista | Verifica e comunica la composizione effettiva del carico, la disposizione su motrice e rimorchio e le informazioni operative sul mezzo. Esegue il viaggio e segue l'ordine di scarico concordato. |
| Logistica | Configura e monitora il viaggio; censisce il carico effettivo; concorda la capacità di ricezione con il capo magazzino; stabilisce luogo, modalità e ordine di scarico; propone, approva e pianifica i trasbordi; chiude la gestione logistica. |
| Capo magazzino | Comunica disponibilità di spazio e capacità di lavorazione; concorda con la Logistica la ricezione e la gestione del carico; coordina le attività del magazzino. |
| Magazziniere | Prende in carico cassoni e materiali; registra scarico, pesatura e classificazione; segnala difformità ed esigenze operative; predispone i materiali per selezione o stoccaggio. |
| Operaio | Esegue la selezione manuale e le lavorazioni assegnate, registrandone tempi ed esito. |

Il magazzino è responsabile della corretta presa in carico, pesatura e messa in stoccaggio. Per il materiale da trasbordare, la responsabilità di approvazione e schedulazione passa alla Logistica.

## 5. Processo operativo e copertura applicativa

### 5.1 Configurazione del viaggio

#### Processo aziendale

La Logistica crea il viaggio e associa:

- autista e mezzo;
- motrice ed eventuale rimorchio;
- ordini e clienti;
- materiali e contenitori;
- destinazione prevista di ogni materiale;
- collocazione prevista su motrice, rimorchio o spazio a riempimento.

La configurazione viene utilizzata per valutare peso e spazio a terra richiesto.

#### Copertura webapp

La webapp gestisce viaggi, ordini, materiali, mezzo, autista, motrice, rimorchio e collocazione del carico. Le posizioni previste sono rappresentate come motrice, rimorchio e riempimento.

Il software conserva una pianificazione iniziale separata dai dati effettivi rilevati durante il viaggio. Questa separazione è un dettaglio digitale coerente con il processo e permette di misurare gli scostamenti.

**Copertura:** implementata, con progressivi affinamenti del workspace logistico.

### 5.2 Censimento del carico effettivo

#### Processo aziendale

L'autista comunica alla Logistica:

- numero effettivo dei contenitori;
- disposizione dei materiali su motrice e rimorchio;
- eventuali differenze rispetto al piano;
- possibilità di accesso del rimorchio e sua configurazione scarrabile.

Attualmente la comunicazione può avvenire telefonicamente.

#### Copertura webapp

Il workspace distingue:

- carico pianificato;
- censimento effettivo;
- allocazioni effettive dei contenitori tra motrice e rimorchio;
- eccezioni e relative motivazioni;
- origine dell'informazione: telefono, interfaccia autista o interfaccia magazzino.

È prevista l'acquisizione diretta tramite applicazione o tablet dell'autista.

Il sistema impedisce di confermare una distribuzione nella quale i contenitori allocati non corrispondano al censimento effettivo.

**Copertura:** implementata nel workspace logistico; inserimento diretto dell'autista da completare.

### 5.3 Valutazione della capacità dei magazzini

#### Processo aziendale

Prima dello scarico, Logistica e capo magazzino si confrontano telefonicamente. Il capo magazzino comunica:

- spazio disponibile;
- capacità di smaltire e lavorare il carico in arrivo;
- eventuali vincoli operativi;
- possibilità di ricevere motrice e rimorchio.

La valutazione tiene conto di destinazione prevista, tipologia dei materiali, configurazione del carico e necessità di eventuali trasbordi.

#### Copertura webapp

La webapp espone alla Logistica i viaggi in corso, la composizione del convoglio, le destinazioni previste, le messe a terra e i trasbordi. La capacità del magazzino viene però ancora concordata principalmente fuori sistema.

**Copertura:** parziale. La decisione può essere registrata, mentre la disponibilità e la capacità del magazzino non costituiscono ancora un flusso applicativo completo.

### 5.4 Pianificazione dello scarico

#### Processo aziendale

Al termine della valutazione, la Logistica stabilisce per motrice e rimorchio:

- magazzino di scarico effettivo;
- sequenza di scarico;
- scarico completo oppure messa a terra.

Le configurazioni principali sono:

- scarico completo presso Metallica;
- scarico completo presso ReLOAD;
- doppio scarico, prima Metallica e poi ReLOAD;
- doppio scarico, prima ReLOAD e poi Metallica;
- messa a terra di uno o più cassoni.

L'ordine deve rispettare la disposizione fisica del carico e i vincoli di accesso. Se il rimorchio non può entrare, la pianificazione deve consentire l'accesso con la sola motrice.

#### Copertura webapp

La webapp registra, separatamente per motrice e rimorchio:

- attivazione della parte del convoglio;
- magazzino assegnato;
- ordine di scarico 1 o 2;
- modalità `unload` o `drop_only`;
- scarico semplice o doppio.

Le istruzioni applicative distinguono scarico semplice, doppio scarico e solo appoggio. La motrice deve essere sempre configurata; il rimorchio è opzionale.

Ogni modifica della pianificazione incrementa la versione del piano e genera un evento di audit.

**Copertura:** implementata nel modulo Logistic Dispatch.

### 5.5 Conferma del piano di scarico

#### Processo aziendale

La Logistica verifica che tutto il carico censito abbia una destinazione operativa e conferma il piano concordato.

#### Copertura webapp

Prima della conferma, il sistema controlla almeno che:

- tutti i contenitori censiti siano allocati;
- i cassoni utilizzati abbiano modalità e magazzino coerenti;
- ogni allocazione abbia istruzioni di scarico;
- gli scostamenti tra destinazione prevista e scarico effettivo abbiano una decisione;
- un doppio scarico indichi un secondo magazzino diverso dal primo.

La conferma produce una baseline tracciata. Le modifiche successive restano auditabili.

**Copertura:** implementata.

### 5.6 Arrivo e scarico al magazzino

#### Processo aziendale

All'arrivo del mezzo, il capo magazzino coordina l'operazione. Il magazziniere prende in carico ogni cassone e verifica la lista dei materiali collegati a ordini e clienti.

In caso di scarico, il cassone viene svuotato. In caso di messa a terra, viene registrata data e ora dell'appoggio e inizia il termine di 24 ore per il trasferimento a un magazzino.

Eventuali differenze tra materiale previsto e materiale ricevuto devono essere segnalate.

#### Copertura webapp

La webapp dispone di dati per rappresentare:

- cassone di motrice o rimorchio;
- magazzino previsto ed effettivo;
- operatore e data/ora dello scarico;
- materiali non trovati o aggiunti dal magazzino;
- non conformità e note;
- fotografie;
- eventi ed eccezioni operative.

La decorrenza e il controllo automatico del limite di 24 ore per la messa a terra devono essere verificati e completati.

**Copertura:** parziale; la parte finale della gestione di magazzino è in fase alpha.

### 5.7 Pesatura

#### Processo aziendale

Il magazziniere pesa il materiale e registra peso lordo, tara e netto. La pesatura mantiene il collegamento con materiale, ordine, cliente e cassone di provenienza.

#### Copertura webapp

Sono disponibili campi per:

- peso dichiarato;
- peso lordo;
- tara;
- peso netto;
- operatore della pesatura;
- data e ora della pesatura.

Il workspace logistico può inoltre stimare il peso delle quote allocate in proporzione ai contenitori censiti. La stima non sostituisce la pesatura effettiva.

**Copertura:** presente, da consolidare nel flusso unificato di magazzino.

### 5.8 Classificazione, selezione e catalogazione

#### Processo aziendale

Dopo la pesatura, il materiale viene classificato quando possibile. Se richiede lavorazione manuale, viene assegnato alla selezione.

La selezione può comportare smontaggio o divisione del materiale. L'esito produce materiali riconosciuti e catalogati. Attualmente parte delle informazioni può essere riportata attraverso un report cartaceo.

#### Copertura webapp

La webapp registra:

- necessità di selezione;
- operatore;
- data e ora;
- durata;
- classificazione e stato del materiale;
- scomposizione del materiale in componenti catalogate tramite esplosioni e ricette;
- peso e valorizzazione riconosciuti.

Questa area non è ancora completamente integrata con il nuovo workspace Logistic Dispatch.

**Copertura:** parziale/alpha.

### 5.9 Materiale scaricato fuori destinazione

#### Processo aziendale

Quando il magazzino effettivo non coincide con la destinazione prevista, Logistica e capo magazzino valutano due possibilità:

1. il materiale viene gestito definitivamente nel magazzino di scarico;
2. il materiale viene pesato, stoccato e predisposto per un successivo trasbordo.

Il materiale mantiene sempre il riferimento alla destinazione originaria.

Il magazzino completa la presa in carico e lo stoccaggio. La proposta formale, l'approvazione e la pianificazione del trasbordo competono alla Logistica.

#### Copertura webapp

Il software confronta destinazione pianificata e magazzino effettivo. In caso di difformità richiede una decisione:

- doppio scarico;
- messa a terra/stoccaggio con esigenza di trasbordo.

La messa a terra fuori destinazione può generare automaticamente una proposta di trasbordo. Il trasbordo conserva origine, destinazione, materiale, numero di contenitori e peso stimato.

È presente una discrepanza autorizzativa da correggere: il modello operativo stabilisce che il magazzino può segnalare l'esigenza, ma la proposta formale e le successive decisioni devono essere riservate alla Logistica.

**Copertura:** implementata per pianificazione e tracciamento iniziale; flusso completo da consolidare.

### 5.10 Approvazione e pianificazione del trasbordo

#### Processo aziendale

La Logistica:

1. valuta l'esigenza con il capo magazzino;
2. decide se effettuare il trasbordo o autorizzare la gestione locale;
3. approva il trasferimento;
4. lo pianifica in un viaggio successivo;
5. ne monitora l'esecuzione fino all'arrivo a destinazione.

#### Copertura webapp

Il ciclo applicativo previsto per il trasbordo è:

`proposto → approvato → pianificato → in trasferimento → completato`

Il trasbordo può anche essere annullato prima del completamento. È previsto il collegamento al viaggio che ne effettuerà il trasferimento.

La webapp implementa proposta, approvazione e annullamento; pianificazione, esecuzione e completamento devono essere verificati nel flusso end-to-end.

**Copertura:** parziale.

### 5.11 Chiusura

#### Processo aziendale

La gestione del viaggio può essere chiusa quando:

- tutti i cassoni risultano scaricati o messi a terra;
- tutti i materiali sono stati presi in carico;
- le pesature disponibili sono registrate;
- classificazioni e selezioni sono concluse o pianificate;
- i materiali fuori destinazione sono gestiti localmente oppure associati a un trasbordo;
- non rimangono decisioni operative prive di responsabile.

La messa a terra o l'attesa di trasbordo non impediscono di considerare completato il trasporto originario, purché le attività residue siano correttamente tracciate.

#### Copertura webapp

Il dispatch applicativo utilizza gli stati:

`Da gestire → In gestione → Gestito`

È inoltre possibile porre temporaneamente il processo `In attesa` e successivamente riprenderlo.

La chiusura congela l'audit del dispatch. Gli stati di viaggio, ordine, cassone, materiale e trasbordo rimangono distinti perché descrivono cicli di vita diversi.

**Copertura:** implementata per il dispatch; criteri completi di chiusura della gestione di magazzino da consolidare.

## 6. Regole decisionali

| Condizione | Regola |
| --- | --- |
| Destinazione prevista e scarico effettivo coincidono | Il magazzino procede con la normale gestione del materiale. |
| Destinazione prevista e scarico effettivo non coincidono | È obbligatoria una decisione esplicita tra gestione locale, doppio scarico o stoccaggio per trasbordo. |
| È scelto il trasbordo | Il magazzino pesa e stocca; la Logistica propone formalmente, approva e pianifica il trasferimento. |
| Il cassone viene messo a terra | Deve essere trasferito a un magazzino entro 24 ore dalla data/ora della messa a terra. |
| Sono coinvolti entrambi i magazzini | La Logistica definisce la sequenza di scarico. |
| Motrice e rimorchio scaricano nello stesso magazzino | È sufficiente un solo magazzino effettivo, mantenendo distinta la presa in carico dei due cassoni. |
| Motrice e rimorchio scaricano in magazzini diversi | Devono essere indicate sequenze e istruzioni coerenti per entrambi. |
| Il rimorchio non può accedere | Il piano deve prevedere la gestione con la sola motrice o una diversa sequenza operativa. |
| Censimento e allocazioni non coincidono | Il piano non può essere confermato. |

## 7. Stati del digital twin

### 7.1 Viaggio e cassone

`creato → attivo → eseguito → chiuso`

Un viaggio o cassone può essere considerato eseguito anche quando il cassone è stato messo a terra o il materiale è in attesa di trasbordo, perché il trasporto originario è terminato.

### 7.2 Materiale

Flusso ordinario:

`creato → caricato → scaricato → lavorazione → classificato → chiuso`

Flusso con trasbordo:

`scaricato → trasbordo → caricato`

Il secondo caricamento appartiene al viaggio utilizzato per il trasferimento verso il magazzino di destinazione.

### 7.3 Dispatch

`pending → in_progress → managed`

Da `in_progress` è possibile passare a `on_hold`, quindi riprendere la gestione oppure concluderla.

### 7.4 Trasbordo

`proposed → approved → planned → in_progress → completed`

Il trasbordo può passare a `cancelled` dagli stati non conclusivi previsti.

## 8. Tracciabilità minima

Per ogni materiale il digital twin deve consentire di ricostruire:

- viaggio originario;
- ordine e cliente;
- codice CER e descrizione;
- contenitore o cassone;
- quantità prevista ed effettiva;
- posizione prevista ed effettiva su motrice o rimorchio;
- destinazione prevista;
- magazzino di scarico effettivo;
- modalità e sequenza di scarico;
- eventuale messa a terra e relativa data/ora;
- operatori e tempi di scarico, pesatura e selezione;
- pesi dichiarato, lordo, tara e netto;
- classificazione e catalogazione;
- non conformità, fotografie e note;
- stato di stoccaggio;
- eventuale decisione e ciclo di trasbordo;
- utenti che hanno assunto o modificato le decisioni.

## 9. Eventi e audit

Le decisioni rilevanti devono essere registrate come eventi immutabili o comunque auditabili. Tra queste:

- aggiornamento del piano;
- modifica della composizione del convoglio;
- conferma della baseline;
- messa in attesa e ripresa;
- registrazione di un'eccezione di magazzino;
- proposta, approvazione o annullamento di un trasbordo;
- chiusura del dispatch.

L'audit deve indicare almeno autore, data/ora, viaggio, entità interessate e contenuto della modifica.

## 10. Matrice sintetica di copertura

| Fase | Copertura webapp | Valutazione |
| --- | --- | --- |
| Configurazione viaggio | Presente | Implementata |
| Disposizione prevista motrice/rimorchio | Presente | Implementata |
| Censimento del carico effettivo | Presente nel workspace | Implementata |
| Inserimento diretto da parte dell'autista | Previsto | Da completare |
| Valutazione capacità del magazzino | Prevalentemente telefonica | Parziale |
| Pianificazione scarico/appoggio | Presente | Implementata |
| Baseline e controlli di coerenza | Presenti | Implementata |
| Presa in carico e operazioni di magazzino | Presenti in moduli esistenti | Parziale/alpha |
| Pesatura | Presente | Da integrare nel flusso unificato |
| Classificazione, selezione e catalogazione | Presenti in parte | Parziale/alpha |
| Messa a terra e termine di 24 ore | Dato rappresentabile | Automazioni da verificare |
| Proposta e approvazione trasbordo | Presenti | Da allineare alle responsabilità definite |
| Schedulazione ed esecuzione trasbordo | Modello predisposto | Parziale |
| Audit delle decisioni logistiche | Presente | Implementata |
| Chiusura completa della gestione di magazzino | Presente in parte | Da consolidare |

## 11. Discrepanze da correggere o monitorare

### 11.1 Proposta di trasbordo

Il processo stabilisce che solo la Logistica possa creare la proposta formale, approvarla e pianificarla. Il magazzino può registrare l'esigenza o un'eccezione operativa. Autorizzazioni, interfacce e documentazione tecnica devono rispettare questa distinzione.

### 11.2 Termine della messa a terra

Il vincolo delle 24 ore decorre dalla data e ora di messa a terra del viaggio. La webapp deve conservare questo istante e rendere riconoscibili scadenza, ritardo e avvenuto trasferimento.

### 11.3 Terminologia

Messa a terra, appoggio, stoccaggio temporaneo e grounding indicano la stessa operazione aziendale. La terminologia mostrata agli utenti dovrebbe essere uniforme, pur potendo mantenere nomi tecnici interni.

### 11.4 Integrazione con la gestione di magazzino

Il nuovo workspace logistico e le funzionalità preesistenti di pesatura, selezione e catalogazione non costituiscono ancora un unico percorso applicativo completo. Il processo aziendale descritto in questo documento rimane la sorgente di verità da coprire.

## 12. Punto da approfondire: selezione forzata

Il diagramma contempla la possibilità che la Logistica richieda la selezione nel magazzino di scarico anche per materiale destinato a un altro magazzino.

È stato chiarito preliminarmente che:

- l'iniziativa spetterebbe alla Logistica;
- il materiale potrebbe essere successivamente trasbordato già selezionato e catalogato;
- la richiesta potrebbe interessare solo una parte dei contenitori.

La regola non viene ancora considerata definitiva e non rientra nei requisiti consolidati di questo documento. Devono essere approfonditi con la Logistica responsabilità, autorizzazioni, quantità, stati e modalità operative. Fino a tale chiarimento, la selezione forzata non deve essere usata per valutare la conformità 1:1 del digital twin.

## 13. Decisione organizzativa da approfondire

La scelta finale tra trasbordo e gestione definitiva nel magazzino di scarico è attualmente attribuita alla Logistica previo accordo con il capo magazzino. La regola deve essere validata e formalizzata, specificando:

- chi avvia il confronto;
- chi registra la decisione;
- quali motivazioni sono obbligatorie;
- se e come la decisione possa essere modificata;
- quando termina la responsabilità del magazzino;
- quali condizioni consentono la chiusura del materiale senza trasbordo.

## 14. Documenti collegati

- Diagramma principale: [`processo_scarico_magazzino_REFACTOR.drawio`](processo_scarico_magazzino_REFACTOR.drawio)
- Diagramma precedente: [`processo_scarico_magazzino.drawio`](processo_scarico_magazzino.drawio)
