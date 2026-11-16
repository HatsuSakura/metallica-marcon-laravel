# Sviluppi dopo UAT — 2026-11-13

Implementati e verificati nel working tree locale, dopo il collaudo QA del
12 novembre e i chiarimenti del PO del 13 novembre. Nessun commit, push,
deploy QA o produzione eseguito per questo pacchetto. Accettazione UAT ancora
necessaria. Base Git: `ab0efc65f7563631ba4f293d55ba02ab0ada2756`.

Requisiti: [tabella concordata](../checkpoints/2026-11-11-driver-uat.md#nuove-richieste-emerse-in-uat).
Fuori perimetro: modifica anagrafica sede per Logistica/amministrazione (in
pausa), integrazione formulari da discutere con Mario e riconciliazione generale
delle API documentali operative già segnalata prima di questo UAT.

## Risultato per issue

| Issue | Comportamento locale |
| --- | --- |
| GLE-127 | Cambio effettivo dell’email: verifica precedente invalidata e nuova notifica dopo commit della transazione, soltanto al nuovo indirizzo. Nessun invio se l’indirizzo è invariato o la transazione viene annullata. Il reinvio manuale autorizzato è disponibile perché il nuovo indirizzo è da verificare. La gestione è condivisa nel model User, per account e amministrazione. |
| GLE-128 | I materiali rimossi sono visibili come record attenuati con Ripristina ed Elimina definitivamente. Ripristina conserva quantità e allocazioni applicando i controlli; Elimina definitivamente chiede conferma e nasconde il record anche ai successivi caricamenti. Nessun record esecutivo o storico viene cancellato. Comandi con revisione attesa, autorizzazioni e guard sulla tappa. |
| GLE-129 | Salta e Completa compaiono nella card della tappa corrente e in fondo al dettaglio. Un componente condiviso usa le stesse azioni e lo stesso stato di richiesta/motivo del salto; i campi hanno identificativi accessibili distinti. |
| GLE-130 | Confronto normalizzato dei dati business prima della scrittura. Il salvataggio invariato conserva identificativi delle tappe, revisione e documenti anche tramite richiesta diretta. In UI Salva si abilita soltanto per modifiche effettive. La prima creazione resta disponibile. |
| GLE-131 | Durante generazione attiva Salva è disabilitato e le modifiche dirette al viaggio sono respinte dal backend sotto lock. Generazione disabilitata con modifiche non salvate. Una modifica reale incrementa plan_version e invalida i documenti senza far retrocedere gli stati business o eliminare file storici. I job restano vincolati al fingerprint atteso; il timeout non blocca permanentemente salvataggio o nuovo tentativo. |
| GLE-132 | Nella modifica consegne è presente soltanto l’effettivamente consegnato. Il campo usa il previsto quando l’effettivo è assente, senza registrarlo prima del salvataggio/conferma tappa. Zero e valori salvati sopravvivono a riapertura e completamento. Il driver non può modificare il previsto di una consegna esistente neppure via API. Ritiri, mandato iniziale e calcolo residui rimangono distinti. |
| GLE-133 | Apri all’avvio è persistente per singolo viaggio, inizialmente true. La testata segue il flag a ogni apertura del workspace; aprire/chiudere manualmente la testata non modifica il flag. Il salvataggio della preferenza non altera revisioni o fingerprint documentali. |
| GLE-134 | Componente CER condiviso fra nuova UI autisti e visualizzazione CER nell’editor ordine. Pericolosità basata su is_dangerous, token error e testo Pericoloso; l’indicazione ADR rimane distinta. |
| GLE-135 | Mostra/Nascondi tappe concluse ha bordo visibile, testo più grande, area di tocco adeguata, conteggio e aria-expanded. |

Via MCP le nuove GLE-127–135 risultano tutte **In corso**, assegnate a
matteo.argenton, non risolte. Non si dichiara completata l’accettazione.
[Snapshot mirato](evidence/2026-11-13-driver-uat/youtrack.json).

## Migrazione e preparazione QA

Nuova migrazione: `2026_11_13_160000_add_driver_uat_preferences.php`.
Aggiunge `journeys.header_open_on_entry` (default true) e
`journey_execution_items.hidden_from_driver` (default false), senza reset di
viaggi, ordini o esecuzioni. Applicata al solo database locale il 13 novembre.

Per il successivo rilascio QA: consolidare il sorgente verificato in un commit,
seguire il processo esistente di backup/deploy QA, applicare la migrazione prima
di aprire le nuove schermate, ricostruire cache e riavviare i worker secondo il
runbook di deploy. Nessuna nuova variabile ambiente, seeder o credenziale.
La disponibilità in QA deve essere confermata con marker remoto e smoke test;
la presente nota è preparazione del rilascio, non evidenza di deploy.

Le modifiche preesistenti ai documenti UAT, a open-points e al CSS generato sono
state conservate. La build non implica pubblicazione del working tree.

## Verifiche eseguite

- `php artisan test --filter='Journey|Driver|SiteContacts'`: **88 test passati,
  609 asserzioni**, con fixture SQLite isolate in memoria. Inclusi 10 nuovi
  casi su email, ripristino/occultamento, autorizzazioni, revisioni, consegna
  zero, preferenza per viaggio, salvataggio invariato, modifica reale,
  generazione scaduta e job con input superati.
- `node --test tests/Frontend/*.test.mjs`: **13 file passati**, inclusi quattro
  nuovi casi renderizzati su record rimosso, badge pericoloso, azioni condivise
  e apertura della testata.
- `npm run build`: completata; rimane l’avviso di dimensione del bundle.
- `git diff --check`: riuscito.
- Chrome headless sui componenti reali, con dati sintetici e trasporti
  API/Inertia simulati: **390×844, 768×1024, 1280×900**, nessuno scorrimento
  orizzontale né errore JavaScript rilevato. Interazioni: ripristina/rimuovi/
  nascondi e rientra; consegna iniziale prevista, salvataggio zero e riapertura;
  flag testata salvato e seguito al rientro; Salva disabilitato su dati invariati,
  abilitato dopo modifica, disabilitato dopo ripristino dei dati e durante
  generazione. Margini del layout riprodotti nella fixture. La mappa della
  pianificazione è esclusa dalla fixture, non coinvolta nelle modifiche.

Evidenze: [PHP](evidence/2026-11-13-driver-uat/php-tests.txt),
[frontend](evidence/2026-11-13-driver-uat/frontend-tests.txt),
[build](evidence/2026-11-13-driver-uat/build.txt),
[browser](evidence/2026-11-13-driver-uat/browser-results.json),
[telefono](evidence/2026-11-13-driver-uat/phone.png),
[tablet](evidence/2026-11-13-driver-uat/tablet.png),
[desktop](evidence/2026-11-13-driver-uat/desktop.png).

Queste prove non attestano la consegna SMTP in una casella reale, la salute
dei worker QA, una prova touch su dispositivi fisici o l’accettazione del PO.

## Scenari di ricollaudo QA

1. Come Logistica, cambiare l’email di un utente di prova già verificato:
   nuovo indirizzo da verificare, messaggio ricevuto nella nuova casella,
   possibilità di reinvio. Salvare senza cambio indirizzo: nessun nuovo invio.
   Verificare anche il percorso account personale e il link ricevuto.
2. Aprire un viaggio creato con documenti correnti: Salva disabilitato.
   Modificare un valore e ripristinarlo: nuovamente disabilitato. Con modifica
   reale salvare, rigenerare e attendere documenti correnti; nessuna perdita
   della preparazione dopo semplice rientro. Durante generazione attiva non
   deve essere possibile salvare modifiche né certificare una revisione superata.
3. Sul viaggio di prova avviato rimuovere e ripristinare un materiale, anche
   suddiviso su motrice/rimorchio. Controllare quantità, conteggi e allocazioni.
   Rimuovere di nuovo e scegliere Elimina definitivamente: assente dopo rientro,
   storico consultabile dai ruoli autorizzati. Riprovare con revisione superata
   e tappa conclusa: nessun recupero fuori dai controlli previsti.
4. Modificare una consegna rispetto al previsto, poi impostarla a zero:
   rientro e completamento conservano zero; mandato iniziale e residui coerenti.
5. Su telefono e tablet usare Salta e Completa da entrambe le posizioni,
   compresi motivo Altro obbligatorio, richiesta in corso e errore di salvataggio.
6. Disattivare Apri all’avvio, rientrare nello stesso viaggio e poi aprirne un
   altro: il primo segue la preferenza, l’altro mantiene il proprio default.
   La chiusura manuale non modifica la preferenza.
7. Confrontare CER pericoloso, CER ordinario ed elemento ADR; verificare
   riconoscibilità e coerenza con creazione ordine. Mostrare/nascondere le tappe
   concluse e verificarne consultabilità anche dopo rientro.
