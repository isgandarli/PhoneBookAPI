# Data Migration Mapping: Old MSSQL → New PostgreSQL

## Source Database (READ-ONLY — Production)

- **Host:** 192.168.9.82
- **Driver:** MSSQL (TDS 7.0)
- **Database:** TLFNCNTCS
- **Tables:** `contacts` (1373 rows), `jobs` (343 rows), `structure` (380 rows), `logs` (214,678 rows)

## Target Database (Local)

- **Host:** 127.0.0.1:5432
- **Driver:** PostgreSQL
- **Database:** phonebook_api
- **Tables:** `employees`, `positions`, `structure`, `structure_types`

---

## Tables NOT Migrated

| Old Table | Reason                                        |
| --------- | --------------------------------------------- |
| `logs`    | No equivalent table in new schema; audit logs |

---

## Schema Change Required Before Migration

The old DB has 4 structure types (`strType` 0–3), but the new DB only seeds 3 (`structure_types` id 1–3). A 4th type **"İdarə"** must be inserted before migrating structures.

| Old `strType` | Count | Meaning       | New `structure_types.id` | New `structure_types.name` |
| ------------- | ----- | ------------- | ------------------------ | -------------------------- |
| 0             | 10    | Root org      | 1                        | Kök Struktur               |
| 1             | 48    | İdarə (dept)  | **4 (NEW)**              | **İdarə**                  |
| 2             | 220   | Şöbə (div)   | 2                        | Şöbə                       |
| 3             | 102   | Sektor        | 3                        | Sektor                     |

---

## Table Mapping: `jobs` → `positions`

| Old Column (`jobs`) | Type         | New Column (`positions`) | Type                   | Transformation                                               |
| ------------------- | ------------ | ------------------------ | ---------------------- | ------------------------------------------------------------ |
| `dataID`            | int, PK      | `id`                     | bigint, PK             | Direct copy                                                  |
| `jobName`           | nvarchar(255) | `name`                  | varchar(255)           | Direct copy (trim whitespace)                                |
| `jobType`           | int          | —                        | —                      | **Dropped** — no equivalent in new schema                    |
| `jobPriority`       | int          | —                        | —                      | **Dropped** — was a unique sort order, not used in new schema |
| `jobStatus`         | int          | `deleted_at`             | timestamp, nullable    | `1` → `NULL`, `0` → current timestamp                       |
| —                   | —            | `created_at`             | timestamp              | Set to `NOW()`                                               |
| —                   | —            | `updated_at`             | timestamp              | Set to `NOW()`                                               |

**Row counts:** 343 total → 325 active + 18 soft-deleted

---

## Table Mapping: `structure` → `structure`

| Old Column (`structure`) | Type        | New Column (`structure`) | Type                   | Transformation                                                 |
| ------------------------ | ----------- | ------------------------ | ---------------------- | -------------------------------------------------------------- |
| `dataID`                 | int, PK     | `id`                     | bigint, PK             | Direct copy                                                    |
| `strName`                | nvarchar(max) | `name`                 | varchar(255)           | Direct copy (trim whitespace; truncate if >255 chars)          |
| `strParent`              | int         | `parent_id`              | bigint unsigned, nullable | `0` → `NULL` (root nodes); otherwise direct copy            |
| `strType`                | int         | `structure_type_id`      | bigint unsigned, FK    | `0→1`, `1→4`, `2→2`, `3→3` (see mapping above)                |
| `strCount`               | int         | `order`                  | bigint unsigned        | Direct copy                                                    |
| `strStatus`              | int         | `deleted_at`             | timestamp, nullable    | `1` → `NULL`, `0` → current timestamp                         |
| —                        | —           | `description`            | varchar(1024), nullable | Set to `NULL`                                                 |
| —                        | —           | `created_at`             | timestamp              | Set to `NOW()`                                                 |
| —                        | —           | `updated_at`             | timestamp              | Set to `NOW()`                                                 |

**Row counts:** 380 total → 347 active + 33 soft-deleted

