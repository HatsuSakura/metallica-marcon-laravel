# Contesto persistente — Logistica E2E

Ultimo aggiornamento: 2026-10-16

## Scopo di questo handoff

Questo file conserva il contesto funzionale emerso durante la ricostruzione del processo logistico Metallica Marcon e la stesura della specifica del digital twin.

Alla ripresa del lavoro, leggere nell'ordine:

1. `docs/Logistica E2E Analisi_v03.md` — fonte dell'analisi funzionale del processo;
2. `docs/Logistica E2E Implementazione_v01.md` — baseline normativa del TO-BE applicativo;
3. `docs/Piano sviluppo dashboard logistica.md` — piano specifico per modello dati e dashboard;
4. questo file — decisioni consolidate, questioni aperte e prossimi passi.

Le versioni `Logistica E2E Analisi_v01.md` e `_v02.md` sono conservate esclusivamente come storico.

## Gerarchia documentale

### Analisi funzionale

`Logistica E2E Analisi_v03.md` ricostruisce il processo aziendale come se le interviste fossero appena terminate. Descrive AS-IS operativo, requisiti e copertura prevista della Web App.

### Specifica di implementazione

`Logistica E2E Implementazione_v01.md` compie l'operazione complementare: descrive il digital twin TO-BE come soluzione già completata.

È la baseline su cui progettare nuove funzionalità, anche quando il codice corrente non ha ancora raggiunto il target. Un fornitore deve:

* progettare sul target consolidato, non sui limiti dell'AS-IS applicativo;
* usare il codice corrente solo per gap analysis, compatibilità e migrazione;
* evitare soluzioni transitorie che diventino obsolete al completamento del target;
* rispettare entità, responsabilità, stati e invarianti del digital twin completo.

La specifica di implementazione comprende come già integrate sia le funzionalità originariamente previste in Fase 1 sia quelle emerse per la Fase 2.

## Priorità realizzativa

La priorità operativa rimane:

* **Fase 1:** ritiri effettuati dalla flotta Metallica Marcon, che rappresentano il volume principale;
* **Fase 2:** consegne, ritiri presso Metallica, conferimenti autonomi e ritiri dei terzisti, numericamente inferiori.

La separazione riguarda l'ordine di sviluppo, non l'architettura target. Il modello introdotto in Fase 1 non deve rendere obbligatori ordine, viaggio, autista Metallica o mezzo aziendale per tutte le future tipologie di movimento.

## Decisioni funzionali consolidate

### Viaggio e movimento

`Viaggio` e `Movimento` sono entità differenti:

* il viaggio organizza mezzo, autista, tappe e carico della flotta Metallica;
* il movimento rappresenta una singola azione logistica esterna conteggiabile;
* un viaggio può comprendere più movimenti;
* alcuni movimenti possono esistere senza un viaggio della flotta propria;
* il viaggio non incrementa autonomamente il KPI Movimenti.

### Tipologie di movimento

Le cinque tipologie target sono:

| Codice logico | Significato |
| --- | --- |
| `pickup_own_fleet` | Ritiro effettuato da Metallica presso un cliente con la propria flotta. |
| `delivery_own_fleet` | Consegna di materiale stoccato a fonderia o impianto. |
| `outbound_external_operator` | Ritiro di materiale presso Metallica da parte di un operatore esterno. |
| `inbound_autonomous_customer` | Conferimento presso Metallica eseguito autonomamente dal cliente. |
| `pickup_third_party` | Ritiro presso un cliente eseguito da un terzista per conto di Metallica. |

La colonna storica Excel `CLIENTI` mescola le ultime due fattispecie e non deve essere riprodotta nel modello target. `MOVIMENTI` è la somma delle cinque tipologie, non una tipologia autonoma.

### Attività escluse dal KPI Movimenti

Non producono un movimento statistico:

* viaggio in quanto tale;
* passaggi, scarichi e lavorazioni interne di magazzino;
* messe a terra;
* trasbordi.

Queste attività rimangono tracciate nei propri cicli di vita e dispongono di KPI separati.

### Esecutore effettivo

Il movimento conserva chi lo ha materialmente eseguito. Sono previsti:

* autista dipendente;
* autista occasionale o a contratto;
* socio o collaboratore autorizzato alla guida;
* terzista;
* cliente autonomo;
* operatore esterno;
* soggetto temporaneamente non identificato.

Piero è un autista occasionale reale. Gli vengono attribuiti soltanto i movimenti realmente eseguiti da lui. Non deve più essere usato come catch-all per soci, collaboratori, emergenze o movimenti di cui non è noto l'esecutore.

Un movimento senza esecutore certo rimane `non attribuito`, con motivo, nota, autore e indicatore di qualità. Rimane incluso nel totale generale e compare separatamente nel breakdown per esecutore.

Le attribuzioni storiche a Piero vengono conservate come dato originario; quando non sono verificabili sono marcate come dato legacy a qualità ridotta.

### Ordine, viaggio e movimenti esterni

L'ordine rappresenta la richiesta di ritiro presso una sede cliente ed è centrale nel flusso principale.

Non tutti i movimenti richiedono un ordine o un viaggio Metallica:

* un ritiro della flotta propria richiede normalmente ordine, viaggio e tappa;
* un ritiro terzista può riferirsi a un ordine senza produrre un viaggio Metallica;
* una consegna origina da materiale stoccato e da una disposizione di uscita;
* un conferimento autonomo o un ritiro presso Metallica può esistere senza ordine di ritiro e senza viaggio della flotta.

### Regole temporali e dashboard

La competenza statistica deriva dalla data e ora effettiva del singolo movimento.

I raggruppamenti richiesti sono giorno, settimana ISO, mese, trimestre, quadrimestre, semestre, anno e intervallo personalizzato.

