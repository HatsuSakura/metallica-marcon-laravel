# Guard Hardening Plan - Metallica Marcon
Data: 2026-03-27
Owner: Backend Security/Authorization
Status: Planned

## Obiettivo
Rendere effettiva la protezione di tutte le chiamate backend (route + policy + authorize controller), eliminando le autorizzazioni permissive ereditate dalla fase prototipale.

## Strategia
Refactor incrementale in 3 blocchi, con patch piccole e verifiche smoke ad ogni blocco.

## Blocco 1 - Route Middleware Hardening
### Scope
- Audit completo route web/api con focus su endpoint mutativi.
- Uniformare middleware minimi obbligatori (`auth`, `verified` dove richiesto, eventuale `can:*` per route ad alto rischio).
- Chiudere buchi evidenti (es. risorse accessibili con solo `web` senza `auth`).

### Deliverable
- Matrice route -> middleware atteso -> middleware reale -> esito.
- Patch su `routes/web.php` (e/o file route dedicati) per allineare tutte le route.

### Smoke test
- Guest su endpoint protetto: 302/401 coerente.
- User autenticato ma non abilitato: 403 su endpoint sensibile.
- User abilitato: 2xx sui flussi leciti.

## Blocco 2 - Policy Ruoli (least privilege)
### Scope
- Revisione completa policy esistenti.
- Rimozione `return true` generalizzati su `view/create/update/delete` non giustificati.
- Introduzione helper comuni per ruoli (logistica, magazzino, driver, admin/dev).
- Definizione regole ownership dove necessario (es. driver solo su viaggio assegnato).

### Deliverable
- Policy matrix dominio per dominio:
  - Order
  - Journey
  - Site
  - Customer
  - Vehicle/Trailer/Cargo/Holder
  - User/Withdraw/Notification
- Patch policy + eventuale registrazione policy mancanti.

### Smoke test
- Copertura minima per role x ability (positivo/negativo).
- Verifica regressioni sui flussi già approvati (dispatch, documenti, driver stops).

## Blocco 3 - Authorize per Endpoint API (controller/service boundary)
### Scope
- Ogni endpoint API deve avere controllo esplicito:
  - `Gate::authorize(...)` o `$this->authorize(...)` su model/ability.
  - Oppure check ownership specifico documentato (solo se più adatto).
- Eliminare endpoint che oggi dipendono solo da `auth`.
- Uniformare codici errore (403 unauthorized, 422 validation/business).

### Deliverable
- Endpoint matrix:
  - route
  - controller@method
  - ability richiesta
  - modello target
  - stato (OK/Missing/Fixato)
- Patch controller API non coperti.

### Smoke test
- Test rapido per endpoint critici:
  - logistic dispatch APIs
  - warehouse APIs
  - driver state transitions
  - user reset/resend
  - NLP parse (se deve restare role-gated)

## Criteri di completamento
- Nessuna route mutativa raggiungibile senza `auth`.
- Nessun endpoint API sensibile privo di `authorize`/ownership check.
- Nessuna policy core con permissività globale non motivata.
- Test smoke passati su ruoli principali: logistic, warehouse_manager, driver, utente non autorizzato.

## Ordine operativo consigliato
1. Blocco 1 (route) - riduce subito superficie esposta.
2. Blocco 2 (policy) - definisce il contratto autorizzativo.
3. Blocco 3 (controller API) - applica il contratto endpoint per endpoint.

## Note implementative
- Patch piccole e atomiche per facilitare rollback.
- Evitare cambiamenti misti logica business + security nello stesso commit.
- Ogni patch deve includere mini report: "cosa protegge" + "cosa potrebbe rompere".
