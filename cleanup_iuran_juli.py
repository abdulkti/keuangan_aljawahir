#!/usr/bin/env python3
"""Final cleanup: remove duplicate Iuran Juli, ensure each guru has exactly 1."""

import re, sys, time, requests
from html import unescape

BASE = "https://keuangan.aljawahirattarbawi.com"
EMAIL = "superadmin@aljawahir.sch.id"
PASS = "superadmin123"

s = requests.Session()
s.headers["User-Agent"] = "Mozilla/5.0"


def login():
    r = s.get(f"{BASE}/login")
    m = re.search(r'name="csrf_test_name"\s+value="([^"]*)"', r.text)
    csrf = m.group(1)
    s.post(f"{BASE}/auth/login", data={"csrf_test_name": csrf, "email": EMAIL, "password": PASS})
    time.sleep(0.3)
    return refresh_csrf()


def refresh_csrf():
    r = s.get(f"{BASE}/tht")
    m = re.search(r'id="csrfToken"\s+value="([^"]*)"', r.text)
    if not m:
        m = re.search(r'name="csrf_test_name"\s+value="([^"]*)"', r.text)
    return m.group(1) if m else ""


def get_riwayat(guru_id):
    for attempt in range(3):
        r = s.get(f"{BASE}/tht/riwayat/{guru_id}", headers={"X-Requested-With": "XMLHttpRequest"})
        if r.status_code == 302 or not r.text.strip():
            time.sleep(1)
            login()
            continue
        try:
            return r.json().get("html", "")
        except:
            time.sleep(1)
            login()
    return ""


def find_iuran_ids(html):
    ids = []
    rows = re.findall(r'<tr[^>]*>(.*?)</tr>', html, re.DOTALL)
    for row in rows:
        if "Iuran Juli" not in row:
            continue
        m = re.search(r'data-id="(\d+)"', row)
        if m:
            ids.append(int(m.group(1)))
    return ids


def delete_tx(tx_id, csrf):
    try:
        r = s.post(f"{BASE}/tht/hapus/{tx_id}", headers={
            "X-Requested-With": "XMLHttpRequest", "X-CSRF-TOKEN": csrf
        })
        if r.status_code == 302:
            return True
        return r.json().get("success", False)
    except:
        return False


def create_tx(guru_id, csrf):
    try:
        r = s.post(f"{BASE}/tht/setor", data={
            "csrf_test_name": csrf,
            "guru_id": guru_id,
            "jumlah": 50000,
            "tanggal": "2026-07-01",
            "keterangan": "Iuran Juli",
        }, headers={"X-Requested-With": "XMLHttpRequest", "X-CSRF-TOKEN": csrf})
        if r.status_code == 302:
            return True
        try:
            return r.json().get("success", False)
        except:
            return True
    except:
        return False


def main():
    csrf = login()
    print("[*] Logged in")

    # Expected guru IDs
    expected = [1,2,3,4,5,6,7,8,9,10,11,12,13,15,17,18,19,22,23,24,25,27,28,29,30,31,32,33,34,35,36,37,39,40,41,42,44,45,46,47,48,49,52,53,54]

    # PHASE 1: Remove ALL Iuran Juli from ALL gurus
    print("\n=== PHASE 1: Remove all Iuran Juli ===")
    total_del = 0
    for gid in range(1, 57):
        try:
            html = get_riwayat(gid)
            ids = find_iuran_ids(html)
            if ids:
                print(f"  Guru {gid}: {len(ids)} Iuran Juli to delete")
                for tid in ids:
                    if delete_tx(tid, csrf):
                        total_del += 1
                        print(f"    Deleted tx {tid}")
                    time.sleep(0.3)
            time.sleep(0.15)
            if gid % 10 == 0:
                csrf = refresh_csrf()
        except Exception as e:
            print(f"  [!] Error guru {gid}: {e}")
            csrf = login()
            time.sleep(1)
    print(f"  Total deleted: {total_del}")

    # PHASE 2: Create exactly 1 Iuran Juli per expected guru
    print("\n=== PHASE 2: Create 1 Iuran Juli per guru ===")
    csrf = refresh_csrf()
    total_created = 0
    for gid in expected:
        try:
            html = get_riwayat(gid)
            existing = find_iuran_ids(html)
            if existing:
                print(f"  Guru {gid}: already has {len(existing)}, skipping")
                continue
            ok = create_tx(gid, csrf)
            if ok:
                total_created += 1
                print(f"  Created for guru {gid}")
            else:
                print(f"  FAILED for guru {gid}")
            time.sleep(0.3)
            if total_created % 10 == 0:
                csrf = refresh_csrf()
        except Exception as e:
            print(f"  [!] Error guru {gid}: {e}")
            csrf = login()
            time.sleep(1)

    print(f"\n{'='*50}")
    print(f"SUMMARY: {total_del} deleted, {total_created} created")
    print(f"Expected: {len(expected)} gurus × Rp 50,000 = Rp {len(expected)*50000:,}")
    print(f"{'='*50}")


if __name__ == "__main__":
    main()
