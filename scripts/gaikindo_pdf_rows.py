#!/usr/bin/env python3
"""
Rekonstruksi baris PDF wholesales GAIKINDO (pdfplumber) — v3 grid-line.

Layout cetak GAIKINDO (berlaku lintas tahun):
- Garis GRID horizontal membatasi setiap baris cetakan secara eksak
  (±470 boundary y per halaman, pitch ~1-6pt).
- Tick vertikal berulang pada x tetap = batas kolom; kolom JAN..DEC menerus
  menembus semua kategori, dan header bulan terulang per kategori.
- Baris model = SATU band: brand+model+specs di kiri, 12 nilai bulanan di
  kanan (kerning spasi dirapikan per kolom), YTD/tahunan di kolom setelah DEC.
- Rekap resmi (DOMESTIC/PASSENGER/COMMERCIAL) = band biasa; band CUMULATIVE
  diabaikan di sisi PHP.

Output JSON ke stdout:
  {"file": "...", "pages": [
      {"index": 1, "month_lo": 472.0,
       "month_bounds": [{"m": 1, "lo": 472.0, "hi": 492.0}, ...],
       "rows": [
          {"top": 42.4,
           "left_cells": [{"x": 152.5, "text": "DAIHATSU"}, ...],
           "months": {"1": 4123, "2": 3604, ...},   // null = kosong
           "ytd": 24801}                            // null = kolom tidak ada
      ]}
  ]}
"""

import json
import re
import sys
from bisect import bisect_right
from collections import defaultdict

import pdfplumber

LINE_TOLERANCE = 1.2
CELL_GAP = 2.5
MONTH_PREFIX = {
    "JAN": 1, "JANUARY": 1, "JANUARI": 1, "FEB": 2, "FEBRUARY": 2, "FEBRUARI": 2,
    "MAR": 3, "MARCH": 3, "MARET": 3, "APR": 4, "APRIL": 4, "MAY": 5, "MEI": 5,
    "JUN": 6, "JUNE": 6, "JUNI": 6, "JUL": 7, "JULY": 7, "JULI": 7,
    "AUG": 8, "AGU": 8, "AUGUST": 8, "AGUSTUS": 8, "SEP": 9, "SEPT": 9,
    "SEPTEMBER": 9, "OCT": 10, "OKT": 10, "OCTOBER": 10, "OKTOBER": 10,
    "NOV": 11, "NOVEMBER": 11, "DEC": 12, "DES": 12, "DECEMBER": 12, "DESEMBER": 12,
}


def cluster_lines(chars):
    lines = []
    last_top = None
    for c in sorted(chars, key=lambda c: (c["top"], c["x0"])):
        if last_top is None or c["top"] - last_top > LINE_TOLERANCE:
            lines.append({"top": c["top"], "chars": [c]})
        else:
            lines[-1]["chars"].append(c)
            last_top = c["top"]
    return lines


def to_cells(line):
    cells = []
    for c in sorted(line["chars"], key=lambda c: c["x0"]):
        if cells and c["x0"] - cells[-1]["x1"] <= CELL_GAP:
            cells[-1]["text"] += c["text"]
            cells[-1]["x1"] = max(cells[-1]["x1"], c["x1"])
        else:
            cells.append({"x": c["x0"], "x1": c["x1"], "text": c["text"].strip()})
    return [c for c in cells if c["text"] != ""]


def grid_lines(page):
    """Boundary y (horizontal) & x (vertikal) dari edge, dikluster 1pt."""
    hy = sorted({round(e["top"]) for e in page.edges if e["orientation"] == "h"})
    vx = sorted({round(e["x0"]) for e in page.edges if e["orientation"] == "v"})
    return hy, vx


