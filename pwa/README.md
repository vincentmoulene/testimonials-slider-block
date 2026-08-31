# pwa.vincentmoulene.fr — lanceur d'applications web

Une PWA installable sur l'écran d'accueil de l'iPhone, qui regroupe toutes vos
applications web derrière une seule icône. 100 % statique : pas de build, pas de
dépendance, pas de serveur applicatif — quatre fichiers et des icônes.

```
pwa/
├── index.html            interface (français, iPhone d'abord)
├── styles.css            thème clair/sombre, encoches et Dynamic Island gérées
├── app.js                logique : liste, recherche, édition, glisser-déposer
├── sw.js                 service worker (fonctionne hors ligne)
├── manifest.webmanifest  nom, icônes, mode plein écran
├── apps.json             liste par défaut, servant d'amorçage
├── icons/                192, 512, maskable 512, apple-touch-icon 180
├── tools/make-icons.py   régénère les icônes (Python seul, sans dépendance)
└── CNAME                 pwa.vincentmoulene.fr (pour GitHub Pages)
```

## Utilisation

- **Ajouter une app** : bouton **+**, ou *Réglages → Ajout rapide* pour coller
  une liste (`Nom | https://…`, ou une URL seule par ligne).
- **Modifier / réordonner / supprimer** : bouton **Modifier**, puis glissez les
  vignettes, touchez le badge **−**, ou touchez une vignette pour l'éditer.
- **Recherche** : filtre sur le nom et l'adresse.
- **Sauvegarde** : *Réglages → Sauvegarde (JSON)*, à copier/coller d'un appareil
  à l'autre. La liste vit dans le stockage local du téléphone ; elle n'est
  envoyée nulle part.
- **Ouverture d'une app** : par défaut dans le lecteur intégré (un bouton *OK*
  ramène au lanceur). Basculez sur « Dans le navigateur » app par app si vous
  préférez sortir vers Safari — utile pour les apps qui doivent elles-mêmes
  poser un raccourci ou utiliser un lecteur de fichiers.

## La liste par défaut

`apps.json` est prérempli avec le hub d'applications de l'app **todo**
(`src/Command/SeedApplicationsCommand.php`), noms, emojis et couleurs compris :

| App | Adresse | Ouverture |
|---|---|---|
| Todo | `todo.vincentmoulene.fr/tasks` | lecteur intégré |
| Compta | `compta.vincentmoulene.fr` | lecteur intégré |
| Ticketing | `ticketing-email.vincentmoulene.fr` | lecteur intégré |
| Review Management | `partagetonavis.fr` | lecteur intégré |
| Attestation irréparabilité | `attestation-irreparabilite.com` | lecteur intégré |
| STL Livraison | `livraison.mdalesrousses.fr` | lecteur intégré |
| MDA Les Rousses | `app.mdalesrousses.fr` | navigateur |
| MDA Lons | `app.mdalons.fr` | navigateur |
| Kinésiologie | `kinesiologieemotionnelle.fr` | lecteur intégré |
| Site perso | `vincentmoulene.fr` | lecteur intégré |

Deux choix à connaître :

- **« Avis MDA Les Rousses » n'y est pas** : le hub la pointe sur `127.0.0.1:8002`,
  une adresse locale au poste de développement, injoignable depuis un téléphone.
  À ajouter le jour où elle aura un domaine.
- **Les deux apps MDA s'ouvrent dans le navigateur**, pas dans le lecteur intégré :
  ce sont elles-mêmes des PWA à installer sur l'écran d'accueil, et Face ID
  (WebAuthn) comme les notifications ne fonctionnent que depuis leur propre icône.
  Le mieux reste de les installer séparément ; la vignette du lanceur ne sert qu'à
  y accéder en attendant.

Pour mettre la liste à jour pour tout le monde (nouvel iPhone, nouvel utilisateur),
modifiez `apps.json` et commitez :

