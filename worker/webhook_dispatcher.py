"""
Dispatcher Callback Webhook OJS/LMS dengan Tanda Tangan HMAC-SHA256 & Anti-SSRF Ketat.

Fitur keamanan & keandalan:
1. Anti-SSRF ketat:
   - Hanya mengizinkan skema HTTPS.
   - Menolak kredensial (user:pass@) dan hostname lokal/internal.
   - Memeriksa resolusi DNS sebelum koneksi dan memblokir seluruh IP privat/lokal/metadata:
     10/8, 172.16/12, 192.168/16, 127/8, 169.254/16 (169.254.169.254), 100.64/10, 0/8,
     ::1, fc00::/7, fe80::/10, multicast, dan reserved.
   - DNS pinning langsung pada level socket HTTPSConnection sehingga mencegah DNS rebinding.
   - Menolak redirect tidak aman dan membatasi hop redirect maksimal 2 kali.
   - Batas timeout HTTP 10 detik.
2. Tanda tangan HMAC-SHA256:
   - Header X-ARTilik-Signature-256 & X-ARTilik-Signature: sha256=<hex_hmac(body)>
   - Kunci rahasia per API key diambil dalam bentuk terenkripsi dari api_keys.webhook_secret
     dan hanya sidik jari (fingerprint) yang boleh muncul di log.
3. Retry dengan backoff bertahap maksimal 3 kali percobaan serta pelacakan ke tabel webhook_deliveries.
"""

from __future__ import annotations

import hashlib
import hmac
import ipaddress
import json
import logging
import os
import re
import secrets
import socket
import sys
import time
from datetime import datetime
from typing import Any, Callable, Dict, List, Optional, Tuple
from urllib.parse import urljoin, urlsplit

import requests
import urllib3
from requests.adapters import HTTPAdapter
from urllib3.connection import HTTPSConnection
from urllib3.connectionpool import HTTPSConnectionPool
from urllib3.exceptions import ConnectTimeoutError, NameResolutionError, NewConnectionError
from urllib3.poolmanager import PoolManager
from urllib3.util import connection as urllib3_connection

from worker.database import get_connection, load_env
from worker.webhook_crypto import decrypt_webhook_secret, secret_fingerprint

logger = logging.getLogger("artilik.webhook")

DEFAULT_TIMEOUT_SECONDS = 10.0
DEFAULT_MAX_ATTEMPTS = 3
DEFAULT_BACKOFF_DELAYS = (2.0, 5.0)
DEFAULT_MAX_REDIRECTS = 2

_BLOCKED_HOSTNAMES = frozenset({
    "localhost",
    "localhost.localdomain",
    "ip6-localhost",
    "ip6-loopback",
    "metadata",
    "metadata.google.internal",
})

_BLOCKED_SUFFIXES = (
    ".localhost",
    ".local",
    ".internal",
    ".localdomain",
)

_BLOCKED_V4_NETS = (
    ipaddress.ip_network("0.0.0.0/8"),
    ipaddress.ip_network("10.0.0.0/8"),
    ipaddress.ip_network("100.64.0.0/10"),
    ipaddress.ip_network("127.0.0.0/8"),
    ipaddress.ip_network("169.254.0.0/16"),
    ipaddress.ip_network("172.16.0.0/12"),
    ipaddress.ip_network("192.0.0.0/24"),
    ipaddress.ip_network("192.0.2.0/24"),
    ipaddress.ip_network("192.168.0.0/16"),
    ipaddress.ip_network("198.18.0.0/15"),
    ipaddress.ip_network("198.51.100.0/24"),
    ipaddress.ip_network("203.0.113.0/24"),
    ipaddress.ip_network("224.0.0.0/4"),
    ipaddress.ip_network("240.0.0.0/4"),
)

_BLOCKED_V6_NETS = (
    ipaddress.ip_network("::/128"),
    ipaddress.ip_network("::1/128"),
    ipaddress.ip_network("fc00::/7"),
    ipaddress.ip_network("fe80::/10"),
    ipaddress.ip_network("ff00::/8"),
    ipaddress.ip_network("2001:db8::/32"),
)


