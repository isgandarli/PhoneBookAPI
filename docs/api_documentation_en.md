# PhoneBookAPI — API Documentation

**Base URL:** `http://localhost:8000/api`

All requests and responses use `Content-Type: application/json`.

Protected endpoints require the header: `Authorization: Bearer <token>`

---

## Table of Contents

1. [Authentication](#1-authentication)
   - [Login](#11-login)
   - [Logout](#12-logout)
   - [Refresh Token](#13-refresh-token)
   - [Current User](#14-current-user)
2. [Phonebook (Public)](#2-phonebook-public)
   - [List Phonebook](#21-list-phonebook)
3. [Structure Types](#3-structure-types)
   - [List All](#31-list-all-structure-types)
   - [Show One](#32-show-structure-type)
   - [Create](#33-create-structure-type)
   - [Update](#34-update-structure-type)
   - [Delete](#35-delete-structure-type)
4. [Positions](#4-positions)
   - [List All](#41-list-all-positions)
   - [Show One](#42-show-position)
   - [Create](#43-create-position)
   - [Update](#44-update-position)
   - [Delete](#45-delete-position)
5. [Structures](#5-structures)
   - [List All](#51-list-all-structures)
   - [Show One](#52-show-structure)
   - [Create](#53-create-structure)
   - [Update](#54-update-structure)
   - [Delete](#55-delete-structure)
6. [Employees](#6-employees)
   - [List All](#61-list-all-employees)
   - [Show One](#62-show-employee)
   - [Create](#63-create-employee)
   - [Update](#64-update-employee)
   - [Delete](#65-delete-employee)
7. [Error Responses](#7-error-responses)

---

## 1. Authentication

### 1.1 Login

Authenticate a user and receive a JWT token.

| | |
|---|---|
| **URL** | `POST /api/auth/login` |
| **Auth** | None |

**Request Body:**

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| username | string | Yes | Must exist in users table |
| password | string | Yes | |

**Request Example:**
```json
{
  "username": "admin",
  "password": "adminpanel123"
}
```

**Success Response (200):**
```json
{
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
  "token_type": "Bearer",
  "expires_in": 3600
}
```

| Field | Description |
|-------|-------------|
| access_token | JWT token to use in Authorization header |
| token_type | Always `"Bearer"` |
| expires_in | Token lifetime in seconds (default: 3600 = 60 minutes) |

**Error Response (401):**
```json
{
  "error": "Unauthorized",
  "message": "Invalid username or password."
}
```

---

### 1.2 Logout

Invalidate the current session (stateless — client should discard the token).

| | |
|---|---|
| **URL** | `POST /api/auth/logout` |
| **Auth** | Bearer Token |

**Request Headers:**
```
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...
```

**Success Response (200):**
```json
{
  "message": "Successfully logged out."
}
```

---

### 1.3 Refresh Token

Get a new token using an expired (but still within refresh window) token.

| | |
|---|---|
| **URL** | `POST /api/auth/refresh` |
| **Auth** | Bearer Token (can be expired) |

**Request Headers:**
```
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...
```

**Success Response (200):**
```json
{
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...(new token)",
  "token_type": "Bearer",
  "expires_in": 3600
}
```

**Error Response (401):**
```json
{
  "error": "Unauthorized",
  "message": "Token cannot be refreshed."
}
```

**Note:** The refresh window is 14 days by default (configurable via `JWT_REFRESH_TTL` env variable in minutes).

---

### 1.4 Current User

Get the authenticated user's profile.

| | |
|---|---|
| **URL** | `GET /api/auth/me` |
| **Auth** | Bearer Token |

**Success Response (200):**
```json
{
  "id": 1,
  "username": "admin",
  "first_name": "Əsas",
  "last_name": "İstifadəçi",
  "email": null,
  "full_name": "Əsas İstifadəçi",
  "created_at": "2026-03-16T10:00:00.000000Z",
  "updated_at": "2026-03-16T10:00:00.000000Z"
}
```

---

## 2. Phonebook (Public)

### 2.1 List Phonebook

Returns all employees with their position and structure information. This is the public-facing endpoint — no authentication required.

| | |
|---|---|
| **URL** | `GET /api/phonebook` |
| **Auth** | None |

**Success Response (200):**
```json
[
  {
    "id": 1,
    "first_name": "Elvin",
    "last_name": "Həsənov",
    "father_name": "Rəşad",
    "email": "elvin.hasanov@example.com",
    "landline_number": "012-555-1234",
    "mobile_number": "+994501234567",
    "order": 1,
    "position_id": 1,
    "structure_id": 1,
    "deleted_at": null,
    "created_at": "2026-03-16T10:00:00.000000Z",
    "updated_at": "2026-03-16T10:00:00.000000Z",
    "position": {
      "id": 1,
      "name": "mühəndis",
      "deleted_at": null,
      "created_at": "2026-03-16T10:00:00.000000Z",
      "updated_at": "2026-03-16T10:00:00.000000Z"
    },
    "structure": {
      "id": 1,
      "name": "Dövlət Su Ehtiyatları Agentliyi",
      "parent_id": null,
      "description": null,
      "order": 1,
      "structure_type_id": 1,
      "deleted_at": null,
      "created_at": "2026-03-16T10:00:00.000000Z",
      "updated_at": "2026-03-16T10:00:00.000000Z",
      "structure_type": {
        "id": 1,
        "name": "Kök Struktur",
        "deleted_at": null,
        "created_at": "2026-03-16T10:00:00.000000Z",
        "updated_at": "2026-03-16T10:00:00.000000Z"
      }
    }
  }
]
```

---

## 3. Structure Types

Manage organizational structure types (e.g., "Kök Struktur", "Şöbə", "Sektor").

**All endpoints require Bearer Token authentication.**

### 3.1 List All Structure Types

| | |
|---|---|
| **URL** | `GET /api/structure-types` |
| **Auth** | Bearer Token |

**Success Response (200):**
```json
[
  {
    "id": 1,
    "name": "Kök Struktur",
    "deleted_at": null,
    "created_at": "2026-03-16T10:00:00.000000Z",
    "updated_at": "2026-03-16T10:00:00.000000Z"
  },
  {
    "id": 2,
    "name": "Şöbə",
    "deleted_at": null,
    "created_at": "2026-03-16T10:00:00.000000Z",
    "updated_at": "2026-03-16T10:00:00.000000Z"
  },
  {
    "id": 3,
    "name": "Sektor",
    "deleted_at": null,
    "created_at": "2026-03-16T10:00:00.000000Z",
    "updated_at": "2026-03-16T10:00:00.000000Z"
  }
]
```

---

### 3.2 Show Structure Type

| | |
|---|---|
| **URL** | `GET /api/structure-types/{id}` |
| **Auth** | Bearer Token |

**Success Response (200):**
```json
{
  "id": 2,
  "name": "Şöbə",
  "deleted_at": null,
  "created_at": "2026-03-16T10:00:00.000000Z",
  "updated_at": "2026-03-16T10:00:00.000000Z"
}
```

**Error Response (422) — ID not found:**
```json
{
  "message": "The selected id is invalid.",
  "errors": {
    "id": ["The selected id is invalid."]
  }
}
```

---

### 3.3 Create Structure Type

| | |
|---|---|
| **URL** | `POST /api/structure-types` |
| **Auth** | Bearer Token |

**Request Body:**

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| name | string | Yes | Must be unique in structure_types table |

**Request Example:**
```json
{
  "name": "İdarə"
}
```

**Success Response (201):**
```json
{
  "name": "İdarə",
  "updated_at": "2026-03-16T10:30:00.000000Z",
  "created_at": "2026-03-16T10:30:00.000000Z",
  "id": 4
}
```

**Error Response (422) — Duplicate name:**
```json
{
  "message": "The name has already been taken.",
  "errors": {
    "name": ["The name has already been taken."]
  }
}
```

---

### 3.4 Update Structure Type

| | |
|---|---|
| **URL** | `PUT /api/structure-types/{id}` |
| **Auth** | Bearer Token |

**Request Body:**

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| name | string | Yes | Must be unique (excluding current record) |

**Request Example:**
```json
{
  "name": "Baş İdarə"
}
```

**Success Response (200):**
```json
{
  "id": 4,
  "name": "Baş İdarə",
  "deleted_at": null,
  "created_at": "2026-03-16T10:30:00.000000Z",
  "updated_at": "2026-03-16T10:35:00.000000Z"
}
```

---

### 3.5 Delete Structure Type

Soft deletes the structure type (sets `deleted_at` timestamp, record remains in database).

| | |
|---|---|
| **URL** | `DELETE /api/structure-types/{id}` |
| **Auth** | Bearer Token |

**Success Response (200):**
```json
{
  "message": "Struktur tipi silindi"
}
```

---

## 4. Positions

Manage employee positions (e.g., "mühəndis", "direktor").

**All endpoints require Bearer Token authentication.**

### 4.1 List All Positions

| | |
|---|---|
| **URL** | `GET /api/positions` |
| **Auth** | Bearer Token |

**Success Response (200):**
```json
[
  {
    "id": 1,
    "name": "mühəndis",
    "deleted_at": null,
    "created_at": "2026-03-16T10:00:00.000000Z",
    "updated_at": "2026-03-16T10:00:00.000000Z"
  }
]
```

---

### 4.2 Show Position

| | |
|---|---|
| **URL** | `GET /api/positions/{id}` |
| **Auth** | Bearer Token |

**Success Response (200):**
```json
{
  "id": 1,
  "name": "mühəndis",
  "deleted_at": null,
  "created_at": "2026-03-16T10:00:00.000000Z",
  "updated_at": "2026-03-16T10:00:00.000000Z"
}
```

---

### 4.3 Create Position

| | |
|---|---|
| **URL** | `POST /api/positions` |
| **Auth** | Bearer Token |

**Request Body:**

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| name | string | Yes | Must be unique in positions table |

**Request Example:**
```json
{
  "name": "direktor"
}
```

**Success Response (201):**
```json
{
  "name": "direktor",
  "updated_at": "2026-03-16T10:30:00.000000Z",
  "created_at": "2026-03-16T10:30:00.000000Z",
  "id": 2
}
```

---

### 4.4 Update Position

| | |
|---|---|
| **URL** | `PUT /api/positions/{id}` |
| **Auth** | Bearer Token |

**Request Body:**

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| name | string | Yes | Must be unique (excluding current record) |

**Request Example:**
```json
{
  "name": "baş mühəndis"
}
```

**Success Response (200):**
```json
{
  "id": 1,
  "name": "baş mühəndis",
  "deleted_at": null,
  "created_at": "2026-03-16T10:00:00.000000Z",
  "updated_at": "2026-03-16T10:35:00.000000Z"
}
```

---

### 4.5 Delete Position

| | |
|---|---|
| **URL** | `DELETE /api/positions/{id}` |
| **Auth** | Bearer Token |

**Success Response (200):**
```json
{
  "message": "Vəzifə silindi"
}
```

---

## 5. Structures

Manage the corporate organizational hierarchy.

**All endpoints require Bearer Token authentication.**

### 5.1 List All Structures

Returns all structures with their structure type.

| | |
|---|---|
| **URL** | `GET /api/structures` |
| **Auth** | Bearer Token |

**Success Response (200):**
```json
[
  {
    "id": 1,
    "name": "Dövlət Su Ehtiyatları Agentliyi",
    "parent_id": null,
    "description": null,
    "order": 1,
    "structure_type_id": 1,
    "deleted_at": null,
    "created_at": "2026-03-16T10:00:00.000000Z",
    "updated_at": "2026-03-16T10:00:00.000000Z",
    "structure_type": {
      "id": 1,
      "name": "Kök Struktur",
      "deleted_at": null,
      "created_at": "2026-03-16T10:00:00.000000Z",
      "updated_at": "2026-03-16T10:00:00.000000Z"
    }
  },
  {
    "id": 2,
    "name": "İT Şöbəsi",
    "parent_id": 1,
    "description": null,
    "order": 1,
    "structure_type_id": 2,
    "deleted_at": null,
    "created_at": "2026-03-16T10:20:00.000000Z",
    "updated_at": "2026-03-16T10:20:00.000000Z",
    "structure_type": {
      "id": 2,
      "name": "Şöbə",
      "deleted_at": null,
      "created_at": "2026-03-16T10:00:00.000000Z",
      "updated_at": "2026-03-16T10:00:00.000000Z"
    }
  }
]
```

---

### 5.2 Show Structure

| | |
|---|---|
| **URL** | `GET /api/structures/{id}` |
| **Auth** | Bearer Token |

**Success Response (200):**
```json
{
  "id": 2,
  "name": "İT Şöbəsi",
  "parent_id": 1,
  "description": null,
  "order": 1,
  "structure_type_id": 2,
  "deleted_at": null,
  "created_at": "2026-03-16T10:20:00.000000Z",
  "updated_at": "2026-03-16T10:20:00.000000Z",
  "structure_type": {
    "id": 2,
    "name": "Şöbə",
    "deleted_at": null,
    "created_at": "2026-03-16T10:00:00.000000Z",
    "updated_at": "2026-03-16T10:00:00.000000Z"
  }
}
```

---

### 5.3 Create Structure

| | |
|---|---|
| **URL** | `POST /api/structures` |
| **Auth** | Bearer Token |

**Request Body:**

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| name | string | Yes | Must be unique in structure table |
| description | string | No | Free-text description of the structure |
| structure_type_id | integer | Yes | Must exist in structure_types table |
| order | integer | Yes | Display order |
| parent_id | integer | No | Must exist in structure table (ID of parent structure). Null for root structures |

**Request Example:**
```json
{
  "name": "İT Şöbəsi",
  "description": "İnformasiya texnologiyaları şöbəsi",
  "structure_type_id": 2,
  "order": 1,
  "parent_id": 1
}
```

**Success Response (201):**
```json
{
  "name": "İT Şöbəsi",
  "description": "İnformasiya texnologiyaları şöbəsi",
  "structure_type_id": 2,
  "order": 1,
  "parent_id": 1,
  "updated_at": "2026-03-16T10:20:00.000000Z",
  "created_at": "2026-03-16T10:20:00.000000Z",
  "id": 2
}
```

**Error Response (422) — Validation errors:**
```json
{
  "message": "The name has already been taken. (and 1 more error)",
  "errors": {
    "name": ["The name has already been taken."],
    "parent_id": ["The selected parent id is invalid."]
  }
}
```

---

### 5.4 Update Structure

Partial update — only send the fields you want to change.

| | |
|---|---|
| **URL** | `PUT /api/structures/{id}` |
| **Auth** | Bearer Token |

**Request Body:**

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| name | string | No | Must be unique (excluding current record) |
| description | string | No | Free-text description of the structure |
| structure_type_id | integer | No | Must exist in structure_types table |
| order | integer | No | Display order |
| parent_id | integer | No | Must exist in structure table. Null for root structures |

**Request Example (rename only):**
```json
{
  "name": "İnformasiya Texnologiyaları Şöbəsi"
}
```

**Success Response (200):**
```json
{
  "id": 2,
  "name": "İnformasiya Texnologiyaları Şöbəsi",
  "parent_id": 1,
  "description": "İnformasiya texnologiyaları şöbəsi",
  "order": 1,
  "structure_type_id": 2,
  "deleted_at": null,
  "created_at": "2026-03-16T10:20:00.000000Z",
  "updated_at": "2026-03-16T10:40:00.000000Z"
}
```

---

### 5.5 Delete Structure

| | |
|---|---|
| **URL** | `DELETE /api/structures/{id}` |
| **Auth** | Bearer Token |

**Success Response (200):**
```json
{
  "message": "Struktur silindi"
}
```

---

## 6. Employees

Manage employee records.

**All endpoints require Bearer Token authentication.**

### 6.1 List All Employees

Returns all employees with their position and structure. Supports optional query parameters for filtering.

| | |
|---|---|
| **URL** | `GET /api/employees` |
| **Auth** | Bearer Token |

**Query Parameters (all optional):**

| Parameter | Type | Description |
|-----------|------|-------------|
| structure_id | integer | Exact match — filter by structure. Must be a valid structure ID |
| name | string | Partial, case-insensitive match across `first_name`, `last_name`, and `father_name` |
| email | string | Partial, case-insensitive match on `email` |
| phone_number | string | Partial, case-insensitive match across `landline_number` and `mobile_number` |

Filters are composable — multiple parameters are combined with AND logic.

**Example Requests:**
```
GET /api/employees                              — all employees
GET /api/employees?structure_id=11              — employees in structure 11
GET /api/employees?name=bayram                  — employees whose name contains "bayram"
GET /api/employees?email=adsea                  — employees whose email contains "adsea"
GET /api/employees?phone_number=709             — employees whose phone contains "709"
GET /api/employees?name=bayram&structure_id=12  — combined filters
```

**Success Response (200):**
```json
[
  {
    "id": 1,
    "first_name": "Elvin",
    "last_name": "Həsənov",
    "father_name": "Rəşad",
    "email": "elvin.hasanov@example.com",
    "landline_number": "012-555-1234",
    "mobile_number": "+994501234567",
    "order": 1,
    "position_id": 1,
    "structure_id": 2,
    "deleted_at": null,
    "created_at": "2026-03-16T10:30:00.000000Z",
    "updated_at": "2026-03-16T10:30:00.000000Z",
    "position": {
      "id": 1,
      "name": "mühəndis",
      "deleted_at": null,
      "created_at": "2026-03-16T10:00:00.000000Z",
      "updated_at": "2026-03-16T10:00:00.000000Z"
    },
    "structure": {
      "id": 2,
      "name": "İT Şöbəsi",
      "parent_id": 1,
      "description": null,
      "order": 1,
      "structure_type_id": 2,
      "deleted_at": null,
      "created_at": "2026-03-16T10:20:00.000000Z",
      "updated_at": "2026-03-16T10:20:00.000000Z"
    }
  }
]
```

---

### 6.2 Show Employee

| | |
|---|---|
| **URL** | `GET /api/employees/{id}` |
| **Auth** | Bearer Token |

**Success Response (200):**
```json
{
  "id": 1,
  "first_name": "Elvin",
  "last_name": "Həsənov",
  "father_name": "Rəşad",
  "email": "elvin.hasanov@example.com",
  "landline_number": "012-555-1234",
  "mobile_number": "+994501234567",
  "order": 1,
  "position_id": 1,
  "structure_id": 2,
  "deleted_at": null,
  "created_at": "2026-03-16T10:30:00.000000Z",
  "updated_at": "2026-03-16T10:30:00.000000Z",
  "position": {
    "id": 1,
    "name": "mühəndis",
    "deleted_at": null,
    "created_at": "2026-03-16T10:00:00.000000Z",
    "updated_at": "2026-03-16T10:00:00.000000Z"
  },
  "structure": {
    "id": 2,
    "name": "İT Şöbəsi",
    "parent_id": 1,
    "description": null,
    "order": 1,
    "structure_type_id": 2,
    "deleted_at": null,
    "created_at": "2026-03-16T10:20:00.000000Z",
    "updated_at": "2026-03-16T10:20:00.000000Z"
  }
}
```

---

### 6.3 Create Employee

| | |
|---|---|
| **URL** | `POST /api/employees` |
| **Auth** | Bearer Token |

**Request Body:**

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| first_name | string | Yes | Letters only (alpha) |
| last_name | string | Yes | Letters only (alpha) |
| father_name | string | No | Letters only (alpha) |
| email | string | No | Valid email format |
| landline_number | string | Yes | Must be unique in employees table |
| mobile_number | string | No | Must be unique in employees table |
| order | integer | Yes | Display order |
| position_id | integer | Yes | Must exist in positions table |
| structure_id | integer | Yes | Must exist in structure table |

**Request Example:**
```json
{
  "first_name": "Elvin",
  "last_name": "Həsənov",
  "father_name": "Rəşad",
  "email": "elvin.hasanov@example.com",
  "landline_number": "012-555-1234",
  "mobile_number": "+994501234567",
  "order": 1,
  "position_id": 1,
  "structure_id": 2
}
```

**Success Response (201):**
```json
{
  "first_name": "Elvin",
  "last_name": "Həsənov",
  "father_name": "Rəşad",
  "email": "elvin.hasanov@example.com",
  "landline_number": "012-555-1234",
  "mobile_number": "+994501234567",
  "order": 1,
  "position_id": 1,
  "structure_id": 2,
  "updated_at": "2026-03-16T10:30:00.000000Z",
  "created_at": "2026-03-16T10:30:00.000000Z",
  "id": 1,
  "position": {
    "id": 1,
    "name": "mühəndis",
    "deleted_at": null,
    "created_at": "2026-03-16T10:00:00.000000Z",
    "updated_at": "2026-03-16T10:00:00.000000Z"
  },
  "structure": {
    "id": 2,
    "name": "İT Şöbəsi",
    "parent_id": 1,
    "description": null,
    "order": 1,
    "structure_type_id": 2,
    "deleted_at": null,
    "created_at": "2026-03-16T10:20:00.000000Z",
    "updated_at": "2026-03-16T10:20:00.000000Z"
  }
}
```

**Error Response (422):**
```json
{
  "message": "The first name field is required. (and 2 more errors)",
  "errors": {
    "first_name": ["The first name field is required."],
    "landline_number": ["The landline number has already been taken."],
    "position_id": ["The selected position id is invalid."]
  }
}
```

---

### 6.4 Update Employee

Partial update — only send the fields you want to change.

| | |
|---|---|
| **URL** | `PUT /api/employees/{id}` |
| **Auth** | Bearer Token |

**Request Body:**

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| first_name | string | No | Letters only (alpha) |
| last_name | string | No | Letters only (alpha) |
| father_name | string | No | Letters only (alpha) |
| email | string | No | Valid email format |
| landline_number | string | No | Must be unique (excluding current record) |
| mobile_number | string | No | Must be unique (excluding current record) |
| order | integer | No | Display order |
| position_id | integer | No | Must exist in positions table |
| structure_id | integer | No | Must exist in structure table |

**Request Example (update phone only):**
```json
{
  "mobile_number": "+994551234567"
}
```

**Success Response (200):**
```json
{
  "id": 1,
  "first_name": "Elvin",
  "last_name": "Həsənov",
  "father_name": "Rəşad",
  "email": "elvin.hasanov@example.com",
  "landline_number": "012-555-1234",
  "mobile_number": "+994551234567",
  "order": 1,
  "position_id": 1,
  "structure_id": 2,
  "deleted_at": null,
  "created_at": "2026-03-16T10:30:00.000000Z",
  "updated_at": "2026-03-16T10:45:00.000000Z",
  "position": { "..." : "..." },
  "structure": { "..." : "..." }
}
```

---

### 6.5 Delete Employee

| | |
|---|---|
| **URL** | `DELETE /api/employees/{id}` |
| **Auth** | Bearer Token |

**Success Response (200):**
```json
{
  "message": "İşçi uğurla silindi"
}
```

---

## 7. Error Responses

### 401 Unauthorized — No token or invalid token
```json
{
  "error": "Unauthorized",
  "message": "Token not provided."
}
```

### 401 Unauthorized — Expired token
```json
{
  "error": "Unauthorized",
  "message": "Token has expired."
}
```

### 422 Unprocessable Entity — Validation failed
```json
{
  "message": "The name field is required.",
  "errors": {
    "name": ["The name field is required."]
  }
}
```

### 422 Unprocessable Entity — Record not found (via exists rule)
```json
{
  "message": "The selected id is invalid.",
  "errors": {
    "id": ["The selected id is invalid."]
  }
}
```

---

## Quick Reference

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| POST | `/api/auth/login` | No | Login |
| POST | `/api/auth/logout` | Yes | Logout |
| POST | `/api/auth/refresh` | Token* | Refresh token |
| GET | `/api/auth/me` | Yes | Current user profile |
| GET | `/api/phonebook` | No | Public phonebook listing |
| GET | `/api/structure-types` | Yes | List structure types |
| GET | `/api/structure-types/{id}` | Yes | Show structure type |
| POST | `/api/structure-types` | Yes | Create structure type |
| PUT | `/api/structure-types/{id}` | Yes | Update structure type |
| DELETE | `/api/structure-types/{id}` | Yes | Delete structure type |
| GET | `/api/positions` | Yes | List positions |
| GET | `/api/positions/{id}` | Yes | Show position |
| POST | `/api/positions` | Yes | Create position |
| PUT | `/api/positions/{id}` | Yes | Update position |
| DELETE | `/api/positions/{id}` | Yes | Delete position |
| GET | `/api/structures` | Yes | List structures |
| GET | `/api/structures/{id}` | Yes | Show structure |
| POST | `/api/structures` | Yes | Create structure |
| PUT | `/api/structures/{id}` | Yes | Update structure |
| DELETE | `/api/structures/{id}` | Yes | Delete structure |
| GET | `/api/employees` | Yes | List employees (supports filtering via query params) |
| GET | `/api/employees/{id}` | Yes | Show employee |
| POST | `/api/employees` | Yes | Create employee |
| PUT | `/api/employees/{id}` | Yes | Update employee |
| DELETE | `/api/employees/{id}` | Yes | Delete employee |

*Token can be expired (within refresh window)
