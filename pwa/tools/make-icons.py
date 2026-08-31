#!/usr/bin/env python3
"""Genere les icones PNG de la PWA sans dependance externe.

Dessin : carre a coins arrondis, degrade diagonal violet -> bleu,
avec une grille 2x2 de pastilles blanches (glyphe "lanceur d'apps").
Rendu en supersampling x3 pour l'anticrenelage, ecriture PNG via zlib.
"""

import os
import struct
import zlib

OUT = os.path.join(os.path.dirname(os.path.abspath(__file__)), "..", "icons")

C1 = (109, 94, 248)   # violet
C2 = (43, 182, 246)   # bleu
SS = 3                # facteur de supersampling


def lerp(a, b, t):
    return a + (b - a) * t


def rounded_box_alpha(x, y, cx, cy, half_w, half_h, radius):
    """1.0 si (x, y) est dans le rectangle arrondi centre sur (cx, cy)."""
    dx = abs(x - cx) - (half_w - radius)
    dy = abs(y - cy) - (half_h - radius)
    if dx <= 0 and dy <= 0:
        return True
    dx = max(dx, 0.0)
    dy = max(dy, 0.0)
    return (dx * dx + dy * dy) <= radius * radius


def render(size, full_bleed=False, glyph_scale=0.56):
    """Retourne un buffer RGBA de cote `size`."""
    s = size * SS
    corner = 0.0 if full_bleed else s * 0.2237  # squircle facon iOS
    # glyphe : grille 2x2 centree
    g_half = s * glyph_scale / 2.0
    gap = g_half * 0.16
    cell = (g_half * 2 - gap) / 2.0
    cell_r = cell * 0.28
    centers = []
    for row in (0, 1):
        for col in (0, 1):
            centers.append((
                s / 2 - g_half + cell / 2 + col * (cell + gap),
                s / 2 - g_half + cell / 2 + row * (cell + gap),
            ))

    # accumulateurs par pixel final
    acc = [[0.0, 0.0, 0.0, 0.0] for _ in range(size * size)]

    for sy in range(s):
        py = sy // SS
        y = sy + 0.5
        for sx in range(s):
            x = sx + 0.5
            inside = True
            if corner > 0:
                inside = rounded_box_alpha(x, y, s / 2, s / 2, s / 2, s / 2, corner)
            if not inside:
                continue
            t = (sx + sy) / float(2 * s)
            r = lerp(C1[0], C2[0], t)
            g = lerp(C1[1], C2[1], t)
            b = lerp(C1[2], C2[2], t)
            for (gx, gy) in centers:
                if rounded_box_alpha(x, y, gx, gy, cell / 2, cell / 2, cell_r):
                    # pastille blanche legerement translucide
                    r = lerp(r, 255, 0.93)
                    g = lerp(g, 255, 0.93)
                    b = lerp(b, 255, 0.93)
                    break
            idx = py * size + (sx // SS)
            a = acc[idx]
            a[0] += r
            a[1] += g
            a[2] += b
            a[3] += 255.0

    n = float(SS * SS)
    raw = bytearray()
    for row in range(size):
        raw.append(0)  # filtre "None"
        for col in range(size):
            a = acc[row * size + col]
            alpha = a[3] / n
            if alpha <= 0.5:
                raw += b"\x00\x00\x00\x00"
                continue
            # les sommes de couleur ne portent que sur les sous-pixels couverts
            cov = a[3] / 255.0
            raw += bytes((
                int(round(a[0] / cov)),
                int(round(a[1] / cov)),
                int(round(a[2] / cov)),
                int(round(alpha)),
            ))
    return bytes(raw)


def chunk(tag, data):
    return (struct.pack(">I", len(data)) + tag + data
            + struct.pack(">I", zlib.crc32(tag + data) & 0xFFFFFFFF))


def write_png(path, size, raw):
    header = struct.pack(">IIBBBBB", size, size, 8, 6, 0, 0, 0)
    png = (b"\x89PNG\r\n\x1a\n"
           + chunk(b"IHDR", header)
           + chunk(b"IDAT", zlib.compress(raw, 9))
           + chunk(b"IEND", b""))
    with open(path, "wb") as fh:
        fh.write(png)
    print("%s (%d octets)" % (os.path.relpath(path), len(png)))


if __name__ == "__main__":
    os.makedirs(OUT, exist_ok=True)
    write_png(os.path.join(OUT, "icon-192.png"), 192, render(192))
    write_png(os.path.join(OUT, "icon-512.png"), 512, render(512))
    # maskable : fond plein bord a bord, glyphe dans la zone sure (80%)
    write_png(os.path.join(OUT, "icon-maskable-512.png"), 512,
              render(512, full_bleed=True, glyph_scale=0.42))
    # iOS applique son propre masque : pas de transparence, pas de coins
    write_png(os.path.join(OUT, "apple-touch-icon.png"), 180,
              render(180, full_bleed=True, glyph_scale=0.52))
