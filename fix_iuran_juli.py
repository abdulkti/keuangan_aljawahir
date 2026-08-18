#!/usr/bin/env python3
"""Fix Iuran Juli THT transactions - delete wrong ones, recreate with correct guru IDs."""

import re, sys, time, requests
from html import unescape

BASE = "https://keuangan.aljawahirattarbawi.com"
EMAIL = "superadmin@aljawahir.sch.id"
PASS = "superadmin123"
IURAN_JUMLAH = 50000
TANGGAL = "2026-07-01"
KETERANGAN = "Iuran Juli"

# Excel guru name → app guru_id (from guruMap)
CORRECT_MAP = {
    28: "Faza Khairani",
    46: "Elsa Anatasya",
    48: "Uswatun Hasanah",
    45: "Kharunnisa",
    29: "Dewi Gustiani",
    44: "Nur Zahara",
    39: "Mika Nurmalaya",
    40: "Arfikah Diah Mendasari",
    31: "Hayati Purnaweni",
    41: "Widya Ningsih",
    32: "Sutriani",
    47: "Miftah Pratiwi",
    30: "Chairani",
    35: "Nur Futri Utami",
    34: "Rusdan",
    42: "Mila Pratiwi",
    36: "Rodia Insani Harahap",
    54: "Sufi El Farobi",
    52: "Ahmad Fathulillah",
    53: "Ramadinisa",
    49: "Ervina Rahma Safira",
    37: "Kak Murni",
    33: "Junaidi",
    2: "Novalia Pratiwi",
    3: "Indah Pertiwi",
    4: "Erni Justika",
    5: "Indah Nuraini",
    6: "Rizka Ramadana",
    7: "Rini Trinasya Audy",
    1: "Khairunnisah",
    8: "Adelia Tristanti",
    9: "Putri Sakina Najwa",
    10: "Ayu Rahayu",
    11: "Nurainun",
    12: "Khairunnisa S.Tr.P",
    13: "Icha Inggrid Lestary",
    15: "Adelia",
    17: "Fiki Hidayat",
    18: "Annisaa Pratiwi Simanjuntak",
    19: "Vivi Destri Yumielda",
    22: "Shafira Anggita Purba",
    23: "Adhe Eva Yolanda",
    24: "Berby Yoreza",
    25: "Nurliana Amelda",
    27: "Dara Aisya",
}

s = requests.Session()
s.headers["User-Agent"] = "Mozilla/5.0"


def login():
    r = s.get(f"{BASE}/login")
    m = re.search(r'name="csrf_test_name"\s+value="([^"]*)"', r.text)
    csrf = m.group(1)
    s.post(f"{BASE}/auth/login", data={"csrf_test_name": csrf, "email": EMAIL, "password": PASS})
    return refresh_csrf()


def refresh_csrf():
    r = s.get(f"{BASE}/tht")
    m = re.search(r'id="csrfToken"\s+value="([^"]*)"', r.text)
    if not m:
        m = re.search(r'name="csrf_test_name"\s+value="([^"]*)"', r.text)
    return m.group(1) if m else ""


def get_riwayat(guru_id, retries=2):
    for i in range(retries):
        r = s.get(f"{BASE}/tht/riwayat/{guru_id}", headers={"X-Requested-With": "XMLHttpRequest"})
        if r.status_code == 302:
            print("  [!] Session expired, re-login...")
            login()
            continue
        try:
            return r.json().get("html", "")
        except Exception:
            if i < retries - 1:
                time.sleep(1)
                login()
            else:
                return ""
    return ""


def find_iuran_juli_ids(html):
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
    r = s.post(f"{BASE}/tht/setor", data={
        "csrf_test_name": csrf,
        "guru_id": guru_id,
        "jumlah": IURAN_JUMLAH,
        "tanggal": TANGGAL,
        "keterangan": KETERANGAN,
    }, headers={"X-Requested-With": "XMLHttpRequest", "X-CSRF-TOKEN": csrf})
    if r.status_code == 302:
        return True
    try:
        return r.json().get("success", False)
    except:
        return True


def main():
    csrf = login()
    print(f"[*] Logged in, CSRF ready")

    # PHASE 1: Delete ALL existing Iuran Juli
    print("\n=== PHASE 1: Delete existing Iuran Juli ===")
    total_deleted = 0
    for gid in range(1, 57):
        try:
            html = get_riwayat(gid)
            ids = find_iuran_juli_ids(html)
            for tid in ids:
                if delete_tx(tid, csrf):
                    total_deleted += 1
                    print(f"  Deleted tx {tid} from guru {gid}")
                time.sleep(0.3)
            if ids:
                time.sleep(0.2)
            if gid % 10 == 0:
                csrf = refresh_csrf()
        except Exception as e:
            print(f"  [!] Error guru {gid}: {e}, re-login...")
            csrf = login()
            time.sleep(1)

    print(f"\n  Total deleted: {total_deleted}")

    # PHASE 2: Create with correct IDs
    print("\n=== PHASE 2: Create Iuran Juli with correct IDs ===")
    csrf = refresh_csrf()
    total_created = 0
    skipped = 0

    for guru_id, name in sorted(CORRECT_MAP.items()):
        try:
            html = get_riwayat(guru_id)
            existing = find_iuran_juli_ids(html)
            if existing:
                print(f"  Guru {guru_id} ({name}): already has Iuran Juli, skipping")
                skipped += 1
                continue

            ok = create_tx(guru_id, csrf)
            if ok:
                total_created += 1
                print(f"  Created Iuran Juli for guru {guru_id} ({name}): Rp {IURAN_JUMLAH:,}")
            else:
                print(f"  FAILED for guru {guru_id} ({name})")
        except Exception as e:
            print(f"  [!] Error guru {guru_id}: {e}, re-login...")
            csrf = login()
            time.sleep(1)
        time.sleep(0.3)

        if total_created % 10 == 0:
            csrf = refresh_csrf()

    print(f"\n{'='*50}")
    print(f"SUMMARY: {total_deleted} deleted, {total_created} created, {skipped} skipped")
    print(f"Expected total: Rp {total_created * IURAN_JUMLAH:,}")
    print(f"{'='*50}")


if __name__ == "__main__":
    main()