class SSRFBlockedError(ValueError):
    """Dilempar ketika callback URL ditolak oleh proteksi anti-SSRF."""


class RedirectLimitError(RuntimeError):
    """Dilempar ketika pengalihan HTTP melebihi batas yang diizinkan."""


def _unwrap_ip(ip_str: str) -> ipaddress.IPv4Address | ipaddress.IPv6Address:
    ip_obj = ipaddress.ip_address(ip_str)
    if isinstance(ip_obj, ipaddress.IPv6Address) and ip_obj.ipv4_mapped is not None:
        return ip_obj.ipv4_mapped
    return ip_obj


def is_public_ip(ip_str: str) -> bool:
    """Memastikan IP bersifat publik global dan bebas dari rentang privat/lokal/metadata."""
    try:
        ip_obj = _unwrap_ip(ip_str.strip())
    except ValueError:
        return False

    if (
        ip_obj.is_private
        or ip_obj.is_loopback
        or ip_obj.is_link_local
        or ip_obj.is_multicast
        or ip_obj.is_reserved
        or ip_obj.is_unspecified
        or not ip_obj.is_global
    ):
        return False

    if isinstance(ip_obj, ipaddress.IPv4Address):
        return not any(ip_obj in net for net in _BLOCKED_V4_NETS)

    return not any(ip_obj in net for net in _BLOCKED_V6_NETS)


def _looks_like_obfuscated_ip(host: str) -> bool:
    if host.isdigit() or host.startswith("0x"):
        return True
    parts = host.split(".")
    if 2 <= len(parts) <= 4 and all(re.match(r"^(?:0x[0-9a-f]+|0[0-7]*|[1-9][0-9]*)$", p, re.I) for p in parts):
        return True
    return False


def _default_dns_resolver(host: str, port: int) -> List[str]:
    infos = socket.getaddrinfo(host, port, type=socket.SOCK_STREAM)
    ips: List[str] = []
    for info in infos:
        sockaddr = info[4]
        if sockaddr:
            addr = sockaddr[0].split("%")[0]
            if addr not in ips:
                ips.append(addr)
    return ips


def validate_webhook_url(
    url: str,
    resolver: Optional[Callable[[str, int], List[str]]] = None,
) -> Tuple[str, str, int, List[str]]:
    """
    Memvalidasi URL webhook dan mengembalikan (normalized_url, hostname, port, pinned_public_ips).

    Melempar SSRFBlockedError bila melanggar kebijakan keamanan.
    """
    if not isinstance(url, str) or not url.strip():
        raise SSRFBlockedError("Callback URL kosong.")

    candidate = url.strip()
    if len(candidate) > 1000:
        raise SSRFBlockedError("Callback URL melebihi panjang maksimal 1000 karakter.")

    parsed = urlsplit(candidate)
    if parsed.scheme.lower() != "https":
        raise SSRFBlockedError(f"Skema URL '{parsed.scheme or 'kosong'}' ditolak: hanya skema HTTPS yang diizinkan.")

    if parsed.username is not None or parsed.password is not None:
        raise SSRFBlockedError("Kredensial pada callback URL tidak diizinkan.")

    raw_host = parsed.hostname
    if not raw_host:
        raise SSRFBlockedError("Hostname callback URL tidak valid.")

    try:
        port = parsed.port or 443
    except ValueError as exc:
        raise SSRFBlockedError("Port callback URL tidak valid.") from exc

    if port < 1 or port > 65535:
        raise SSRFBlockedError("Port callback URL di luar rentang valid.")

    host = raw_host.strip("[]").rstrip(".").lower()
    if not host:
        raise SSRFBlockedError("Hostname callback URL kosong.")

    # Normalisasi IDNA bila hostname bukan IP literal
    is_literal_ip = False
    try:
        ipaddress.ip_address(host)
        is_literal_ip = True
    except ValueError:
        try:
            host = host.encode("idna").decode("ascii").lower()
        except UnicodeError as exc:
            raise SSRFBlockedError("Hostname callback URL tidak valid (IDNA gagal).") from exc

    if host in _BLOCKED_HOSTNAMES or any(host.endswith(suffix) for suffix in _BLOCKED_SUFFIXES):
        raise SSRFBlockedError(f"Hostname internal/lokal '{host}' ditolak oleh proteksi anti-SSRF.")

    if not is_literal_ip and _looks_like_obfuscated_ip(host):
        raise SSRFBlockedError(f"Hostname numerik terselubung '{host}' ditolak oleh proteksi anti-SSRF.")

    if is_literal_ip:
        if not is_public_ip(host):
            raise SSRFBlockedError(f"Alamat IP non-publik '{host}' ditolak oleh proteksi anti-SSRF.")
        return candidate, host, port, [host]

    resolve_fn = resolver or _default_dns_resolver
    try:
        resolved_ips = resolve_fn(host, port)
    except OSError as exc:
        raise SSRFBlockedError(f"Resolusi DNS untuk '{host}' gagal: {exc}") from exc

    unique_ips: List[str] = []
    for ip_str in resolved_ips:
        clean_ip = str(ip_str).split("%")[0].strip()
        if clean_ip and clean_ip not in unique_ips:
            unique_ips.append(clean_ip)

    if not unique_ips:
        raise SSRFBlockedError(f"Host '{host}' tidak memiliki alamat IP DNS yang valid.")

    for ip_str in unique_ips:
        if not is_public_ip(ip_str):
            raise SSRFBlockedError(
                f"Resolusi DNS '{host}' menghasilkan IP non-publik '{ip_str}' (ditolak anti-SSRF)."
            )

    return candidate, host, port, unique_ips


