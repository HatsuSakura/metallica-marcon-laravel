# Piano di sviluppo — Dashboard logistica

## 1. Obiettivo

Realizzare nella webapp una dashboard logistica che sostituisca il foglio `Dashboard viaggi autisti.xlsx`, mantenendone le informazioni utili ma superandone le approssimazioni.

La soluzione dovrà distinguere chiaramente:

- i **viaggi**, introdotti dalla webapp come unità di pianificazione ed esecuzione;
- i **movimenti**, cioè le singole azioni operative conteggiate dalla Logistica;
- le attività interne di magazzino e i trasbordi, che devono essere tracciati ma non inclusi nel KPI storico dei movimenti;
- il soggetto che esegue ogni movimento, senza attribuzioni fittizie a un autista.

## 2. Definizioni funzionali consolidate

### 2.1 Viaggio

Il viaggio è l'unità organizzativa introdotta dalla webapp. Raggruppa una o più tappe e può comprendere più movimenti.

Un viaggio non deve essere contato come movimento. La dashboard deve mostrare separatamente:

- numero di viaggi;
- numero di movimenti;
- numero medio di movimenti per viaggio;
- viaggi con uno o più ritiri;
- viaggi con una o più consegne;
- viaggi eseguiti da flotta propria o da terzisti.

### 2.2 Movimento

Un movimento è una singola azione logistica esterna appartenente a una delle categorie consolidate di seguito.

| Codice proposto | Categoria mostrata | Definizione |
| --- | --- | --- |
| `pickup_own_fleet` | Ritiro | Ritiro di materiale effettuato da Metallica Marcon presso un cliente con un proprio mezzo. Un viaggio può comprendere più ritiri. |
| `delivery_own_fleet` | Consegna | Trasferimento, con mezzo Metallica Marcon, di materiale stoccato in magazzino verso una fonderia o un impianto di conferimento. |
| `outbound_external_operator` | Ritiro da Metallica | Operatore o cliente esterno che ritira presso Metallica Marcon materiali da gestire nel proprio circuito, per esempio plastica, batterie o altri materiali specialistici. Corrisponde alla colonna Excel “IN USCITA”. |
| `inbound_autonomous_customer` | Conferimento autonomo cliente | Cliente che porta materiale a Metallica Marcon con un proprio mezzo. È la prima componente della colonna Excel “CLIENTI”. |
| `pickup_third_party` | Ritiro eseguito da terzista | Terzista che effettua un ritiro presso un cliente per conto di Metallica Marcon. È la seconda componente della colonna Excel “CLIENTI”. |

Il totale dovrà essere calcolato come:

```text
Movimenti = Ritiri
           + Consegne
           + Ritiri da Metallica
           + Conferimenti autonomi cliente
           + Ritiri eseguiti da terzisti
```

La categoria Excel “CLIENTI” non dovrà essere riproposta come contenitore indistinto.

### 2.3 Attività escluse dal KPI Movimenti

Non devono incrementare il numero dei movimenti:

- passaggi o lavorazioni interne al magazzino;
- scarichi intermedi nei magazzini Metallica o ReLOAD;
- messe a terra;
- trasbordi;
- il viaggio in quanto tale.

Queste attività devono comunque essere tracciate e analizzabili in sezioni dedicate. L'esclusione dal KPI non equivale alla perdita dell'informazione.

### 2.4 Soggetto esecutore

Il movimento deve conservare il soggetto che lo ha materialmente eseguito. Il modello deve distinguere almeno:

- autista dipendente Metallica Marcon;
- autista occasionale o a contratto;
- socio o altro collaboratore autorizzato alla guida;
- terzista;
- cliente autonomo;
- operatore esterno che ritira presso Metallica Marcon;
- soggetto temporaneamente non identificato.

Piero è un autista occasionale reale e deve ricevere soltanto i movimenti che ha effettivamente eseguito. Non deve più fungere da categoria residuale.

Un movimento non attribuibile deve rimanere esplicitamente **non attribuito**, accompagnato da una motivazione. Non deve essere assegnato a un utente “ghost” o a un autista reale per far quadrare i totali.

## 3. Relazione tra viaggi e movimenti

La relazione non è uno a uno:

- un viaggio può contenere più movimenti;
- un movimento di flotta propria deve normalmente appartenere a un viaggio e a una sua tappa;
- un conferimento autonomo, un ritiro presso Metallica o un'attività di un terzista può esistere senza un viaggio della flotta propria;
- un viaggio può contenere tappe tecniche o attività di magazzino che non producono movimenti;
- un trasbordo può appartenere a un viaggio, ma non deve aumentare il KPI Movimenti.

