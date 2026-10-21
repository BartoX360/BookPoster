# BookPoster

**[Français](#français) · [English](#english)**

![BookPoster sur un écran couleur](recipe/screenshots/fr-color-hot.png)

---

## Français

*Ce qui paraît en librairie, façon affiche.*

Un plugin TRMNL qui met un livre à l'affiche : les livres les plus attendus, ceux qui viennent de paraître ou les prochaines parutions en France, avec sa couverture, son auteur, son éditeur et un compte à rebours avant sa sortie. Il fait partie de la suite Poster avec [FlightPoster](https://github.com/BartoX360/FlightPoster), [WeatherPoster](https://github.com/BartoX360/WeatherPoster), [GamePoster](https://github.com/BartoX360/GamePoster), [MoviePoster](https://github.com/BartoX360/MoviePoster) et [ShowPoster](https://github.com/BartoX360/ShowPoster), dont il reprend le style.

Aucun serveur ni clé à installer : les listes et les couvertures viennent de [Place des Libraires](https://www.placedeslibraires.fr), le site des librairies indépendantes.

### Fonctionnalités

- **E-ink et couleur** : un style noir et blanc pur pour les écrans 1 bit (TRMNL OG, TRMNL X) et un style couleur pour les écrans LCD
- **Tous les formats** : plein écran, demi horizontal, demi vertical et quart d'écran (mashups)
- **Trois rubriques**, chacune avec sa couleur de ciel : *Les plus attendus* (doré), *Juste parus* (bleu), *Prochainement* (crépuscule)
- **Rotation** : chaque heure, l'affiche passe à une autre rubrique et à un autre de ses trois premiers livres, ou reste sur la rubrique choisie
- **Tampon** : le nombre de jours avant la parution, ou depuis la parution
- **Votre rayon** : littérature, romans, polar, fantasy & SF, romance, BD, mangas, jeunesse, sciences humaines, arts, vie pratique, ou tous les livres
- **Nouveautés seulement** : les rééditions en poche sont écartées, sauf si vous les voulez (toujours gardées pour les mangas)
- **Français ou anglais**
- Un livre sans couverture est illustré par un livre ouvert

### Captures

| | |
|---|---|
| ![E-ink, juste parus](recipe/screenshots/fr-eink-now.png) | ![Couleur, prochainement](recipe/screenshots/fr-color-upcoming.png) |
| E-ink · juste parus | Couleur · prochainement |
| ![TRMNL X, prochainement](recipe/screenshots/fr-x-upcoming.png) | ![E-ink, sans couverture](recipe/screenshots/fr-eink-noimg.png) |
| TRMNL X · prochainement | E-ink · livre sans couverture |
| ![Demi horizontal](recipe/screenshots/fr-hh.png) | ![Demi vertical](recipe/screenshots/fr-hv.png) |
| Demi horizontal | Demi vertical |

### Installation

**LaraPaper / BYOS** : *Plugins → Import recipe*, choisissez `recipe/bookposter.zip` (créé par `recipe_render.sh`, ou zippez le contenu de `recipe/src/`), puis choisissez vos réglages.

**TRMNL (usetrmnl.com)** : créez un *Private Plugin* avec la stratégie *Polling*, puis copiez :
- `polling_url` et `polling_headers` de `recipe/src/settings.yml` dans *Polling URL* et *Polling headers* ;
- les `custom_fields` de `recipe/src/settings.yml` dans *Custom fields* (YAML) ;
- `recipe/src/shared.liquid` dans *Shared markup*, et chaque `recipe/src/<format>.liquid` dans l'onglet correspondant.

Le dossier `recipe/src/` suit l'organisation de [trmnlp](https://github.com/usetrmnl/trmnlp). Le plugin est développé et testé sur LaraPaper : sur usetrmnl.com, les réponses HTML et leur taille (environ 1 Mo) ne sont pas vérifiées.

### Réglages

| Champ | Rôle |
|---|---|
| Genre | Le rayon des livres à l'affiche (Littérature par défaut) |
| Poches | Sans (par défaut) ou avec les rééditions en poche |
| Affiche | *Rotation* (chaque heure), ou toujours le livre le plus attendu, le plus demandé des juste parus ou la prochaine parution |
| Langue | Français ou anglais |
| Style | E-ink (noir et blanc) ou Couleur |

### Fonctionnement

L'URL de polling charge trois pages de recherche de Place des Libraires, triées par pertinence (les livres les plus demandés d'abord) et filtrées sur le rayon et la date de parution. Le gabarit n'en garde que l'en-tête des seize premiers livres (titre, auteurs, éditeur, date, EAN) ; la couverture est chargée au moment du rendu d'après l'EAN.

- *Les plus attendus* : les plus demandés parmi ceux qui paraissent d'ici deux mois
- *Juste parus* : parus ces deux dernières semaines, les plus demandés d'abord
- *Prochainement* : le prochain jour de parution, le livre le plus demandé d'abord, puis les suivants (d'ici deux semaines)

