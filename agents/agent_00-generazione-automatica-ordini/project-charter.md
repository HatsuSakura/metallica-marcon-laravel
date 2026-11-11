# Project Charter — Agente per la gestione delle mail della segreteria logistica

**Stato:** bozza v0.1 · **Data:** 2026-11-07 · **Fase corrente:** definizione architettura, prima del pilota

---

## 1. Contesto e scopo

Il cliente ha una segreteria logistica di **quattro dipendenti** che ricevono mail dai clienti con:

- richieste di **nuovi ordini**;
- **correzioni** a ordini già inviati;
- **integrazioni** a ordini già inviati;
- altro (richieste generiche, solleciti, comunicazioni non operative, rumore).

Le mail arrivano su **quattro caselle personali** (es. `Mario@dominio`, `Carla@dominio`, ...) su **Google Workspace**.

**Scopo del progetto:** costruire un agente che legga le mail in ingresso, le classifichi, ne interpreti il contenuto (riferimenti a ordini, righe, quantità, date, modifiche) e prepari l'azione corretta, lasciando alle segretarie solo i casi dubbi.

### Obiettivi

1. Ridurre il lavoro manuale di lettura, smistamento e trascrizione.
2. **Non perdere nessuna mail operativa** (priorità assoluta: recall > costo).
3. Rendere ogni decisione tracciabile e verificabile (audit).
4. Avere un sistema resiliente, che non si rompe in silenzio.

### Non obiettivi (per ora)

- Risposta automatica ai clienti senza revisione umana.
- Scrittura diretta nel gestionale senza bozza/conferma (da valutare solo dopo il pilota).
- Gestione di canali diversi dalla mail.

---

## 2. Vincoli e dati di partenza

| Voce | Valore |
|---|---|
| Piattaforma mail | Google Workspace (aziendale) |
| Caselle | 4, personali |
| Infrastruttura | VPS Linux con Docker |
| Orchestrazione ipotizzata | n8n |
| Dati trattati | Mail di clienti, allegati (probabilmente PDF/Excel), dati personali → **GDPR** |

---

## 3. Riflessioni fatte e decisioni prese

### 3.1 Valutazione dell'architettura iniziale (catch-all → classificatore → LLM)

L'idea è valida, con tre punti deboli emersi:

- **Due LLM in sequenza sono in parte ridondanti.** Classificare e interpretare si possono fare in una sola chiamata con output strutturato. Due stadi hanno senso solo se il primo è economico/veloce/locale e scarta il rumore.
- **Classificare la singola mail senza contesto sbaglia.** Correzioni e integrazioni hanno senso solo rispetto a un ordine esistente: servono thread, anagrafica cliente, ordini aperti e allegati.
- **Mancano componenti più importanti del modello:** deduplica, coda con stato, soglie di confidenza, revisione umana, audit log, scrittura idempotente.

**Decisione:** mantenere la struttura a due stadi, ma con regole precise (vedi 4).

### 3.2 Il primo stadio: modello decisionale (Jev)

Jev (TypeSafe AI, lanciato il 15 settembre 2026) è un modello "System One": restituisce decisioni tipizzate con probabilità calibrate, non testo generato. Tre primitive: **Choice**, **Score**, **Noul** (probabilità di un sì).

**A favore**
- Nessun addestramento né dati etichettati iniziali: le domande si definiscono via schema (risolve il cold start di un classificatore classico).
- Più domande in parallelo sulla stessa mail → gestisce categorie **non esclusive** (una mail può contenere un ordine e una correzione).
- Confidenza calibrata → utile per le soglie "automatico / revisione / scarto".

**Cautele**
- Benchmark prevalentemente del fornitore; accuratezza dichiarata inferiore ai migliori LLM frontier. Il vantaggio è costo/velocità/calibrazione, non accuratezza assoluta.
- Prodotto in early access, API e prezzi possono cambiare.
- **GDPR:** le mail andrebbero a un fornitore nuovo. Servono DPA, sede del trattamento, politica di retention prima di usare dati reali.
- Supporto dell'**italiano** non verificato esplicitamente → da testare nel pilota.
- Vede solo testo: gli ordini negli allegati vanno estratti prima.
- **Prompt injection:** le mail sono input non fidato; l'output tipizzato garantisce la forma, non la verità.
- Al volume previsto (centinaia di mail/giorno) il risparmio economico è trascurabile: il valore sta nella **confidenza calibrata**, non nel costo.

**Decisione:** usare Jev come **smistatore, non come decisore finale**, dietro un'interfaccia sostituibile, e validarlo con un pilota in shadow mode prima di metterlo in produzione.

### 3.3 Regola sulla propagazione dell'errore

