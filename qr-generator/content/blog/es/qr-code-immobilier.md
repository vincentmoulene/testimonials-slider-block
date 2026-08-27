---
key: use-case-real-estate
slug: codigo-qr-inmobiliaria
title: 'Código QR inmobiliario: el cartel de «Se vende» que enseña el piso'
description: 'Carteles, escaparate de la agencia, dosier de alquiler, entregas: cómo un código QR cualifica a los interesados antes incluso de la primera llamada.'
date: 2026-08-04
updated: 2026-08-04
tags: [inmobiliaria, casos-de-uso]
tool: qrcode
---

Un cartel de «Se vende» da un número de teléfono. Quien pasa lo apunta, o más probablemente no. Con un código QR ve las fotos, el precio, los metros y el certificado energético antes de llamarte — y cuando llama, ya sabe lo que quiere.

## El cartel: cualificar antes de la primera llamada

El código debe abrir **el anuncio de ese inmueble**, no la web de la agencia. Fotos, precio, superficie, certificados, plano. Quien llame después ha pasado el primer filtro por su cuenta.

Limitaciones prácticas del cartel:

- Se escanea desde la acera, a menudo desde un coche parado: cuenta **15 cm de ancho como mínimo** para escanear a 1,5 m, más si el cartel está en alto.
- El cartel pasa meses a la intemperie: impresión resistente a los UV, o el contraste se apaga y el código muere.
- Prevé el caso «vendido»: la página debe decirlo y ofrecer inmuebles similares, no devolver un error 404.

## El escaparate de la agencia

El momento real es el domingo: la agencia está cerrada y la gente mira igual. Un código por anuncio en el escaparate, o uno único hacia los inmuebles de la zona, capta a un público que si no no habría hecho nada.

## El dosier de alquiler

Un código en el anuncio que abre la lista de documentos y el formulario de solicitud. Recibes expedientes completos en lugar de veinte correos con adjuntos que faltan.

## Entregas y gestión

Poco vistoso, muy útil:

- Un código en el cuarto de contadores que abre la ficha del equipo, la fecha del último mantenimiento y el contacto del técnico.
- Un código dentro de un alquiler vacacional que abre la guía de bienvenida.
- Un código en el tablón de la comunidad hacia las actas de la última junta.

## Tres errores que cuestan contactos

1. **Enlazar a la portada de la agencia.** El interesado quería *ese* inmueble; no va a buscar entre doscientos anuncios.
2. **Codificar una URL con una referencia interna** del tipo `?ref=A12345&session=…`. Cambia con la próxima migración de software y el código impreso muere con ella.
3. **Olvidar la medición.** Añade `?utm_source=cartel`: en un trimestre sabrás si los carteles valen lo que cuestan.

## Lo que hay que recordar

En inmobiliaria el código QR no vende: elimina el retraso entre el interés y la información. Y en este oficio, el retraso es justo lo que hace perder contactos.
