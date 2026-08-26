---
title: 'À propos de ce générateur'
description: 'Qui est derrière ce générateur gratuit de QR codes et de codes-barres, comment il est financé et pourquoi les codes n''expirent jamais.'
---

## Un outil qui reste gratuit

Ce site fait une chose : transformer vos données en un code téléchargeable et utilisable immédiatement, sans compte. Pas de période d'essai, pas d'offre « premium » qui cache les fonctions utiles, pas de filigrane sur la version gratuite.

## Comment il est financé

Par la publicité, et rien d'autre. Les annonces ne sont chargées qu'après votre accord, et le site fonctionne exactement de la même façon si vous refusez. Nous ne vendons pas de données, nous n'insérons aucune redirection d'affiliation dans vos codes et nous ne plaçons jamais de domaine de tracking entre votre code et sa destination.

## Pourquoi uniquement des codes statiques

Tous les codes générés ici sont **statiques** : le contenu est inscrit dans le motif lui-même. Cela signifie que :

- le code fonctionne indéfiniment, même hors ligne, même si ce site disparaît ;
- personne ne peut le désactiver, le limiter ou vous le facturer au mois ;
- aucun tiers — nous compris — ne sait qui le scanne, ni quand.

La contrepartie : vous ne pouvez pas changer la destination après coup. Si vous prévoyez de la modifier, encodez une URL que vous maîtrisez et changez la redirection sur votre propre serveur.

## Choix techniques

Le site est une application Symfony rendue côté serveur, avec une fine couche JavaScript pour l'aperçu en direct. Les codes sont générés en mémoire et renvoyés directement à votre navigateur : rien n'est écrit sur disque, rien n'est journalisé, rien n'est conservé.

## Contact

Une question, un bug, une symbologie que vous aimeriez voir ajoutée ? Écrivez-nous, nous lisons tout.
