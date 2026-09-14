# Guichet Support

Application de ticketing par email : les courriels reçus sur les boîtes partagées
deviennent des tickets suivis, assignés et mesurés contre un SLA.

## Contenu

- `guichet-support.html` — l'application entière (un seul fichier : HTML, CSS, JS,
  sans dépendance hors les polices Google). Publiée comme Artifact Claude, elle
  utilise la capacité `db` pour stocker les tickets côté serveur et les partager
  en temps réel entre les personnes qui ouvrent la page.
- `donnees-exemple/` — douze tickets d'amorçage, écrits dans la base de l'Artifact.
  Le fichier HTML ne contient aucune donnée métier ; sans base accessible, il
  bascule en mode démonstration avec trois exemples explicitement signalés.

## Fonctionnement

**Statuts** — Nouveau, En cours, En attente client, Résolu, Fermé.

**Priorités et SLA de première réponse** — Urgente 1 h, Haute 4 h, Normale 8 h,
Basse 24 h. Le délai court depuis la réception du courriel jusqu'au premier
message sortant ; la liste et le détail affichent le temps restant, le retard,
ou le fait que l'objectif a été tenu.

**Files** — tous les ouverts, mes tickets, non assignés, en retard SLA, historique
complet ; plus un filtrage par statut et par adresse de réception.

**Fil de conversation** — messages entrants, réponses sortantes et notes internes
(visibles de l'équipe seulement). Cinq réponses types insérables, avec
substitution de `{{ref}}`, `{{agent}}` et `{{sla}}`.

**Tableau de bord** — flux entrant sur quatorze jours, répartition par statut,
volume par adresse, charge par agent avec délai moyen de première réponse,
étiquettes récurrentes.

## Adapter

Le référentiel se trouve en tête du bloc `<script>` : `STATUTS`, `PRIORITES`
(dont la durée de SLA), `BOITES`, `AGENTS`, `MOI` et `MACROS`.
