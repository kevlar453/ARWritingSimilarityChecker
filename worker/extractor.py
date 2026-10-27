import os
import re
import zipfile
import xml.etree.ElementTree as ET
from datetime import datetime, timezone
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

def detect_and_normalize_code_blocks(text: str) -> str:
    """
    Mendeteksi dan mengisolasi blok kode pemrograman (Java, Python, C++, PHP, SQL, HTML/JS)
    agar tidak memicu false positive kemiripan leksikal n-gram pada karya ilmiah.
    """
    if not text:
        return ""

    # 1. Tangani blok kode berpagar (Fenced Code Blocks: ```lang ... ``` atau ~~~ ... ~~~)
    fenced_pattern = re.compile(
        r'```([a-zA-Z0-9_\-\+\#]*)\s*\n(.*?)```',
        re.DOTALL
    )
    def _replace_fenced(m):
        lang = m.group(1).strip()
        lang_label = f" ({lang})" if lang else ""
        return f"\n\n[💻 Blok Kode Program{lang_label}]\n\n"
    text = fenced_pattern.sub(_replace_fenced, text)

    # 2. Tangani blok tag HTML <pre><code> ... </code></pre>
    html_code_pattern = re.compile(
        r'<pre[^>]*><code[^>]*>(.*?)</code></pre>',
        re.DOTALL | re.IGNORECASE
    )
    text = html_code_pattern.sub("\n\n[💻 Blok Kode Program]\n\n", text)

    # 3. Deteksi blok kode mentah multi-baris (Raw Multi-line Code Listings)
    code_indicators = [
        r'^\s*(?:public|private|protected|static|final|abstract|class|interface|enum)\b',
        r'^\s*(?:def|async\s+def|class|lambda)\b.*:$',
        r'^\s*#(?:include|define|pragma|ifndef|endif)\b',
        r'^\s*(?:import\s+[a-zA-Z0-9_\.]+|from\s+[a-zA-Z0-9_\.]+\s+import)\b',
        r'^\s*(?:package\s+[a-zA-Z0-9_\.]+|using\s+[a-zA-Z0-9_\.]+;)',
        r'^\s*<\?php\b',
        r'^\s*(?:SELECT|INSERT\s+INTO|UPDATE|DELETE\s+FROM|CREATE\s+TABLE|ALTER\s+TABLE|DROP\s+TABLE)\b',
        r'^\s*(?:function\s+[a-zA-Z0-9_]+\s*\(|const\s+[a-zA-Z0-9_]+\s*=|let\s+[a-zA-Z0-9_]+\s*=|var\s+[a-zA-Z0-9_]+\s*=)',
        r'^\s*(?:for|while|foreach)\s*\(.*?\)\s*\{?$',
        r'^\s*(?:if|else\s+if|elif)\s*\(.*?\)\s*\{?$',
        r'^\s*(?:return|echo|print|System\.out\.println|console\.log)\b.*[;\)]\s*$',
        r'^\s*[\{\}]\s*$',
        r'[;\{\}]\s*$',
    ]
    compiled_indicators = [re.compile(p, re.IGNORECASE) for p in code_indicators]

    def is_code_line(line: str) -> bool:
        s = line.strip()
        if not s:
            return False
        # Komentar kode
        if s.startswith('//') or s.startswith('/*') or s.startswith('* ') or (s.startswith('#') and not s.startswith('# ')):
            return True
        for ci in compiled_indicators:
            if ci.search(line):
                return True
        return False

    lines = text.split('\n')
    new_lines = []
    code_buffer = []

    for line in lines:
        if is_code_line(line):
            code_buffer.append(line)
        else:
            if len(code_buffer) >= 4:
                first_few = " ".join(code_buffer[:2])
                lang_guess = ""
                if "def " in first_few or ("import " in first_few and ":" in "".join(code_buffer)):
                    lang_guess = "Python"
                elif "public class" in first_few or "System.out" in "".join(code_buffer):
                    lang_guess = "Java"
                elif "<?php" in first_few or "->" in first_few or "$" in first_few:
                    lang_guess = "PHP"
                elif "SELECT " in first_few.upper() or "CREATE TABLE" in first_few.upper():
                    lang_guess = "SQL"
                elif "#include" in first_few:
                    lang_guess = "C/C++"
                
                label = f" ({lang_guess})" if lang_guess else ""
                new_lines.append(f"\n[💻 Blok Kode Program{label}]\n")
            elif code_buffer:
                new_lines.extend(code_buffer)
            code_buffer = []
            new_lines.append(line)

    if len(code_buffer) >= 4:
        new_lines.append("\n[💻 Blok Kode Program]\n")
    elif code_buffer:
        new_lines.extend(code_buffer)

    return '\n'.join(new_lines)

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

    # Deteksi dan format blok rumus matematika (misal: P =\n∑x\nSMI × 100%)
    math_pattern = re.compile(
        r'(?:^|\n)[ \t]*(?:P\s*=\s*\n+)?'
        r'[ \t]*∑\s*x\s*\n+'
        r'[ \t]*(?:SMI|∑\s*x\s*i?)\s*'
        r'(?:[×x*]\s*100%)?',
        re.MULTILINE | re.IGNORECASE
    )

    def _replace_math_formula(m):
        raw = m.group(0).strip()
        if 'smi' in raw.lower():
            return '\n\n[📐 Rumus: P = (∑x / SMI) × 100%]\n\n'
        elif 'xi' in raw.lower() or 'x i' in raw.lower():
            return '\n\n[📐 Rumus: P = (∑x / ∑xi) × 100%]\n\n'
        return '\n\n[📐 Rumus: ' + raw.replace('\n', ' ') + ']\n\n'

    result = math_pattern.sub(_replace_math_formula, result)

    # Deteksi dan isolasi blok kode pemrograman agar tidak memicu false positive
    result = detect_and_normalize_code_blocks(result)

    # Rapikan spasi vertikal berlebih dari pecahan/notasi rumus matematika
    result = re.sub(r'[ \t]*\n[ \t]*\n(?:[ \t]*\n)+', '\n\n', result)

    return result.strip()

