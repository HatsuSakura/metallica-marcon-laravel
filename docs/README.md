# Documentazione Metallica Marcon

- [Ordini e gestione viaggi: convenzione UX/UI — 2026-11-16](qa/2026-11-16-planning-ui.md): componenti condivisi, dotazioni della sede, form responsive e verifiche locali.

- [Gap analysis DaisyUI 5 / Tailwind 4 — 2026-11-16](design/2026-11-16-daisyui5-gap-analysis.md): inventario, dipendenze e piano proposto; nessun upgrade eseguito, pulsanti attuali conservati.

- [Anagrafiche: form e punti di modifica — 2026-11-16](qa/2026-11-16-registry-forms.md): struttura comune, editor sede unico e verifiche locali; revisione grafica di clienti/sedi e orari ancora da concordare.

- [Convenzione grafica applicata agli autisti — 2026-11-16](qa/2026-11-16-driver-convention.md): pulsanti condivisi, dashboard compatta, temi e verifiche; solo locale. Eccezioni PO: menu con icone pulite e chiamate verdi; Salva verde, Annulla rosso outline.

- [Convenzione UX/UI approvata — 2026-11-16](../DESIGN.md): prima applicazione locale agli autisti e componenti comuni; prossime aree anagrafiche e poi Logistica. [Analisi iniziale](design/ux-ui-convention-proposal.md).

- [GLE-132: limite dei vuoti consegnabili — 2026-11-16](qa/2026-11-16-delivery-capacity.md): redistribuzione fra clienti entro la dotazione del viaggio, blocco delle eccedenze e verifiche locali.

- [Sviluppi post-UAT — 2026-11-13](qa/2026-11-13-driver-uat-followup.md): GLE-127–135 implementati e verificati localmente; evidenze, migrazione e scenari pronti per il successivo rilascio QA.

**Stato corrente 2026-11-11:** [checkpoint UAT autisti](checkpoints/2026-11-11-driver-uat.md). Commit 89b2b22 in QA, GLE-116–126 tutte In corso via MCP, attesa UAT; GLE-122 con residui espliciti. Gli aggiornamenti sottostanti sono fotografie precedenti.


- [Notifiche globali — 2026-11-09](qa/2026-11-09-global-notifications.md): coda condivisa, grafica conservata e verifiche di non regressione.

Aggiornamento locale 2026-11-09: [note sede e avvio per riordino](qa/2026-11-09-driver-notes-reorder.md) implementati e verificati, non ancora pubblicati in QA. OP-001 risolto.

- **[Avanzamento GLE autisti — 2026-11-09](reviews/2026-11-09-driver-gle.md)**: confronto aggiornato tra YouTrack, implementazione, collaudo e sviluppi residui.

- **[Deploy QA del 2026-11-04](qa/2026-11-04-deploy-result.md)**: codice pubblicato, controlli, backup preventivi e collaudo ancora da eseguire.

- **[Pacchetto QA UI autisti — 2026-11-04](qa/2026-11-04-driver-ui.md)**: perimetro del commit, migrazioni e scenari di collaudo; sviluppi ancora aperti.

- **[Checkpoint di ripresa 2026-10-31](checkpoints/2026-10-31-driver-ux.md)**: stato locale UI autisti/esecuzioni, ultime decisioni, verifiche e prossima sessione.

- [GLE-118: riapertura delle tappe](GLE-118%20Riapertura%20tappa.md): dati conservati, corrente unica, guard e preparazione locale del viaggio 159.

- [Punti aperti per la call con Metallica Marcon](open-points.md): riordino delle tappe da autista prima della partenza.

- [GLE-122: modifiche operative e ripristini](GLE-122%20Modifiche%20operative%20e%20ripristini.md): anteprima valori, annullamenti e protezione delle modifiche non salvate.

- [Piano operativo esecuzione viaggi](Piano%20operativo%20esecuzione%20viaggi.md):
  implementazione autorizzata di baseline, esecuzioni e ciclo documentale.

- [Confine pianificazione/esecuzione](Proposta%20confine%20pianificazione-esecuzione.md):
  decisione della prima partenza e proposta di ridisegno organico.
- [Ciclo documentale](Ciclo%20documenti%20viaggio.md): generazione, rigenerazione
  per modifica di ordine o viaggio e ristampa senza effetti sul processo.

- [Piano originale e tracciatura](Verifica%20piano%20viaggio%20e%20tracciatura.md):
  ricognizione del codice e del DB locale prima di progettare GLE-122.

- [Dati locali autisti](development-driver-fixtures.md): comando giornaliero
  per spostare a oggi i viaggi aperti antecedenti durante sviluppo e test.

- [Piano UI autisti mobile-first](Piano%20UI%20autisti.md): requisiti concordati,
  issue GLE-116–126 e sequenza proposta dalla dashboard alle operazioni.

- [Contesto corrente](context.md): decisioni di sessione, risultati e prossimi passi.
- [Stato sviluppi riconciliato con YouTrack](development-status.md): fotografia
  del 2026-10-30, backlog e discrepanze tra tracker, codice e note.
- [Recupero del vecchio vault](recovery/MIGRATION.md): 20 note storiche,
  provenienza, sanificazione e limiti del backup.
- [Logistica E2E](Logistica%20E2E%20Analisi_v03.md): analisi funzionale esistente.
- [Intervista logistica](intervista-logistica-marcon.md): raccolta requisiti.

Le procedure e decisioni correnti restano anche in `.claude/rules/` e `.ai/`;
il recupero non le sostituisce. La KB in `~/knowledge` contiene sintesi
revisionabili, non una copia di tutta questa documentazione.
