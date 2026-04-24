# Release Notes — Metallica Marcon Logistics

Questo file traccia le modifiche da rilasciare in QA e PROD rispetto all'ultima release stabile.
Aggiornare ad ogni sessione di sviluppo. Svuotare la sezione dopo ogni rilascio confermato.

---

## Convenzioni

- **QA** — rilascio frequente, anche parziale. Include feature in validazione.
- **PROD** — rilascio validato da QA. Richiede checklist completa.
- Le sezioni `.env` elencano le variabili da aggiungere/modificare manualmente sul server prima del deploy.

---

## [QA] — In attesa di rilascio

### .env — variabili da aggiungere

```dotenv
COMPANY_HQ_LAT=45.7095973
COMPANY_HQ_LNG=12.3054438
```

### Migrations

```bash
php artisan migrate
```

Crea la tabella `nlp_query_logs`.

### Feature

**NLP Logistics — parse + execute**
- Nuovo endpoint `POST /api/nlp/logistics/parse` — interpreta query in linguaggio naturale e restituisce JSON strutturato
- Nuovo endpoint `POST /api/nlp/logistics/execute` — parse + esecuzione query MySQL, restituisce siti/ordini candidati
- Provider attivo: `heuristic` (deterministico, zero costo API)
- Logging completo su `nlp_query_logs` (latenza, provider, esito, testo grezzo)

**NLP — filtri estesi**
- `site_filters.last_withdraw_days_min` — filtra siti non visitati da N giorni (`withdraws.withdrawn_at`)
- `site_filters.has_no_active_orders` — filtra siti senza ordini in stato creato/pronto/pianificato
- `order_filters.has_bulk` — filtra ordini con materiale sfuso (`order_items.is_bulk`)
- `order_filters.cer_codes` — filtra per codici CER espliciti (es. `["160117","170405"]`)
- `order_filters.cer_keyword` — filtra per keyword su `cer_codes.description` (es. `"metall"`)
- `order_filters.cer_dangerous` — filtra ordini con CER pericolosi (`cer_codes.is_dangerous`)

**NLP — risoluzione sede aziendale**
- Pattern riconosciuti come sede propria: `nostra sede`, `dalla nostra`, `sede di Metallica Marcon`, `la Metallica`, `da noi`, ecc.
- Risolve automaticamente a `COMPANY_HQ_LAT` / `COMPANY_HQ_LNG` come origine geo

**NLP — miglioramenti parser heuristic**
- `aperti` / `aperto` riconosciuti come sinonimi di status `requested`
- Risoluzione nome cliente estesa: match su singola parola significativa (≥4 chars) oltre al nome completo
- Validatore: `geo.radius_km` e `reference.customer/site` contano come segnale sufficiente per `planning_sites`

### Dati

**Seeder NlpTestOrderSeeder**
- 772 ordini su 500 clienti campionati, ~1870 item
- CER subset: 12 codici (ferrosi, RAEE, plastici, bombolette, batterie) di cui 4 pericolosi
- Da eseguire **solo in DEV/QA**, mai in PROD

```bash
php artisan db:seed --class=NlpTestOrderSeeder
```

---

## [PROD] — In attesa di rilascio

### .env — variabili da aggiungere

```dotenv
COMPANY_HQ_LAT=45.7095973
COMPANY_HQ_LNG=12.3054438
```

### Migrations

```bash
php artisan migrate
```

Crea la tabella `nlp_query_logs`.

### Feature

Stesse feature del blocco QA sopra, escluso il seeder dati di test.

### Checklist pre-rilascio PROD

- [ ] QA ha validato parse + execute su almeno 5 query representative
- [ ] Nessun errore 500 nei log NLP di QA
- [ ] `COMPANY_HQ_LAT` / `COMPANY_HQ_LNG` aggiunte al `.env` di PROD prima del build
- [ ] `php artisan migrate` eseguito dopo il deploy
- [ ] `php artisan config:clear` eseguito dopo il deploy
- [ ] Verificato che `/api/nlp/logistics/parse` risponda 200 con utente autenticato
- [ ] Verificato che la mappa mostri il pannello NLP e applichi i filtri correttamente

---

## Storico rilasci

| Data | Ambiente | Contenuto |
|------|----------|-----------|
| 2026-04-15 | PROD | Deploy iniziale — auth, ordini, journey, dispatch, documenti, avatar, user_code |