def repair_pdf_math_cmaps(doc):
    """
    Memperbaiki ToUnicode CMap font matematika Identity-H (seperti Cambria Math)
    yang rusak akibat bug ekspor Word ke PDF (huruf/angka dipetakan ke spasi).
    Memetakan ulang GID ke karakter alfanumerik matematika dan simbol sesuai glyph name TrueType.
    """
    try:
        import io
        from fontTools.ttLib import TTFont
        from fontTools.agl import toUnicode
    except ImportError:
        return

    MATH_TO_ASCII = {
        0x1D443: 'P', 0x1D465: 'x', 0x1D446: 'S', 0x1D440: 'M', 0x1D43C: 'I',
        0x1D44E: 'a', 0x1D44F: 'b', 0x1D450: 'c', 0x1D451: 'd', 0x1D452: 'e',
        0x1D453: 'f', 0x1D454: 'g', 0x1D456: 'i', 0x1D457: 'j', 0x1D458: 'k',
        0x1D459: 'l', 0x1D45A: 'm', 0x1D45B: 'n', 0x1D45C: 'o', 0x1D45D: 'p',
        0x1D45E: 'q', 0x1D45F: 'r', 0x1D460: 's', 0x1D461: 't', 0x1D462: 'u',
        0x1D463: 'v', 0x1D464: 'w', 0x1D466: 'y', 0x1D467: 'z',
        0x1D434: 'A', 0x1D435: 'B', 0x1D436: 'C', 0x1D437: 'D', 0x1D438: 'E',
        0x1D439: 'F', 0x1D43A: 'G', 0x1D43B: 'H', 0x1D43D: 'J', 0x1D43E: 'K',
        0x1D43F: 'L', 0x1D441: 'N', 0x1D442: 'O', 0x1D444: 'Q', 0x1D445: 'R',
        0x1D447: 'T', 0x1D448: 'U', 0x1D449: 'V', 0x1D44A: 'W', 0x1D44B: 'X',
        0x1D44C: 'Y', 0x1D44D: 'Z'
    }

    for xref in range(1, doc.xref_length()):
        obj = doc.xref_object(xref)
        if '/Type /Font' in obj and '/Subtype /Type0' in obj and '/ToUnicode' in obj:
            tu_m = re.search(r'/ToUnicode\s+(\d+)\s+0\s+R', obj)
            desc_m = re.search(r'/DescendantFonts\s*(?:\[\s*)?(\d+)\s+0\s+R', obj)
            if tu_m and desc_m:
                tu_xref = int(tu_m.group(1))
                desc_xref = int(desc_m.group(1))
                desc_obj = doc.xref_object(desc_xref)
                if desc_obj.strip().startswith('['):
                    inner_refs = re.findall(r'(\d+)\s+0\s+R', desc_obj)
                    if inner_refs:
                        desc_obj = doc.xref_object(int(inner_refs[0]))
                fd_m = re.search(r'/FontDescriptor\s+(\d+)\s+0\s+R', desc_obj)
                if fd_m:
                    fd_obj = doc.xref_object(int(fd_m.group(1)))
                    ff_m = re.search(r'/FontFile2\s+(\d+)\s+0\s+R', fd_obj)
                    if ff_m:
                        ff_xref = int(ff_m.group(1))
                        try:
                            font_bytes = doc.xref_stream(ff_xref)
                            tt = TTFont(io.BytesIO(font_bytes))
                            order = tt.getGlyphOrder()
                            cmap_entries = []
                            for cid, name in enumerate(order):
                                uni_char = None
                                m = re.match(r'^u([0-9a-fA-F]{4,6})$', name)
                                if m:
                                    cp = int(m.group(1), 16)
                                    uni_char = MATH_TO_ASCII.get(cp, chr(cp))
                                elif name.startswith('uni') and len(name) == 7:
                                    try:
                                        uni_char = chr(int(name[3:], 16))
                                    except Exception:
                                        pass
                                else:
                                    uni_char = toUnicode(name)

                                if uni_char:
                                    hex_str = ''.join(f'{ord(c):04X}' for c in uni_char)
                                    cmap_entries.append(f'<{cid:04X}> <{hex_str}>')

                            if cmap_entries:
                                chunks = [f'{len(cmap_entries[i:i+100])} beginbfchar\n' + '\n'.join(cmap_entries[i:i+100]) + '\nendbfchar' for i in range(0, len(cmap_entries), 100)]
                                new_cmap = f'''/CIDInit /ProcSet findresource begin
12 dict begin
begincmap
/CIDSystemInfo << /Registry (Adobe) /Ordering (UCS) /Supplement 0 >> def
/CMapName /Adobe-Identity-UCS def
/CMapType 2 def
1 begincodespacerange
<0000> <FFFF>
endcodespacerange
{'\\n'.join(chunks)}
endcmap
CMapName currentdict /CMap defineresource pop
end
end'''
                                doc.update_stream(tu_xref, new_cmap.encode('latin1'))
                        except Exception:
                            pass

