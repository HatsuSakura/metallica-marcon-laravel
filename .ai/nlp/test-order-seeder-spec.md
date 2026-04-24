# Spec — Seeder ordini di test per NLP

Documento di riferimento per il seeder `NlpTestOrderSeeder`.
Traccia il subset di CER, le regole di generazione e i criteri di randomizzazione.

---

## Subset CER selezionato

12 codici scelti per coprire le categorie rilevanti per il dominio e per i test NLP.
Almeno 2 pericolosi garantiti (marcati **sì**).

| # | ID DB | Codice CER | Categoria | Pericoloso | Descrizione |
|---|-------|------------|-----------|------------|-------------|
| 1 | 67 | 160117 | ferrosi | no | Metalli ferrosi |
| 2 | 99 | 170405 | ferrosi | no | Ferro e acciaio |
| 3 | 102 | 170409 | ferrosi | **sì** | Rifiuti metallici contaminati da sostanze pericolose |
| 4 | 73 | 160214 | RAEE | no | Apparecchiature fuori uso (non pericolose) |
| 5 | 72 | 160213 | RAEE | **sì** | Apparecchiature fuori uso contenenti componenti pericolosi |
| 6 | 75 | 160216 | RAEE | no | Componenti rimossi da apparecchiature fuori uso |
| 7 | 1 | 020104 | plastici | no | Rifiuti plastici (ad esclusione degli imballaggi) |
| 8 | 69 | 160119 | plastici | no | Plastica |
| 9 | 117 | 191204 | plastici | no | Plastica e gomma |
| 10 | 61 | 150110 | bombolette | **sì** | Imballaggi contenenti residui di sostanze pericolose |
| 11 | 80 | 160602 | batterie | **sì** | Batterie al nichel cadmio |
| 12 | 82 | 160604 | batterie | no | Batterie alcaline (tranne 160603) |

**Pericolosi totali: 4** (170409, 160213, 150110, 160602)

---

## Regole di generazione ordine

Ogni ordine generato dal seeder deve rispettare le seguenti regole.

### Ordine (tabella `orders`)

| Campo | Regola |
|-------|--------|
| `customer_id` | estratto casualmente dai clienti esistenti |
| `site_id` | sede principale (`is_main = true`) del cliente estratto; se assente, prima sede disponibile |
| `status` | distribuzione pesata: `creato` 40% · `pronto` 30% · `pianificato` 20% · `eseguito` 8% · `chiuso` 2% |
| `is_urgent` | `true` con probabilità 15% |
| `requested_at` | data casuale negli ultimi 90 giorni |
| `expected_withdraw_at` | `requested_at` + 7–45 giorni casuali |
| `documents_status` | sempre `not_generated` |
| `journey_id` | sempre `null` (ordini non ancora pianificati) |
| `notes` | `null` |

### Numero di item per ordine

Ogni ordine contiene **1–4 item** generati indipendentemente.

### Item (tabella `order_items`)

Ogni item viene generato seguendo questa logica:

**1. Selezione CER**
Estratto casualmente dal subset dei 12 CER sopra, con distribuzione uniforme.

**2. Tipo materiale: sfuso vs holder**

| Categoria CER | Probabilità sfuso | Probabilità holder |
|---------------|------------------|--------------------|
| ferrosi | 60% | 40% |
| RAEE | 10% | 90% |
| plastici | 50% | 50% |
| bombolette | 0% | 100% |
| batterie | 0% | 100% |

**3. Se sfuso (`is_bulk = true`)**

| Campo | Regola |
|-------|--------|
| `holder_id` | `null` |
| `holder_quantity` | `null` |
| `weight_declared` | 150–1000 kg (intero casuale, multiplo di 10) |

**4. Se in holder (`is_bulk = false`)**

Holder compatibile per categoria:

