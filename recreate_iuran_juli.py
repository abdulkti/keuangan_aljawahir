import requests
import re
import time

BASE_URL = "https://keuangan.aljawahirattarbawi.com"
EMAIL = "superadmin@aljawahir.sch.id"
PASSWORD = "superadmin123"
DELAY = 0.3
JUMLAH = 50000
TANGGAL = "2026-07-01"
KETERANGAN = "Iuran Juli"

GURU_IDS = [1,2,3,4,5,6,7,8,9,10,11,12,13,15,17,18,19,22,23,24,25,27,28,29,30,31,32,33,34,35,36,37,39,40,41,42,44,45,46,47,48,49,52,53,54]

session = requests.Session()
deleted = 0
created = 0
errors = 0
csrf_token = None  # for X-CSRF-TOKEN header
csrf_form = None   # for csrf_test_name form field


def login():
    global errors
    r = session.get(f"{BASE_URL}/login")
    m = re.search(r'name="csrf_test_name"\s+value="([^"]+)"', r.text)
    if not m:
        print("ERROR: Could not find csrf_test_name on login page")
        errors += 1
        return False
    csrf = m.group(1)
    r = session.post(f"{BASE_URL}/auth/login", data={
        "csrf_test_name": csrf,
        "email": EMAIL,
        "password": PASSWORD,
    }, allow_redirects=True)
    if "/dashboard" in r.url or r.status_code == 200:
        print("Login successful")
        return True
    print(f"ERROR: Login failed, status={r.status_code}, url={r.url}")
    errors += 1
    return False


def refresh_csrf():
    global csrf_token, csrf_form
    r = session.get(f"{BASE_URL}/tht")
    m1 = re.search(r'id="csrfToken"\s+value="([^"]+)"', r.text)
    m2 = re.search(r'name="csrf_test_name"\s+value="([^"]+)"', r.text)
    if m1:
        csrf_token = m1.group(1)
    if m2:
        csrf_form = m2.group(1)
    return bool(m1 and m2)


def ensure_session():
    """Re-login if session expired, then refresh CSRF."""
    global csrf_token, csrf_form
    r = session.get(f"{BASE_URL}/tht")
    # If we got redirected to login, re-login
    if "/login" in r.url or "login" in r.text[:500].lower() and "csrf_test_name" in r.text[:2000]:
        print("  Session expired, re-logging in...")
        if not login():
            return False
        time.sleep(DELAY)
        return refresh_csrf()
    # Otherwise just extract CSRF
    m1 = re.search(r'id="csrfToken"\s+value="([^"]+)"', r.text)
    m2 = re.search(r'name="csrf_test_name"\s+value="([^"]+)"', r.text)
    if m1:
        csrf_token = m1.group(1)
    if m2:
        csrf_form = m2.group(1)
    return bool(m1 and m2)


def check_session_valid():
    """Quick check if session is still valid by hitting riwayat/1."""
    try:
        r = session.get(
            f"{BASE_URL}/tht/riwayat/1",
            headers={"X-Requested-With": "XMLHttpRequest"}
        )
        r.json()  # will throw if not JSON
        return True
    except Exception:
        return False


def delete_iuran_juli():
    global deleted, errors
    print("\n=== PHASE 1: Deleting existing Iuran Juli ===")
    if not refresh_csrf():
        print("ERROR: Could not get initial CSRF")
        return

    ops_since_refresh = 0
    for i, gid in enumerate(GURU_IDS):
        # Refresh every 12 ops to stay safe
        if ops_since_refresh >= 12:
            if not check_session_valid():
                ensure_session()
            else:
                refresh_csrf()
            ops_since_refresh = 0

        try:
            r = session.get(
                f"{BASE_URL}/tht/riwayat/{gid}",
                headers={"X-Requested-With": "XMLHttpRequest"}
            )
            data = r.json()
            html = data.get("html", "")
            if "Iuran Juli" not in html:
                time.sleep(DELAY)
                continue
            ids = re.findall(r'data-id="(\d+)"', html)
            for tid in ids:
                if ops_since_refresh >= 12:
                    if not check_session_valid():
                        if not ensure_session():
                            print("  FATAL: Cannot re-establish session")
                            return
                    else:
                        refresh_csrf()
                    ops_since_refresh = 0

                r2 = session.post(
                    f"{BASE_URL}/tht/hapus/{tid}",
                    headers={
                        "X-Requested-With": "XMLHttpRequest",
                        "X-CSRF-TOKEN": csrf_token,
                    }
                )
                ops_since_refresh += 1
                if r2.status_code == 200:
                    print(f"  Deleted Iuran Juli id={tid} for guru_id={gid}")
                    deleted += 1
                else:
                    # Could be CSRF issue, try re-login
                    if r2.status_code in (302, 403) or "login" in r2.text[:500].lower():
                        print(f"  Session/CSRF issue on delete, re-login...")
                        ensure_session()
                        r2 = session.post(
                            f"{BASE_URL}/tht/hapus/{tid}",
                            headers={
                                "X-Requested-With": "XMLHttpRequest",
                                "X-CSRF-TOKEN": csrf_token,
                            }
                        )
                        ops_since_refresh = 1
                        if r2.status_code == 200:
                            print(f"  Deleted Iuran Juli id={tid} for guru_id={gid} (after re-login)")
                            deleted += 1
                        else:
                            print(f"  ERROR deleting id={tid}: status={r2.status_code}")
                            errors += 1
                    else:
                        print(f"  ERROR deleting id={tid}: status={r2.status_code}")
                        errors += 1
        except Exception as e:
            print(f"  EXCEPTION for guru_id={gid}: {e}")
            # Session likely expired, re-login
            if not check_session_valid():
                ensure_session()
            errors += 1
        time.sleep(DELAY)