def reorder_blocks_two_column(blocks: list, page_width: float, page_height: float) -> list:
    """
    Mendeteksi layout halaman akademik (1 kolom vs 2 kolom - Inisiatif 1.2).
    Jika terdeteksi 2 kolom, mengurutkan blok teks:
    1. Header / Judul / Abstrak (blok full-width di bagian atas)
    2. Kolom Kiri (dari atas ke bawah berdasarkan y0)
    3. Kolom Kanan (dari atas ke bawah berdasarkan y0)
    4. Catatan Kaki / Footnotes (blok di bagian bawah)
    Jika 1 kolom standar, mengurutkan secara vertikal alami (y0 lalu x0).
    """
    text_blocks = [b for b in blocks if len(b) > 6 and b[6] == 0 and b[4].strip()]
    if len(text_blocks) < 2:
        return text_blocks

    mid_x = page_width / 2.0
    gutter_margin = 0.05 * page_width
    full_width_threshold = 0.60 * page_width

    left_candidates = []
    right_candidates = []
    full_candidates = []

    for b in text_blocks:
        bx0, by0, bx1, by1 = b[0], b[1], b[2], b[3]
        bw = bx1 - bx0
        cx = (bx0 + bx1) / 2.0

        if bw >= full_width_threshold:
            full_candidates.append(b)
        elif cx < mid_x and bx1 <= (mid_x + gutter_margin):
            left_candidates.append(b)
        elif cx >= mid_x and bx0 >= (mid_x - gutter_margin):
            right_candidates.append(b)
        else:
            if cx < mid_x:
                left_candidates.append(b)
            else:
                right_candidates.append(b)

    # Deteksi 2 kolom: minimal ada blok di kiri dan kanan, dan ada tumpang tindih vertikal (Y-overlap)
    is_two_column = False
    if len(left_candidates) >= 1 and len(right_candidates) >= 1:
        y_overlap_count = 0
        for lb in left_candidates:
            for rb in right_candidates:
                if not (lb[3] <= rb[1] or lb[1] >= rb[3]):
                    y_overlap_count += 1
                    break
        if y_overlap_count >= 1 and (len(left_candidates) + len(right_candidates)) >= 2:
            is_two_column = True

    if not is_two_column:
        return sorted(text_blocks, key=lambda b: (b[1], b[0]))

    col_blocks = left_candidates + right_candidates
    min_col_y0 = min(b[1] for b in col_blocks)
    max_col_y1 = max(b[3] for b in col_blocks)

    top_blocks = []
    bottom_blocks = []

    for b in full_candidates:
        if b[3] <= min_col_y0 + 15:
            top_blocks.append(b)
        elif b[1] >= max_col_y1 - 15:
            bottom_blocks.append(b)
        else:
            if b[1] < (min_col_y0 + max_col_y1) / 2.0:
                top_blocks.append(b)
            else:
                bottom_blocks.append(b)

    top_blocks.sort(key=lambda b: b[1])
    left_candidates.sort(key=lambda b: b[1])
    right_candidates.sort(key=lambda b: b[1])
    bottom_blocks.sort(key=lambda b: b[1])

    return top_blocks + left_candidates + right_candidates + bottom_blocks