def _connect_pinned_ips(
    pinned_ips: List[str],
    port: int,
    timeout: Any,
    source_address: Any = None,
    socket_options: Any = None,
    host_label: str = "",
) -> socket.socket:
    """Membuka koneksi TCP hanya ke IP publik yang sudah diverifikasi (anti-DNS-rebinding)."""
    last_err: Optional[OSError] = None
    for ip_str in pinned_ips:
        ip_obj = ipaddress.ip_address(ip_str)
        family = socket.AF_INET6 if isinstance(ip_obj, ipaddress.IPv6Address) else socket.AF_INET
        sock = None
        try:
            sock = socket.socket(family, socket.SOCK_STREAM)
            if socket_options:
                for opt in socket_options:
                    sock.setsockopt(*opt)
            if timeout is not urllib3_connection._DEFAULT_TIMEOUT:
                sock.settimeout(timeout)
            if source_address:
                sock.bind(source_address)
            sock.connect((ip_str, port))
            return sock
        except OSError as exc:
            last_err = exc
            if sock is not None:
                sock.close()

    if last_err is not None:
        raise last_err
    raise OSError(f"Tidak ada IP terverifikasi untuk {host_label}:{port}")


def _build_pinned_https_session(expected_host: str, pinned_ips: List[str]) -> requests.Session:
    """Membangun session Requests yang hanya melayani HTTPS dan mem-pin koneksi TCP ke pinned_ips."""

    class _PinnedHTTPSConnection(HTTPSConnection):
        def _new_conn(self) -> socket.socket:
            target_host = self.host.lower()
            if target_host != expected_host:
                raise SSRFBlockedError(f"Host koneksi '{target_host}' tidak sesuai dengan host tervalidasi '{expected_host}'.")
            try:
                sock = _connect_pinned_ips(
                    pinned_ips=pinned_ips,
                    port=self.port,
                    timeout=self.timeout,
                    source_address=self.source_address,
                    socket_options=self.socket_options,
                    host_label=self.host,
                )
            except TimeoutError as exc:
                raise ConnectTimeoutError(
                    self, f"Connection to {self.host} timed out. (connect timeout={self.timeout})"
                ) from exc
            except OSError as exc:
                raise NewConnectionError(self, f"Failed to establish a new connection: {exc}") from exc

            sys.audit("http.client.connect", self, self.host, self.port)
            return sock

    class _PinnedHTTPSConnectionPool(HTTPSConnectionPool):
        ConnectionCls = _PinnedHTTPSConnection

    class _PinnedHTTPSAdapter(HTTPAdapter):
        def init_poolmanager(self, connections, maxsize, block=False, **pool_kwargs):
            self._pool_connections = connections
            self._pool_maxsize = maxsize
            self._pool_block = block
            pm = PoolManager(num_pools=connections, maxsize=maxsize, block=block, **pool_kwargs)
            pm.pool_classes_by_scheme = {"https": _PinnedHTTPSConnectionPool}
            self.poolmanager = pm

    session = requests.Session()
    # Pastikan tidak ada adapter HTTP polos yang terpasang
    session.adapters.clear()
    session.mount("https://", _PinnedHTTPSAdapter(max_retries=0))
    return session