def create_iuran_juli():
    global created, errors
    print("\n=== PHASE 2: Creating Iuran Juli ===")
    if not refresh_csrf():
        print("ERROR: Could not get CSRF for creation")
        return

    ops_since_refresh = 0
    total = len(GURU_IDS)
    for i, gid in enumerate(GURU_IDS):
        if ops_since_refresh >= 12:
            if not check_session_valid():
                if not ensure_session():
                    print("  FATAL: Cannot re-establish session")
                    return
            else:
                refresh_csrf()
            ops_since_refresh = 0

        print(f"  [{i+1}/{total}] Creating Iuran Juli for guru_id={gid}...")
        try:
            r = session.post(
                f"{BASE_URL}/tht/setor",
                data={
                    "csrf_test_name": csrf_form,
                    "guru_id": gid,
                    "jumlah": JUMLAH,
                    "tanggal": TANGGAL,
                    "keterangan": KETERANGAN,
                },
                headers={
                    "X-Requested-With": "XMLHttpRequest",
                    "X-CSRF-TOKEN": csrf_token,
                },
                allow_redirects=False,
            )
            ops_since_refresh += 1
            if r.status_code in (200, 302, 303):
                created += 1
            else:
                # Try re-login
                if r.status_code in (403,) or "login" in r.text[:500].lower():
                    print(f"    Session issue, re-login...")
                    if ensure_session():
                        r = session.post(
                            f"{BASE_URL}/tht/setor",
                            data={
                                "csrf_test_name": csrf_form,
                                "guru_id": gid,
                                "jumlah": JUMLAH,
                                "tanggal": TANGGAL,
                                "keterangan": KETERANGAN,
                            },
                            headers={
                                "X-Requested-With": "XMLHttpRequest",
                                "X-CSRF-TOKEN": csrf_token,
                            },
                            allow_redirects=False,
                        )
                        ops_since_refresh = 1
                        if r.status_code in (200, 302, 303):
                            created += 1
                        else:
                            print(f"    ERROR status={r.status_code}")
                            errors += 1
                    else:
                        print(f"    FATAL: cannot re-login")
                        errors += 1
                else:
                    print(f"    ERROR status={r.status_code}")
                    errors += 1
        except Exception as e:
            print(f"    EXCEPTION: {e}")
            if not check_session_valid():
                ensure_session()
            errors += 1
        time.sleep(DELAY)


def verify():
    print("\n=== PHASE 3: Verification ===")
    found = 0
    missing = []
    for gid in GURU_IDS:
        try:
            r = session.get(
                f"{BASE_URL}/tht/riwayat/{gid}",
                headers={"X-Requested-With": "XMLHttpRequest"}
            )
            data = r.json()
            html = data.get("html", "")
            if "Iuran Juli" in html:
                found += 1
            else:
                missing.append(gid)
        except Exception as e:
            print(f"  Verification error guru_id={gid}: {e}")
            missing.append(gid)
            if not check_session_valid():
                ensure_session()
        time.sleep(DELAY)
    print(f"  Gurus with Iuran Juli: {found}/{len(GURU_IDS)}")
    if missing:
        print(f"  Missing: {missing}")
    return found


def main():
    global errors
    if not login():
        return
    time.sleep(DELAY)
    delete_iuran_juli()
    time.sleep(DELAY)
    create_iuran_juli()
    time.sleep(DELAY)
    found = verify()
    print(f"\nDELETED: {deleted}, CREATED: {created}, ERRORS: {errors}")
    if found == len(GURU_IDS):
        print("ALL OK - every guru has Iuran Juli")
    else:
        print(f"WARNING: only {found}/{len(GURU_IDS)} gurus have Iuran Juli")


if __name__ == "__main__":
    main()