def extract_pdf(path: str) -> list[dict]:
    import fitz  # PyMuPDF
    pages = []
    doc = fitz.open(path)
    repair_pdf_math_cmaps(doc)
    for index, page in enumerate(doc):
        # Bersihkan area margin header (40pt) dan footer (40pt) jika tinggi halaman mencukupi
        rect = page.rect
        clip_box = fitz.Rect(rect.x0, rect.y0 + 40, rect.x1, rect.y1 - 40) if rect.height > 150 else None

        # Deteksi apakah halaman memiliki struktur tabel
        try:
            tabs = page.find_tables(clip=clip_box)
        except Exception:
            tabs = None

        raw_blocks = page.get_text("blocks", clip=clip_box) if clip_box else page.get_text("blocks")

        if not tabs or not tabs.tables:
            ordered = reorder_blocks_two_column(raw_blocks, rect.width, rect.height)
            text = '\n\n'.join(b[4].strip() for b in ordered if b[4].strip())
        else:
            # Halaman memiliki tabel: rekonstruksi layout dua kolom dan isolasi tabel
            tables_list = sorted(tabs.tables, key=lambda t: t.bbox[1])
            text_blocks = [b for b in raw_blocks if b[6] == 0]

            def is_inside_table(b, tab_bbox):
                bx0, by0, bx1, by1 = b[:4]
                tx0, ty0, tx1, ty1 = tab_bbox
                overlap_x = max(0, min(bx1, tx1) - max(bx0, tx0))
                overlap_y = max(0, min(by1, ty1) - max(by0, ty0))
                area_overlap = overlap_x * overlap_y
                area_b = (bx1 - bx0) * (by1 - by0)
                return area_b > 0 and (area_overlap / area_b) > 0.35

            outside_blocks = [b for b in text_blocks if not any(is_inside_table(b, t.bbox) for t in tables_list)]
            ordered_outside = reorder_blocks_two_column(outside_blocks, rect.width, rect.height)

            table_markdowns = []
            for t in tables_list:
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
                            table_markdowns.append((t.bbox, '\n' + '\n'.join(lines) + '\n'))
                except Exception:
                    pass

            out_parts = []
            used_tables = set()

            for b in ordered_outside:
                by0 = b[1]
                for tidx, (tbbox, tmd) in enumerate(table_markdowns):
                    if tidx not in used_tables and tbbox[1] <= by0:
                        out_parts.append(tmd)
                        used_tables.add(tidx)
                b_text = b[4].strip()
                if b_text:
                    out_parts.append(b_text)

            for tidx, (tbbox, tmd) in enumerate(table_markdowns):
                if tidx not in used_tables:
                    out_parts.append(tmd)
                    used_tables.add(tidx)

            text = '\n\n'.join(out_parts)

        pages.append({
            "page_number": index + 1,
            "text": clean_extracted_text(text)
        })

    # Deteksi dan tangani naskah PDF hasil pindaian (scanned image) tanpa lapisan teks
    total_words = sum(len(p["text"].split()) for p in pages)
    if total_words < 15 and len(doc) > 0:
        try:
            import io
            import pytesseract
            from PIL import Image

            if os.path.exists('/usr/bin/tesseract'):
                pytesseract.pytesseract.tesseract_cmd = '/usr/bin/tesseract'

            ocr_pages = []
            for idx, p in enumerate(doc):
                pix = p.get_pixmap(dpi=150)
                img_bytes = pix.tobytes("png")
                pil_img = Image.open(io.BytesIO(img_bytes))
                ocr_raw = pytesseract.image_to_string(pil_img, lang="ind+eng")
                ocr_clean = clean_extracted_text(ocr_raw)
                ocr_pages.append({
                    "page_number": idx + 1,
                    "text": ocr_clean
                })

            ocr_words = sum(len(p["text"].split()) for p in ocr_pages)
            if ocr_words >= 15:
                pages = ocr_pages
        except Exception:
            # Fallback jika Tesseract OCR gagal dieksekusi
            pass

    doc.close()
    return pages

