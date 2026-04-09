# YouTrack Workflow

Progetto: `GLE` — `https://metallicamarcon.youtrack.cloud`

## Regole per la creazione di issue

1. **Lingua**: `summary` e `description` sempre in italiano.
2. **Assegnatario**: `matteo.argenton` di default, salvo indicazione esplicita.
3. **Tipo**: chiedere conferma al PO prima di creare. Valori possibili:
   - `Funzionalità` (default)
   - `Bug`
   - `Estetico`
4. **Priorità**: mantenere il default del progetto salvo richiesta esplicita.
5. **Workflow summary**:
   - Il PO descrive il problema o la feature.
   - Claude propone il `summary`.
   - L'issue viene creata solo dopo approvazione del PO.

## Issue completate a posteriori

Se richiesto, creare l'issue e impostare lo stato su `Completato`.
Aggiungere eventuali note di completamento come description o commento.

## Nested issues (parent/subtask)

1. Creare prima il parent con l'obiettivo ad alto livello.
2. Creare le child issue come issue separate.
3. Linkare ogni child al parent con tipo link `subtask of` (child → parent).
4. Tenere il parent aperto fino al completamento di tutti i subtask.
5. Aggiungere opzionalmente una checklist nel parent per visibilità.

## Default operativi

- Project key: `GLE`
- Chiedere conferma prima di creare se il tipo non è specificato.
- Se l'assegnatario non è fornito, usare `matteo.argenton`.