La dashboard deve consentire il passaggio dal dato aggregato al dettaglio:

```text
Periodo → categoria → soggetto → movimento → viaggio/tappa/ordine
```

Quando il movimento non appartiene a un viaggio, il drill-down deve mostrare comunque soggetto, data, luogo, causale ed eventuali documenti collegati.

## 4. Modello dati proposto

### 4.1 Entità movimento

Introdurre un'entità dedicata, indicativamente `logistic_movements`, con almeno:

- identificativo;
- tipo di movimento;
- data e ora pianificata, se disponibile;
- data e ora effettiva, usata per le statistiche consuntive;
- stato: pianificato, eseguito, annullato;
- viaggio e tappa, entrambi opzionali secondo il tipo;
- ordine, cliente e sede, quando applicabili;
- luogo di origine e destinazione;
- soggetto esecutore;
- autista interno, quando applicabile;
- mezzo e rimorchio, quando applicabili;
- terzista, cliente autonomo o operatore esterno, quando applicabili;
- origine del dato;
- note;
- autore della registrazione e audit.

Il tipo deve essere un enum di dominio. Le combinazioni ammesse devono essere validate: per esempio un ritiro della flotta propria richiede viaggio, autista e mezzo, mentre un conferimento autonomo richiede il cliente esecutore ma non un autista Metallica Marcon.

### 4.2 Anagrafica dei soggetti esterni

Evitare campi testuali liberi come unica identificazione. Prevedere un'anagrafica o una relazione equivalente per:

- terzisti;
- clienti che effettuano conferimenti autonomi;
- operatori esterni che ritirano materiale;
- relativi autisti e mezzi, quando conosciuti.

Lo stesso soggetto può avere più ruoli, ma il ruolo assunto deve essere registrato sul singolo movimento.

### 4.3 Persone autorizzate alla guida

Il concetto di autista non deve coincidere necessariamente con il ruolo applicativo `driver`. Soci o collaboratori che guidano occasionalmente devono poter essere selezionati come esecutori autorizzati senza modificarne artificiosamente il ruolo principale.

Una possibile soluzione è una qualifica o relazione `driver_eligible`, con intervallo di validità e dati utili all'abilitazione. La scelta definitiva va coordinata con il modello utenti e lavoratori già presente.

### 4.4 Movimenti non attribuiti

Consentire temporaneamente un movimento senza esecutore soltanto con:

- flag esplicito di non attribuzione;
- codice motivo;
- nota;
- utente che lo ha registrato;
- indicatore di qualità del dato;
- coda di riconciliazione nella dashboard.

Il totale generale deve includere questi movimenti, mentre il breakdown per autista deve mostrarli in una riga separata “Non attribuiti”.

### 4.5 Chilometri

I chilometri provengono dal software di geolocalizzazione a fine giornata, quando il mezzo è fermo. Devono essere modellati separatamente dai movimenti.

Registrare almeno:

- mezzo;
- data;
- chilometri effettivi;
- sorgente e identificativo del dato esterno;
- autista o autisti associati, se ricostruibili;
- eventuale viaggio;
- stato di riconciliazione;
- correzioni manuali auditabili.

Non distribuire automaticamente i chilometri sui movimenti senza una regola concordata. I KPI `km per movimento`, `km per viaggio` e `km per autista` devono indicare quando il dato deriva da un'associazione esatta oppure da una ripartizione.

## 5. Regole temporali

La competenza statistica di un movimento è determinata dalla sua data e ora effettiva. In assenza del dato effettivo si può usare provvisoriamente la data del viaggio, segnalando la minore qualità del dato.

I raggruppamenti devono essere calendariali:

- giorno;
- settimana ISO, da lunedì a domenica;
- mese;
- trimestre;
- quadrimestre;
- semestre;
- anno;
- intervallo personalizzato.

I mesi, trimestri, quadrimestri e semestri devono essere calcolati dalla data del singolo movimento. Una settimana a cavallo di due mesi non deve essere assegnata interamente al mese del lunedì, come avviene nel foglio Excel.

Il fuso orario di riferimento è `Europe/Rome`.

## 6. KPI e dimensioni della dashboard

### 6.1 KPI principali

- movimenti totali;
- ritiri con flotta propria;
- consegne a fonderie o impianti;
- ritiri presso Metallica da operatori esterni;
- conferimenti autonomi dei clienti;
- ritiri eseguiti da terzisti;
- viaggi totali;
- movimenti medi per viaggio;
- chilometri totali;
- chilometri per viaggio;
- chilometri per movimento, con indicazione della qualità dell'associazione;
- movimenti attribuiti e non attribuiti;
- numero di soggetti esterni coinvolti.