def compute_hmac_signature(raw_body: bytes, secret: str) -> str:
    """Menghasilkan nilai header tanda tangan HMAC-SHA256 berawalan 'sha256='."""
    digest = hmac.new(secret.encode("utf-8"), raw_body, hashlib.sha256).hexdigest()
    return f"sha256={digest}"


def sanitize_url_for_log(url: str) -> str:
    """Menyembunyikan query string saat menulis URL ke log aplikasi."""
    parsed = urlsplit(url)
    return f"{parsed.scheme}://{parsed.netloc}{parsed.path}"


def _build_base_urls() -> Tuple[str, str]:
    load_env()
    base_url = (os.getenv("app.baseURL") or os.getenv("APP_URL") or "https://tilik.arsetontong.top").strip().rstrip("/")
    return base_url, ""


def build_similarity_webhook_payload(
    report: Dict[str, Any],
    external_id: Optional[str],
    delivery_id: str,
    event_type: str = "similarity_check.completed",
    error_message: Optional[str] = None,
) -> Dict[str, Any]:
    """Menyusun payload JSON ringkas untuk dikirim ke OJS/LMS callback_url."""
    base_url, _ = _build_base_urls()
    report_id = int(report["id"])
    doc_id = int(report["document_id"])
    ver_code = report.get("verification_code") or ""

    payload: Dict[str, Any] = {
        "event": event_type,
        "delivery_id": delivery_id,
        "timestamp": int(time.time()),
        "external_id": external_id or None,
        "document_id": doc_id,
        "report_id": report_id,
        "verification_code": ver_code or None,
        "status": "failed" if event_type == "similarity_check.failed" else (report.get("status") or "completed"),
        "overall_similarity": float(report.get("overall_similarity") or 0.0),
        "exact_similarity": float(report.get("exact_similarity") or 0.0),
        "cosine_similarity": float(report.get("cosine_similarity") or 0.0),
        "predicted_decision": report.get("predicted_decision") or ("failed" if event_type == "similarity_check.failed" else "completed"),
        "match_count": int(report.get("match_count") or 0),
        "source_count": int(report.get("source_count") or 0),
        "longest_match_words": int(report.get("longest_match_words") or 0),
        "web_report_url": f"{base_url}/dashboard/report/{report_id}",
        "verification_url": f"{base_url}/verify/{ver_code}" if ver_code else None,
        "pdf_export_url": f"{base_url}/dashboard/export-pdf/{report_id}",
        "api_report_url": f"{base_url}/api/v1/report/{report_id}",
    }

    evidence_raw = report.get("evidence_data")
    if evidence_raw:
        try:
            evidence = json.loads(evidence_raw) if isinstance(evidence_raw, str) else evidence_raw
            if isinstance(evidence, dict) and "substantive_similarity" in evidence:
                payload["substantive_similarity"] = float(evidence["substantive_similarity"])
        except Exception:
            pass

    if error_message:
        payload["error_message"] = error_message[:300]

    return payload


