# Roadmap — NLP + Journey Planning

Idee tracciate per evolutive future. Non sviluppare senza conferma PO.

---

## [CART] Paniere ordini multi-query (e-commerce pattern)

**Idea**: l'operatore esegue più query NLP successive per esplorare clienti/ordini.
Ad ogni query può selezionare site/ordini dalla mappa e aggiungerli a un "paniere".
Quando ha composto il paniere, va alla creazione journey con tutti gli ordini disponibili in elenco.

**Comportamento atteso**:
- Selezione site dalla mappa → aggiunge ordini afferenti al paniere
- Il paniere persiste tra query successive nella stessa sessione
- Site già in paniere mostrato con badge/icona distinta sulla mappa ("già attenzionato")
- Pulsante fisso "Vai a Journey (N ordini)" che si attiva quando paniere non è vuoto
- Journey/Create riceve l'elenco ordini del paniere come input pre-popolato

**Complessità stimata**: media — richiede stato condiviso tra mappa e pannello NLP,
gestione selezione marker Google Maps, e passaggio dati a Journey/Create.

**Prerequisiti**: definire come Journey/Create accetta ordini pre-selezionati via URL/state.

---

## [AUTO-JOURNEY] Composizione automatica del journey

**Idea**: dato il paniere ordini, un LLM o euristica suggerisce l'ordine ottimale delle tappe
(minimizzazione distanza, rispetto timetable clienti, vincoli mezzo/rimorchio).

**Approcci possibili**:
- Euristica: nearest-neighbor greedy con vincoli
- LLM: riceve lista ordini con coordinate e vincoli, restituisce sequenza ottimizzata
- Ibrido: euristica propone, LLM raffina con ragionamento sui vincoli non strutturati

**Dipende da**: [CART] completato.

---

## [LEARNING] Apprendimento da journey storici

**Idea**: analizzare i journey eseguiti storicamente per apprendere pattern di pianificazione
(sequenze frequenti, criteri impliciti usati dalla logistica, eccezioni ricorrenti).
Usare i pattern per migliorare [AUTO-JOURNEY].

**Approcci possibili**:
- Analytics descrittivi: frequenze, cluster geografici, orari preferiti per zona
- Fine-tuning o few-shot examples per il prompt [AUTO-JOURNEY]
- n8n per orchestrare pipeline di estrazione + feed al modello

**Dipende da**: [AUTO-JOURNEY] almeno in bozza.

---

## Note architetturali comuni

- Il "carrello" vive lato frontend (sessione) — non richiede tabella DB in prima implementazione.
- La persistenza cross-sessione del carrello (nice-to-have futuro) richiederebbe una tabella `nlp_journey_drafts`.
- L'integrazione n8n è il layer naturale per i loop di hardening e learning asincrono.