Un errore del primo stadio (es. correzione classificata come "altro") farebbe scartare la mail in silenzio. Quindi:

- il primo stadio **scarta solo il rumore con confidenza molto alta**;
- tutto il resto passa all'LLM, che ricalcola il tipo con più contesto ed estrae i dati nella stessa chiamata;
- le mail scartate restano in Postgres e vengono **campionate periodicamente** (es. 5%) per misurare gli errori.

### 3.4 Polling vs push

Gmail supporta il push solo tramite **Google Cloud Pub/Sub** (`users.watch`), con: rinnovo del watch ogni 7 giorni, notifiche best-effort, necessità di `history.list`, gestione di `historyId` scaduti, endpoint pubblico (o subscription pull).

**Decisione:** **polling ogni minuto** nella v1. A quattro caselle di ordini la latenza di 60 secondi è irrilevante, le quote API Gmail sono ampiamente sufficienti, e il push aggiunge parti che possono rompersi in silenzio. Il livello di ingestione resta **sostituibile**: se servirà, si passa a Pub/Sub senza toccare il resto.

### 3.5 Accesso alle caselle (credenziali)

In n8n ogni casella è una **Credential** separata; il Gmail node non permette la scelta dinamica della credenziale con un'espressione, quindi di norma si hanno più trigger o un sotto-workflow per casella.