**Notes:**
- Some `strName` values contain extra info (addresses, phone numbers) — e.g. `"İnformasiya Texnologiyalarının Təşkili və Tətbiqi İdarəsi: Ünvan: Az 1012, Bakı ş. Moskva pr.67, Tel:012 431 47 67"`. These will be migrated as-is (no splitting).
- `parent_id` references `structure.id` (self-referencing). Old data uses `strParent=0` for roots; new schema uses `NULL`. All non-zero `strParent` values reference valid `structure.dataID` values.

---

## Table Mapping: `contacts` → `employees`

| Old Column (`contacts`) | Type         | New Column (`employees`) | Type                    | Transformation                                                |
| ----------------------- | ------------ | ------------------------ | ----------------------- | ------------------------------------------------------------- |
| `dataID`                | int, PK      | `id`                     | bigint, PK              | Direct copy                                                   |
| `conName`               | nvarchar(100) | `first_name`            | varchar(255)            | Direct copy (trim whitespace)                                 |
| `conSurname`            | nvarchar(255) | `last_name`             | varchar(255)            | Direct copy (trim whitespace)                                 |
| —                       | —            | `father_name`            | varchar(255), nullable  | Set to `NULL` (old DB has no patronymic field)                |
| `email`                 | varchar(100) | `email`                  | varchar(255), unique, nullable | Direct copy; empty string → `NULL`                     |
| `internalNumber`        | varchar(50)  | `landline_number`        | varchar(255), nullable  | Direct copy; empty string → `NULL`                            |
| `mobileNumber`          | varchar(50)  | `mobile_number`          | varchar(255), unique, nullable | Direct copy; empty string → `NULL`                     |
| `conJob`                | int, FK      | `position_id`            | bigint unsigned, FK     | Direct copy (old `jobs.dataID` = new `positions.id`)          |
| `conStructure`          | int, FK      | `structure_id`           | bigint unsigned, FK     | Direct copy (old `structure.dataID` = new `structure.id`)     |
| `positionOrder`         | int          | `order`                  | bigint unsigned         | Direct copy                                                   |
| `conStatus`             | int          | `deleted_at`             | timestamp, nullable     | `1` → `NULL`, `0` → current timestamp                        |
| `datDate`               | varchar(25)  | `created_at`             | timestamp               | Parse as date if valid, else `NOW()`                          |
| —                       | —            | `updated_at`             | timestamp               | Set to `NOW()`                                                |
| —                       | —            | `description`            | varchar(1024), nullable | Set to `NULL`                                                 |

**Row counts:** 1373 total → 1155 active + 218 soft-deleted

**Data quality notes:**
- Some `conName` values have trailing spaces (e.g. `'Elnarə '`) — will be trimmed
- Some `email` values use old domain `@isst.gov.az` — migrated as-is
- `email` has a UNIQUE constraint in new DB; duplicates (if any) will need deduplication
- `mobile_number` has a UNIQUE constraint; empty strings and `''` values must become `NULL`
- FK integrity is clean: 0 orphaned `conJob` refs, 0 orphaned `conStructure` refs

---

## Migration Order (respects FK constraints)

1. Insert new structure type "İdarə" into `structure_types` (id=4)
2. Migrate `jobs` → `positions` (no FK dependencies)
3. Migrate `structure` → `structure` (depends on `structure_types`; self-referencing `parent_id` handled by deferred constraints or inserting parents first)
4. Migrate `contacts` → `employees` (depends on `positions` and `structure`)

---

## ID Preservation Strategy

Old `dataID` values are preserved as `id` in the new tables. This keeps FK relationships intact without needing an ID mapping table. PostgreSQL sequences will be reset to `MAX(id) + 1` after migration to avoid conflicts with future inserts.

---

## Unique Constraint Handling

| Table      | Column          | Strategy                                                         |
| ---------- | --------------- | ---------------------------------------------------------------- |
| employees  | `email`         | Check for duplicates; append suffix if needed (e.g. `_dup1`)    |
| employees  | `mobile_number` | Empty/blank → `NULL` (NULLs don't violate UNIQUE in PostgreSQL) |

---

## Rollback Plan

Since the target is a local dev database, rollback is simple:
```bash
php artisan migrate:fresh --seed
```
This drops all tables and re-seeds from scratch, removing all migrated data.
