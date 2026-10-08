import os
from pathlib import Path

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
            "text": text
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
        "text": "\n\n".join(text_blocks)
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
