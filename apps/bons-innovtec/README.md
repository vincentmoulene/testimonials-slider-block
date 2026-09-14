# Bons Innovtec

Application web autonome (`index.html`) pour piloter les commandes passées sur
les bons du salon Innovtec (MDA).

## Le format réel du bon

Un bon Innovtec n'est pas une facture : c'est un **catalogue par fournisseur**.
Chaque ligne décrit un produit, et la commande consiste à inscrire des
quantités dans les quatre colonnes de période de livraison.

| Colonne | Contenu |
|---|---|
| FAMILLE / SOUS FAMILLE | segment produit |
| MARQUE | marque commerciale |
| REFERENCE | référence fournisseur |
| DESCRIPTIF | caractéristiques abrégées |
| COMMENTAIRES | promo, ODR, mention « prix promo » |
| ODR | offre de remboursement client, en euros |
| PAF | prix d'achat final, retenu HT |
| PV TTC | prix de vente conseillé |
| Période 1 à 4 | quantités commandées par quinzaine |

Le bas de page porte les **accélérateurs** : paliers de volume déclenchant une
remise par pièce ou un produit offert (« à partir de 6P : 20 € », « pour
10 MWO : 1 MS20A3010AL gratuit », « 10P ENC + POS : 1 EW6FI6834BA offerte »).

## Ce que fait l'application

- **Engagement** : total HT et nombre de pièces par période de livraison,
  répartition par fournisseur, marge potentielle, et distance au palier
  d'accélérateur suivant.
- **Bons** : catalogue complet ou seulement les lignes commandées, saisie des
  quantités période par période, correction du PAF et du PV TTC en place.
- **Contrôles** : marge négative, marge sous 15 %, PAF manquant sur une ligne
  commandée, référence engagée sur plusieurs fournisseurs, PAF divergent pour
  une même référence, palier atteignable à deux pièces près.
- **Import** : PDF (lecture locale via pdf.js), tableau collé depuis Excel, ou
  saisie manuelle. Export CSV au format français.

## Lecture des PDF

Les colonnes sont retrouvées par la **ligne d'en-tête** : le parseur repère les
libellés `REFERENCE`, `MARQUE`, `DESCRIPTIF`, `ODR`, `PAF`, `PV TTC` et
`Période 1` à `Période 4`, mémorise leur abscisse, puis affecte chaque cellule
des lignes suivantes à la colonne la plus proche. Cette approche suit la mise
en page réelle du document plutôt que de deviner le rôle des nombres.

L'onglet « Import & saisie » affiche le texte brut du dernier PDF et les
colonnes reconnues, ce qui permet de diagnostiquer un bon mal lu.

## Marge

`PV HT = PV TTC / 1,2` ; `marge unitaire = PV HT − PAF`. L'ODR n'entre pas dans
le calcul : c'est un remboursement au client final, pas une remise d'achat.

## Stockage

Publiée comme Artifact, l'app utilise la capacité `db` (collection `bons`,
réglages dans `config/profil`). Sinon, repli sur le `localStorage`.
