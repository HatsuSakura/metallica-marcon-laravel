# Convenzione grafica — prima applicazione autisti, 2026-11-16

Implementazione locale della convenzione approvata, con le correzioni del PO
su header, telefonate e Salva/Annulla. Nessun deploy QA/produzione, commit o
push eseguito per questa attività. Nessuna nuova migrazione o dipendenza.

Autorità comune: [DESIGN.md](../../DESIGN.md), richiamato anche da AGENTS.md.
La [proposta precedente](../design/ux-ui-convention-proposal.md) è evidenza
storica: le indicazioni iniziali su header e chiamate sono state superate.

## Risultato

- `AppAction` condiviso: funzioni primary outline; Salva/conferme success pieno;
  Annulla/scarta e rimozione error outline; conferma distruttiva error pieno.
  Conservati type submit, form esterni, nomi accessibili, listener e blocco
  disabled/busy. Busy può mostrare uno spinner conservando le dimensioni.
- Eccezione header: icone pulite, senza cerchi o bordi permanenti, target 48 px
  e focus da tastiera. Le chiamate sono sempre success verdi, con distinzione
  mobile/fisso e nomi accessibili. Modifiche nei componenti comuni, non per route.
- Selettore giorno compatto: nessuna data estesa ripetuta sopra il campo,
  giorno/Oggi e frecce accessibili; toolbar in riga quando lo spazio consente.
  Un errore di navigazione ripristina il giorno effettivamente selezionato.
- Comandi operativi da 48 px (note, split, quantità, chiamate, salvataggi).
  Matite e aperture materiale/consegne uniformate. Salva nella riga dei
  disponibili rimane disabilitato se il valore torna al riferimento iniziale.
- Esito tappa alleggerito: Salta e Completa restano sia nella card sia in fondo
  al dettaglio; le istruzioni estese sono mostrate una sola volta nel dettaglio.
  Note sede prima dei referenti restano in entrambe le posizioni.
- Temi: content e superfici neutrali espliciti, primary scuro schiarito e
  tinte error adattate centralmente per gli outline, senza colori per pagina.
  Campi nativi seguono color-scheme chiaro/scuro. I bordi semantici DaisyUI
  (error/success/warning/primary ecc.) sono preservati rispetto alle utility
  generiche legacy. Nessuna modifica alla mappatura JourneyStatusBadge.

## Verifiche

- **15 file frontend passati**, inclusi contratto AppAction, type submit,
  busy/disabled, link nativi, variante conferma verde e annullamento rosso.
- Rigenerazione CSS Tailwind e build Vite completate, dipendenze invariate.
  Restano gli avvisi di dimensione bundle e database Browserslist non aggiornato;
  non sono stati eseguiti aggiornamenti fuori perimetro.
- Rendering Chrome dei componenti attuali con dati sintetici: dashboard e
  workspace a **320×740, 390×844, 768×1024, 1024×768 e 1440×1000**, nei due temi.
  Nessun overflow orizzontale o errore JavaScript osservato.
- Interazioni simulate: consegna zero conservata, Salva disabilitato all’inizio,
  abilitato dopo modifica e disabilitato tornando al valore iniziale;
  ripristino/rimozione/occultamento, preferenza della testata per viaggio;
  invio reale DOM del modulo Aggiungi materiale (trasporto API simulato).
  I controlli della pianificazione di sola verifica continuano a funzionare.
- Contrasto misurato nello stato stabile: Salva verde **7.81:1 / 5.38:1**,
  Annulla outline **6.47:1 / 6.41:1** (chiaro/scuro). Testo campo data
  **17.74:1 / 16.12:1**; bordo **3.41:1 / 4.86:1**. Verificato il mantenimento
  del bordo error dei campi, oltre al color-scheme nativo corretto.
- `git diff --check` riuscito. Revisione indipendente dei componenti: controllata
  la semantica dei submit; corretto il rischio di sovrascrivere i bordi di
  validazione con gli override generici dei temi.

[Evidenze complete](evidence/2026-11-16-driver-convention),
[dashboard telefono](evidence/2026-11-16-driver-convention/dashboard-phone.png),
[dashboard PC scura](evidence/2026-11-16-driver-convention/dashboard-desktop-dark.png),
[workspace tablet scuro](evidence/2026-11-16-driver-convention/workspace-tablet-dark.png).

Limiti: route, API e sessione delle fixture sono simulati, mentre Vue,
componenti, CSS e icone sono quelli correnti. Non è certificazione WCAG,
collaudo su dispositivi fisici o accettazione QA. Restano da eseguire sul
rilascio QA i controlli con dati e sessione reali, tastiera, zoom del browser e
comportamento dei controlli nativi sui browser usati dagli autisti.

## Prossime aree concordate

1. **Gestione anagrafiche**, prima di intervenire sulla Logistica.
2. **Logistica:** creazione ordini/viaggi e gestione ordini/viaggi.

Procedere una schermata alla volta usando il medesimo linguaggio visivo,
verificando i consumer condivisi e senza aggiornare ora Tailwind/Laravel/DaisyUI.
Non sono stati avviati ridisegni delle interfacce d’ufficio in questa attività.
