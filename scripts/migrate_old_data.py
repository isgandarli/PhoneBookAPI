"""
Migrate data from old MSSQL PhoneBook (TLFNCNTCS) into new PostgreSQL (phonebook_api).

Usage:
    python scripts/migrate_old_data.py [--dry-run]

Safety:
    - Old DB is opened READ-ONLY (only SELECT queries)
    - New DB changes are wrapped in a single transaction (atomic commit/rollback)
    - --dry-run prints what would happen without writing to new DB
"""

import sys
import io
import argparse
from datetime import datetime

import pymssql
import psycopg2

# Force UTF-8 stdout on Windows
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding="utf-8")

# ── Config ──────────────────────────────────────────────────────────────────

OLD_DB = {
    "server": "192.168.9.82",
    "port": 1433,
    "user": "nezaret",
    "password": "Nt123456",
    "database": "TLFNCNTCS",
    "tds_version": "7.0",
    "charset": "UTF-8",
}

NEW_DB = {
    "host": "127.0.0.1",
    "port": 5432,
    "dbname": "phonebook_api",
    "user": "postgres",
    "password": "123456",
}

# strType → structure_type_id mapping
STR_TYPE_MAP = {0: 1, 1: 4, 2: 2, 3: 3}

NOW = datetime.now()


# ── Helpers ─────────────────────────────────────────────────────────────────

def clean(val):
    """Trim whitespace from strings; convert empty/blank to None."""
    if val is None:
        return None
    if isinstance(val, str):
        val = val.strip()
        return val if val else None
    return val


def parse_date(val):
    """Parse a date string into a datetime, or return NOW."""
    if not val or not isinstance(val, str):
        return NOW
    val = val.strip()
    for fmt in ("%Y-%m-%d", "%d.%m.%Y", "%d/%m/%Y"):
        try:
            return datetime.strptime(val, fmt)
        except ValueError:
            continue
    return NOW


def status_to_deleted_at(status):
    """Convert old status int to deleted_at timestamp."""
    if status == 1:
        return None  # active
    return NOW  # inactive → soft-deleted


# ── Main ────────────────────────────────────────────────────────────────────

