# Preparazione giornaliera dei viaggi autista locali

Comando: `php artisan dev:journeys-today`.
Disponibile solo con `APP_ENV=local`. Usa la connessione DB configurata
dall'applicazione: prima dell'uso verificare che sia il database di sviluppo.
Non configurarlo come operazione schedulata di produzione.

Anteprima per un autista:

```bash
php artisan dev:journeys-today --driver=antonio@test.com
```

Applicazione:

```bash
php artisan dev:journeys-today --driver=antonio@test.com --apply
```

Per tutti gli autisti, sostituire `--driver=...` con `--all-drivers`.
Senza `--apply` non vengono modificati dati.

Seleziona soltanto viaggi non eliminati, assegnati, con stato `creato` o
`attivo` e partenza pianificata antecedente a oggi. Viaggi futuri, già odierni,
eseguiti, chiusi, senza data o senza stato restano esclusi. Non vengono
riaperti viaggi né ripristinate tappe completate.

Il giorno di destinazione è quello corrente in `Europe/Rome`, anche se
l'applicazione usa UTC. Conserva l'ora italiana della partenza e la durata
prevista, spostando coerentemente la fine pianificata quando presente.
I valori sono scritti in UTC. Date effettive, tappe, ordini, stato e
`updated_at` non vengono alterati. Il comando è idempotente nello stesso giorno.

Prima delle scritture salva ID e date prima/dopo in un JSON locale sotto
`storage/app/dev-journey-date-shifts/`, senza email o nominativi. Le modifiche
DB sono transazionali con blocco delle righe; il report descrive le modifiche
preparate e va confrontato con il DB qualora la transazione fallisca.
È una preparazione dei dati locali di test: non genera falsi eventi di
esecuzione dell'autista e non sostituisce l'audit operativo dell'applicazione.

## Prima esecuzione verificata — 2026-10-31

Autista locale ID 2: aggiornati viaggi 38, 40 e 159. Escluso il viaggio 44,
privo di stato. Gli altri viaggi eseguiti sono rimasti invariati.
Report: `storage/app/dev-journey-date-shifts/246f9376-79d7-46ba-b980-e25e30173310.json`.
Test automatici su SQLite isolato: anteprima, applicazione, filtro autista,
stati, futuro, eliminati, fine assente, durata, ora italiana/cambio ora,
report, idempotenza e blocco ambiente non locale.

Questa operazione prepara il dataset; la dashboard centrata su oggi è ancora
uno sviluppo previsto da GLE-124. La dashboard attuale è un collegamento
alla lista dei viaggi.