def extract_docx_footnotes(path: str) -> list[str]:
    """Ekstraksi teks catatan kaki (footnotes) dan catatan akhir (endnotes) dari paket OpenXML docx."""
    notes = []
    try:
        with zipfile.ZipFile(path) as z:
            for part_name in ('word/footnotes.xml', 'word/endnotes.xml'):
                if part_name in z.namelist():
                    xml_content = z.read(part_name)
                    root = ET.fromstring(xml_content)
                    ns = {'w': 'http://schemas.openxmlformats.org/wordprocessingml/2006/main'}
                    tag_prefix = 'footnote' if 'footnotes' in part_name else 'endnote'
                    for fn in root.findall(f'.//w:{tag_prefix}', ns):
                        fn_type = fn.get('{http://schemas.openxmlformats.org/wordprocessingml/2006/main}type')
                        if fn_type in ('separator', 'continuationSeparator'):
                            continue
                        texts = [node.text for node in fn.findall('.//w:t', ns) if node.text]
                        if texts:
                            note_text = ''.join(texts).strip()
                            if note_text:
                                notes.append(note_text)
    except Exception:
        pass
    return notes

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

    # Ekstraksi catatan kaki & catatan akhir jika ada (misal gaya Chicago / naskah sejarah)
    footnotes = extract_docx_footnotes(path)
    if footnotes:
        fn_text = '\n\n[Catatan Kaki / Footnotes]\n' + '\n'.join(footnotes)
        # Sisipkan SEBELUM Daftar Pustaka / Lampiran jika ada, agar tidak mencemari bagian referensi
        back_matter_re = re.compile(
            r'^\s*(?:DAFTAR\s+PUSTAKA|BIBLIOGRAFI|BIBLIOGRAPHY|REFERENCES?|DAFTAR\s+REFERENSI|DAFTAR\s+RUJUKAN|SUMBER\s+RUJUKAN|LAMPIRAN)\b',
            re.IGNORECASE
        )
        insert_idx = None
        for i, block in enumerate(text_blocks):
            if back_matter_re.match(block.strip()):
                insert_idx = i
                break
        if insert_idx is not None:
            text_blocks.insert(insert_idx, fn_text)
        else:
            text_blocks.append(fn_text)

    return [{
        "page_number": 1,
        "text": clean_extracted_text("\n\n".join(text_blocks))
    }]