def main():
    parser = argparse.ArgumentParser(description="Migrate old PhoneBook data")
    parser.add_argument("--dry-run", action="store_true", help="Preview without writing")
    args = parser.parse_args()
    dry_run = args.dry_run

    if dry_run:
        print("=== DRY RUN MODE — no changes will be written ===\n")

    # Connect to old DB (read-only usage)
    print("Connecting to old MSSQL database...")
    old_conn = pymssql.connect(**OLD_DB)
    old_cur = old_conn.cursor()

    # Connect to new DB
    print("Connecting to new PostgreSQL database...")
    new_conn = psycopg2.connect(**NEW_DB)
    new_cur = new_conn.cursor()

    try:
        # ── Step 0: Pre-flight checks ──────────────────────────────────────

        # Check new DB has the base structure_types from seeder
        new_cur.execute("SELECT COUNT(*) FROM structure_types")
        st_count = new_cur.fetchone()[0]
        if st_count < 3:
            print(f"ERROR: structure_types has {st_count} rows. Run `php artisan migrate --seed` first.")
            sys.exit(1)

        # Check if data already migrated
        new_cur.execute("SELECT COUNT(*) FROM positions")
        if new_cur.fetchone()[0] > 1:  # 1 from seeder
            print("WARNING: positions table already has data beyond the seed.")
            resp = input("Continue and skip existing IDs? (y/N): ").strip().lower()
            if resp != "y":
                print("Aborted.")
                sys.exit(0)

        # ── Step 1: Insert "İdarə" structure type ──────────────────────────

        new_cur.execute("SELECT id FROM structure_types WHERE name = 'İdarə'")
        if new_cur.fetchone() is None:
            print("Inserting structure type 'İdarə' (id=4)...")
            if not dry_run:
                new_cur.execute(
                    "INSERT INTO structure_types (id, name, created_at, updated_at) VALUES (%s, %s, %s, %s)",
                    (4, "İdarə", NOW, NOW),
                )
        else:
            print("Structure type 'İdarə' already exists, skipping.")

        # ── Step 2: Migrate jobs → positions ───────────────────────────────

        print("\nMigrating jobs → positions...")
        old_cur.execute("SELECT dataID, jobName, jobStatus FROM jobs ORDER BY dataID")
        jobs = old_cur.fetchall()

        inserted = skipped = 0
        for dataID, jobName, jobStatus in jobs:
            # Check if already exists
            new_cur.execute("SELECT id FROM positions WHERE id = %s", (dataID,))
            if new_cur.fetchone():
                skipped += 1
                continue

            name = clean(jobName)
            deleted_at = status_to_deleted_at(jobStatus)

            if not dry_run:
                new_cur.execute(
                    """INSERT INTO positions (id, name, deleted_at, created_at, updated_at)
                       VALUES (%s, %s, %s, %s, %s)""",
                    (dataID, name, deleted_at, NOW, NOW),
                )
            inserted += 1

        print(f"  positions: {inserted} inserted, {skipped} skipped (already exist)")

        # ── Step 3: Migrate structure → structure ──────────────────────────

        print("\nMigrating structure → structure...")
        old_cur.execute(
            "SELECT dataID, strName, strParent, strType, strCount, strStatus "
            "FROM structure ORDER BY dataID"
        )
        structures = old_cur.fetchall()

        # Insert roots first (strParent=0), then children, to satisfy FK-like consistency
        # Since parent_id is not a formal FK in the DB, we can insert in any order,
        # but let's sort: roots first for clarity
        roots = [s for s in structures if s[2] == 0]
        children = [s for s in structures if s[2] != 0]

        inserted = skipped = 0
        for dataID, strName, strParent, strType, strCount, strStatus in roots + children:
            new_cur.execute("SELECT id FROM structure WHERE id = %s", (dataID,))
            if new_cur.fetchone():
                skipped += 1
                continue

            name = clean(strName)
            parent_id = None if strParent == 0 else strParent
            structure_type_id = STR_TYPE_MAP.get(strType, 1)
            order = strCount if strCount is not None else 0
            deleted_at = status_to_deleted_at(strStatus)

            if not dry_run:
                new_cur.execute(
                    """INSERT INTO structure (id, name, description, parent_id, structure_type_id, "order", deleted_at, created_at, updated_at)
                       VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s)""",
                    (dataID, name, None, parent_id, structure_type_id, order, deleted_at, NOW, NOW),
                )
            inserted += 1

        print(f"  structure: {inserted} inserted, {skipped} skipped (already exist)")

        # ── Step 4: Migrate contacts → employees ──────────────────────────

        print("\nMigrating contacts → employees...")
        old_cur.execute(
            "SELECT dataID, conName, conSurname, email, internalNumber, mobileNumber, "
            "conJob, conStructure, positionOrder, conStatus, datDate "
            "FROM contacts ORDER BY dataID"
        )
        contacts = old_cur.fetchall()

        # Pre-check for email/mobile_number duplicates
        email_seen = {}
        mobile_seen = {}
        for row in contacts:
            dataID = row[0]
            email = clean(row[3])
            mobile = clean(row[5])
            if email:
                email_seen.setdefault(email, []).append(dataID)
            if mobile:
                mobile_seen.setdefault(mobile, []).append(dataID)

        email_dups = {k: v for k, v in email_seen.items() if len(v) > 1}
        mobile_dups = {k: v for k, v in mobile_seen.items() if len(v) > 1}

        if email_dups:
            print(f"  WARNING: {len(email_dups)} duplicate emails found, will append _dupN suffix:")
            for email, ids in email_dups.items():
                print(f"    {email}: contact IDs {ids}")

        if mobile_dups:
            print(f"  WARNING: {len(mobile_dups)} duplicate mobile numbers found, will append _dupN suffix:")
            for mobile, ids in mobile_dups.items():
                print(f"    {mobile}: contact IDs {ids}")

        # Track used emails/mobiles for dedup
        used_emails = set()
        used_mobiles = set()
        # Also check what's already in the DB
        if not dry_run:
            new_cur.execute("SELECT email FROM employees WHERE email IS NOT NULL")
            used_emails.update(r[0] for r in new_cur.fetchall())
            new_cur.execute("SELECT mobile_number FROM employees WHERE mobile_number IS NOT NULL")
            used_mobiles.update(r[0] for r in new_cur.fetchall())

        inserted = skipped = 0
        for dataID, conName, conSurname, email, internalNumber, mobileNumber, conJob, conStructure, positionOrder, conStatus, datDate in contacts:
            new_cur.execute("SELECT id FROM employees WHERE id = %s", (dataID,))
            if new_cur.fetchone():
                skipped += 1
                continue

            first_name = clean(conName)
            last_name = clean(conSurname)
            email_val = clean(email)
            landline = clean(internalNumber)
            mobile = clean(mobileNumber)

            # Handle '.....' or similar placeholder values for mobile
            if mobile and all(c in ".?-_ " for c in mobile):
                mobile = None

            # Dedup email
            if email_val and email_val in used_emails:
                base = email_val
                i = 1
                while email_val in used_emails:
                    parts = base.rsplit("@", 1)
                    if len(parts) == 2:
                        email_val = f"{parts[0]}_dup{i}@{parts[1]}"
                    else:
                        email_val = f"{base}_dup{i}"
                    i += 1
            if email_val:
                used_emails.add(email_val)

            # Dedup mobile
            if mobile and mobile in used_mobiles:
                base = mobile
                i = 1
                while mobile in used_mobiles:
                    mobile = f"{base}_dup{i}"
                    i += 1
            if mobile:
                used_mobiles.add(mobile)

            order = positionOrder if positionOrder is not None else 0
            deleted_at = status_to_deleted_at(conStatus)
            created_at = parse_date(datDate)

            if not dry_run:
                new_cur.execute(
                    """INSERT INTO employees (id, first_name, last_name, father_name, email, landline_number,
                       mobile_number, description, "order", position_id, structure_id, deleted_at, created_at, updated_at)
                       VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)""",
                    (dataID, first_name, last_name, None, email_val, landline,
                     mobile, None, order, conJob, conStructure, deleted_at, created_at, NOW),
                )
            inserted += 1

        print(f"  employees: {inserted} inserted, {skipped} skipped (already exist)")

        # ── Step 5: Reset sequences ────────────────────────────────────────

        if not dry_run:
            print("\nResetting PostgreSQL sequences...")
            for table in ["positions", "structure", "employees", "structure_types"]:
                new_cur.execute(
                    f"SELECT setval(pg_get_serial_sequence('{table}', 'id'), COALESCE(MAX(id), 1)) FROM {table}"
                )
                seq_val = new_cur.fetchone()[0]
                print(f"  {table}: sequence set to {seq_val}")

        # ── Commit ─────────────────────────────────────────────────────────

        if dry_run:
            print("\n=== DRY RUN COMPLETE — rolling back ===")
            new_conn.rollback()
        else:
            new_conn.commit()
            print("\n=== Migration committed successfully ===")

    except Exception as e:
        new_conn.rollback()
        print(f"\nERROR: {e}")
        print("All changes rolled back.")
        raise
    finally:
        old_cur.close()
        old_conn.close()
        new_cur.close()
        new_conn.close()


if __name__ == "__main__":
    main()
