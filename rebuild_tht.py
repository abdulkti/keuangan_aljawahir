#!/usr/bin/env python3
"""Rebuild ALL THT transactions: THT Lampau + Iuran Juli, with correct CSRF handling."""

import re, time, requests

BASE = "https://keuangan.aljawahirattarbawi.com"
EMAIL = "superadmin@aljawahir.sch.id"
PASS = "superadmin123"

# guru_id: (excel_name, lampau_amount)
# Only gurus that exist in the app
GURU_DATA = {
    1:  ("Khairunnisah", 3230000),
    2:  ("Novalia Pratiwi", 1800000),
    3:  ("Indah Pertiwi", 1800000),
    4:  ("Erni Justika", 1800000),
    5:  ("Indah Nuraini", 1800000),
    6:  ("Rizka Ramadana", 1260000),
    7:  ("Rini Trinasya Audy", 870000),
    8:  ("Adelia Tristanti", 0),
    9:  ("Putri Sakina Najwa", 1050000),
    10: ("Ayu Rahayu", 1050000),
    11: ("Nurainun", 3420000),
    12: ("Khairunnisa S.Tr.P", 1050000),
    13: ("Icha Inggrid Lestary", 1050000),
    15: ("Adelia", 1050000),
    17: ("Fiki Hidayat", 1050000),
    18: ("Annisaa Pratiwi Simanjuntak", 1050000),
    19: ("Vivi Destri Yumielda", 1050000),
    22: ("Shafira Anggita Purba", 600000),
    23: ("Adhe Eva Yolanda", 600000),
    24: ("Berby Yoreza", 600000),
    25: ("Nurliana Amelda", 600000),
    27: ("Dara Aisya", 250000),
    28: ("Faza Khairani", 6960000),
    29: ("Dewi Gustiani", 6240000),
    30: ("Chairani", 6960000),
    31: ("Hayati Purnaweni", 3290000),
    32: ("Sutriani", 3360000),
    33: ("Junaidi", 3000000),
    34: ("Rusdan", 3840000),
    35: ("Nur Futri Utami", 3120000),
    36: ("Rodia Insani Harahap", 3360000),
    37: ("Kak Murni", 600000),
    39: ("Mika Nurmalaya", 2400000),
    40: ("Arfikah Diah Mendasari", 2400000),
    41: ("Widya Ningsih", 2400000),
    42: ("Mila Pratiwi", 1150000),
    44: ("Nur Zahara", 1750000),
    45: ("Kharunnisa", 1750000),
    46: ("Elsa Anatasya", 1050000),
    47: ("Miftah Pratiwi", 900000),
    48: ("Uswatun Hasanah", 600000),
    49: ("Ervina Rahma Safira", 600000),
    52: ("Ahmad Fathulillah", 300000),
    53: ("Ramadinisa", 300000),
    54: ("Sufi El Farobi", 150000),
}

s = requests.Session()
s.headers["User-Agent"] = "Mozilla/5.0"


def do_login():
    r = safe_request("GET", f"{BASE}/login")
    if r is None:
        print("    Cannot reach server, waiting 30s...")
        time.sleep(30)
        r = safe_request("GET", f"{BASE}/login")
    m = re.search(r'name="csrf_test_name"\s+value="([^"]*)"', r.text)
    csrf = m.group(1)
    safe_request("POST", f"{BASE}/auth/login", data={"csrf_test_name": csrf, "email": EMAIL, "password": PASS})
    time.sleep(0.5)


def get_csrf():
    """Get CSRF from cookie (authoritative with tokenRandomize=true)."""
    return s.cookies.get("csrf_cookie_name", "")


def get_csrf_from_page():
    """Fallback: get from page HTML."""
    r = s.get(f"{BASE}/tht")
    m = re.search(r'id="csrfToken"\s+value="([^"]*)"', r.text)
    return m.group(1) if m else ""


def refresh_session():
    """Login and visit page to set cookies."""
    do_login()
    safe_request("GET", f"{BASE}/tht")
    time.sleep(0.5)


def safe_request(method, url, **kwargs):
    """Request with retry on connection errors."""
    for attempt in range(5):
        try:
            return s.request(method, url, **kwargs)
        except (requests.exceptions.ConnectionError, requests.exceptions.Timeout):
            wait = 3 * (attempt + 1)
            print(f"    Connection error, waiting {wait}s...")
            time.sleep(wait)
            if attempt >= 2:
                refresh_session()
    return None


def get_riwayat(guru_id):
    for attempt in range(3):
        r = safe_request("GET", f"{BASE}/tht/riwayat/{guru_id}",
                         headers={"X-Requested-With": "XMLHttpRequest"})
        if r is None:
            continue
        if r.status_code != 200 or not r.text.strip().startswith("{"):
            time.sleep(2)
            refresh_session()
            continue
        try:
            return r.json().get("html", "")
        except:
            time.sleep(2)
            refresh_session()
    return ""