Con Google Workspace il metodo consigliato è un **service account con domain-wide delegation**:
- una sola chiave, nessun consenso per casella;
- una credenziale n8n per casella con l'email da impersonare;
- app interna: nessuna verifica Google degli scope "restricted", nessuna scadenza del refresh token (il problema dei 7 giorni in modalità "Testing" dell'OAuth non si pone).

**Rischio da gestire:** la delega a livello di dominio dà alla chiave potenzialmente accesso a **tutte** le caselle del dominio (non limitabile per utente lato Google). Mitigazioni in 5.

**Alternativa più pulita** (da proporre al cliente): regola di routing Workspace che inoltra una **copia** delle mail ordini a una **casella dedicata** all'agente → una sola casella monitorata, nessun accesso alle caselle personali. Dipende da come arrivano le mail (alias comune vs caselle personali dirette). *Domanda aperta.*

---

## 4. Architettura proposta

```
Gmail (4 caselle)
      │  polling 1 min (service account + delega)
      ▼
[1] Ingestione ──► Postgres (mail grezza, allegati, Message-ID univoco, stato)
      ▼
[2] Pre-filtro deterministico (regole: auto-reply, newsletter, mittenti noti)
      ▼
[3] Estrazione testo allegati (PDF/Excel → testo)
      ▼
[4] Triage Jev (domande Noul/Choice, confidenza calibrata)
      │      ├─ rumore sicuro (conf. molto alta) ──► archiviata, campionata
      │      └─ tutto il resto ──────────────────┐
      ▼                                           ▼
[5] LLM unico: classificazione definitiva + estrazione strutturata
    (con thread, anagrafica cliente, ordini aperti, testo allegati)
      ▼
[6] Validazione in codice (schema, cliente/articoli/quantità vs anagrafica)
      ▼
[7] Routing
      ├─ confidenza alta + validazione OK ──► bozza d'ordine / modifica nel gestionale
      └─ altrimenti ───────────────────────► coda di revisione umana (etichetta Gmail "da verificare")
      ▼
[8] Osservabilità: log di audit, error workflow, dead-letter, riconciliazione giornaliera
```

### Componenti

- **Orchestrazione:** n8n in **queue mode** (Postgres + Redis, worker separati), workflow suddivisi in sotto-workflow. Se la logica cresce, il cuore passa in codice (Python/FastAPI) e n8n resta per i trigger.
- **Stato:** Postgres come fonte di verità (non lo stato interno di n8n).
- **Decisione:** Jev dietro un'interfaccia nostra `classifica(mail) → probabilità`, sostituibile con un LLM piccolo o un classificatore.
- **Interpretazione:** un solo LLM con output JSON validato da schema.
- **Azione:** sempre prima come **bozza** o revisione umana; scrittura diretta solo dopo dati di pilota.

### Principi di progetto

1. **Recall prima del costo:** meglio una chiamata LLM in più che un ordine perso.
2. **Nessuno scarto silenzioso:** ogni mail ha uno stato e una ragione tracciata.
3. **Idempotenza:** la stessa mail processata due volte non crea due ordini.
4. **Umano nel ciclo:** ogni correzione delle segretarie diventa dato etichettato per migliorare soglie e (forse) un classificatore addestrato.
5. **Componenti sostituibili:** ingestione, triage, LLM, gestionale dietro interfacce.

---

## 5. Sicurezza e privacy

- Scope minimo: `gmail.readonly` (o `gmail.modify` solo se si applicano etichette).
- Chiave del service account in **Docker secret**, mai nel repository; accesso limitato; rotazione pianificata.
- `N8N_ENCRYPTION_KEY` in secret + **backup separato** (senza, le credenziali sono irrecuperabili).
- **Filtro in ingresso:** processare/salvare solo mail che passano un criterio (es. mittenti clienti noti, domini), scartando subito il resto senza persisterlo.
- Informare i dipendenti; verificare la base giuridica GDPR con il DPO; definire retention di mail e allegati.
- Trattare ogni mail come **input non fidato** (injection): il testo della mail non deve mai poter cambiare soglie, istruzioni di sistema o azioni autorizzate; il codice, non il modello, decide cosa è permesso.
- DPA e verifica della sede di trattamento per ogni fornitore AI (Jev e LLM).

---

## 6. Resilienza

- Error workflow n8n con notifica; retry con backoff; **dead-letter** in tabella.
- Salvataggio di `historyId`/Message-ID per ogni casella: nessun buco, nessun duplicato.
- **Riconciliazione giornaliera:** confronto tra mail in casella e mail in Postgres.
- Healthcheck su worker, Redis, Postgres; backup Postgres; alert se nessuna mail arriva per N ore in orario lavorativo.
- Se il primo stadio o il LLM sono indisponibili: le mail restano in stato `pending`, non vengono perse e la coda di revisione umana resta funzionante.

---

## 7. Rischi principali

| Rischio | Impatto | Mitigazione |
|---|---|---|
| Ordine o correzione scartati dal triage | Alto | Scarto solo con conf. altissima + campionamento + recall come metrica principale |
| Allegati non letti correttamente | Alto | Estrazione testo robusta; mail con allegati non estraibili → revisione umana |
| Jev immaturo / cambi di API o prezzo | Medio | Interfaccia sostituibile; pilota prima di impegnarsi |
| Dati personali a fornitore terzo | Alto | DPA, sede, retention; eventuale alternativa self-hosted |
| Prompt injection via mail | Medio-alto | Output validato, azioni decise da codice, bozze invece di scritture dirette |
| Chiave con delega sull'intero dominio | Alto | Scope minimo, secret, filtro in ingresso, o casella dedicata via routing |
| Scadenza silenziosa di credenziali/watch | Medio | Healthcheck, alert sul silenzio, riconciliazione |

---

## 8. Fasi

1. **Fase 0 – Definizioni:** conferme con il cliente (domande aperte), accesso Workspace, DPA.
2. **Fase 1 – Alpha / pilota in shadow mode:** vedi `primo-disegno-alpha.md`. Nessuna azione automatica; si misura.
3. **Fase 2 – Beta assistita:** bozze nel gestionale + revisione umana su tutto.
4. **Fase 3 – Produzione graduale:** automatismo solo per le classi/soglie che hanno dimostrato affidabilità.

---

## 9. Domande aperte

1. Le mail ordini arrivano a un alias di gruppo o direttamente alle quattro caselle personali? (decide se si può usare una casella dedicata)
2. Qual è il gestionale/ERP di destinazione e quali API espone? Si può leggere anagrafica e ordini aperti?
3. In che formato arrivano gli ordini: corpo mail, PDF, Excel, immagini, mix?
4. Volume reale giornaliero per casella e per tipo di mail?
5. Lingue presenti oltre all'italiano?
6. Come identificano i clienti gli ordini da correggere (numero ordine, riferimento cliente, "l'ordine di ieri")?
7. Ci sono mail storiche già smistate da usare come dataset di pilota? Il cliente ne autorizza l'uso?
8. Vincoli del cliente su sede dei dati e fornitori AI esterni?
9. Chi risponde dei casi in coda di revisione e con quale SLA?

---

## 10. Fonti consultate su Jev

- Kanerika – *Jev AI model*: https://kanerika.com/blogs/jev-ai-model/
- YourStory – *Introducing Jev*: https://yourstory.com/ai-story/what-is-jev-typesafe-ai-decision-model
- Towards Data Science – *Jev vs. LLMs*: https://towardsdatascience.com/jev-vs-llms-when-ai-moves-from-generation-to-decision-making/
- AI Superior – *Jev vs LLMs*: https://aisuperior.com/blog/jev-vs-llms/
- Olostep – *Jev AI*: https://www.olostep.com/blog/jev-ai
- Vibe Coding Academy – *Jev vs LLM*: https://www.vibecodingacademy.ai/blog/jev-vs-llm-system-one-model

*Nota: le fonti su Jev sono per lo più secondarie e in parte basate su dati del fornitore; da verificare sulla documentazione ufficiale prima dell'adozione.*
