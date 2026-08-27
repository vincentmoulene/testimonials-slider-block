---
key: use-case-real-estate
slug: qr-code-immobiliare
title: 'QR code immobiliare: il cartello «Vendesi» che fa la visita'
description: 'Cartello, vetrina d''agenzia, dossier di affitto, consegne: come un QR code qualifica i contatti immobiliari ancora prima della prima telefonata.'
date: 2026-08-04
updated: 2026-08-04
tags: [immobiliare, casi-uso]
tool: qrcode
---

Un cartello «Vendesi» dà un numero di telefono. Chi passa lo annota, o più probabilmente no. Con un QR code vede foto, prezzo, metratura e classe energetica prima ancora di chiamarti — e quando chiama sa già cosa vuole.

## Il cartello: qualificare prima della prima chiamata

Il codice deve aprire **l'annuncio di quell'immobile**, non il sito dell'agenzia. Foto, prezzo, superficie, certificazioni, planimetria. Chi chiama dopo ha superato il primo filtro da solo.

Vincoli pratici del cartello:

- Si scansiona dal marciapiede, spesso da un'auto ferma: conta **almeno 15 cm di larghezza** per una scansione a 1,5 m, di più se il cartello è in alto.
- Il cartello resta fuori per mesi: stampa resistente ai raggi UV, altrimenti il contrasto sbiadisce e il codice muore.
- Prevedi il caso «venduto»: la pagina deve dirlo e proporre immobili simili, non restituire un errore 404.

## La vetrina dell'agenzia

Il momento vero è la domenica: l'agenzia è chiusa e la gente guarda lo stesso. Un codice per annuncio in vetrina, o un codice unico verso gli immobili della zona, intercetta un pubblico che altrimenti non avrebbe fatto nulla.

## Il dossier di affitto

Un codice sull'annuncio che apre l'elenco dei documenti richiesti e il modulo di candidatura. Ricevi pratiche complete invece di venti e-mail con allegati mancanti.

## Consegne e gestione

Poco spettacolare, molto utile:

- Un codice nel vano contatori che apre la scheda dell'impianto, la data dell'ultima manutenzione e il contatto del tecnico.
- Un codice dentro una casa vacanze che apre la guida di benvenuto.
- Un codice sulla bacheca condominiale verso i verbali dell'ultima assemblea.

## Tre errori che costano contatti

1. **Rimandare alla home dell'agenzia.** L'interessato voleva *quell'*immobile: non andrà a cercarlo fra duecento annunci.
2. **Codificare un URL con un riferimento interno** tipo `?ref=A12345&session=…`. Cambia alla prossima migrazione software e il codice stampato muore con lui.
3. **Dimenticare la misurazione.** Aggiungi `?utm_source=cartello`: dopo un trimestre sai se i cartelli valgono quello che costano.

## Cosa ricordare

Nell'immobiliare il QR code non vende: elimina il ritardo tra l'interesse e l'informazione. E in questo mestiere il ritardo è proprio ciò che fa perdere i contatti.
