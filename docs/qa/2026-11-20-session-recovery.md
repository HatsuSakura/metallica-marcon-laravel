# Sessioni e recupero bozze — GLE-145

Requisiti approvati: sessioni web da 720 minuti di inattività, verifica al
rientro dopo almeno cinque minuti, gestione condivisa 401/419 e bozze locali.
Il problema CSRF osservato in QA non ha una causa dimostrata; il runtime QA
verificato prima del rilascio usa database sessions da 120 minuti.

## Comportamento implementato

Il probe GET `/session/status` restituisce identità e fingerprint della sessione,
mai il token CSRF. Nessun heartbeat mantiene artificialmente attiva la sessione.
Al rientro vengono riletti i dati; un comando cliccato su dati vecchi viene
annullato e richiede una conferma esplicita successiva. Nessuna mutazione viene
ritentata automaticamente. Timeout delle verifiche 15 secondi, errori di rete
separati dalla sessione scaduta. Le API Bearer `/api/v1` sono escluse.

401/419 aprono «Accedi nuovamente»; il pulsante effettua navigazione completa
verso un login con ritorno sicuro alla pagina originaria. Destinazioni esterne,
API e circuiti login/sessione rifiutati dal backend. L'header opzionale
X-Session-User verifica la coerenza dell'utenza della scheda, senza sostituire
autenticazione e autorizzazione. BroadcastChannel/storage-event segnalano cambi
utenza tra schede. I comandi restano bloccati durante verifica/scadenza.

## Bozze locali

IndexedDB, schema 1, isolamento per utente/viaggio/risorsa/scheda e TTL 24 ore.
Dati espliciti dei campi, baseline per campo e revisione del viaggio; niente
password, token, credenziali o coda di comandi. Debounce 250ms e flush prima del
login. Limite 64 KiB per risorsa: superamento o storage indisponibile producono
un avviso, mai un falso salvataggio. In caso di errore del flush il login
richiede una seconda conferma di perdita e offre campi copiabili.

Recupero solo esplicito: Recupera/Scarta. Confronto con valori correnti;
modifiche server indipendenti consentite, conflitti sullo stesso campo bloccati.
Risorse non modificabili non si recuperano. Schede diverse hanno record separati;
compare-and-delete impedisce di cancellare una versione altrui più recente.
Cleanup dopo salvataggio confermato, scarto o TTL al caricamento dello scope.

Prima integrazione nel workspace viaggio: campi materiali e aggiunta materiale,
consegne e aggiunta contenitore, note sede, split motrice/rimorchio, motivo/note
salto, sequenza tappe, campi aggiunta tappa e registrazioni di magazzino.
Le sezioni devono essere montate per proporre il recupero: riaprire la tappa o
il modulo interessato quando necessario. Non recuperare risorse eliminate,
tappe non modificabili o l'esecuzione di Completa/Salta/Inizia/Termina/Chiudi.
Le bozze non sono un backup remoto: nessuna garanzia tra browser/dispositivi,
in navigazione privata, cancellazione dati del browser o perdita del dispositivo.
Le operazioni di intervento Logistica già accodate per conferma non sono una
coda persistente in IndexedDB: il recupero riguarda i campi editabili.

## Configurazione e rilascio

Default e `.env.example`: SESSION_LIFETIME=720. Il default da solo non sovrascrive
un valore ambiente già impostato. `session:lifetime 720` aggiorna solo quella
chiave nell'ambiente, preservando permessi e altri valori, poi pulisce config.
Dopo `config:cache`, `session:lifetime 720 --check` verifica il valore effettivo.
Il deploy QA applica entrambe le operazioni. In Docker modificare la sorgente
dell'ambiente prima di ricreare app/worker/scheduler: non affidarsi a modifiche
interne a un container temporaneo. Non allungare i Bearer token degli agenti.

## Verifiche prima dell'integrazione

- Backend: 8 test / 69 assertion per probe, safe return, durata del gestore
  database reale (11h valido, 13h scaduto), login/ritorno, 419, cambio utenza
  prima della scrittura e comando configurazione.
- Frontend: suite Node completa del branch, 21 file passati.
- Chrome / IndexedDB reali con API simulate: persistenza, login dopo 419,
  recupero esplicito, nessun replay, cleanup, conflitto campi e baseline,
  isolamento utenza e schede concorrenti, discard concorrente protetto,
  ripresa offline e storage non disponibile con riconoscimento perdita.

Le verifiche integrate della release sono nel documento di rilascio. Nessun
rilascio QA/Jarvis è dimostrato dalla presenza di questo documento.
