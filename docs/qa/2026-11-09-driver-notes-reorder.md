# Note sede e avvio per riordino — 2026-11-09

Implementazione locale successiva alla riconciliazione GLE del 9 novembre.
Non ancora pubblicata in QA; nessuna modifica al tracker, nessuna migrazione.

## Note della sede

Nel dettaglio tappa, dopo i contatti e prima dei materiali, una sezione per
sede distinta mostra le note aggiornate o «Nessuna nota della sede». Matita,
textarea inline, annulla e salva; salva disabilitato finché il testo non cambia.
L'aggiornamento riguarda l'anagrafica condivisa Site, non le note dell'ordine.
Baseline e snapshot documentali restano immutati. Logistica vede il testo
aggiornato e lo storico JourneyEvent; l'autista non vede lo storico.

PUT /api/journeys/{journey}/sites/{site}/notes accetta solo note e versione.
Permesso ristretto al viaggio assegnato attivo con baseline e sede associata
ad almeno un ordine esecutivo pending/completed. Sedi eliminate o solo di
ordini rilasciati non sono modificabili. SitePolicy generale resta invariata.
Hash delle note letto all'apertura dell'editor: salvataggio concorrente rifiutato
con 409, bozza mantenuta, nessuna sovrascrittura. Evento con autore e prima/dopo;
nessun evento per un salvataggio invariato. Nessuna revisione esecutiva alterata
per una modifica anagrafica.

## Riordino e partenza

OP-001 risolto: l'autista non modifica il piano prima della partenza.
«Riordina» pre-avvio apre conferma esplicita della partenza effettiva.
«Annulla» non cambia nulla. «Avvia e riordina» usa il comando di avvio esistente,
carica i cataloghi operativi condivisi e apre il riordino senza ricaricare pagina.
Il salvataggio della sequenza rimane un'azione distinta. Annullarlo dopo l'avvio
non annulla la partenza. Se l'avvio fallisce, viene mostrato il motivo e non si
modificano piano, baseline o sequenza.

## Verifiche automatiche e collaudo manuale

86 test backend, 586 asserzioni; 11 file di test frontend superati. Coperti
permessi, concorrenza, note vuote, multisede, ordini rilasciati, baseline immutata,
visibilità dello storico, avvio bloccato, idempotenza e riordino operativo.

Dopo pubblicazione verificare su telefono/tablet:

1. Aprire una tappa di viaggio attivo: modificare note, annullare e salvare;
   verificare la stessa nota nella scheda sede e dopo rientro nel viaggio.
2. Aprire la stessa sede in due sessioni: salvare nella prima, tentare nella
   seconda; verificare conflitto e conservazione della bozza.
3. Sede condivisa da più ordini: una sola sezione; sedi diverse: sezioni distinte.
4. Viaggio da iniziare con più tappe: Riordina → Annulla, nessuna partenza;
   Riordina → Avvia e riordina, partenza reale e editor aperto.
5. Avvio impedito da documenti non aggiornati o altro viaggio attivo: motivo
   visibile e nessuna modifica della sequenza. Dopo avvio riuscito verificare
   anche aggiunta materiale/tappa senza reload.
6. Dopo conferma partenza, annullare solo il riordino: viaggio resta iniziato,
   sequenza iniziale conservata. Salvare riordino e verificarlo dopo reload.
