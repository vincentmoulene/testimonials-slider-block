---
key: qr-code-print-size
slug: tamano-impresion-codigo-qr
title: '¿A qué tamaño se imprime un código QR?'
description: 'La regla 10:1, los tamaños mínimos para una tarjeta, un cartel o una valla, y los cinco errores que hacen ilegible un código QR impreso.'
date: 2026-08-04
updated: 2026-08-04
tags: [impresion, buenas-practicas]
tool: url
---

Un código QR que no se escanea es peor que no poner ninguno: quema la confianza de quien se ha molestado en sacar el teléfono. Casi todos los fallos vienen de una de estas cinco causas, y el tamaño es la primera.

## La regla 10:1

La regla que usan los profesionales de la impresión es simple:

> **El código debe medir al menos una décima parte de la distancia desde la que se va a escanear.**

| Distancia de escaneo | Tamaño mínimo | Soporte habitual |
|---|---|---|
| 20 cm | 2 cm | tarjeta de visita, etiqueta |
| 50 cm | 5 cm | folleto, carta, envase |
| 1,5 m | 15 cm | escaparate, cartel de pasillo |
| 5 m | 50 cm | cartel de pared, estand |
| 20 m | 2 m | valla publicitaria, fachada |

La regla es deliberadamente conservadora. Un teléfono reciente hará más con buena luz y una URL corta, y bastante menos en un restaurante en penumbra o con una impresión manchada. Diseña para el peor caso realista, no para tu propio móvil.

## Mínimo absoluto: 2 cm

Por debajo de 2 × 2 cm, la impresión offset o de inyección corriente empieza a fundir entre sí los módulos más pequeños. Si tienes que bajar más, reduce los datos codificados: una URL de 25 caracteres genera un patrón mucho más grueso —y por tanto más robusto— que una de 120 con parámetros de seguimiento.

## La zona de silencio no es opcional

El margen blanco alrededor del código indica al lector dónde termina el patrón. La norma pide cuatro módulos; en la práctica, deja una banda blanca aproximadamente tan ancha como uno de los tres cuadrados de las esquinas. Pegar el código al borde de una mancha de color es el error de maquetación más frecuente.

## Contraste: oscuro sobre claro, nunca al revés

Los lectores esperan módulos oscuros sobre fondo claro. Un código invertido falla en una parte significativa de los dispositivos. Los códigos de color funcionan mientras el contraste sea alto: azul marino o verde intenso sobre blanco, sí; amarillo pastel sobre crema, no. Sobre material de color o texturado, coloca un rectángulo blanco detrás.

## Corrección de errores: M para pantalla, H para la calle

El nivel de corrección define qué parte del patrón puede destruirse sin perder la lectura: 7 % (L), 15 % (M), 25 % (Q) o 30 % (H). Más corrección implica un patrón más denso con el mismo contenido.

- **M** — opción sensata para pantallas, PDF e impresión limpia.
- **Q o H** — exteriores, envases manipulados, textil, superficies curvas y siempre que haya un logotipo encima.

## Imprime siempre desde un archivo vectorial

Exporta el SVG, no el PNG. Un mapa de bits reescalado por la imprenta produce bordes de módulo difusos, y ese difuminado es justo lo que rompe la decodificación en tamaños pequeños.

## La prueba de cinco minutos que salva una tirada

1. Imprime a tamaño final sobre el material real.
2. Escanea con un iPhone y con un Android, a la distancia real.
3. Escanea con la iluminación del lugar de uso.
4. Escanea en ángulo, no solo de frente.
5. Que lo pruebe alguien que no lo haya visto nunca, sin instrucciones.

Si pasa las cinco, puedes imprimir diez mil ejemplares con tranquilidad.
