# Bons Innovtec

Application web autonome (un seul fichier, `index.html`) pour dépouiller les
bons de commande PDF du salon Innovtec reçus par mail
(`diffusion@reporting.mda-company.com`, objet « Votre commande INNOVTEC Num … »).

## Ce qu'elle fait

- **Import PDF** : glisser-déposer d'un ou plusieurs bons. La lecture se fait
  dans le navigateur (pdf.js), aucun fichier n'est envoyé ailleurs.
- **Extraction des lignes** : référence, désignation, quantité, prix unitaire HT,
  remise, montant HT. La quantité, le PU et le total sont identifiés par
  cohérence arithmétique (`qté × PU = total`) et non par position de colonne,
  ce qui rend la lecture indépendante de la mise en page exacte du bon.
  Les codes à barres (8 chiffres et plus) sont exclus des montants candidats.
- **Achats du jour** : total HT de la journée, répartition par fournisseur,
  détail bon par bon, quantités et prix corrigeables à la main.
- **Contrôles** : montants incohérents, même référence sur plusieurs bons
  (double commande), écart de prix supérieur à 2 % sur une même référence,
  quantités ≥ 10, bons dont aucune ligne n'a pu être lue.
- **Export CSV** français (séparateur `;`, virgule décimale, BOM pour Excel).
- **Calibrage** : le texte brut du dernier PDF est affiché, et les repères de
  lecture (numéro de bon, date, fournisseur, magasin, format des références)
  sont modifiables sans toucher au code. Saisie manuelle d'un bon également
  possible.

## Stockage

Publiée comme Artifact, l'app utilise la capacité `db` : les bons sont
conservés d'une session à l'autre (collection `bons`, réglages dans
`config/profil`). Hors de ce contexte, elle retombe sur le `localStorage`
du navigateur. Rien n'est perdu si la base n'est pas disponible.

## Calibrage sur un vrai bon

Les repères par défaut sont génériques. Après le premier import, ouvrir
« Calibrage & saisie » : si le numéro, la date ou le fournisseur sont mal lus,
ajuster l'expression correspondante puis « Relire le dernier PDF ».
