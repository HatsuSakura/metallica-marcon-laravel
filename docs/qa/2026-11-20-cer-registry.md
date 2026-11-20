# Anagrafica Codici CER — 20 novembre 2026

Implementazione locale, non committata né distribuita su QA/Jarvis.

## Funzioni

- Menu **Anagrafiche → Codici CER**, percorso `/cer-code`.
- Elenco paginato server-side (25 righe), ricerca per codice/descrizione e filtro Attivi/Disattivati/Tutti.
- Inserimento e modifica di codice, descrizione, pericolosità e stato attivo.
- Normalizzazione a sei cifre, con accettazione di spazi/asterisco; la pericolosità si imposta esplicitamente nel campo dedicato. Validazione dei duplicati anche rispetto ai formati importati e ai codici disattivati.
- Disattivazione reversibile, senza eliminazione del record o dei riferimenti storici.
- Audit del modello esteso a `is_active`.

## Autorizzazioni

Tutte le cinque rotte sono protette da autenticazione, verifica email e Gate `accessBackofficeArea`: flag `is_admin` oppure ruolo `developer`, `logistic`, `manager` (commerciale nel modello attuale). Il menu segue lo stesso insieme di profili; driver, clienti e ruoli magazzino senza flag admin ricevono 403 anche sulle scritture dirette.

## Compatibilità operativa

Il campo `is_active` viene aggiunto con default true e indice. La migrazione `2026_11_20_160000_add_active_to_cer_codes` è già applicata al database locale; resta da applicare negli ambienti di rilascio.

Nessuno scope globale filtra il modello: le relazioni degli ordini e le ricerche storiche continuano a leggere i CER disattivati. I selettori di creazione propongono i soli attivi; in modifica i riferimenti precedenti rimangono leggibili ma le opzioni inattive non sono selezionabili. La regola backend condivisa `AvailableCerCode` permette di mantenere il CER disattivato solo sulla stessa riga esistente dello stesso ordine; lo vieta su nuove righe e nuove assegnazioni. La stessa protezione è applicata agli inserimenti/cambi CER nelle copie esecutive dei viaggi, mantenendo consentite le altre modifiche a materiali preesistenti.

## UI

Componenti condivisi RegistryPage, RegistryForm, AppAction, CerCodeBadge e Pagination; DaisyUI 4/Tailwind 3. Header con azioni a destra e ritorno prima del titolo. Salva success, Annulla error outline, matita primary outline; stato dirty/revert e validità dei campi governano il salvataggio. Layout a righe adattabile, senza tabella che imponga scorrimento orizzontale sui telefoni.

## Verifiche

- Suite registro, elenco ordini, materiali esecutivi e notifiche: 26 test / 228 asserzioni prima dell'aggiunta dell'ultimo scenario; suite registro finale 5 test / 74 asserzioni, comprendendo il blocco di nuove assegnazioni in esecuzione e la conservazione del CER sulla riga precedente.
- Build applicativa riuscita; rimane l'avviso già presente sulla dimensione dei bundle.
- Componenti reali renderizzati in fixture con trasporto Inertia simulato: 30 combinazioni (Index/Create/Edit, larghezze 320/390/768/1024/1440, temi chiaro/scuro), nessun errore Vue e nessun overflow pagina. Verificata visivamente la resa mobile/desktop. Controllo interattivo dirty/revert di Salva.
- `git diff --check` senza errori.
- Evidenze sintetiche e screenshot con dati dimostrativi: [cartella](../evidence/cer-registry-2026-11-20/).

Il lavoro locale preesistente sulle notifiche resta nella working tree. Un eventuale rilascio CER separato richiede una selezione esplicita dei file/hunk, in particolare nel servizio esecuzione, layout, rotte e fixture condivise.