Trasbordi, messe a terra e passaggi di magazzino dovranno avere KPI dedicati, visivamente separati dai movimenti.

### 6.2 Breakdown

Tutti i KPI compatibili devono poter essere suddivisi per:

- periodo;
- categoria di movimento;
- autista o soggetto esecutore;
- natura del soggetto: flotta propria, occasionale, terzista, cliente, operatore esterno;
- mezzo;
- cliente e sede;
- viaggio;
- attribuito/non attribuito;
- pianificato/effettivo/annullato.

### 6.3 Riconciliazione obbligatoria

La dashboard deve mostrare sempre:

```text
Totale movimenti
= movimenti flotta propria
+ movimenti terzisti
+ conferimenti autonomi
+ ritiri di operatori esterni
+ movimenti non attribuiti
```

Il breakdown deve riconciliarsi con il totale. In caso contrario la UI deve evidenziare lo scostamento e consentire il drill-down sui record che lo generano.

## 7. Esperienza utente

### 7.1 Filtri globali

- intervallo date;
- granularità;
- categoria di movimento;
- autista o soggetto;
- tipo di soggetto;
- mezzo;
- cliente e sede;
- attribuito/non attribuito;
- stato del movimento.

I filtri devono essere mantenuti nell'URL per rendere la vista condivisibile.

### 7.2 Visualizzazioni

- KPI card con valore, variazione e confronto con il periodo precedente;
- serie temporale dei movimenti per categoria;
- barre impilate per autista e tipo di movimento;
- confronto tra viaggi e movimenti;
- chilometri per mezzo e autista;
- tabella soggetti esterni;
- quota e andamento dei non attribuiti;
- pannello separato per trasbordi e attività di magazzino;
- tabella di dettaglio esportabile.

Ogni grafico deve permettere il drill-down ai record sorgente.

### 7.3 Migliorie rispetto all'Excel

- aggiornamento automatico dai dati operativi;
- periodi calendariali corretti;
- distinzione tra viaggio e movimento;
- separazione delle due componenti dell'attuale categoria “CLIENTI”;
- soggetti esterni identificati;
- assenza di attribuzioni fittizie a Piero;
- riconciliazione automatica dei totali;
- indicatori di qualità e completezza;
- confronto con periodi precedenti;
- filtri combinabili e viste salvate;
- esportazione CSV/XLSX;
- autorizzazioni per ruolo;
- audit e navigazione al dato sorgente.

## 8. Piano di implementazione

### Fase 0 — Validazione funzionale

1. Approvare il glossario movimento/viaggio.
2. Confermare i cinque tipi di movimento.
3. Definire per ogni tipo l'evento che ne determina l'esecuzione.
4. Confermare che trasbordi e passaggi di magazzino siano esclusi dal totale.
5. Definire la gestione dei movimenti senza viaggio.
6. Concordare la relazione tra chilometri giornalieri, mezzi, autisti e viaggi.

**Criterio di uscita:** esempi reali classificati senza ambiguità e formule KPI approvate dalla Logistica.

### Fase 1 — Modello di dominio e migration

1. Introdurre tipi e stati del movimento.
2. Creare l'entità movimento e le relazioni.
3. Modellare soggetti esterni e persone autorizzate alla guida.
4. Aggiungere la raccolta dei chilometri effettivi.
5. Integrare audit e autorizzazioni.
6. Predisporre indici su data, tipo, esecutore, viaggio e mezzo.

**Criterio di uscita:** schema in grado di rappresentare tutti gli esempi della call senza record fittizi.

### Fase 2 — Generazione dei movimenti dalla webapp

1. Generare o confermare i ritiri dalle tappe cliente dei viaggi.
2. Registrare le consegne a fonderia o impianto.
3. Fornire flussi dedicati per conferimenti autonomi, ritiri esterni e terzisti.
4. Evitare che trasbordi e attività interne producano movimenti statistici.
5. Consentire correzioni con audit, senza duplicare l'evento.

**Criterio di uscita:** ogni attività operativa produce esattamente il record statistico previsto.

### Fase 3 — Integrazione chilometri

1. Acquisire il report del sistema di geolocalizzazione.
2. Riconciliare data e mezzo.
3. Collegare autista e viaggio quando determinabili.
4. Gestire anomalie e giornate con più autisti o viaggi.
5. Rendere trasparente l'origine di ogni KPI chilometrico.