def extract_text_file(path: str) -> list[dict]:
    with open(path, "rb") as f:
        raw_bytes = f.read()

    encodings = ["utf-8-sig", "utf-8", "cp1252", "latin-1"]
    text = ""
    for enc in encodings:
        try:
            text = raw_bytes.decode(enc)
            break
        except (UnicodeDecodeError, LookupError):
            continue
    else:
        text = raw_bytes.decode("utf-8", errors="replace")

    return [{
        "page_number": 1,
        "text": clean_extracted_text(text)
    }]

def extract_tex(path: str) -> list[dict]:
    """
    Mengekstrak dan membersihkan naskah karya ilmiah dari berkas LaTeX (.tex).
    Menghilangkan baris komentar, preamble, macro penataan letak, serta mengisolasi
    blok kode pemrograman dan rumus matematika agar tidak memicu false positive.
    """
    with open(path, "rb") as f:
        raw_bytes = f.read()

    encodings = ["utf-8-sig", "utf-8", "cp1252", "latin-1"]
    text = ""
    for enc in encodings:
        try:
            text = raw_bytes.decode(enc)
            break
        except (UnicodeDecodeError, LookupError):
            continue
    else:
        text = raw_bytes.decode("utf-8", errors="replace")

    # 1. Hapus komentar LaTeX (% komentar, tetapi bukan \% tanda persen)
    text = re.sub(r'(?<!\\)%.*$', '', text, flags=re.MULTILINE)

    # 2. Potong preamble sebelum \begin{document} jika ada
    doc_start = text.find(r'\begin{document}')
    if doc_start != -1:
        text = text[doc_start + len(r'\begin{document}'):]

    # Potong setelah \end{document} jika ada
    doc_end = text.find(r'\end{document}')
    if doc_end != -1:
        text = text[:doc_end]

    # 3. Tangani blok kode program pada LaTeX (lstlisting, minted, verbatim)
    text = re.sub(r'\\begin\{(?:lstlisting|minted|verbatim)\*?\}(?:\[.*?\])?(?:\{.*?\})?(.*?)\\end\{(?:lstlisting|minted|verbatim)\*?\}',
                  r'\n\n[💻 Blok Kode Program]\n\n', text, flags=re.DOTALL)

    # 4. Tangani blok rumus matematika multi-baris (equation, align, gather, flalign, dsb.)
    text = re.sub(r'\\begin\{(?:equation|align|gather|flalign|multline|bmatrix|pmatrix|matrix)\*?\}(?:\[.*?\])?(.*?)\\end\{(?:equation|align|gather|flalign|multline|bmatrix|pmatrix|matrix)\*?\}',
                  r'\n\n[📐 Rumus: Equation]\n\n', text, flags=re.DOTALL)
    text = re.sub(r'\\\[(.*?)\\\]', r'\n\n[📐 Rumus: Equation]\n\n', text, flags=re.DOTALL)
    text = re.sub(r'\$\$(.*?)\$\$', r'\n\n[📐 Rumus: Equation]\n\n', text, flags=re.DOTALL)

    # 5. Tangani tabel LaTeX (tabular) menjadi tabel markdown
    def _convert_tabular(m):
        raw_tab = m.group(1)
        clean_tab = re.sub(r'\\(?:hline|toprule|midrule|bottomrule|cline\{.*?\})', '', raw_tab)
        rows = [r.strip() for r in clean_tab.split(r'\\') if r.strip()]
        table_lines = []
        header_done = False
        for r in rows:
            cells = [re.sub(r'\\[a-zA-Z]+', '', c).strip() for c in r.split('&')]
            if any(cells):
                table_lines.append('| ' + ' | '.join(cells) + ' |')
                if not header_done:
                    table_lines.append('| ' + ' | '.join(['---'] * len(cells)) + ' |')
                    header_done = True
        return '\n\n' + '\n'.join(table_lines) + '\n\n' if table_lines else '\n'

    text = re.sub(r'\\begin\{tabular\*?\}(?:\{.*?\})?(.*?)\\end\{tabular\*?\}', _convert_tabular, text, flags=re.DOTALL)

    # 6. Tangani sitasi dan rujukan silang
    text = re.sub(r'\\(?:cite|citep|citet|parencite|textcite)(?:\[.*?\])?\{([^}]+)\}', r'(\1)', text)
    text = re.sub(r'\\(?:ref|pageref|autoref|eqref)\{([^}]+)\}', r'[\1]', text)

    # 7. Tangani heading hierarkis (\chapter, \section, \subsection, dsb.)
    text = re.sub(r'\\(?:chapter|section|subsection|subsubsection|paragraph)\*?\{([^}]+)\}', r'\n\n\1\n', text)

    # 8. Tangani formatting font (\textbf, \textit, \emph, \underline, dll.)
    text = re.sub(r'\\(?:textbf|textit|emph|underline|text|mathrm|textsf)\{([^}]+)\}', r'\1', text)

    # 9. Tangani keterangan gambar/tabel dan bersihkan kontrol struktural
    text = re.sub(r'\\caption\{([^}]+)\}', r'\nKeterangan: \1\n', text)
    text = re.sub(r'\\label\{[^}]+\}', '', text)
    text = re.sub(r'\\(?:newpage|clearpage|cleardoublepage|noindent|centering|raggedright|raggedleft|bigskip|medskip|smallskip|tableofcontents|listoffigures|listoftables)\b', '', text)
    text = re.sub(r'\\vspace\*?\{[^}]+\}', '', text)
    text = re.sub(r'\\hspace\*?\{[^}]+\}', '', text)

    # 10. Normalisasi karakter escape khas LaTeX
    latex_escapes = {
        r'\%': '%',
        r'\&': '&',
        r'\$': '$',
        r'\#': '#',
        r'\_': '_',
        r'\{': '{',
        r'\}': '}',
        r'~': ' ',
    }
    for esc, rep in latex_escapes.items():
        text = text.replace(esc, rep)

    # 11. Hapus macro sisa yang belum tertangani
    text = re.sub(r'\\[a-zA-Z]+\*?', '', text)

    # 12. Rapikan spasi berlebih
    text = re.sub(r'[ \t]+', ' ', text)
    text = re.sub(r'\n\s*\n\s*\n+', '\n\n', text)

    return [{
        "page_number": 1,
        "text": clean_extracted_text(text)
    }]