```json
{
  "version": 1,
  "apps": [
    { "name": "Facturation", "url": "https://factures.vincentmoulene.fr", "icon": "🧾", "color": "#4f46e5", "mode": "overlay" },
    { "name": "CRM", "url": "https://crm.vincentmoulene.fr", "icon": "", "color": null, "mode": "overlay" }
  ]
}
```

`icon` accepte un emoji, 1–2 lettres ou une URL d'image ; `color` vaut `null`
pour une couleur déduite du domaine ; `mode` vaut `overlay` ou `browser`.

## Installation sur l'iPhone

1. Ouvrir `https://pwa.vincentmoulene.fr` **dans Safari** (Chrome iOS ne sait pas
   installer de PWA).
2. Bouton **Partager** → **Sur l'écran d'accueil** → **Ajouter**.
3. L'icône apparaît sur l'écran d'accueil ; l'app s'ouvre en plein écran, sans
   barre d'adresse, et fonctionne même sans réseau.

## Mise en ligne

Le dossier `pwa/` est la racine du site. **HTTPS est obligatoire** : sans lui, ni
service worker ni installation sur l'écran d'accueil.

### Option A — GitHub Pages

Le fichier `CNAME` est déjà en place. Ajoutez ce workflow dans
`.github/workflows/deploy-pwa.yml` (il n'est pas versionné ici : pousser un
workflow demande une autorisation supplémentaire sur le jeton) :

```yaml
name: Deploy PWA
on:
  push:
    branches: [master]
    paths: ['pwa/**', '.github/workflows/deploy-pwa.yml']
  workflow_dispatch:
permissions:
  contents: read
  pages: write
  id-token: write
concurrency:
  group: pages
  cancel-in-progress: true
jobs:
  deploy:
    runs-on: ubuntu-latest
    environment:
      name: github-pages
      url: ${{ steps.deployment.outputs.page_url }}
    steps:
      - uses: actions/checkout@v4
      - uses: actions/configure-pages@v5
      - uses: actions/upload-pages-artifact@v3
        with:
          path: pwa
      - id: deployment
        uses: actions/deploy-pages@v4
```

Puis *Settings → Pages → Source: GitHub Actions*, et
*Custom domain : `pwa.vincentmoulene.fr`* avec **Enforce HTTPS** coché.

DNS chez votre registraire :

```
pwa   CNAME   <votre-compte>.github.io.
```

### Option B — Netlify / Vercel / Cloudflare Pages

Aucun build. Répertoire de publication : `pwa`. Commande de build : vide.
Ajoutez ensuite le domaine `pwa.vincentmoulene.fr` dans l'interface de l'hébergeur
et suivez l'enregistrement DNS qu'il indique (le certificat est automatique).

### Option C — Votre propre serveur (OVH, VPS, Apache/nginx)

```bash
rsync -av --delete pwa/ user@serveur:/var/www/pwa.vincentmoulene.fr/
```

VirtualHost pointant sur ce dossier, certificat Let's Encrypt
(`certbot --apache -d pwa.vincentmoulene.fr`), et un DNS `A`/`AAAA` vers le
serveur. Deux réglages à ne pas oublier :

- `sw.js` doit être servi **sans cache long** (`Cache-Control: no-cache`),
  sinon les mises à jour ne descendent pas.
- `manifest.webmanifest` doit sortir en `application/manifest+json`.

Exemple nginx :

```nginx
location = /sw.js { add_header Cache-Control "no-cache"; }
types { application/manifest+json webmanifest; }
```

## Mettre à jour l'app

Modifiez les fichiers, incrémentez `VERSION` dans `sw.js` **et** dans `app.js`,
publiez. Au prochain lancement, le lanceur propose « Mise à jour disponible →
Recharger » (ou *Réglages → Rechercher une mise à jour*).

## Régénérer les icônes

```bash
python3 pwa/tools/make-icons.py
```

Les couleurs du dégradé sont les constantes `C1` / `C2` en haut du script.

## Test local

```bash
python3 -m http.server 8000     # depuis la racine du dépôt
# puis http://localhost:8000/pwa/
```

`localhost` est considéré comme une origine sûre : le service worker s'y active
comme en HTTPS.