**Criterio di uscita:** totale giornaliero per mezzo riconciliato con la sorgente esterna.

### Fase 4 — Servizio analytics

1. Creare query e DTO indipendenti dalla UI.
2. Implementare granularità giorno, settimana, mese, trimestre, quadrimestre, semestre e anno.
3. Implementare filtri e breakdown.
4. Separare KPI movimenti, viaggi, chilometri, trasbordi e magazzino.
5. Restituire indicatori di qualità e righe non attribuite.
6. Aggiungere cache e indici dopo misurazioni reali.

**Criterio di uscita:** API riconciliata sui casi campione e stabile rispetto al cambio di granularità.

### Fase 5 — Dashboard web

1. Aggiungere una sezione “Volumi logistici” alla dashboard Logistica.
2. Implementare filtri, KPI, grafici e tabelle.
3. Aggiungere confronto con il periodo precedente.
4. Implementare drill-down e link a viaggio, tappa, ordine o movimento.
5. Aggiungere export e viste salvate.
6. Evidenziare record incompleti e scostamenti di riconciliazione.

**Criterio di uscita:** ogni valore visualizzato è spiegabile tramite il dettaglio sottostante.

### Fase 6 — Migrazione dello storico

1. Importare gli aggregati Excel con origine `legacy_aggregate`.
2. Separare i dati storici aggregati dai movimenti nativi della webapp.
3. Conservare Piero come dato storico originario, marcando il periodo come affetto da attribuzione residuale.
4. Non tentare una redistribuzione automatica senza fonti affidabili.
5. Fissare una data di passaggio al nuovo modello.
6. Confrontare settimane e mesi campione con l'Excel.

**Criterio di uscita:** continuità storica garantita senza presentare come preciso ciò che nello storico non lo è.

### Fase 7 — Test, rilascio e osservazione

1. Testare regole di classificazione e autorizzazione.
2. Testare confini temporali e fuso orario.
3. Testare riconciliazione dei totali e drill-down.
4. Testare movimenti esterni, non attribuiti e senza viaggio.
5. Testare che trasbordi e passaggi interni non entrino nei movimenti.
6. Rilasciare inizialmente in parallelo all'Excel.
7. Raccogliere gli scostamenti e farli validare dalla Logistica.
8. Dismettere l'Excel dopo un periodo di riconciliazione concordato.

## 9. Test funzionali minimi

| Scenario | Risultato atteso |
| --- | --- |
| Viaggio Metallica con tre ritiri | Un viaggio, tre movimenti di tipo ritiro |
| Consegna da magazzino a fonderia | Un viaggio e un movimento di tipo consegna |
| Cliente che conferisce con mezzo proprio | Un movimento di conferimento autonomo, nessun autista Metallica |
| Terzista che ritira per conto di Metallica | Un movimento terzista, collegato al cliente e al terzista |
| Operatore che ritira plastica presso Metallica | Un movimento “Ritiro da Metallica” |
| Scarico del viaggio in magazzino | Attività tracciata, nessun nuovo movimento statistico |
| Trasbordo tra magazzini | Trasbordo tracciato, nessun nuovo movimento statistico |
| Socio che guida un mezzo per un'urgenza | Movimento attribuito al socio come persona autorizzata, non a Piero |
| Esecutore non noto | Movimento nel totale e nella voce “Non attribuiti”, con motivazione |
| Settimana a cavallo tra due mesi | Ogni movimento confluisce nel mese della propria data effettiva |
| Movimento annullato | Escluso dai consuntivi, disponibile nel dettaglio audit |

## 10. Punti ancora da chiarire

- evento esatto che conclude e rende conteggiabile ciascun tipo di movimento;
- eventuale distinzione tra fonderia e impianto di conferimento;
- gestione di un movimento eseguito da più persone;
- associazione dei chilometri quando un mezzo compie più viaggi nella stessa giornata;
- identificazione del soggetto nei dati storici oggi attribuiti a Piero;
- possibilità di ricostruire la settimana 34 mancante;
- livello di dettaglio disponibile dal software di geolocalizzazione e modalità d'integrazione;
- regole per eventuali movimenti manuali o retrodatati.

## 11. Documenti di riferimento

- [`Dashboard viaggi autisti.xlsx`](Dashboard%20viaggi%20autisti.xlsx)
- [`Logistica E2E Analisi_v03.md`](Logistica%20E2E%20Analisi_v03.md)
- [`intervista-logistica-marcon.md`](intervista-logistica-marcon.md)