def extract_document(path: str, mime_type: str | None = None) -> list[dict]:
    ext = os.path.splitext(path)[1].lower()
    if ext == ".pdf" or mime_type == "application/pdf":
        return extract_pdf(path)
    elif ext == ".docx" or (mime_type and "officedocument.wordprocessingml" in mime_type):
        return extract_docx(path)
    elif ext == ".txt" or mime_type == "text/plain":
        return extract_text_file(path)
    elif ext in [".tex", ".latex"] or (mime_type and ("tex" in mime_type or "latex" in mime_type)):
        return extract_tex(path)
    else:
        raise ValueError(f"Format dokumen tidak didukung: {ext} ({mime_type})")


# ─────────────────────────────────────────────────────────────────────────────
# Metadata berkas (era pra-LLM)
# ─────────────────────────────────────────────────────────────────────────────
# Peluncuran ChatGPT (30 Nov 2022) dipakai sebagai batas konservatif "era AI generatif".
PRE_LLM_CUTOFF = datetime(2022, 11, 30, tzinfo=timezone.utc)


def _as_utc(dt):
    if dt is None:
        return None
    if dt.tzinfo is None:
        return dt.replace(tzinfo=timezone.utc)
    return dt.astimezone(timezone.utc)


def _parse_pdf_date(value):
    """Parse tanggal PDF format D:YYYYMMDDHHmmSS (+zona opsional)."""
    if not value:
        return None
    m = re.match(r"D:(\d{4})(\d{2})?(\d{2})?(\d{2})?(\d{2})?", str(value))
    if not m:
        return None
    parts = [int(g) if g else d for g, d in zip(m.groups(), (1970, 1, 1, 0, 0, 0))]
    try:
        return datetime(*parts, tzinfo=timezone.utc)
    except ValueError:
        return None