### Développement

| Fichier | Rôle |
|---|---|
| `recipe_render.sh <dossier>` | Zippe la recette, l'importe dans LaraPaper comme « BookPoster (test) », rend chaque cas de test et copie les PNG dans `<dossier>` ; avec `LIVE=1`, vérifie aussi les listes réelles |
| `recipe_test.php` | Les cas de test (rubrique, rotation, format, appareil, langue, couverture manquante, erreurs, rayon vide), exécutés dans LaraPaper |
| `test_payload.json` | Les listes de littérature figées le 21 octobre 2026, utilisées par les cas de test |
| `update.php` | Copie le code de « BookPoster (test) » dans le plugin installé, sans toucher à ses réglages |
| `install.php` | Première installation : importe la recette comme « BookPoster » |

### Notes

- L'écran est construit en JavaScript au moment du rendu : le moteur de rendu doit exécuter le JavaScript.
- Place des Libraires refuse les requêtes qui ne se présentent pas comme un navigateur, d'où l'en-tête `User-Agent` du polling.
- Les couvertures sont chargées depuis Place des Libraires au moment du rendu ; en e-ink, elles passent en niveaux de gris, tramés par le moteur de rendu.
- Un rafraîchissement toutes les 60 minutes suffit à faire tourner l'affiche, soit trois pages par heure.
- Les données appartiennent à Place des Libraires ; le plugin se contente de les afficher pour un usage personnel.

---

## English

*What's coming out in bookstores, drawn as a poster.*

A TRMNL plugin that puts one book on a poster: the most anticipated books, the ones just out or the next releases in France, with its cover, author, publisher and a countdown to its release. It is part of the Poster suite with [FlightPoster](https://github.com/BartoX360/FlightPoster), [WeatherPoster](https://github.com/BartoX360/WeatherPoster), [GamePoster](https://github.com/BartoX360/GamePoster), [MoviePoster](https://github.com/BartoX360/MoviePoster) and [ShowPoster](https://github.com/BartoX360/ShowPoster), and shares their look.

No server and no key: the lists and covers come from [Place des Libraires](https://www.placedeslibraires.fr), the French independent bookstores' website.

### Features

- **E-ink and color**: a pure black & white style built for 1-bit screens (TRMNL OG, TRMNL X), and a color style for LCD screens
- **Every layout**: full screen, half horizontal, half vertical and quadrant (mashups)
- **Three sections**, each with its own sky color: *Most anticipated* (gold), *Just out* (blue), *Coming soon* (dusk)
- **Rotation**: every hour the poster moves to another section and another of its top three books, or stays on the section you pick
- **Stamp**: the number of days before or since the release
- **Your shelf**: literature, novels, crime, fantasy & SF, romance, comics, manga, kids, humanities, arts, lifestyle, or all books
- **New books only**: paperback reissues are left out unless you want them (always kept for manga)
- **French or English**
- Books without a cover get an open book illustration

### Screenshots

| | |
|---|---|
| ![Color, just out](recipe/screenshots/en-color-rotate-now3.png) | ![Quadrant](recipe/screenshots/en-q.png) |
| Color · just out | Quadrant |

### Installation

**LaraPaper / BYOS**: *Plugins → Import recipe*, pick `recipe/bookposter.zip` (built by `recipe_render.sh`, or zip the contents of `recipe/src/`), then pick your settings.

**TRMNL (usetrmnl.com)**: create a *Private Plugin* with the *Polling* strategy, then copy `polling_url` and `polling_headers`, the `custom_fields`, `shared.liquid` and each layout from `recipe/src/`. The plugin is built and tested on LaraPaper; HTML responses of about 1 MB are not checked on usetrmnl.com.

### Settings

| Field | Purpose |
|---|---|
| Genre | The shelf the books come from (Literature by default) |
| Paperbacks | Without (default) or with paperback reissues |
| Poster | *Rotate* (every hour), or always the most anticipated book, the most wanted just-out book or the next release |
| Language | French or English |
| Style | E-ink (black & white) or Color |

### How it works

The polling URL loads three Place des Libraires search pages, sorted by relevance (the most wanted books first) and filtered on the shelf and the release date. The template keeps only the header of the first sixteen books (title, authors, publisher, date, EAN); the cover is loaded at render time from the EAN.

- *Most anticipated*: the most wanted books coming out within two months
- *Just out*: released in the last two weeks, most wanted first
- *Coming soon*: the next release day, most wanted first, then the following ones (within two weeks)

### Notes

- The screen is built with JavaScript at render time, so the renderer must run JavaScript.
- Place des Libraires turns away requests that don't look like a browser, hence the polling `User-Agent` header.
- A refresh interval of 60 minutes keeps the rotation going, three pages an hour.
- The data belongs to Place des Libraires; the plugin only displays it for personal use.
