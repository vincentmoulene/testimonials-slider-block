---
key: static-vs-dynamic
slug: qr-code-statique-ou-dynamique
title: 'QR code statique ou dynamique : ce que l''abonnement achète vraiment'
description: 'Les QR codes dynamiques sont modifiables et traçables — et ils cessent de fonctionner le jour où vous arrêtez de payer. Comment décider, et comment obtenir la modifiabilité sans l''abonnement.'
date: 2026-08-11
updated: 2026-08-11
tags: [strategie, tracking]
tool: qrcode
---

Tous les services payants de QR codes vendent le même argument phare : *« changez la destination après impression »*. C'est un vrai bénéfice, et il s'accompagne d'une vraie prise d'otage. Comprendre le mécanisme rend la décision évidente.

## Comment chacun fonctionne

Un code **statique** contient la destination elle-même. Scanner `https://exemple.fr/menu`, c'est lire exactement ces caractères dans le motif et les transmettre au navigateur. Aucun serveur intermédiaire. Rien à maintenir.

Un code **dynamique** contient une URL courte appartenant au prestataire — `https://qr.presta.io/a7Xk2` — qui redirige vers votre vraie destination. Le prestataire peut changer cette redirection, et compte chaque scan au passage.

## Ce que le dynamique apporte

- **Destination modifiable.** Imprimer une fois, rediriger plus tard.
- **Statistiques de scan.** Nombre, horodatage, localisation approximative, type d'appareil.
- **Tests A/B et règles d'expiration**, sur les offres avancées.

Pour un catalogue imprimé qui vivra deux ans, ou un tirage d'emballage de 200 000 unités, cette souplesse vaut réellement de l'argent.

## Ce que vous abandonnez

1. **Une dépendance qui survit à la campagne.** Vous arrêtez de payer, la redirection meurt. Chaque code imprimé devient un lien mort — sur un emballage que vous ne pouvez pas rappeler.
2. **Un tiers entre vous et votre public.** Chaque scan transite par le domaine de quelqu'un d'autre, journalisé avec une adresse IP. Dans l'UE, c'est une relation de sous-traitance à documenter.
3. **Un coût de confiance.** Un aperçu de scan qui affiche `qr.presta.io` au lieu de votre domaine ressemble à un lien d'hameçonnage pour qui regarde.
4. **Une fragilité.** Panne du prestataire, saisie de domaine, rachat, hausse de tarif : tous cassent des codes déjà imprimés.

## La troisième option dont personne ne parle

Vous pouvez avoir la modifiabilité *et* l'indépendance, sans abonnement :

1. Créez une redirection sur **votre propre domaine** : `https://votremarque.fr/go/menu`.
2. Encodez cette URL dans un QR code **statique**.
3. Quand la destination change, modifiez votre propre redirection.

Le code imprimé ne change jamais. La redirection, c'est deux lignes dans votre serveur web, une page dans votre CMS ou une redirection gratuite chez l'hébergeur que vous payez déjà. Vous possédez le domaine : personne ne peut le couper. Et si vous voulez compter les scans, votre outil d'analyse enregistre déjà les visites sur cette URL.

La seule chose perdue est le tableau de bord du prestataire — remplacé par des données que vous détenez déjà.

## Une règle de décision simple

| Situation | Choix |
|---|---|
| Destination fixe (Wi-Fi, vCard, téléphone, page permanente) | Statique |
| Campagne courte, destination connue | Statique |
| Impression longue durée, destination susceptible de changer, domaine à vous | Code statique sur votre propre redirection |
| Campagne à grande échelle avec analytique fine par code, sans outils internes | Dynamique, avec un plan de sortie documenté |

## Si vous partez quand même sur du dynamique

Posez trois questions avant de signer :

- Que deviennent mes codes si j'arrête de payer ? (Souvent : ils meurent immédiatement.)
- Puis-je exporter la correspondance entre chaque code et sa destination ?
- Puis-je faire pointer les redirections du prestataire sur un sous-domaine qui m'appartient, afin de pouvoir les reprendre ?

Si la réponse à la troisième est oui, l'essentiel du risque disparaît. Si c'est non, vous louez votre support imprimé.

## À retenir

Les codes dynamiques ne sont pas une arnaque : ils règlent un vrai problème. Mais la plupart du temps, ce problème se règle tout seul avec une redirection sur votre propre domaine et un code statique qui fonctionnera encore dans quinze ans.
