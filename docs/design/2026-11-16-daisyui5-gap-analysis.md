# Gap analysis — DaisyUI 5 e Tailwind 4

Analisi del 16 novembre 2026 sul commit `b92b50d7878c026b665b8c9d46eceaa57217c3de`.
Documento proposto: nessuna dipendenza aggiornata, nessuna migrazione eseguita,
nessuna modifica ai pulsanti o deploy come effetto dell’analisi.

## Decisione proposta

Mantenere per ora la dimensione media attuale; preparare un aggiornamento
coordinato DaisyUI 5 + Tailwind 4 in branch dedicato. Non introdurre una misura
intermedia temporanea soltanto per alleggerire i pulsanti.

Il PO indica PC con Chrome aggiornato e telefoni/tablet Android e iPhone recenti.
Non emerge un requisito dichiarato di browser obsoleti; verificare comunque
Safari/iOS e Chrome Android sui dispositivi effettivamente usati nel collaudo.
Tailwind dichiara Safari 16.4+, Chrome 111+ e Firefox 128+ e Node 20+ per lo
strumento di upgrade. [Guida ufficiale Tailwind](https://tailwindcss.com/docs/upgrade-guide).

## Stato effettivo

| Elemento | Installato / rilevato | Conseguenza |
| --- | --- | --- |
| DaisyUI | 4.12.24 | Temi in configurazione JS, classi v4 e variabili abbreviate |
| Tailwind | 3.4.18 | Direttive e CLI v3, CSS generato separatamente |
| Vite | 5.4.2 | Da integrare con la compilazione Tailwind 4 |
| Plugin Vue | 5.1.2 | Peer Vite 5 già coerente con lo stack attuale |
| Plugin Laravel/Vite | 1.0.5 | Peer Vite 5: evitare un aggiornamento Vite indiscriminato |
| Laravel | 11.57.1, vincolo Composer ^11.0 | Nessun blocco backend individuato che richieda un upgrade Laravel per questa attività CSS |
| Node locale | 24.18.0 | Soddisfa la soglia dello strumento di upgrade |
| Build Docker | node:20-alpine | Verificare patch effettiva e requisiti delle dipendenze selezionate |
| Form plugin | @tailwindcss/forms 0.5.8, disabilitato in config | Non abilitarlo incidentalmente durante l’upgrade |

Fonti locali: `package-lock.json`, `composer.lock`, `tailwind.config.cjs`,
`vite.config.js`, `Dockerfile`, `deploy-qa.sh` e `local.ecosystem.config.cjs`.

La guida DaisyUI richiede il passaggio al nuovo stack Tailwind, non un upgrade
del framework PHP. Il sorgente ufficiale consultato di `@tailwindcss/vite`
dichiara compatibilità peer con Vite ^5.2.0, oltre alle major successive.
Questo rende plausibile conservare Vite 5 e i plugin attuali: è compatibilità
dichiarata, **non una build migrata già verificata**. Bloccare versioni esatte
nel lockfile durante l’implementazione e verificare anche dipendenze transitive,
Node e ambiente Linux/Alpine. [Guida DaisyUI](https://daisyui.com/docs/upgrade/),
[package del plugin Vite](https://raw.githubusercontent.com/tailwindlabs/tailwindcss/main/packages/@tailwindcss-vite/package.json).

## Gap e interventi

| Priorità | Area | Evidenza nel progetto | Intervento previsto |
| --- | --- | --- | --- |
| Alta | Generazione CSS | Vite e Blade caricano `resources/css/tailwind.css`, già compilato; `npm run build` non contiene una compilazione Tailwind esplicita | Far derivare il CSS dai sorgenti nella build riproducibile; preferire il plugin ufficiale Vite. Eliminare la dipendenza dal watcher locale e da un file compilato potenzialmente non aggiornato |
| Alta | Configurazione | `tailwind.config.cjs` mescola require ed export default; temi e spacing sono nel file JS | Migrare verso configurazione CSS, preservando spacing 18, font, sorgenti Vue/Blade e due temi. Non affidarsi al rilevamento automatico della vecchia config |
| Alta | Temi e CSS proprietario | 33 riferimenti lessicali a variabili DaisyUI v4 in 5 file | Convertire ai token v5 completi: ad esempio `--bc` → `--color-base-content`, con uso CSS corretto del valore colore completo. Non avvolgere automaticamente il nuovo token in `oklch()` |
| Alta | Sizing | `AppAction` impone min-h-12 e forme 48×48; `RegistryPage` forza dimensioni via CSS; altri controlli mantengono misure esplicite | Fare rispettare i preset dal componente condiviso e rimuovere le forzature concorrenti. Stabilire separatamente quali controlli operativi devono conservare un target maggiore |
| Alta | Form | Classi rimosse diffuse, label globali e controlli v-select/form-wizard | Migrare markup e CSS comuni, preservando etichette, errori, focus, processing e dirty/revert. Una rimozione meccanica delle classi non garantisce lo stesso layout |
| Media | Tabs | Schedario clienti e vecchio editor magazzino usano varianti rinominate | Adeguare le varianti, mantenere ARIA/tastiera e rivalutare il raccordo custom di -1px sul componente v5, anziché trascinarlo senza verifica |
| Media | Utility Tailwind | Due occorrenze di bg-opacity-* negli overlay | Usare modificatori di opacità; inventariare anche le altre utility obsolete, nuove scale shadow/ring e Preflight durante la prova di migrazione |
| Media | Stato dark e contrasto | data-theme, regole dark: legacy e darkMode dentro la configurazione DaisyUI | Preservare lighttheme/darktheme, impostare esplicitamente la variante dark e verificare bordi, placeholder, input nativi e stati semantici |
| Media | CSS di terzi | DataTables, vue-select, datepicker, form-wizard, mappe e SVG FontAwesome | Controllare cascade/layer, import, popup, dimensioni native e focus. Non aggiornare tutte le librerie insieme senza una necessità dimostrata |
| Media | Deploy e sviluppo | QA e Docker eseguono npm ci + npm run build; vecchio launcher Windows usa npx tailwindcss --watch | Validare una build pulita su Linux/Alpine; aggiornare gli eventuali comandi CLI realmente usati. Non serve compilazione Node sul server QA se il processo continua a produrre gli asset localmente |

### Inventario delle classi

Ricerca lessicale in `resources` (Vue, JS, CSS e Blade), escluso il CSS compilato.
I conteggi comprendono selettori CSS e file legacy: non rappresentano pagine
attive uniche né una stima automatica delle ore. Dettaglio con percorsi in
[inventory.json](evidence/2026-11-16-daisy5/inventory.json).

| Pattern | Occorrenze | File |
| --- | ---: | ---: |
| form-control | 58 | 15 |
| label-text / label-text-alt | 46 | 11 |
| input-bordered | 79 | 36 |
| select-bordered | 95 | 40 |
| textarea-bordered | 25 | 22 |
| tabs-lifted | 1 | 1 |
| tabs-bordered | 1 | 1 |
| bg-opacity-* | 2 | 2 |
| Variabili colore v4 esaminate | 33 | 5 |
| Utility di dimensionamento 48 px esaminate | 95 | 30 |
| Utilizzi di AppAction | 69 | 26 |

`input-bordered`, `select-bordered` e `textarea-bordered` scompaiono perché
i controlli hanno già il bordo; form-control e label-text non forniscono più
lo stile precedente. Le tabs diventano `tabs-lift` e `tabs-border`.
[Mappatura ufficiale v4 → v5](https://daisyui.com/docs/upgrade/).

I cinque file di colore da correggere prioritariamente sono `resources/css/app.css`,
`resources/css/theme-overrides.css`, `EditableTimetable.vue`, `RegistryPage.vue`
e `SearchableSelect.vue`. In `app.css` ci sono inoltre stili globali per input,
label e autofill che vanno armonizzati, non aggiunti in coda a nuovi override.

## Conseguenze per i pulsanti

| Preset | v4 attuale | v5 con size-field standard e rem a 16 px |
| --- | ---: | ---: |
| btn-sm | 32 px | 32 px |
| btn-md / default | 48 px | 40 px |
| btn-lg | 64 px | 48 px |

La scala v5 è confermata dal [sorgente ufficiale button](https://raw.githubusercontent.com/saadeghi/daisyui/refs/heads/master/packages/daisyui/src/components/button.css).
Il cambio di misura non avverrà nei nostri componenti se rimangono le
forzature CSS a 48 px. È questo il motivo per intervenire sui componenti comuni
durante la migrazione, invece di aggiungere h-10/h-11 a ogni pulsante.

Proposta: `AppAction` resta il contratto di ruolo/forma/stato; DaisyUI gestisce
il preset. Conservare i controlli operativi da 48 px dove concordato tramite
una variante esplicita, per esempio il preset large v5, e non tramite CSS di
pagina che prevale silenziosamente sul componente. I consumer legacy ancora
diretti richiedono una ricognizione una tantum: nessuna promessa di migrazione
senza controllare le singole schermate.

## Sequenza di sviluppo proposta

1. Branch isolato e baseline visiva dei due temi; nessuna nuova funzione business.
2. Bloccare le versioni scelte e integrare Tailwind 4/DaisyUI 5 nella build.
   La guida suggerisce l’upgrade tool in branch, con Node 20+ e revisione del diff:
   non eseguirlo sul main operativo senza prima isolare la migrazione.
3. Migrare i temi e le variabili CSS; verificare AppHeader, AppAction, RegistryPage,
   quantità, stati, notifiche e campi comuni prima dei consumer.
4. Migrare le classi dei form e delle tabs, poi verificare clienti/sedi/orari,
   dashboard autisti, order/create?site={id}, journey create/edit e magazzino.
5. Verificare 320/390/768/1024/1440, light/dark, tastiera, popup e scrolling;
   collaudare Safari iPhone/iPad e Chrome Android/desktop, come target del PO.
6. Eseguire build pulita npm ci, suite frontend e controlli PHP pertinenti;
   verificare submit, dirty/revert, errori, drag mappa e conferme. Confrontare
   gli artefatti di deploy con la build locale, poi dry-run QA e rilascio concordato.

Non serve cambiare Laravel, PHP, Inertia o le regole applicative per principio.
Se emerge un vincolo concreto nella prova di integrazione, documentarlo prima
di ampliare il perimetro. Questo è un aggiornamento frontend trasversale, non
una semplice sostituzione di una classe, ma il lavoro sui componenti condivisi
già svolto riduce gli interventi ripetuti.

## Fonti e limiti

- [DaisyUI: upgrade v5](https://daisyui.com/docs/upgrade/).
- [DaisyUI: temi e token](https://daisyui.com/docs/themes/).
- [Tailwind: upgrade v4, browser, build e utility](https://tailwindcss.com/docs/upgrade-guide).
- File e lockfile del progetto al commit indicato; runtime Node locale.

Non installate dipendenze candidate, non eseguiti upgrade tool o build v5,
non interrogati QA/produzione. Peer dichiarati e documentazione upstream non
equivalgono a una validazione dell’integrazione. Versioni candidate definitive,
patch Node dell’immagine Docker e runtime usato per i deploy locali vanno
confermati durante l’implementazione.
