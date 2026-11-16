# GLE-132 — Limite fisico dei vuoti consegnabili

Chiarimento PO del 16 novembre 2026, implementato nel working tree locale.
Nessun deploy QA/produzione. Nessuna nuova migrazione.

## Requisito e comportamento

La quantità effettivamente consegnata può superare il mandato del singolo
cliente, ma il totale delle consegne non può superare i vuoti disponibili
nel viaggio, per ciascun tipo di contenitore. I ritiri non alimentano lo stock
di vuoti. Il mandato iniziale resta congelato nella baseline e costituisce la
dotazione iniziale già usata dal calcolo dei residui, incluse le scorte previste.

Esempio di accettazione con tre clienti e 18 casse: al primo si possono
consegnare 7 casse anziché 6; se il secondo ne riceve 6, il terzo può riceverne
al massimo 5. Confermare automaticamente le 6 previste per il terzo viene
bloccato. Consegne totali inferiori a 18 sono ammesse: il residuo torna a casa.
Nessun obbligo di esaurire la dotazione o di modificare anticipatamente il
mandato degli altri clienti.

Il controllo condiviso `ContainerBalance::assertAvailable` viene applicato
ai comandi di aggiunta/modifica consegna, completamento tappa (anche quando
conferma automaticamente il previsto) e fine viaggio. Usa quantità effettive
registrate e dotazione originale, con il lock transazionale del viaggio;
il rifiuto annulla tutte le scritture del comando, senza incrementare revisione
o creare eventi di successo. Non è possibile compensare casse con fusti o
creare dotazione aggiungendo un nuovo tipo nella UI. In caso di disponibilità
non determinabile, la consegna positiva viene bloccata. Eventuali anomalie
storiche restano visibili nel riepilogo e non vengono normalizzate a zero.

La UI mostra il massimo disponibile per la singola consegna, limita il
contatore e disabilita il salvataggio fuori disponibilità. Per correggere una
consegna già registrata, il massimo aggiunge al residuo la quantità salvata
sulla stessa riga, evitando di sottrarla due volte. Il backend resta vincolante
anche per richieste dirette e revisioni concorrenti/superate.

## Verifiche

- Suite PHP pertinente: **91 test passati, 631 asserzioni**.
- Frontend: **14 file passati**, compreso il limite per tre clienti, la
  correzione di una consegna già registrata, zero, tipi diversi e disponibilità
  sconosciuta.
- Test transazionali: terza tappa bloccata oltre stock, rollback di stato,
  quantità, revisione ed eventi; aggiunta consegna/API diretta fuori stock;
  concorrenza tramite revisione; residui positivi consentiti a fine viaggio.
- Chrome con dati sintetici a 390, 768 e 1280 px: massimo del contatore verificato, consegna zero e rientro corretti; nessun overflow o errore JavaScript. Trasporti API simulati, vincolo reale coperto dai test backend.
- Build completata, avviso preesistente dimensione bundle.

Evidenze in [evidence/2026-11-16-delivery-capacity](evidence/2026-11-16-delivery-capacity).
I test usano SQLite isolato e dati sintetici. Il ricollaudo QA deve includere
lo scenario 7 + 6 + 5 e un rientro con vuoti residui.

## Preparazione dati local dev richiesta nella stessa sessione

Per i test dell’autista locale ID 2 sono rimasti al 16 novembre soltanto i
viaggi creati 38 e 40 e il viaggio attivo 159. Conservati ora italiana, durata
prevista, stati, date effettive, tappe, ordini ed esecuzioni.

Inizialmente erano stati spostati tutti i sette viaggi non eliminati; il PO ha
poi precisato il perimetro ai soli nuovi/in corso. Ripristinate dal backup le
date originali dei viaggi eseguiti 37, 41 e 118 e del viaggio senza stato 44,
verificando che non fossero intervenute altre modifiche alle date. Nessun
viaggio riaperto o azzerato.

Backup privati verificati, esclusi da Git/KB:
`storage/app/dev-journey-date-shifts/antonio-756ffb6f3ae39b56.json` (prima modifica)
e `storage/app/dev-journey-date-shifts/correction-e35ccc965b566b68.json` (correzione).
Operazioni transazionali verificate nel DB locale; nessun accesso QA o
produzione. Le date fixture di un piano già documentato possono richiedere
la normale rigenerazione prima di una nuova partenza.
