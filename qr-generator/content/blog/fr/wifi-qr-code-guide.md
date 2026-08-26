---
key: wifi-qr-code-guide
slug: guide-qr-code-wifi
title: 'QR code Wi-Fi : le guide complet pour hôtes, cafés et bureaux'
description: 'Comment fonctionne un QR code Wi-Fi, quels téléphones le gèrent, les erreurs de SSID qui le cassent, et pourquoi il vaut mieux qu''un mot de passe écrit sur une ardoise.'
date: 2026-08-18
updated: 2026-08-18
tags: [wifi, guides]
tool: wifi
---

Si vous dictez encore un mot de passe Wi-Fi de vingt caractères plusieurs fois par jour, vous faites à la main ce qu'un bout de papier fait mieux. Un QR code Wi-Fi se crée en trente secondes et supprime la corvée définitivement.

## Ce qu'il y a vraiment dans le code

Rien de magique — une seule ligne de texte suivant une convention que tous les appareils photo comprennent :

```
WIFI:T:WPA;S:MonReseau;P:mon-mot-de-passe;;
```

`T` est le type de sécurité, `S` le nom du réseau, `P` le mot de passe. C'est tout le standard. Le téléphone l'analyse, reconnaît une configuration réseau et propose de se connecter.

Comme il s'agit de texte brut, le mot de passe **est** lisible par quiconque décode l'image avec un lecteur de QR. Un code Wi-Fi protège des fautes de frappe et des moments gênants, pas d'un invité déterminé. Ce n'est pas un problème : il allait obtenir le mot de passe de toute façon.

## Quels appareils le gèrent

- **iPhone / iPad** : nativement depuis l'appareil photo, à partir d'iOS 11 (2017).
- **Android** : nativement depuis Android 10 (2019). Android 11 et suivants savent aussi *générer* le code du réseau courant, dans Paramètres → Réseau → Partager.
- **Android plus ancien** : nécessite une application de lecture compatible Wi-Fi. La plupart des lecteurs gratuits le sont.

En pratique, en 2026, quasiment tous les téléphones qui entrent dans un café savent l'utiliser.

## Les quatre erreurs qui le cassent

1. **Mauvaise casse dans le SSID.** `MonReseau` et `monreseau` sont deux réseaux différents pour le téléphone. Recopiez le nom exactement tel qu'il apparaît dans l'interface du routeur.
2. **Caractères spéciaux non échappés.** Un point-virgule, deux-points, une virgule ou un antislash dans le mot de passe doivent être échappés dans la charge utile. Un générateur s'en occupe ; écrire la chaîne à la main, rarement.
3. **Cocher « réseau masqué » alors que le SSID est diffusé.** Certains téléphones refusent alors purement et simplement la connexion. Ne cochez que si le SSID n'est réellement pas diffusé.
4. **Choisir le mauvais type de sécurité.** Presque tous les routeurs modernes sont en WPA2 ou WPA3 — les deux utilisent le réglage `WPA`. `WEP` ne concerne que du matériel assez ancien pour être un problème de sécurité en soi. `nopass` est réservé aux réseaux réellement ouverts.

## Où le placer

- **Locations saisonnières et hôtels** : carte plastifiée sur le bureau, plus une dans le livret d'accueil.
- **Cafés et restaurants** : sur le chevalet de table ou au dos du menu, pas seulement au comptoir.
- **Bureaux** : à côté de l'écran de la salle de réunion — les visiteurs se connectent sans déranger personne.
- **Salles d'attente** : sur l'affiche qui annonce déjà le temps d'attente.

Imprimez-le à au moins 5 cm de large, en noir sur blanc, avec une ligne de texte : *« Scannez pour vous connecter au Wi-Fi »*. Tout le monde ne connaît pas encore l'astuce.

## Le réflexe réseau invité

La bonne pratique de sécurité n'est pas de cacher le mot de passe, c'est de séparer les réseaux. Placez les invités sur le SSID invité de votre routeur (presque tous les modèles en ont un), générez le code pour ce réseau, et gardez vos appareils, NAS et imprimantes sur le principal. Le code ne vous coûte alors rien en sécurité, et vous épargne une conversation chaque jour.

## Changer le mot de passe

Si vous modifiez le mot de passe Wi-Fi, le code imprimé cesse de fonctionner : il contient l'ancien. Deux habitudes rendent cela indolore : conservez le fichier source (SVG) avec votre documentation réseau, et régénérez puis réimprimez la carte le jour même du changement. C'est l'affaire d'une minute.
