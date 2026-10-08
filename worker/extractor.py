import os
import re
from pathlib import Path

# Pemetaan karakter Private Use Area (PUA) dari font Symbol / Wingdings ke Unicode standar
PUA_SYMBOL_MAP = {
    0xF0B7: '•',   # Bullet
    0xF0A7: '▪',   # Black small square
    0xF0A8: '▫',   # White small square
    0xF0D8: '➢',   # Arrow pointer
    0xF0FC: '✔',   # Checkmark
    0xF0FB: '✗',   # Cross
    0xF0E0: '→',   # Right arrow
    0xF0DF: '⇔',   # Left-right double arrow
    0xF0DE: '⇒',   # Right double arrow
    0xF0B1: '±',   # Plus-minus
    0xF0B4: '×',   # Multiplication
    0xF0F7: '÷',   # Division
    0xF0A3: '≤',   # Less than or equal
    0xF0B3: '≥',   # Greater than or equal
    0xF0B9: '≠',   # Not equal
    0xF0BB: '≈',   # Almost equal
    0xF0A5: '∞',   # Infinity
    0xF0E5: '∑',   # Summation
    0xF0F2: '∫',   # Integral
    0xF0D6: '√',   # Square root
    0xF061: 'α',   # alpha
    0xF062: 'β',   # beta
    0xF067: 'γ',   # gamma
    0xF064: 'δ',   # delta
    0xF065: 'ε',   # epsilon
    0xF06C: 'λ',   # lambda
    0xF06D: 'μ',   # mu
    0xF070: 'π',   # pi
    0xF073: 'σ',   # sigma
    0xF071: 'θ',   # theta
    0xF077: 'ω',   # omega
    0xF044: 'Δ',   # Delta
    0xF053: 'Σ',   # Sigma
    0xF050: 'Π',   # Pi
    0xF057: 'Ω',   # Omega
}

def clean_extracted_text(text: str) -> str:
    """
    Membersihkan teks hasil ekstraksi dari karakter PUA (Private Use Area),
    mengonversi simbol/bullet Word ke Unicode resmi, dan mendeteksi blok
    kaligrafi non-Latin (seperti font HQPB Quran in Word) serta merapikan baris rumus.
    """
    if not text:
        return ''

    lines = text.split('\n')
    cleaned_lines = []
    in_pua_block = False

    for line in lines:
        stripped = line.strip()
        pua_chars = [c for c in stripped if 0xE000 <= ord(c) <= 0xF8FF]
        latin_chars = [c for c in stripped if c.isascii() and c.isalnum()]

        # Baris merupakan blok naskah non-Latin / kaligrafi khusus (seperti font HQPB Quran in Word)
        # jika terdiri dari karakter PUA tanpa teks Latin/angka alfanumerik biasa
        if len(pua_chars) >= 3 and len(latin_chars) == 0:
            if not in_pua_block:
                cleaned_lines.append('\n[📜 Naskah Kaligrafi / Teks Arab (Font Khusus HQPB)]\n')
                in_pua_block = True
            continue
        else:
            in_pua_block = False

        # Konversi karakter simbol PUA yang tersisa (bullet, panah, operator rumus)
        row_chars = []
        for c in line:
            code = ord(c)
            if code in PUA_SYMBOL_MAP:
                row_chars.append(PUA_SYMBOL_MAP[code])
            elif 0xE000 <= code <= 0xF8FF:
                # Abaikan karakter PUA liar agar tidak menjadi kotak tofu
                continue
            else:
                row_chars.append(c)
        cleaned_lines.append(''.join(row_chars))

    result = '\n'.join(cleaned_lines)

    # Runtuhkan duplikasi marker naskah kaligrafi berturut-turut
    result = re.sub(r'(\[📜 Naskah Kaligrafi / Teks Arab \(Font Khusus HQPB\)\]\s*)+', r'[📜 Naskah Kaligrafi / Teks Arab (Font Khusus HQPB)]\n\n', result)

    # Deteksi dan rapikan urutan sumbu grafik/diagram (misal: 0%\n10%\n20%... Tahap 1 Tahap 2)
    chart_axis_pattern = re.compile(
        r'(?:^|\n)[ \t]*(?:\d{1,3}%\s*\n){3,}'
        r'(?:[ \t]*(?:Tahap|Tahun|Kelas|Kategori|Nilai|\d+)[ \t]*\n*)*'
        r'(?:[ \t]*(?:Perbedaan|Grafik|Hasil|Diagram)[^\n]+\n*)?',
        re.MULTILINE
    )

    def _replace_chart_axis(m):
        raw = m.group(0)
        pcts = re.findall(r'\d{1,3}%', raw)
        min_p = pcts[0] if pcts else '0%'
        max_p = pcts[-1] if pcts else '100%'
        title_m = re.search(r'(?:Perbedaan|Grafik|Hasil|Diagram)[^\n]+', raw)
        title = title_m.group(0).strip() if title_m else 'Grafik / Diagram Batang'
        return f'\n\n[📊 Elemen Grafik: {title} (Skala {min_p} – {max_p})]\n\n'

    result = chart_axis_pattern.sub(_replace_chart_axis, result)

    # Rapikan spasi vertikal berlebih dari pecahan/notasi rumus matematika
    result = re.sub(r'[ \t]*\n[ \t]*\n(?:[ \t]*\n)+', '\n\n', result)

    return result.strip()