def send_webhook_request(
    callback_url: str,
    raw_body: bytes,
    webhook_secret: str,
    event_type: str,
    delivery_id: str,
    timeout: float = DEFAULT_TIMEOUT_SECONDS,
    max_attempts: int = DEFAULT_MAX_ATTEMPTS,
    backoff_delays: Tuple[float, ...] = DEFAULT_BACKOFF_DELAYS,
    max_redirects: int = DEFAULT_MAX_REDIRECTS,
    resolver: Optional[Callable[[str, int], List[str]]] = None,
    session_factory: Optional[Callable[[str, List[str]], requests.Session]] = None,
    sleep_fn: Callable[[float], None] = time.sleep,
) -> Dict[str, Any]:
    """
    Mengirim HTTP POST webhook ber-HMAC dengan proteksi Anti-SSRF, batas redirect, timeout, dan retry backoff.
    """
    fingerprint = secret_fingerprint(webhook_secret) if webhook_secret else "none"
    signature = compute_hmac_signature(raw_body, webhook_secret)
    safe_log_url = sanitize_url_for_log(callback_url) if isinstance(callback_url, str) else ""

    headers = {
        "Content-Type": "application/json; charset=utf-8",
        "Accept": "application/json",
        "User-Agent": "AR-Tilik-Webhook/1.0",
        "X-ARTilik-Event": event_type,
        "X-ARTilik-Delivery-Id": delivery_id,
        "X-ARTilik-Timestamp": str(int(time.time())),
        "X-ARTilik-Signature-256": signature,
        "X-ARTilik-Signature": signature,
    }

    attempt_count = 0
    last_http_status: Optional[int] = None
    last_excerpt: Optional[str] = None
    last_error: Optional[str] = None

    for attempt_idx in range(1, max_attempts + 1):
        current_url = callback_url
        redirect_hops = 0

        try:
            while True:
                normalized_url, host, _port, pinned_ips = validate_webhook_url(current_url, resolver=resolver)
                factory = session_factory or _build_pinned_https_session
                session = factory(host, pinned_ips)
                try:
                    attempt_count = attempt_idx
                    resp = session.post(
                        normalized_url,
                        data=raw_body,
                        headers=headers,
                        timeout=timeout,
                        allow_redirects=False,
                        verify=True,
                    )
                finally:
                    session.close()

                last_http_status = int(resp.status_code)
                last_excerpt = (resp.text or "")[:500]

                if resp.status_code in (301, 302, 303, 307, 308):
                    location = resp.headers.get("Location", "").strip()
                    if not location:
                        raise RedirectLimitError("Server mengembalikan redirect tanpa header Location.")
                    next_url = urljoin(normalized_url, location)
                    redirect_hops += 1
                    if redirect_hops > max_redirects:
                        raise RedirectLimitError(f"Redirect webhook melebihi batas maksimal {max_redirects} kali.")
                    # Validasi ulang target redirect dengan Anti-SSRF sebelum hop berikutnya
                    validate_webhook_url(next_url, resolver=resolver)
                    if resp.status_code not in (307, 308):
                        raise RedirectLimitError(f"Kode redirect HTTP {resp.status_code} tidak didukung untuk POST webhook.")
                    current_url = next_url
                    continue

                if 200 <= resp.status_code < 300:
                    logger.info(
                        "[WEBHOOK] Terkirim delivery_id=%s url=%s status=%s attempt=%s secret_fp=%s",
                        delivery_id,
                        safe_log_url,
                        resp.status_code,
                        attempt_count,
                        fingerprint,
                    )
                    return {
                        "status": "delivered",
                        "attempt_count": attempt_count,
                        "http_status": last_http_status,
                        "response_excerpt": last_excerpt,
                        "last_error": None,
                        "delivered_at": datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
                    }

                last_error = f"HTTP {resp.status_code}: {last_excerpt[:180]}"
                logger.warning(
                    "[WEBHOOK] Gagal HTTP delivery_id=%s url=%s status=%s attempt=%s/%s secret_fp=%s",
                    delivery_id,
                    safe_log_url,
                    resp.status_code,
                    attempt_count,
                    max_attempts,
                    fingerprint,
                )

                # Kesalahan klien permanen (selain 408/429) tidak perlu retry
                if 400 <= resp.status_code < 500 and resp.status_code not in (408, 429):
                    return {
                        "status": "failed",
                        "attempt_count": attempt_count,
                        "http_status": last_http_status,
                        "response_excerpt": last_excerpt,
                        "last_error": last_error,
                        "delivered_at": None,
                    }
                break

        except SSRFBlockedError as exc:
            logger.warning(
                "[WEBHOOK SSRF BLOCKED] delivery_id=%s url=%s reason=%s secret_fp=%s",
                delivery_id,
                safe_log_url,
                exc,
                fingerprint,
            )
            return {
                "status": "rejected_ssrf",
                "attempt_count": attempt_count,
                "http_status": last_http_status,
                "response_excerpt": last_excerpt,
                "last_error": str(exc),
                "delivered_at": None,
            }
        except RedirectLimitError as exc:
            last_error = str(exc)
            logger.warning(
                "[WEBHOOK REDIRECT BLOCKED] delivery_id=%s url=%s reason=%s secret_fp=%s",
                delivery_id,
                safe_log_url,
                exc,
                fingerprint,
            )
            return {
                "status": "failed",
                "attempt_count": attempt_count,
                "http_status": last_http_status,
                "response_excerpt": last_excerpt,
                "last_error": last_error,
                "delivered_at": None,
            }
        except requests.RequestException as exc:
            attempt_count = attempt_idx
            last_error = f"Request error: {exc}"
            logger.warning(
                "[WEBHOOK] Kesalahan jaringan delivery_id=%s url=%s attempt=%s/%s err=%s secret_fp=%s",
                delivery_id,
                safe_log_url,
                attempt_count,
                max_attempts,
                exc,
                fingerprint,
            )

        if attempt_idx < max_attempts:
            delay = backoff_delays[min(attempt_idx - 1, len(backoff_delays) - 1)] if backoff_delays else 2.0
            sleep_fn(delay)

    return {
        "status": "failed",
        "attempt_count": attempt_count,
        "http_status": last_http_status,
        "response_excerpt": last_excerpt,
        "last_error": last_error or "Pengiriman webhook gagal setelah seluruh percobaan.",
        "delivered_at": None,
    }


