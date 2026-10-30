
> **Nota recuperata — storica, non procedura corrente.** Origine: vecchio vault Obsidian; recupero 2026-10-30. Per lo stato attuale consultare [sviluppi e riconciliazione](../../../development-status.md). Non eseguire comandi di deploy, migrazione o modifica dati sulla sola base di questa nota.

# Refactor controller ordini

Data: 2026-03-26
Progetto: Metallica Marcon Laravel
Stato: sospeso su richiesta cliente

## Contesto
Il refactor dei controller Ordini impatta direttamente i flussi Driver e Magazzino. Il cliente ha messo in pausa questi sviluppi, quindi il tema va conservato come contesto per ripartenza futura.

## Obiettivo
Ridurre la duplicazione della logica CRUD e di validazione degli ordini, mantenendo intatti i flussi speciali ancora necessari.

## Controller coinvolti
- `app/Http/Controllers/OrderController.php`
- `app/Http/Controllers/DriverOrderController.php`
- `app/Http/Controllers/WorkerOrderController.php`
- `app/Http/Controllers/WarehouseManagerOrderController.php`

## Stato dell'analisi fatta
- `OrderController` è il controller canonico per il flusso ordini.
- `DriverOrderController` è ancora attivo via route `driver.order.*`.
- `WorkerOrderController` al momento non risulta esposto da route attive come resource dedicata.
- Le view driver usano già in più punti route canoniche `order.*`, segnale utile per un consolidamento progressivo.
- `WarehouseManagerOrderController` non è un clone puro del controller ordini: gestisce un flusso operativo diverso, focalizzato su lavorazione magazzino e item.

## Osservazioni architetturali
- Il refactor non va affrontato come semplice cancellazione immediata dei controller speciali.
- Il primo target realistico è il consolidamento della logica condivisa tra `OrderController` e `DriverOrderController`.
- `WorkerOrderController` sembra più vicino a dead code/residuo legacy che a flusso vivo di prodotto.
- Il ramo warehouse-manager va trattato separatamente, perché copre use case diversi da create/edit canonico ordine.

## Strategia consigliata
1. Estrarre logica condivisa di validazione, creazione e update ordine in un layer comune.
2. Ridurre `DriverOrderController` a wrapper sottile o rimuoverlo, riallineando route e viste dove possibile.
3. Verificare se `WorkerOrderController` può essere archiviato/rimosso in sicurezza.
4. Lasciare separato il flusso `WarehouseManagerOrderController`, salvo estrazioni molto mirate di codice riusabile.

## Vincoli / cautele
- Non rompere i processi Driver ancora usati dal cliente.
- Non toccare il flusso Magazzino operativo senza test di regressione mirati.
- Evitare di mescolare questo refactor con nuove feature di dominio.

## Segnali da verificare alla ripartenza
- Route ancora realmente utilizzate dagli utenti finali.
- Pagine Driver effettivamente ancora in uso.
- Eventuale dipendenza da policy/redirect che rimandano ancora a route `driver.order.*`.
- Codice morto relativo a `WorkerOrderController`.

## Contesto funzionale già completato prima della pausa
- Implementato `fixed_withdraw_at` su ordini.
- Implementato widget dashboard logistica per ordini con data fissa.
- Chiuse issue YouTrack `GLE-54` e `GLE-94`.

## Nota operativa
Quando il cliente riaprirà il tema, il passo successivo consigliato è ripartire dal ramo Driver, non dal Magazzino.
