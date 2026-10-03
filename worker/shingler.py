import hashlib

def generate_shingles(words: list[str], size: int = 5) -> list[dict]:
    """
    Menghasilkan 5-word shingles dan SHA-256 fingerprint (binary 32-byte).
    Setiap shingle memiliki:
    - position: offset index relatif dalam list words
    - text: frase n-kata
    - fingerprint: bytes hash sha256 (32 bytes)
    """
    shingles = []
    if len(words) < size:
        return shingles
    
    for position in range(len(words) - size + 1):
        phrase = " ".join(words[position:position + size])
        fingerprint = hashlib.sha256(phrase.encode("utf-8")).digest()
        shingles.append({
            "position": position,
            "text": phrase,
            "fingerprint": fingerprint
        })
    
    return shingles
