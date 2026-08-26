---
key: qr-code-print-size
slug: taille-impression-qr-code
title: 'Quelle taille pour imprimer un QR code ?'
description: 'La règle du 10:1, les tailles minimales pour une carte de visite, une affiche ou un panneau, et les cinq erreurs qui rendent un QR code imprimé illisible.'
date: 2026-08-04
updated: 2026-08-04
tags: [impression, bonnes-pratiques]
tool: url
---

Un QR code qui ne se scanne pas est pire que pas de QR code du tout : il brûle la confiance de la personne qui a pris la peine de sortir son téléphone. Presque tous les échecs viennent de l'une de ces cinq causes, et la taille arrive en premier.

## La règle du 10:1

La règle de travail des professionnels de l'impression est simple :

> **Le code doit mesurer au moins un dixième de la distance à laquelle il sera scanné.**

| Distance de scan | Taille minimale du code | Support typique |
|---|---|---|
| 20 cm | 2 cm | carte de visite, étiquette produit |
| 50 cm | 5 cm | flyer, menu, emballage |
| 1,5 m | 15 cm | vitrine, affiche dans un couloir |
| 5 m | 50 cm | affiche murale, stand de salon |
| 20 m | 2 m | panneau 4x3, façade |

La règle est volontairement prudente. Un téléphone récent fera mieux en bonne lumière avec une URL courte — et bien pire dans un restaurant sombre, sur une impression tachée ou avec un milieu de gamme de deux ans. Concevez pour le pire cas réaliste, pas pour votre propre téléphone.

## Minimum absolu : 2 cm

En dessous de 2 × 2 cm, l'offset ou le jet d'encre ordinaire commence à faire baver les plus petits modules les uns dans les autres. Si vous devez descendre plus bas, réduisez la quantité de données encodées : une URL de 25 caractères produit un motif bien plus grossier — donc plus robuste — qu'une URL de 120 caractères bourrée de paramètres de tracking.

## La zone de silence n'est pas une option

La marge blanche autour du code — la *zone de silence* — est ce qui indique au lecteur où s'arrête le motif. La norme demande quatre modules ; en pratique, gardez une bande blanche à peu près aussi large que l'un des trois grands carrés d'angle.

L'erreur de mise en page la plus fréquente consiste à coller le code au bord d'un aplat de couleur, ou à laisser un texte venir le toucher. Les deux vous coûtent des scans.

## Contraste : sombre sur clair, jamais l'inverse

Les lecteurs attendent des modules sombres sur fond clair. Un code inversé (blanc sur noir) échoue sur une part non négligeable des appareils. Les codes colorés fonctionnent tant que le rapport de contraste reste élevé : un bleu foncé ou un vert profond sur blanc, oui ; un jaune pastel sur crème, non.

Si vous imprimez sur un support coloré ou texturé, ajoutez un rectangle blanc uni derrière le code. C'est moins élégant sur la maquette, et c'est la différence entre une campagne qui fonctionne et une qui échoue.

## Correction d'erreurs : M pour l'écran, H pour le terrain

Le niveau de correction détermine la part du motif qui peut être détruite tout en restant lisible : 7 % (L), 15 % (M), 25 % (Q) ou 30 % (H). Plus de correction signifie un motif plus dense à contenu égal : c'est un arbitrage, pas une amélioration gratuite.

- **M** — le choix par défaut raisonnable pour l'écran, les PDF et une impression propre.
- **Q ou H** — en extérieur, sur un emballage manipulé, sur du textile, sur toute surface courbe, et dès qu'un logo est posé sur le code.

## Imprimez toujours depuis un fichier vectoriel

Exportez le SVG, pas le PNG. Une image matricielle redimensionnée par l'imprimeur produit des bords de modules flous, et ce flou est exactement ce qui casse le décodage en petite taille. Le SVG reste mathématiquement net à 2 cm comme à 2 m, et toutes les chaînes d'impression professionnelles l'acceptent.

## Le test de cinq minutes qui sauve un tirage

Avant de valider le fichier :

1. Imprimez la maquette à la taille finale, sur le vrai support.
2. Scannez avec un iPhone et un Android, à la distance réelle.
3. Scannez dans l'éclairage du lieu d'usage — un bar tamisé n'est pas un bureau.
4. Scannez de biais, pas seulement de face.
5. Faites essayer quelqu'un qui n'a jamais vu le code, sans consigne.

Si les cinq passent, vous pouvez tirer à dix mille exemplaires en confiance. Si un seul échoue, vous venez d'éviter un retirage.
