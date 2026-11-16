# Configurazione mezzi prima del carico — analisi

## Aggiornamento dopo conferma e implementazione locale

Confermati: cassone omonimo fisso per sponda/furgone; area rimorchio inattiva e
attenuata; rimorchio opzionale ma con cassone obbligatorio; combinazione sempre
lungo+corto (vietati sia lungo+lungo sia corto+corto).

Implementati in Create/Edit: configurazione condivisa, evidenziazione warning dei
campi mancanti, blocco drag e comandi alternativi fino a configurazione valida,
blocco salvataggio, azzeramento del cassone rimorchio residuo. Gli ordini rimorchio
e riempimento rimangono visibili e recuperabili con comando esplicito agli ordini
aperti, senza spostamenti automatici al cambio mezzo.

Backend: servizio VehicleConfiguration invocato da store/update prima delle
scritture. Controlla esistenza, compatibilità, rimorchio+cassone, lungo+corto e
ordini rimorchio/riempimento senza rimorchio. Nessuna modifica ai calcoli di carico.
I nomi Sponda/Furgone restano il contratto esistente dei cassoni speciali;
se manca il cassone omonimo, compare un messaggio e il carico rimane bloccato.

Verifiche: 6 test PHP / 51 asserzioni (incluse guardie store/update e confini del
convoglio in esecuzione), 18 file frontend, build Vite, 60 controlli responsive.
Scenari browser sintetici: sequenza campi, rimorchio senza cassone, doppio corto,
coppia valida, sponda/furgone con flag incoerente e motrice senza rimorchio.
Le sezioni seguenti documentano i riscontri precedenti alla correzione.

2026-11-16. Analisi del codice Create/Edit, pannelli condivisi, validazioni
JourneyController e VehicleController. Cinque scenari sintetici eseguiti in
Chrome con MainLayout reale; nessuna lettura o modifica delle anagrafiche reali.
Nessuna modifica funzionale durante questa analisi.

## Regole confermate dal responsabile prodotto

- Motrice e cassone motrice obbligatori prima di abilitare il carico.
- Rimorchio facoltativo.
- Sponda e furgone obbligatoriamente senza rimorchio.
- Guida progressiva con bordo warning, analoga al form ordine.

## Riscontri

1. JourneyVehiclePanels disabilita il cassone motrice tramite `!trailerEnabled`.
   Una motrice ordinaria senza rimorchio rimane senza cassone selezionabile.
2. Sponda/Furgone con has_trailer false selezionano automaticamente il cassone
   omonimo e lo bloccano: funziona nei due scenari sintetici coerenti.
3. Con has_trailer true, entrambi abilitano rimorchio e rifiutano il loro stesso
   cassone automatico. Riprodotto con alert contraddittorio, senza eccezioni JS.
4. Le compatibilità dipendono da nomi letterali Sponda/Furgone e dal flag rimorchio,
   non da una matrice di tipi. Mancano filtri equivalenti per cassone rimorchio.
5. Il cambio a un mezzo senza rimorchio svuota trailer_id ma non trailer_cargo_id
   né la lista ordini rimorchio; checkCargo continua a usare il cassone residuo
   nel calcolo delle capacità. Riscontro statico, non riprodotto nel browser.
6. Due cassoni lunghi producono alert/reset solo frontend. Create accede a
   evt.target.value anche nella chiamata interna con target stringa: possibile
   eccezione se un cassone rimorchio lungo residuo incontra un automatico lungo.
7. Drag e comandi alternativi di assegnazione non richiedono configurazione valida;
   i conteggi possono quindi confrontare carichi con capacità iniziali zero.
8. Store/update richiedono motrice e cassone ma non validano esistenza/compatibilità
   dei mezzi e dei cassoni in quei campi; non impongono il divieto rimorchio per tipo.
   L'anagrafica mezzi corregge has_trailer nel frontend al cambio tipo, ma il server
   non applica una corrispondente regola incrociata.

## Decisioni ancora necessarie

- Cassone Sponda/Furgone fisso e automatico, come oggi, oppure selezionabile?
- Cassone rimorchio obbligatorio quando si sceglie un rimorchio?
- Confermare il divieto assoluto della combinazione lungo + lungo.
- Cambio mezzo con ordini già assegnati: evitare rimozioni silenziose; decidere
  trasferimento o restituzione agli ordini aperti prima di confermare il cambio.

## Proposta da concordare

Separare compatibilità del mezzo, validità della configurazione e conteggio
del carico. Regole condivise Create/Edit, validate anche sul server; abilitazione
per destinazione di drag e comandi alternativi. Prima motrice, poi cassone;
rimorchio opzionale con configurazione propria. Sponda/furgone non devono mai
abilitare componenti o carichi di rimorchio, anche con anagrafiche incoerenti.

Evidenza browser: [cinque scenari](../qa/evidence/2026-11-16-planning/vehicle-audit.json).
