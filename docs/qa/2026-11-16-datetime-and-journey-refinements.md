# Date/ora e rifiniture viaggi — 2026-11-16

Follow-up di GLE-137 (ordini) e GLE-138 (viaggi). Implementazione e verifiche
locali completate; deploy QA delle nuove modifiche non ancora confermato.
Nessun upgrade di dipendenze e nessuna migrazione database.

## Convenzione data/ora

AppDateTimePicker centralizza Vue Datepicker 10 con flusso calendario → orario,
ore/minuti evidenti e comandi nativi a icona per tornare al calendario/orario.
Nessun pulsante aggiuntivo “1 Data / 2 Orario”. Clic o Enter/Spazio sul campo
valorizzato riaprono il selettore senza cancellare il valore.

La proposta fa floor al multiplo di 5 minuti e azzera secondi/millisecondi:
17:24 → 17:20. Frecce e griglia usano 5 minuti. La sola apertura/annullamento
non modifica valori salvati, nemmeno se i minuti originali non sono multipli di 5.
Annulla a sinistra con X/error outline, Conferma a destra con check/success
outline: entrambi size sm/32px. La conferma aggiorna il campo, non salva il form.
La convenzione è registrata in DESIGN.md.

Applicato a JourneySidebar (Create/Edit viaggio), Create/Edit ordine,
Driver/Order/Edit per i soli campi data+ora e ai tre campi data/ora della riga
magazzino (scarico, pesatura, selezione). Stili menu isolati e compatibili con
teleport. Conservati i formati Date/stringa dei consumatori. I campi sola data
dei ritiri, il giorno dashboard e gli orari sede mantengono il loro formato.
Le vecchie copie inattive sotto Driver/Journey copy non sono consumatori attivi.
Rimossi import diretti inutilizzati nelle pagine prive di datepicker.

Default nuovi di Richiesta e Data fissa usano lo stesso floor. Nel viaggio il
ritorno automatico usa la stringa prevista dal model-type; viene ricalcolato
alla conferma della partenza, non alla semplice chiusura del menu.

Riferimenti ufficiali della versione installata:
[flow](https://v10.vue3datepicker.com/props/modes/#flow),
[time picker](https://v10.vue3datepicker.com/props/time-picker-configuration/),
[slot e metodi pubblici](https://v10.vue3datepicker.com/methods-and-events/methods/).

## Altre rifiniture viaggio

- Modifica: Salva viaggio resta nell'editor; Salva ed esci success outline torna
  all'elenco. Entrambi disabilitati senza modifiche, con configurazione non valida,
  durante processing o generazione documenti. Icona floppy identica text-lg.
  Dopo il salvataggio in pagina la baseline viene aggiornata. Backend valida
  post_action e gestisce sia le modifiche sia le richieste equivalenti; il default
  per vecchi chiamanti resta uscita verso elenco.
- Tabella documenti: badge stato ordine sotto il codice nella stessa colonna;
  eliminata la colonna separata. Codici, badge, intestazioni, download e pulsanti
  non spezzano parole; nome cliente può andare a capo. Scorrimento orizzontale
  interno quando necessario, senza allargare la pagina.
- Ordini Aperti: comando di apertura/chiusura sul titolo con chevron e stato
  aria-expanded. Filtro sempre in testata; chiusura conserva ricerca e lista.
  Applicato sia in creazione sia in modifica viaggio.

## Verifiche

- Build Vite riuscita; rimane il warning sulle dimensioni bundle.
- 20 file frontend passati, incluso floor 17:24 → 17:20, confini dei minuti,
  secondi azzerati e immutabilità del valore originale.
- 8 test PHP / 69 asserzioni: comprende salvataggio reale e no-op in entrambe
  le destinazioni (12 asserzioni dedicate), oltre alle guardie mezzi/convoglio/delete.
- Browser Chrome, componenti e MainLayout reali con API/router/mappe simulati:
  Data → Orario, step di 5, conferma, riapertura di partenza/ritorno, annulla
  mantiene valore salvato e stato non modificato; telefono senza overflow.
- Menu teleported: stili e floor corretti. Due salvataggi: payload save_stay/
  save_exit, pulsanti disattivati dopo successo. Lista richiudibile: filtro
  conservato. Tabella: cinque colonne, stato sotto ID e badge nowrap.
- Regressione interattiva ordine/viaggio e matrice 60 combinazioni (6 schermate,
  5 larghezze, 2 temi), senza overflow di pagina o errori JavaScript.

Non è un collaudo E2E in QA. Da confermare su dispositivi reali: popup calendario/
orario e tastiera, dati magazzino con teleport, navigazione Inertia dopo entrambi
i salvataggi e tabella con codici/nominativi lunghi.

[Evidenze](evidence/2026-11-16-datetime/).