def extract_document_metadata(path: str, mime_type: str | None = None) -> dict:
    """
    Membaca tanggal dibuat/diubah dari metadata berkas (docx / pdf / tex / txt).
    Metadata dapat diubah atau terbawa dari templat, jadi hasilnya HANYA bersifat pendukung:
    dipakai untuk membatalkan klaim indikasi-AI berbasis LLM, bukan untuk membuktikan keaslian.
    """
    ext = os.path.splitext(path)[1].lower()
    created = modified = None
    author = last_modified_by = None
    try:
        if ext == ".docx" or (mime_type and "officedocument.wordprocessingml" in mime_type):
            from docx import Document
            cp = Document(path).core_properties
            created, modified = _as_utc(cp.created), _as_utc(cp.modified)
            author, last_modified_by = cp.author, cp.last_modified_by
        elif ext == ".pdf" or mime_type == "application/pdf":
            import fitz
            doc = fitz.open(path)
            md = doc.metadata or {}
            created, modified = _parse_pdf_date(md.get("creationDate")), _parse_pdf_date(md.get("modDate"))
            author = md.get("author")
            doc.close()
        elif ext in [".tex", ".latex", ".txt"] or (mime_type and ("tex" in mime_type or "text" in mime_type)):
            mtime = os.path.getmtime(path)
            ctime = os.path.getctime(path)
            modified = datetime.fromtimestamp(mtime, tz=timezone.utc)
            created = datetime.fromtimestamp(ctime, tz=timezone.utc)
    except Exception:
        pass

    newest = max([d for d in (created, modified) if d is not None], default=None)
    return {
        "created": created.isoformat() if created else None,
        "modified": modified.isoformat() if modified else None,
        "author": author or None,
        "last_modified_by": last_modified_by or None,
        # Pra-LLM hanya jika ada tanggal dan SEMUA tanggal yang tersedia sebelum batas.
        "is_pre_llm_era": bool(newest is not None and newest < PRE_LLM_CUTOFF),
        "reliability_note": "Metadata berkas dapat diubah atau terbawa dari templat; gunakan sebagai petunjuk, bukan bukti.",
    }