Mesi e periodi superiori sono calcolati dalla data effettiva di ciascun movimento. Una settimana a cavallo di due mesi non viene assegnata interamente al mese del lunedì.

I chilometri provengono dal sistema di geolocalizzazione a fine giornata, quando il mezzo è fermo. Sono modellati separatamente dai movimenti e non vengono ripartiti automaticamente senza una regola concordata. Ogni KPI chilometrico deve indicare la qualità dell'associazione.

### Messa a terra

Messa a terra, appoggio, stoccaggio temporaneo e grounding sono sinonimi.

Il limite delle 24 ore decorre dalla data e ora effettiva di messa a terra relativa a quel viaggio.

### Trasbordo

Il magazzino può segnalare un'esigenza operativa, ma la proposta formale, l'approvazione e la pianificazione spettano esclusivamente alla Logistica.

La scelta tra trasbordo e gestione definitiva nel magazzino di scarico spetta attualmente alla Logistica previo accordo con il capo magazzino. La responsabilità del magazzino termina dopo corretta presa in carico, pesatura e stoccaggio; il governo del trasferimento passa alla Logistica.

Il trasbordo viene collegato a un viaggio successivo, ma non incrementa il KPI Movimenti.

### Selezione richiesta dalla Logistica

La selezione nel magazzino di scarico può essere richiesta soltanto dalla Logistica, può riguardare solo una parte dei contenitori e non impedisce un successivo trasbordo del materiale già selezionato e catalogato.

Questo tema richiede ancora formalizzazione organizzativa dettagliata; vedere i punti aperti.

## Stato dei documenti prodotti

### `docs/Logistica E2E Analisi_v03.md`

Contiene:

* perimetro Fase 1 centrato sui ritiri;
* definizione AS-IS di tutte le categorie di movimento;
* separazione viaggio/movimento;
* collocazione delle categorie a basso volume in Fase 2;
* esecuzione del viaggio e registrazione dei ritiri;
* gestione dell'esecutore reale, di Piero e dei non attribuiti;
* stati, tracciabilità, audit e matrice applicativa aggiornati.

### `docs/Logistica E2E Implementazione_v01.md`

È scritto al presente e assume il target completo. Contiene:

* uso normativo della specifica;
* principi del digital twin;
* attori e autorizzazioni;
* modello applicativo e relazioni;
* cinque flussi di movimento;
* workspace per Logistica, autista, dispatch e magazzino;
* invarianti;
* audit, qualità del dato e riconciliazione;
* dashboard target;
* criteri funzionali di accettazione;
* glossario.

### `docs/Piano sviluppo dashboard logistica.md`

Contiene il piano specifico per sostituire e migliorare il foglio `Dashboard viaggi autisti.xlsx`, includendo modello movimento, soggetti esterni, non attribuiti, periodi calendariali, drill-down, migrazione e test.

I riferimenti interni sono già stati aggiornati ai nomi `Logistica E2E Analisi_v0x.md`.

## Questioni ancora aperte

Le seguenti questioni non devono essere considerate decise senza validazione della Logistica o del PO:

1. **Evento di consuntivazione del movimento:** definire l'evento esatto che rende eseguito e conteggiabile ciascun tipo di movimento. La specifica target usa una conferma esplicita dell'esito; va validato il momento operativo preciso.
2. **Fonderia vs impianto:** confermare se debbano essere sottotipi distinti della consegna o attributi della destinazione.
3. **Più esecutori:** definire come rappresentare un movimento materialmente eseguito da più persone.
4. **Chilometri e più viaggi:** definire l'associazione quando lo stesso mezzo compie più viaggi nella giornata.
5. **Dettaglio geolocalizzazione:** verificare granularità, formato e modalità di integrazione del software esterno.
6. **Storico attribuito a Piero:** determinare quale parte possa essere ricostruita e quale debba rimanere legacy.
7. **Settimana 34 del foglio storico:** verificare se i dati mancanti siano ricostruibili.
8. **Movimenti manuali o retrodatati:** definire autorizzazioni, motivazioni e regole di chiusura.
9. **Selezione richiesta dalla Logistica:** formalizzare autorizzazioni, stati, quantità, registrazione dell'esito e interazioni con il trasbordo.
10. **Gestione locale vs trasbordo:** formalizzare chi avvia il confronto, chi registra la decisione, motivazioni obbligatorie, possibilità di revisione e criteri di chiusura senza trasbordo.
11. **Ritiro e granularità:** confermare se in tutti i casi un movimento corrisponda esattamente a una tappa cliente completata o se una singola tappa possa produrre più movimenti distinti.
12. **Dati storici Excel:** definire strategia e periodo di riconciliazione prima della dismissione definitiva del foglio.

## Prossimi passi consigliati

1. Riesaminare `Logistica E2E Implementazione_v01.md` con Logistica, capo magazzino e PO.
2. Risolvere prioritariamente i punti aperti 1, 9, 10 e 11, perché influenzano stati e invarianti.
3. Produrre una matrice di tracciabilità `requisito di analisi → comportamento target → entità/API/UI/test`.
4. Eseguire una gap analysis tra specifica target e codebase corrente.
5. Derivare dal target epiche e incrementi implementativi, mantenendo la priorità Fase 1/Fase 2.
6. Allineare il piano dashboard alla stessa matrice di tracciabilità.
7. Definire dati di collaudo rappresentativi per tutti gli scenari del capitolo 13 della specifica.

## Nota sul repository

Al momento dell'ultimo controllo i documenti di questa analisi risultavano non tracciati in Git. Prima di considerarli patrimonio stabile del progetto, verificare lo stato del repository e includerli nel normale processo di versionamento quando approvati.

