# Notifiche di esecuzione viaggio — piano 2026-11-17

Stato: implementazione locale e verifiche completate; migrazione applicata
nel database di sviluppo. Non ancora committata, pubblicata o rilasciata in QA. Branch `feature/journey-stop-notifications`, creato
su main `f8d2444`. Il piano sostituisce le precedenti proposte di tipo
`driver-stop`, `journey-stop-update` e `journey-update`.

## Tracciamento

- [GLE-139 — Master](https://metallicamarcon.youtrack.cloud/issue/GLE-139).
- [GLE-140 — Invio e destinatari](https://metallicamarcon.youtrack.cloud/issue/GLE-140).
- [GLE-141 — Riepilogo e variazioni](https://metallicamarcon.youtrack.cloud/issue/GLE-141).
- [GLE-142 — Interfaccia e riferimenti](https://metallicamarcon.youtrack.cloud/issue/GLE-142).
- [GLE-143 — Paginazione, date e ciclo di vita](https://metallicamarcon.youtrack.cloud/issue/GLE-143).
- [GLE-144 — Interventi multipli con conferma unica](https://metallicamarcon.youtrack.cloud/issue/GLE-144).

Master e figlie In corso; da completare il collaudo QA. Tracciamento via MCP YouTrack autorizzato
nella sessione; creazione della figlia aggiuntiva motivata dal nuovo requisito
che richiede una conferma unica di modifiche attualmente salvate separatamente.

## Contratto concordato

Unico type stabile: `journey-execution-update`, tramite databaseType di Laravel,
indipendente dalla classe PHP e dal ruolo dell'autore. Famiglia comune per
filtri e futuro purge. Metadati separati per intervento, autore/ruolo, viaggio,
tappe, ordini, data dell'evento, revisione e versione del payload.

Qualsiasi modifica di esecuzione deve essere tracciata nel dominio. La notifica
riassume un intervento confermato, non ogni campo/chiamata tecnica. Aggiungere
una tappa, rimuoverne un'altra e cambiare le casse con una conferma produce
una sola notifica per destinatario, con tutte le variazioni. Nessun timer
arbitrario per decidere la fine di un intervento.

Conclusione, salto e riapertura sono interventi autonomi. Tappe tecniche
incluse. Riapertura e successiva conclusione producono notifiche distinte;
alla nuova conclusione ricalcolare il delta e distinguere ciò che è cambiato
rispetto al precedente esito. Un esito senza variazioni quantitative resta
un esito da notificare; un retry dello stesso intervento non duplica nulla.

## Destinatari selezionati dal backend

| Destinatario | Ricezione |
| --- | --- |
| Utenti ruolo logistic | Tutti gli interventi di esecuzione notificabili su tutti i viaggi |
| Autista assegnato | Solo interventi della logistica sul proprio viaggio in corso |
| Altri autisti | Nessuna notifica di quel viaggio |

Nessuna notifica all'autista delle sue stesse azioni. La selezione avviene
prima della creazione delle copie personali e dei conteggi; non è un filtro
solo frontend. Valutare assegnazione/stato consolidati al commit dell'intervento.
Nessuna estensione implicita ai destinatari manager/developer/admin con altro
ruolo. Eventuali interventi di questi ruoli autorizzati restano tracciati;
l'equiparazione degli autori di controllo alla logistica va esplicitata nella
matrice prima di ampliare la distribuzione verso l'autista.

## Riscontri iniziali prima dello sviluppo

- NotificationController carica tutte le notifiche personali con paginate(10),
  ordinate latest() dalla relazione Laravel. MarkAsRead valorizza read_at dopo
  autorizzazione; le lette restano nell'elenco e nel DB, escluse dal conteggio.
- La tabella contiene type e data JSON; nessun tag/flag di categoria dedicato.
- Workspace.executeCommand salva oggi ogni comando immediatamente. Non esistono
  ancora bozza logistica multipla e confine applicativo di intervento confermato.
- JourneyExecutionService usa transazione, lock, execution_revision e JourneyEvent.
  I no-op non registrano un evento nuovo. Le sue guardie vanno riutilizzate.
- L'API espone add_stop ma non un comando specifico di rimozione tappa esecutiva:
  servirà un annullamento tracciato, con vincoli sulle movimentazioni già eseguite.
- Skip può sganciare gli ordini sorgente dal viaggio: riferimenti e contenuto
  della notifica non devono dipendere soltanto dalle associazioni successive.
- Baseline e ExecutionProjection.original_plan/original_values sono disponibili.
  Il diff dell'ultimo comando non equivale al delta complessivo rispetto al piano.
- La campanella attuale riceve il conteggio tramite le risposte Inertia: non è
  ancora un sistema di avviso in tempo reale o un segnale sonoro.

## Piano di sviluppo

### 1. Contratto e confine di intervento — GLE-144 / GLE-140

Definire un identificativo di intervento e il payload versionato. Distinguere
comandi di dominio, intervento confermato e copia personale della notifica.
Una conferma senza cambiamenti non invia nulla; gli esiti di tappa, invece,
sono cambiamenti anche se le quantità rimangono identiche.

Progettare la bozza logistica con conferma/annulla espliciti. Non applicare
la regola un comando HTTP = una notifica ai form che salvano più campi separati.
Mantenere il flusso operativo autista: i salvataggi di quantità, pesi,
consegne, split e Caricato restano tracciati ma non inviano notifiche immediate. Azioni complete/skip/
reopen restano autonome; con bozza pendente occorre confermarla o annullarla.

### 2. Applicazione atomica e invio affidabile — GLE-144 / GLE-140

Applicare insieme le modifiche confermate tramite i servizi esistenti,
verificando la revisione iniziale. Mantenere eventi dettagliati e riferimenti
all'intervento. Conflitti o errori annullano l'intero intervento: niente effetti
parziali o notifiche di modifiche non salvate. Nuovi record della bozza devono
avere riferimenti risolti e validati dal server.

Persistenza transazionale e idempotente dell’intervento e delle copie personali
nel database, visibili soltanto dopo commit. Un errore di consegna annulla
anche il salvataggio; il client può riprovare con lo stesso identificativo. Congelare i destinatari coerenti con lo stato consolidato. Riutilizzare
le guardie di convoglio, disponibilità vuoti, assegnazione ordini e magazzino.

### 3. Delta e riepilogo — GLE-141

Preparare uno snapshot stabile all'intervento. Due confronti distinti:
prima/dopo intervento per descrivere il lavoro appena confermato, e piano
iniziale/effettivo per gli esiti. In riapertura/nuova conclusione distinguere
anche il precedente esito, senza mostrare vecchie differenze come nuove.

Includere cambiamenti a tappe, sequenza, materiali, CER e quantità pertinenti,
raggruppati per tappa/ordine. Conservare zero, assente e non rilevato. Evitare
rumore da formati e ID tecnici. Gestire aggiunte senza piano iniziale e tappe
tecniche prive di ordine. La proposta UI rimane sintesi dei delta più dettaglio
Pianificato/Effettivo espandibile, da rifinire con il responsabile prodotto.

### 4. Centro notifiche e collegamenti — GLE-142 / GLE-143

Pagina /notification conforme a AppPage/DaisyUI: autore, data, riepilogo,
collegamenti a viaggio e ordini coinvolti. Link verificati dalle normali policy;
per ordini solo esecutivi, aprire il contesto nel viaggio senza inventare un ID
ordine sorgente. Gestire riferimenti non più disponibili mantenendo il riepilogo.

Paginazione server e filtro per intervallo data, con filtri mantenuti nelle
pagine e confini temporali coerenti con Europe/Rome. Conservare stato letto
personale e compatibilità con eventuali notifiche generiche. Viste Tutte (default), Non lette e Lette; lettura soltanto tramite comando
esplicito. Le lette restano consultabili, senza archivio o eliminazione.

Aggiornamento leggero e autenticato del contatore mentre l'app è aperta,
sospeso quando non visibile, senza introdurre per default websocket o audio.
Il suono resta una decisione separata, soggetta ai consensi del browser.

### 5. Conservazione e verifiche — GLE-143 / tutte

Nessun auto-purge, job di cancellazione o soglia in settimane in questa fase.
Il tipo stabile permette una futura retention specifica. Le notifiche non sono
lo storico autorevole: conservare JourneyEvent e i timestamp di dominio.
Journey.updated_at non ricostruisce durate/frequenze, né viene aggiornato da
ogni DB::table update. Le riaperture cancellano la conclusione corrente:
ricostruire i cicli dagli eventi, non dalla sola riga corrente.

Test di accettazione:
- Aggiunta+rimozione+quantità, una conferma: una notifica per destinatario.
- Logistica vede tutti gli interventi; autista solo quelli di sua competenza;
  nessun invio o conteggio per autisti colleghi o proprie azioni.
- Complete, skip, reopen e nuova complete su tappa cliente/tecnica; delta invariato.
- Retry, no-op, rollback, conflitto concorrente e recupero invii falliti.
- Link multiordine, sorgenti mancanti, isolamento utente e autorizzazioni.
- Paginazione, intervallo date e stato letto; contatore coerente senza reload.
- Verifiche responsive nei due temi e regressione dell'esecuzione esistente.

## Sequenza di consegna

Prima contratto e interventi multipli, poi destinatari/invio e delta, infine
centro notifiche e contatore. Collaudo locale e QA prima di dichiarare completate
le issue. Nessun commit, push o deploy delle nuove funzionalità è stato eseguito.


## Implementazione locale verificata — 2026-11-17

- `JourneyExecutionUpdate` conserva identificativo, hash richiesta, operazioni,
  revisione, snapshot e payload. `JourneyEvent` conserva il dettaglio dei comandi
  con `intervention_id`: nessuna dipendenza dello storico dal futuro purge.
- `ExecutionInterventions` riesegue la bozza dentro una transazione di anteprima
  sempre annullata. Conferma atomica con revisione iniziale e riferimenti tipizzati
  per stop/ordine/materiale/consegna creati nella bozza, anche se gli ID cambiano.
- La prima modifica della logistica apre automaticamente l’intervento. Si può
  anche aprirlo esplicitamente. Conferma e annulla sono visibili; esiti di tappa,
  termine viaggio e note anagrafiche sede sono separati dalla bozza. Abbandono
  con modifiche pendenti richiede conferma nel browser; nessun draft persistente
  tra ricaricamenti o sessioni.
- `cancel_stop` annulla la tappa mantenendone storico e baseline e restituisce
  gli ordini alla pianificazione. Rifiuta movimentazioni già registrate.
- Invio solo database: copie personali nella stessa transazione del dominio,
  visibili al commit. Nessuna coda, outbox o dipendenza da worker per questo
  canale. Se in futuro si aggiungono email/push, occorrerà un outbox/after-commit
  dedicato: non eseguire trasporti esterni dentro questa transazione.
- Snapshot semantici prima/dopo, confronto con baseline e precedente esito;
  etichette CER/contenitori risolte dal catalogo, quantità zero conservate.
- Centro notifiche: 20 elementi/pagina, date Europe/Rome, tipologia e stato
  letto. Lettura esplicita e personale. Collegamenti al contesto esecutivo,
  anche per ordini senza sorgente o sganciati dopo un salto.
- Campanella con polling ogni 20 secondi, soltanto con documento visibile;
  aggiornamento al focus, richieste annullate alla dismissione e nessun overlay
  di caricamento per il polling. Badge 99+, nome accessibile con conteggio reale.
- Nessun audio, auto-purge o archivio separato. Nessuna notifica retroattiva.
  Avvio viaggio, preferenze della testata e modifiche anagrafiche delle note
  sede non generano notifiche di esecuzione: restano i rispettivi eventi/preferenze.
  I salvataggi espliciti dell’autista restano nello storico senza avvisi immediati;
  più modifiche della logistica sono raccolte fino alla conferma, senza timer.

Verifiche e limiti: [collaudo locale](../qa/2026-11-17-operational-notifications.md).


## Affinamento dopo prova locale sul viaggio 159 — 2026-11-17

Requisito confermato dal PO: evitare una notifica per ogni toggle Caricato.
Per l’autista notificare soltanto conclusione, salto e riapertura della tappa;
quantità, pesi, consegne e split vengono riepilogati all’esito rispetto al piano
iniziale. Caricato resta nello storico e non compare fra le variazioni notificate.
Il payload versione 2 indica `summary_basis=plan` per gli esiti driver e
`intervention` per gli interventi della logistica; motivo e note del salto sono
mostrati separatamente. Il riepilogo principale dell’esito contiene già il delta
cumulativo, senza richiedere di aprire un secondo confronto con il piano.

Lo split è confrontato con la distribuzione pianificata: in assenza di split
esplicito, il totale è attribuito al mezzo previsto, non rappresentato come 0/0.
Anche gli snapshot versione 1 dei precedenti esiti vengono interpretati così
in memoria per evitare falsi delta alla nuova conclusione, senza riscriverli.

La logistica continua a confermare una bozza: una conferma riuscita produce
un riepilogo prima/dopo per tutti i logistic e il driver del viaggio attivo;
nessun avviso durante l’anteprima o all’annullamento. Gli interventi driver
silenziosi mantengono ledger, revisioni ed eventi per audit e retry idempotenti.
Le notifiche già emesse durante il primo collaudo restano conservate: questa
modifica non elimina retroattivamente le sette notifiche osservate dal PO.
