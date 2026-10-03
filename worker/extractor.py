import os
from pathlib import Path

def extract_pdf(path: str) -> list[dict]:
    import fitz  # PyMuPDF
    pages = []
    doc = fitz.open(path)
    for index, page in enumerate(doc):
        # Bersihkan area margin header (50pt) dan footer (50pt) jika tinggi halaman mencukupi
        rect = page.rect
        if rect.height > 150:
            clip_box = fitz.Rect(rect.x0, rect.y0 + 40, rect.x1, rect.y1 - 40)
            text = page.get_text("text", clip=clip_box)
        else:
            text = page.get_text("text")
            
        pages.append({
            "page_number": index + 1,
            "text": text
        })
    doc.close()
    return pages

def extract_docx(path: str) -> list[dict]:
    from docx import Document
    doc = Document(path)
    text_blocks = []
    for paragraph in doc.paragraphs:
        val = paragraph.text.strip()
        if val:
            text_blocks.append(val)
            
    return [{
        "page_number": 1,
        "text": "\n".join(text_blocks)
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