def extract_pdf(path: str) -> list[dict]:
    import fitz  # PyMuPDF
    pages = []
    doc = fitz.open(path)
    for index, page in enumerate(doc):
        # Bersihkan area margin header (40pt) dan footer (40pt) jika tinggi halaman mencukupi
        rect = page.rect
        clip_box = fitz.Rect(rect.x0, rect.y0 + 40, rect.x1, rect.y1 - 40) if rect.height > 150 else None

        # Deteksi apakah halaman memiliki struktur tabel
        try:
            tabs = page.find_tables(clip=clip_box)
        except Exception:
            tabs = None

        if not tabs or not tabs.tables:
            text = page.get_text("text", clip=clip_box) if clip_box else page.get_text("text")
        else:
            # Terdapat tabel: rekonstruksi teks halaman agar tabel tersusun rapi dalam baris dan kolom
            tables_list = sorted(tabs.tables, key=lambda t: t.bbox[1])
            blocks = page.get_text("blocks", clip=clip_box) if clip_box else page.get_text("blocks")
            text_blocks = [b for b in blocks if b[6] == 0]

            def is_inside_table(b, tab_bbox):
                bx0, by0, bx1, by1 = b[:4]
                tx0, ty0, tx1, ty1 = tab_bbox
                overlap_x = max(0, min(bx1, tx1) - max(bx0, tx0))
                overlap_y = max(0, min(by1, ty1) - max(by0, ty0))
                area_overlap = overlap_x * overlap_y
                area_b = (bx1 - bx0) * (by1 - by0)
                return area_b > 0 and (area_overlap / area_b) > 0.35

            out_parts = []
            used_blocks = set()

            for t in tables_list:
                ty0 = t.bbox[1]
                # Ambil teks paragraf sebelum tabel
                for idx, b in enumerate(text_blocks):
                    if idx not in used_blocks and b[3] <= ty0 + 4 and not any(is_inside_table(b, ot.bbox) for ot in tables_list):
                        b_text = b[4].strip()
                        if b_text:
                            out_parts.append(b_text)
                        used_blocks.add(idx)

                # Tandai blok teks di dalam tabel agar tidak diekstrak dobel
                for idx, b in enumerate(text_blocks):
                    if idx not in used_blocks and is_inside_table(b, t.bbox):
                        used_blocks.add(idx)

                # Ekstrak tabel dan format ke struktur Markdown Table
                try:
                    extracted = t.extract()
                    if extracted:
                        lines = []
                        header_done = False
                        for row in extracted:
                            clean_cells = [str(c or '').strip().replace('\n', ' ') for c in row]
                            if not any(clean_cells):
                                continue
                            lines.append('| ' + ' | '.join(clean_cells) + ' |')
                            if not header_done:
                                lines.append('| ' + ' | '.join(['---'] * len(clean_cells)) + ' |')
                                header_done = True
                        if lines:
                            out_parts.append('\n' + '\n'.join(lines) + '\n')
                except Exception:
                    pass

            # Ambil sisa teks paragraf setelah semua tabel
            for idx, b in enumerate(text_blocks):
                if idx not in used_blocks and not any(is_inside_table(b, ot.bbox) for ot in tables_list):
                    b_text = b[4].strip()
                    if b_text:
                        out_parts.append(b_text)
                    used_blocks.add(idx)

            text = '\n\n'.join(out_parts)

        pages.append({
            "page_number": index + 1,
            "text": clean_extracted_text(text)
        })
    doc.close()
    return pages

def extract_docx(path: str) -> list[dict]:
    from docx import Document
    from docx.text.paragraph import Paragraph
    from docx.table import Table

    doc = Document(path)
    text_blocks = []

    # Iterasi elemen body secara berurutan (paragraf dan tabel)
    for child in doc.element.body:
        if child.tag.endswith('p'):
            p = Paragraph(child, doc)
            val = p.text.strip()
            if val:
                text_blocks.append(val)
        elif child.tag.endswith('tbl'):
            t = Table(child, doc)
            table_lines = []
            header_done = False
            for row in t.rows:
                seen_cells = []
                for cell in row.cells:
                    ctext = cell.text.strip().replace('\n', ' ')
                    if not seen_cells or ctext != seen_cells[-1]:
                        seen_cells.append(ctext)
                if any(seen_cells):
                    table_lines.append('| ' + ' | '.join(seen_cells) + ' |')
                    if not header_done:
                        table_lines.append('| ' + ' | '.join(['---'] * len(seen_cells)) + ' |')
                        header_done = True
            if table_lines:
                text_blocks.append('\n' + '\n'.join(table_lines) + '\n')

    return [{
        "page_number": 1,
        "text": clean_extracted_text("\n\n".join(text_blocks))
    }]

def extract_text_file(path: str) -> list[dict]:
    with open(path, "r", encoding="utf-8", errors="replace") as f:
        text = f.read()
    return [{
        "page_number": 1,
        "text": text
    }]

def extract_document(path: str, mime_type: str | None = None) -> list[dict]:
    ext = os.path.splitext(path)[1].lower()
    if ext == ".pdf" or mime_type == "application/pdf":
        return extract_pdf(path)
    elif ext == ".docx" or (mime_type and "officedocument.wordprocessingml" in mime_type):
        return extract_docx(path)
    elif ext == ".txt" or mime_type == "text/plain":
        return extract_text_file(path)
    else:
        raise ValueError(f"Format dokumen tidak didukung: {ext} ({mime_type})")