def _fetch_report_and_secret(conn, report_id: int, api_key_id: Optional[int]) -> Tuple[Optional[Dict[str, Any]], Optional[str], str]:
    cursor = conn.cursor(dictionary=True)
    try:
        cursor.execute(
            """
            SELECT id, document_id, engine, predicted_decision, exact_similarity, cosine_similarity,
                   evidence_data, verification_code, overall_similarity, source_count, match_count,
                   longest_match_words, status, error_message
            FROM reports
            WHERE id = %s
            LIMIT 1
            """,
            (report_id,),
        )
        report = cursor.fetchone()

        secret_plain: Optional[str] = None
        secret_fp = ""
        if api_key_id:
            cursor.execute(
                """
                SELECT webhook_secret, webhook_secret_fingerprint
                FROM api_keys
                WHERE id = %s
                LIMIT 1
                """,
                (int(api_key_id),),
            )
            key_row = cursor.fetchone()
            if key_row and key_row.get("webhook_secret"):
                secret_plain = decrypt_webhook_secret(key_row["webhook_secret"])
                secret_fp = key_row.get("webhook_secret_fingerprint") or secret_fingerprint(secret_plain)

        return report, secret_plain, secret_fp
    finally:
        cursor.close()


def _insert_delivery_record(
    conn,
    *,
    job_id: Optional[int],
    report_id: int,
    document_id: int,
    api_key_id: Optional[int],
    external_id: Optional[str],
    callback_url: str,
    event_type: str,
    delivery_id: str,
    payload_sha256: str,
    secret_fp: str,
    max_attempts: int = DEFAULT_MAX_ATTEMPTS,
) -> int:
    cursor = conn.cursor()
    try:
        cursor.execute(
            """
            INSERT INTO webhook_deliveries
                (job_id, report_id, document_id, api_key_id, external_id, callback_url,
                 event_type, delivery_id, payload_sha256, secret_fingerprint, status,
                 attempt_count, max_attempts, created_at, updated_at)
            VALUES
                (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, 'pending', 0, %s, NOW(), NOW())
            """,
            (
                job_id,
                report_id,
                document_id,
                api_key_id,
                external_id or None,
                callback_url[:1000],
                event_type,
                delivery_id,
                payload_sha256,
                secret_fp or None,
                max_attempts,
            ),
        )
        conn.commit()
        return int(cursor.lastrowid)
    finally:
        cursor.close()