| Categoria CER | Holder ammessi (ID) |
|---------------|---------------------|
| ferrosi | Bancale (2) · Cassa (4) · Cassa in FERRO (5) · BIG-bag (3) |
| RAEE | Bancale (2) · Cassa (4) · Imballo NON Standard (1) |
| plastici | BIG-bag (3) · Bancale (2) · Cassa (4) |
| bombolette | Cassa (4) · Fusto (8) |
| batterie | Cassa BATTERIE (6) · Cassettina BATTERIE (7) |

| Campo | Regola |
|-------|--------|
| `holder_id` | estratto dalla lista ammessa per categoria |
| `holder_quantity` | 1–10 (batterie/bombolette: 1–5) |
| `weight_declared` | `holder_quantity` × peso_medio_holder (vedi tabella sotto) |
| `custom_l_cm` / `custom_w_cm` / `custom_h_cm` | solo se holder = Imballo NON Standard (1): valore casuale da `[60, 70, 80, 90, 100, 110, 120]` per ciascuna dimensione, indipendentemente |

**Pesi medi per holder (riferimento)**

| Holder | Peso medio contenuto (kg) |
|--------|--------------------------|
| Imballo NON Standard (1) | 30 |
| Bancale (2) | 150 |
| BIG-bag (3) | 500 |
| Cassa (4) | 80 |
| Cassa in FERRO (5) | 200 |
| Cassa BATTERIE (6) | 40 |
| Cassettina BATTERIE (7) | 15 |
| Fusto (8) | 25 |

Aggiungi ±20% di variazione casuale sul peso calcolato.

**5. Campi comuni a tutti gli item**

| Campo | Valore |
|-------|--------|
| `has_adr` | `true` se `cer_codes.is_dangerous = true`, altrimenti `false` |
| `adr` | stesso di `has_adr` |
| `weight_gross` | `weight_declared` × 1.05 (tara stimata 5%) |
| `weight_tare` | `weight_gross - weight_declared` |
| `weight_net` | `null` (non ancora pesato in magazzino) |
| `status` | sempre `creato` |
| `is_warehouse_added` | `false` |
| `is_not_found` | `false` |
| `has_non_conformity` | `false` |
| `has_exploded_children` | `false` |
| `has_selection` | `false` |
| `is_transshipment` | `false` |
| `is_machinery_time_manual` | `false` |
| `is_holder_dirty` | `false` |
| `is_holder_broken` | `false` |

---

## Volumi target

Il seeder deve generare un numero di ordini utile per testare le query NLP senza appesantire il DB di sviluppo.

| Parametro | Valore consigliato |
|-----------|-------------------|
| Clienti campionati | 500 su quelli esistenti |
| Ordini per cliente | 1–2 |
| Ordini totali attesi | ~500–1000 |
| Item totali attesi | ~1250–2500 |

---

## Invarianti da rispettare

- Ogni sito deve avere coordinate (`latitude` e `longitude` non null) — filtrare i siti senza coordinate.
- Non generare ordini su siti `deleted_at IS NOT NULL`.
- Non generare ordini su clienti `deleted_at IS NOT NULL`.
- I CER usati devono esistere nel DB (verificare per ID, non per codice stringa).
- `updated_by_user_id` = id dell'utente admin (id=1) per tutti gli item.

---

## Keyword NLP attese dopo il seeding

Con questo subset, le seguenti query NLP devono restituire risultati non vuoti:

| Query di esempio | Segnali attesi |
|-----------------|----------------|
| "ordini aperti con materiale ferroso" | statuses=requested, cer_keyword=metall |
| "ordini con batterie" | cer_keyword=batter |
| "ordini con materiale sfuso" | has_bulk=true |
| "ordini con codici CER 160602" | cer_codes=[160602] |
| "ordini con materiali pericolosi" | cer_dangerous=true |
| "ordini aperti da clienti non visitati da oltre un mese" | statuses=requested, last_withdraw_days_min=30 |
| "ordini con plastica sfusa" | cer_keyword=plast, has_bulk=true |