def find_tx_ids(html, keterangan):
    ids = []
    rows = re.findall(r'<tr[^>]*>(.*?)</tr>', html, re.DOTALL)
    for row in rows:
        if keterangan not in row:
            continue
        m = re.search(r'data-id="(\d+)"', row)
        if m:
            ids.append(int(m.group(1)))
    return ids


def delete_tx(tx_id, csrf):
    try:
        r = safe_request("POST", f"{BASE}/tht/hapus/{tx_id}", headers={
            "X-Requested-With": "XMLHttpRequest", "X-CSRF-TOKEN": csrf
        }, allow_redirects=False)
        if r is None:
            return False
        return r.status_code in (302, 303) or r.json().get("success", False)
    except:
        return False


def create_tx(guru_id, jumlah, tanggal, keterangan, csrf):
    try:
        r = safe_request("POST", f"{BASE}/tht/setor", data={
            "csrf_test_name": csrf,
            "guru_id": guru_id,
            "jumlah": jumlah,
            "tanggal": tanggal,
            "keterangan": keterangan,
        }, allow_redirects=False)
        if r is None:
            return False
        return r.status_code in (302, 303)
    except:
        return False


def main():
    refresh_session()
    csrf = get_csrf()
    print(f"[*] Logged in, CSRF cookie: {csrf[:16]}...")

    # PHASE 1: Delete ALL existing transactions
    print("\n=== PHASE 1: Delete ALL existing THT transactions ===")
    total_del = 0
    for gid in range(1, 57):
        try:
            html = get_riwayat(gid)
            for keterangan in ["THT Lampau", "Iuran Juli", "THT setoran", "THT tarik"]:
                ids = find_tx_ids(html, keterangan)
                for tid in ids:
                    if delete_tx(tid, csrf):
                        total_del += 1
                        print(f"  Deleted tx {tid} from guru {gid} ({keterangan})")
                    time.sleep(0.5)
                    csrf = get_csrf()
            time.sleep(0.5)
        except Exception as e:
            print(f"  [!] Error guru {gid}: {e}")
            refresh_session()
            csrf = get_csrf()

    print(f"  Total deleted: {total_del}")

    # PHASE 2: Create THT Lampau (date 2026-06-30)
    print("\n=== PHASE 2: Create THT Lampau ===")
    refresh_session()
    csrf = get_csrf()
    lampau_created = 0
    for gid, (name, lampau) in sorted(GURU_DATA.items()):
        if lampau <= 0:
            print(f"  Skip guru {gid} ({name}): lampau = 0")
            continue
        try:
            html = get_riwayat(gid)
            existing = find_tx_ids(html, "THT Lampau")
            if existing:
                print(f"  Guru {gid}: already has THT Lampau, skip")
                continue
            csrf = get_csrf()
            ok = create_tx(gid, lampau, "2026-06-30", "THT Lampau", csrf)
            if ok:
                lampau_created += 1
                print(f"  Created THT Lampau for guru {gid} ({name}): Rp {lampau:,}")
            else:
                print(f"  FAILED THT Lampau for guru {gid} ({name})")
            time.sleep(0.5)
        except Exception as e:
            print(f"  [!] Error guru {gid}: {e}")
            refresh_session()
            csrf = get_csrf()

    print(f"  Total THT Lampau created: {lampau_created}")

    # PHASE 3: Create Iuran Juli (date 2026-07-01, Rp 50000 each)
    print("\n=== PHASE 3: Create Iuran Juli ===")
    refresh_session()
    csrf = get_csrf()
    iuran_created = 0
    for gid, (name, _) in sorted(GURU_DATA.items()):
        try:
            html = get_riwayat(gid)
            existing = find_tx_ids(html, "Iuran Juli")
            if existing:
                print(f"  Guru {gid}: already has Iuran Juli, skip")
                continue
            csrf = get_csrf()
            ok = create_tx(gid, 50000, "2026-07-01", "Iuran Juli", csrf)
            if ok:
                iuran_created += 1
                print(f"  Created Iuran Juli for guru {gid} ({name})")
            else:
                print(f"  FAILED Iuran Juli for guru {gid} ({name})")
            time.sleep(0.5)
        except Exception as e:
            print(f"  [!] Error guru {gid}: {e}")
            refresh_session()
            csrf = get_csrf()

    print(f"  Total Iuran Juli created: {iuran_created}")

    print(f"\n{'='*50}")
    print(f"SUMMARY:")
    print(f"  Deleted: {total_del}")
    print(f"  THT Lampau created: {lampau_created}")
    print(f"  Iuran Juli created: {iuran_created}")
    print(f"  Expected totals:")
    print(f"    2025-2026: Rp {sum(l for _,l in GURU_DATA.values()):,}")
    print(f"    2026-2027: Rp {iuran_created * 50000:,}")
    print(f"{'='*50}")


if __name__ == "__main__":
    main()