def _update_delivery_record(conn, delivery_row_id: int, result: Dict[str, Any]) -> None:
    cursor = conn.cursor()
    try:
        cursor.execute(
            """
            UPDATE webhook_deliveries
            SET status = %s,
                attempt_count = %s,
                http_status = %s,
                response_excerpt = %s,
                last_error = %s,
                delivered_at = %s,
                updated_at = NOW()
            WHERE id = %s
            """,
            (
                result["status"],
                int(result.get("attempt_count") or 0),
                result.get("http_status"),
                result.get("response_excerpt"),
                result.get("last_error"),
                result.get("delivered_at"),
                delivery_row_id,
            ),
        )
        conn.commit()
    finally:
        cursor.close()


def dispatch_webhook_if_configured(
    job_id: Optional[int],
    payload: Dict[str, Any],
    event_type: str = "similarity_check.completed",
    error_message: Optional[str] = None,
    timeout: float = DEFAULT_TIMEOUT_SECONDS,
    max_attempts: int = DEFAULT_MAX_ATTEMPTS,
    backoff_delays: Tuple[float, ...] = DEFAULT_BACKOFF_DELAYS,
) -> Optional[Dict[str, Any]]:
    """
    Mengirim webhook callback apabila payload job similarity_check menyertakan callback_url.
    """
    callback_url = str(payload.get("callback_url") or "").strip()
    if not callback_url:
        return None

    report_id = int(payload["report_id"])
    document_id = int(payload["document_id"])
    api_key_id = int(payload["api_key_id"]) if payload.get("api_key_id") else None
    external_id = str(payload.get("external_id") or "").strip() or None
    delivery_id = f"whdel_{secrets.token_hex(12)}"

    conn = get_connection()
    delivery_row_id: Optional[int] = None
    try:
        report, webhook_secret, secret_fp = _fetch_report_and_secret(conn, report_id, api_key_id)
        if not report:
            report = {
                "id": report_id,
                "document_id": document_id,
                "status": "failed" if event_type == "similarity_check.failed" else "completed",
            }

        body_obj = build_similarity_webhook_payload(
            report=report,
            external_id=external_id,
            delivery_id=delivery_id,
            event_type=event_type,
            error_message=error_message,
        )
        raw_body = json.dumps(body_obj, separators=(",", ":"), ensure_ascii=False).encode("utf-8")
        payload_sha256 = hashlib.sha256(raw_body).hexdigest()

        delivery_row_id = _insert_delivery_record(
            conn,
            job_id=job_id,
            report_id=report_id,
            document_id=document_id,
            api_key_id=api_key_id,
            external_id=external_id,
            callback_url=callback_url,
            event_type=event_type,
            delivery_id=delivery_id,
            payload_sha256=payload_sha256,
            secret_fp=secret_fp,
            max_attempts=max_attempts,
        )

        if not webhook_secret:
            result = {
                "status": "failed",
                "attempt_count": 0,
                "http_status": None,
                "response_excerpt": None,
                "last_error": "webhook_secret untuk api_key_id tidak tersedia/belum dikonfigurasi.",
                "delivered_at": None,
            }
            _update_delivery_record(conn, delivery_row_id, result)
            return result

        result = send_webhook_request(
            callback_url=callback_url,
            raw_body=raw_body,
            webhook_secret=webhook_secret,
            event_type=event_type,
            delivery_id=delivery_id,
            timeout=timeout,
            max_attempts=max_attempts,
            backoff_delays=backoff_delays,
        )
        _update_delivery_record(conn, delivery_row_id, result)
        return result
    except Exception as exc:
        logger.exception("[WEBHOOK] Kesalahan internal dispatcher untuk report_id=%s: %s", report_id, exc)
        err_result = {
            "status": "failed",
            "attempt_count": 0,
            "http_status": None,
            "response_excerpt": None,
            "last_error": f"Internal webhook error: {exc}"[:500],
            "delivered_at": None,
        }
        if delivery_row_id is not None:
            try:
                _update_delivery_record(conn, delivery_row_id, err_result)
            except Exception:
                pass
        return err_result
    finally:
        conn.close()
