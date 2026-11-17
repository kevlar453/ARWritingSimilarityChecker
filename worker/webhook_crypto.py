"""
Enkripsi & Dekripsi Rahasia HMAC Webhook Lintas Bahasa (PHP <-> Python AES-256-GCM).

Kompatibel 100% dengan App\\Libraries\\WebhookCrypto di PHP:
- Format ciphertext: "v1:" + base64(iv_12B || tag_16B || ciphertext)
- Derivasi kunci AES-256: HMAC-SHA256(master_key_bytes, b"artilik-webhook-secret-v1")
"""

import base64
import hashlib
import hmac
import os
from typing import Optional

from cryptography.hazmat.primitives.ciphers.aead import AESGCM

from worker.database import load_env

_VERSION_PREFIX = "v1:"
_KDF_CONTEXT = b"artilik-webhook-secret-v1"


def resolve_master_key_bytes(raw_key: Optional[str] = None) -> bytes:
    """Membaca master key dari argumen atau environment (.env)."""
    load_env()
    val = raw_key
    if not val:
        val = (os.getenv("WEBHOOK_ENCRYPTION_KEY") or os.getenv("encryption.key") or "").strip()

    if not val:
        raise RuntimeError("encryption.key atau WEBHOOK_ENCRYPTION_KEY belum dikonfigurasi pada environment.")

    if (val.startswith("'") and val.endswith("'")) or (val.startswith('"') and val.endswith('"')):
        val = val[1:-1]

    if val.startswith("hex2bin:"):
        hex_str = val[8:].strip()
        try:
            key_bytes = bytes.fromhex(hex_str)
        except ValueError as exc:
            raise RuntimeError("Format encryption.key hex2bin tidak valid.") from exc
        if len(key_bytes) < 32:
            raise RuntimeError("Kunci enkripsi hex2bin wajib minimal 32 byte (64 karakter hex).")
        return key_bytes[:32]

    if val.startswith("base64:"):
        try:
            key_bytes = base64.b64decode(val[7:].strip(), validate=True)
        except Exception as exc:
            raise RuntimeError("Format encryption.key base64 tidak valid.") from exc
        if len(key_bytes) < 32:
            raise RuntimeError("Kunci enkripsi base64 wajib minimal 32 byte.")
        return key_bytes[:32]

    raw_bytes = val.encode("utf-8")
    if len(raw_bytes) < 32:
        raise RuntimeError("Kunci enkripsi wajib minimal 32 byte.")
    return raw_bytes if len(raw_bytes) == 32 else hashlib.sha256(raw_bytes).digest()


def derive_encryption_key(raw_key: Optional[str] = None) -> bytes:
    """Menurunkan sub-kunci 32-byte khusus enkripsi webhook_secret."""
    master = resolve_master_key_bytes(raw_key)
    return hmac.new(master, _KDF_CONTEXT, hashlib.sha256).digest()


def encrypt_webhook_secret(plaintext: str, raw_key: Optional[str] = None) -> str:
    """Mengenkripsi webhook_secret plaintext ke format envelope 'v1:...'."""
    key = derive_encryption_key(raw_key)
    iv = os.urandom(12)
    ct_and_tag = AESGCM(key).encrypt(iv, plaintext.encode("utf-8"), None)
    ciphertext, tag = ct_and_tag[:-16], ct_and_tag[-16:]
    return _VERSION_PREFIX + base64.b64encode(iv + tag + ciphertext).decode("ascii")


def decrypt_webhook_secret(encoded: str, raw_key: Optional[str] = None) -> str:
    """Mendekripsi envelope 'v1:...' menjadi webhook_secret plaintext."""
    if not isinstance(encoded, str) or not encoded.startswith(_VERSION_PREFIX):
        raise ValueError("Format ciphertext webhook_secret tidak dikenali.")

    try:
        raw = base64.b64decode(encoded[len(_VERSION_PREFIX):], validate=True)
    except Exception as exc:
        raise ValueError("Payload base64 webhook_secret tidak valid.") from exc

    if len(raw) < 29:
        raise ValueError("Ciphertext webhook_secret korup atau terlalu pendek.")

    iv = raw[:12]
    tag = raw[12:28]
    ciphertext = raw[28:]

    key = derive_encryption_key(raw_key)
    plaintext_bytes = AESGCM(key).decrypt(iv, ciphertext + tag, None)
    return plaintext_bytes.decode("utf-8")


def secret_fingerprint(secret: str) -> str:
    """Mengembalikan 12 karakter awal hash SHA-256 sebagai sidik jari aman log."""
    if not secret:
        return ""
    return hashlib.sha256(secret.encode("utf-8")).hexdigest()[:12]
