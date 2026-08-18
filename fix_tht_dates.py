#!/usr/bin/env python3
"""Fix THT Lampau transaction dates from 2026-07-01 to 2026-06-30."""

import re
import sys
import time
import requests
from html import unescape

BASE_URL = "https://keuangan.aljawahirattarbawi.com"
EMAIL = "superadmin@aljawahir.sch.id"
PASSWORD = "superadmin123"
CORRECT_DATE = "2026-06-30"
GURU_RANGE = range(1, 57)

session = requests.Session()
session.headers.update({"User-Agent": "Mozilla/5.0"})


def login():
    print("[*] Logging in...")
    r = session.get(f"{BASE_URL}/login")
    r.raise_for_status()
    m = re.search(r'name="csrf_test_name"\s+value="([^"]*)"', r.text)
    if not m:
        print("[!] Could not find csrf_test_name on login page")
        sys.exit(1)
    csrf = m.group(1)

    r = session.post(
        f"{BASE_URL}/auth/login",
        data={"csrf_test_name": csrf, "email": EMAIL, "password": PASSWORD},
        allow_redirects=True,
    )
    print(f"    Login OK, status={r.status_code}")
    return csrf


def refresh_csrf():
    """Fetch /tht page to get a fresh csrf_hash() token."""
    r = session.get(f"{BASE_URL}/tht")
    r.raise_for_status()
    m = re.search(r'id="csrfToken"\s+value="([^"]*)"', r.text)
    if not m:
        print("[!] Could not find csrfToken on /tht page")
        return ""
    return m.group(1)


def get_riwayat(guru_id):
    r = session.get(
        f"{BASE_URL}/tht/riwayat/{guru_id}",
        headers={"X-Requested-With": "XMLHttpRequest"},
    )
    r.raise_for_status()
    data = r.json()
    if not data.get("success"):
        return ""
    return data.get("html", "")


def parse_tht_lampau(html, guru_id):
    transactions = []
    rows = re.findall(r'<tr[^>]*>(.*?)</tr>', html, re.DOTALL)

    def clean(s):
        return unescape(re.sub(r'<[^>]+>', '', s)).strip()

    for row in rows:
        if "THT Lampau" not in row:
            continue
        id_match = re.search(r'data-id="(\d+)"', row)
        if not id_match:
            continue

        tds = re.findall(r'<td[^>]*>(.*?)</td>', row, re.DOTALL)
        if len(tds) < 5:
            continue

        tanggal = clean(tds[0])
        jumlah_str = re.sub(r'[^\d]', '', clean(tds[2]))
        keterangan = clean(tds[4])

        if not jumlah_str:
            continue

        transactions.append({
            "id": int(id_match.group(1)),
            "guru_id": guru_id,
            "tanggal": tanggal,
            "jumlah": int(jumlah_str),
            "keterangan": keterangan,
        })

    return transactions


def delete_transaksi(tx_id, csrf):
    r = session.post(
        f"{BASE_URL}/tht/hapus/{tx_id}",
        headers={
            "X-Requested-With": "XMLHttpRequest",
            "X-CSRF-TOKEN": csrf,
        },
    )
    try:
        return r.json().get("success", False)
    except Exception:
        return False


def create_transaksi(guru_id, jumlah, keterangan, csrf):
    r = session.post(
        f"{BASE_URL}/tht/setor",
        data={
            "csrf_test_name": csrf,
            "guru_id": guru_id,
            "jumlah": jumlah,
            "tanggal": CORRECT_DATE,
            "keterangan": keterangan,
        },
        headers={
            "X-Requested-With": "XMLHttpRequest",
            "X-CSRF-TOKEN": csrf,
        },
    )
    if r.status_code == 200:
        try:
            return r.json().get("success", False)
        except Exception:
            return True
    return r.status_code == 302


def main():
    login()
    csrf = refresh_csrf()
    print(f"[*] CSRF token ready ({csrf[:12]}...)")

    total_deleted = 0
    total_created = 0
    errors = []
    guru_count = 0

    for guru_id in GURU_RANGE:
        try:
            html = get_riwayat(guru_id)
        except Exception as e:
            print(f"  [!] Guru {guru_id}: fetch error: {e}")
            errors.append(f"guru {guru_id}: fetch error")
            continue

        txs = parse_tht_lampau(html, guru_id)

        if not txs:
            continue

        guru_count += 1
        print(f"  Guru {guru_id}: {len(txs)} THT Lampau tx(s)")

        for tx in txs:
            ok = delete_transaksi(tx["id"], csrf)
            if ok:
                total_deleted += 1
                print(f"    Del tx {tx['id']}: Rp {tx['jumlah']:,} (was {tx['tanggal']})")
            else:
                print(f"    [!] FAILED delete tx {tx['id']}")
                errors.append(f"tx {tx['id']}: delete failed")
                continue

            time.sleep(0.3)

            ok = create_transaksi(tx["guru_id"], tx["jumlah"], tx["keterangan"], csrf)
            if ok:
                total_created += 1
                print(f"    New tx: Rp {tx['jumlah']:,} on {CORRECT_DATE}")
            else:
                print(f"    [!] FAILED create tx for guru {tx['guru_id']}")
                errors.append(f"tx for guru {tx['guru_id']}: create failed")

            time.sleep(0.3)

        time.sleep(0.2)

        if guru_count % 10 == 0:
            csrf = refresh_csrf()

    print()
    print("=" * 50)
    print(f"SUMMARY: {total_deleted} deleted, {total_created} re-created")
    if errors:
        print(f"ERRORS ({len(errors)}):")
        for e in errors:
            print(f"  - {e}")
    print("=" * 50)


if __name__ == "__main__":
    main()