def find_month_band(page_rows, vx):
    """Cari band header yang memuat ≥4 token bulan; kembalikan interval kolom
    bulan {bulan: (lo, hi)} memakai boundary vx di sekitar tiap token."""
    best = None
    for row in page_rows:
        months = {}
        for cell in row["cells"]:
            key = cell["text"].upper()
            if key in MONTH_PREFIX and len(cell["text"]) <= 9:
                m = MONTH_PREFIX[key]
                i = bisect_right(vx, cell["x"])
                if i > 0:
                    lo, hi = vx[i - 1], vx[i] if i < len(vx) else cell["x1"] + 30
                    months[m] = (lo, hi)
        if len(months) >= 4:
            keys = sorted(months)
            if keys[0] == 1 and keys == list(range(keys[0], keys[0] + len(keys))):
                if best is None or len(months) > len(best[1]):
                    best = (row, months)
    return best


def month_intervals(vx, month_cols):
    """Ubah peta bulan→(lo,hi) menjadi interval kontigu berurutan."""
    out = []
    for m in sorted(month_cols):
        lo, hi = month_cols[m]
        out.append({"m": m, "lo": float(lo), "hi": float(hi)})
    return out


def main():
    if len(sys.argv) != 2:
        print("usage: gaikindo_pdf_rows.py <file.pdf>", file=sys.stderr)
        sys.exit(2)

    path = sys.argv[1]
    out = {"file": path, "pages": []}

    with pdfplumber.open(path) as pdf:
        for index, page in enumerate(pdf.pages, start=1):
            hy, vx = grid_lines(page)
            lines = cluster_lines(page.chars)

            # tempatkan tiap line ke band grid (antara dua boundary y berurutan)
            bands = defaultdict(list)
            for line in lines:
                i = bisect_right(hy, line["top"])
                lo = hy[i - 1] if i > 0 else 0
                hi = hy[i] if i < len(hy) else line["top"] + 2
                bands[(lo, hi)].append(line)

            page_rows = []
            for (lo, hi), band_lines in bands.items():
                cells = []
                for line in band_lines:
                    for cell in to_cells(line):
                        cells.append(cell)
                cells.sort(key=lambda c: c["x"])
                page_rows.append({"top": float(lo), "cells": cells})

            month_band = find_month_band(page_rows, vx)
            month_bounds = []
            if month_band:
                _, month_cols = month_band
                month_bounds = month_intervals(vx, month_cols)

            rows_out = []
            for row in page_rows:
                left_cells = [{"x": round(c["x"], 1), "text": c["text"]}
                              for c in row["cells"] if month_bounds and c["x1"] <= month_bounds[0]["lo"]]
                months = None
                ytd = None
                if month_bounds:
                    months = {}
                    for b in month_bounds:
                        seg = "".join(c["text"] for c in row["cells"] if b["lo"] <= c["x"] < b["hi"]).strip()
                        seg = re.sub(r"[\s]", "", seg)
                        if seg == "" or set(seg) <= {"-", "–"}:
                            months[str(b["m"])] = None
                            continue
                        clean = re.sub(r"[%]", "", seg)
                        if re.fullmatch(r"\d{1,3}(?:[.,]\d{3})+|\d+", clean):
                            months[str(b["m"])] = int(re.sub(r"[.,]", "", clean))
                        else:
                            months[str(b["m"])] = None
                    if month_bounds:
                        last = month_bounds[-1]["hi"]
                        ytd_seg = "".join(c["text"] for c in row["cells"] if c["x"] >= last).strip()
                        ytd_seg = re.sub(r"[%\s]", "", ytd_seg)
                        m = re.search(r"\d{1,3}(?:[.,]\d{3})+|\d+", ytd_seg)
                        ytd = int(re.sub(r"[.,]", "", m.group())) if m else None
                if left_cells or months or ytd is not None:
                    rows_out.append({"top": round(row["top"], 1), "left_cells": left_cells,
                                     "months": months, "ytd": ytd})

            out["pages"].append({"index": index,
                                 "month_lo": month_bounds[0]["lo"] if month_bounds else None,
                                 "month_bounds": month_bounds,
                                 "rows": rows_out})

    json.dump(out, sys.stdout, ensure_ascii=False)


if __name__ == "__main__":
    main()
