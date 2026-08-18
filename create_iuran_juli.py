#!/usr/bin/env python3
"""Create missing 'Iuran Juli' THT setoran transactions for all gurus."""

import re
import sys
import time
import requests
from html import unescape

BASE_URL = "https://keuangan.aljawahirattarbawi.com"
EMAIL = "superadmin@aljawahir.sch.id"
PASSWORD = "superadmin123"

NAME_MAP = {
    'Faza Khairani': 3,
    'Elsa anatasya': None,
    'Yanti Peronika': None,
    'Uswatun Hasanah': None,
    'Kharunnisa': 2,
    'Dewi gustiani': 5,
    'Nur Zahara': 6,
    'Mika Nurmalaya': 7,
    'Arfikah Diah Mendasari': None,
    'Hayati Purnaweni': 1,
    'Widya Ningsing': 9,
    'Sutriani': 10,
    'Miftah Pratiwi': 11,
    'Chairani': 4,
    'Nur Futri Utami': 12,
    'Rusdan': 13,
    'Mila Pratiwi': 15,
    'Rodia Insani Harahap': 17,
    'Sufi El Farobi': 18,
    'Ahmad Fathulillah': None,
    'Ramadinisa': 19,
    'Ervina Rahma Safira': 22,
    'Nur Asiyah': None,
    'kak Murni': 23,
    'Junaidi': 24,
    'Novalia Pratiwi': 25,
    'Indah Pertiwi': 27,
    'Erni Justika': 42,
    'Indah Nuraini': 29,
    'Rizka Ramadana': 30,
    'Rini Trinasya Audy': 31,
    'Khairunnisah': 32,
    'Adelia Tristanti': 33,
    'Putri Sakina Najwa': 34,
    'Ayu Rahayu': 35,
    'Nurainun': 36,
    'Khairunnisa, S.Tr.P': 37,
    'Icha Inggrid Lestary': 52,
    'Adelia, S.Pd': 39,
    'Fiki Hidayat': 44,
    'Annisaa Pratiwi Simanjuntak': 53,
    'Vivi Destri Yumielda': 54,
    'Shafira Anggita Purba': None,
    'Adhe Eva Yolanda': 46,
    'Berby Yoreza': 47,
    'Nurliana Amelda': 48,
    'Dara Aisya': 49,
    'Mega Rahma Putri': None,
}

# Guru IDs that need Iuran Juli (filtered from NAME_MAP)
TARGET_GURUS = [
    (1, 'Hayati Purnaweni'),
    (2, 'Kharunnisa'),
    (3, 'Faza Khairani'),
    (4, 'Chairani'),
    (5, 'Dewi gustiani'),
    (6, 'Nur Zahara'),
    (7, 'Mika Nurmalaya'),
    (9, 'Widya Ningsing'),
    (10, 'Sutriani'),
    (11, 'Miftah Pratiwi'),
    (12, 'Nur Futri Utami'),
    (13, 'Rusdan'),
    (15, 'Mila Pratiwi'),
    (17, 'Rodia Insani Harahap'),
    (18, 'Sufi El Farobi'),
    (19, 'Ramadinisa'),
    (22, 'Ervina Rahma Safira'),
    (23, 'kak Murni'),
    (24, 'Junaidi'),
    (25, 'Novalia Pratiwi'),
    (27, 'Indah Pertiwi'),
    (29, 'Indah Nuraini'),
    (30, 'Rizka Ramadana'),
    (31, 'Rini Trinasya Audy'),
    (32, 'Khairunnisah'),
    (33, 'Adelia Tristanti'),
    (34, 'Putri Sakina Najwa'),
    (35, 'Ayu Rahayu'),
    (36, 'Nurainun'),
    (37, 'Khairunnisa, S.Tr.P'),
    (39, 'Adelia, S.Pd'),
    (42, 'Erni Justika'),
    (44, 'Fiki Hidayat'),
    (46, 'Adhe Eva Yolanda'),
    (47, 'Berby Yoreza'),
    (48, 'Nurliana Amelda'),
    (49, 'Dara Aisya'),
    (52, 'Icha Inggrid Lestary'),
    (53, 'Annisaa Pratiwi Simanjuntak'),
    (54, 'Vivi Destri Yumielda'),
]

session = requests.Session()
session.headers.update({"User-Agent": "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)"})


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
    """Fetch /tht page to get a fresh csrf token."""
    r = session.get(f"{BASE_URL}/tht")
    r.raise_for_status()
    m = re.search(r'id="csrfToken"\s+value="([^"]*)"', r.text)
    if not m:
        # fallback: try other patterns
        m = re.search(r'name="csrf_test_name"\s+value="([^"]*)"', r.text)
    if not m:
        print("[!] Could not find csrf token on /tht page")
        return ""
    return m.group(1)


def has_iuran_juli(guru_id):
    """Check if guru already has an 'Iuran Juli' transaction."""
    r = session.get(
        f"{BASE_URL}/tht/riwayat/{guru_id}",
        headers={"X-Requested-With": "XMLHttpRequest"},
    )
    r.raise_for_status()
    data = r.json()
    html = data.get("html", "")
    return "Iuran Juli" in html


def create_setoran(guru_id, csrf):
    """Create a setoran of 50000 for Iuran Juli."""
    r = session.post(
        f"{BASE_URL}/tht/setor",
        data={
            "csrf_test_name": csrf,
            "guru_id": guru_id,
            "jumlah": 50000,
            "tanggal": "2026-07-01",
            "keterangan": "Iuran Juli",
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
    csrf = login()
    csrf = refresh_csrf()
    print(f"[*] CSRF token ready ({csrf[:12]}...)")

    created = 0
    already_exists = 0
    errors = []

    for i, (guru_id, name) in enumerate(TARGET_GURUS):
        try:
            exists = has_iuran_juli(guru_id)
        except Exception as e:
            print(f"  [!] Guru {guru_id} ({name}): fetch error: {e}")
            errors.append(f"{name} (ID {guru_id}): fetch error")
            continue

        if exists:
            print(f"  [skip] Guru {guru_id} ({name}): already has Iuran Juli")
            already_exists += 1
            continue

        time.sleep(0.3)

        try:
            ok = create_setoran(guru_id, csrf)
        except Exception as e:
            print(f"  [!] Guru {guru_id} ({name}): create error: {e}")
            errors.append(f"{name} (ID {guru_id}): create error")
            continue

        if ok:
            created += 1
            print(f"  [OK]  Guru {guru_id} ({name}): created Iuran Juli Rp 50,000")
        else:
            print(f"  [FAIL] Guru {guru_id} ({name}): create failed")
            errors.append(f"{name} (ID {guru_id}): create failed")

        time.sleep(0.3)

        # Refresh CSRF every 10 creates
        if created % 10 == 0 and created > 0:
            csrf = refresh_csrf()

    print()
    print("=" * 50)
    print(f"SUMMARY:")
    print(f"  Created:      {created}")
    print(f"  Already had:  {already_exists}")
    total = len(TARGET_GURUS)
    print(f"  Total gurus:  {total}")
    if errors:
        print(f"  ERRORS ({len(errors)}):")
        for e in errors:
            print(f"    - {e}")
    print("=" * 50)


if __name__ == "__main__":
    main()
