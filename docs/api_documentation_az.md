# PhoneBookAPI — API Sənədləşməsi

**Əsas URL:** `http://localhost:8000/api`

Bütün sorğu və cavablar `Content-Type: application/json` formatındadır.

Qorunan endpointlər üçün başlıq tələb olunur: `Authorization: Bearer <token>`

---

## Mündəricat

1. [Autentifikasiya](#1-autentifikasiya)
   - [Giriş](#11-giriş)
   - [Çıxış](#12-çıxış)
   - [Tokeni Yeniləmə](#13-tokeni-yeniləmə)
   - [Cari İstifadəçi](#14-cari-istifadəçi)
2. [Telefon Kitabçası (İctimai)](#2-telefon-kitabçası-ictimai)
   - [Siyahı](#21-telefon-kitabçası-siyahısı)
3. [Struktur Tipləri](#3-struktur-tipləri)
   - [Hamısını Göstər](#31-bütün-struktur-tiplərini-göstər)
   - [Birini Göstər](#32-struktur-tipini-göstər)
   - [Yarat](#33-struktur-tipi-yarat)
   - [Yenilə](#34-struktur-tipini-yenilə)
   - [Sil](#35-struktur-tipini-sil)
4. [Vəzifələr](#4-vəzifələr)
   - [Hamısını Göstər](#41-bütün-vəzifələri-göstər)
   - [Birini Göstər](#42-vəzifəni-göstər)
   - [Yarat](#43-vəzifə-yarat)
   - [Yenilə](#44-vəzifəni-yenilə)
   - [Sil](#45-vəzifəni-sil)
5. [Strukturlar](#5-strukturlar)
   - [Hamısını Göstər](#51-bütün-strukturları-göstər)
   - [Birini Göstər](#52-strukturu-göstər)
   - [Yarat](#53-struktur-yarat)
   - [Yenilə](#54-strukturu-yenilə)
   - [Sil](#55-strukturu-sil)
6. [İşçilər](#6-işçilər)
   - [Hamısını Göstər](#61-bütün-işçiləri-göstər)
   - [Birini Göstər](#62-işçini-göstər)
   - [Yarat](#63-işçi-yarat)
   - [Yenilə](#64-işçini-yenilə)
   - [Sil](#65-işçini-sil)
7. [Xəta Cavabları](#7-xəta-cavabları)

---

## 1. Autentifikasiya

### 1.1 Giriş

İstifadəçini autentifikasiya edin və JWT token alın.

| | |
|---|---|
| **URL** | `POST /api/auth/login` |
| **Autentifikasiya** | Yoxdur |

**Sorğu Gövdəsi:**

| Sahə | Tip | Məcburi | Qaydalar |
|------|-----|---------|----------|
| username | string | Bəli | İstifadəçilər cədvəlində mövcud olmalıdır |
| password | string | Bəli | |

**Sorğu Nümunəsi:**
```json
{
  "username": "admin",
  "password": "adminpanel123"
}
```

**Uğurlu Cavab (200):**
```json
{
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
  "token_type": "Bearer",
  "expires_in": 3600
}
```

| Sahə | Açıqlama |
|------|----------|
| access_token | Authorization başlığında istifadə ediləcək JWT token |
| token_type | Həmişə `"Bearer"` |
| expires_in | Tokenin ömrü saniyələrlə (standart: 3600 = 60 dəqiqə) |

**Xəta Cavabı (401):**
```json
{
  "error": "Unauthorized",
  "message": "Invalid username or password."
}
```

---

### 1.2 Çıxış

Cari sessiyanı ləğv edin (stateless — müştəri tokeni silməlidir).

| | |
|---|---|
| **URL** | `POST /api/auth/logout` |
| **Autentifikasiya** | Bearer Token |

**Sorğu Başlıqları:**
```
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...
```

**Uğurlu Cavab (200):**
```json
{
  "message": "Successfully logged out."
}
```

---

### 1.3 Tokeni Yeniləmə

Vaxtı keçmiş (lakin yeniləmə pəncərəsi daxilində olan) token ilə yeni token alın.

| | |
|---|---|
| **URL** | `POST /api/auth/refresh` |
| **Autentifikasiya** | Bearer Token (vaxtı keçmiş ola bilər) |

**Sorğu Başlıqları:**
```
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...
```

**Uğurlu Cavab (200):**
```json
{
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...(yeni token)",
  "token_type": "Bearer",
  "expires_in": 3600
}
```

**Xəta Cavabı (401):**
```json
{
  "error": "Unauthorized",
  "message": "Token cannot be refreshed."
}
```

**Qeyd:** Yeniləmə pəncərəsi standart olaraq 14 gündür (`JWT_REFRESH_TTL` env dəyişəni ilə dəqiqə ilə konfiqurasiya oluna bilər).

---

### 1.4 Cari İstifadəçi

Autentifikasiya olunmuş istifadəçinin profilini əldə edin.

| | |
|---|---|
| **URL** | `GET /api/auth/me` |
| **Autentifikasiya** | Bearer Token |

**Uğurlu Cavab (200):**
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

## 2. Telefon Kitabçası (İctimai)

### 2.1 Telefon Kitabçası Siyahısı

Bütün işçiləri vəzifə və struktur məlumatları ilə qaytarır. Bu ictimai endpointdir — autentifikasiya tələb olunmur.

| | |
|---|---|
| **URL** | `GET /api/phonebook` |
| **Autentifikasiya** | Yoxdur |

**Uğurlu Cavab (200):**
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

## 3. Struktur Tipləri

Təşkilati struktur tiplərini idarə edin (məs., "Kök Struktur", "Şöbə", "Sektor").

**Bütün endpointlər Bearer Token autentifikasiyası tələb edir.**

### 3.1 Bütün Struktur Tiplərini Göstər

| | |
|---|---|
| **URL** | `GET /api/structure-types` |
| **Autentifikasiya** | Bearer Token |

**Uğurlu Cavab (200):**
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

### 3.2 Struktur Tipini Göstər

| | |
|---|---|
| **URL** | `GET /api/structure-types/{id}` |
| **Autentifikasiya** | Bearer Token |

**Uğurlu Cavab (200):**
```json
{
  "id": 2,
  "name": "Şöbə",
  "deleted_at": null,
  "created_at": "2026-03-16T10:00:00.000000Z",
  "updated_at": "2026-03-16T10:00:00.000000Z"
}
```

**Xəta Cavabı (422) — ID tapılmadı:**
```json
{
  "message": "The selected id is invalid.",
  "errors": {
    "id": ["The selected id is invalid."]
  }
}
```

---

### 3.3 Struktur Tipi Yarat

| | |
|---|---|
| **URL** | `POST /api/structure-types` |
| **Autentifikasiya** | Bearer Token |

**Sorğu Gövdəsi:**

| Sahə | Tip | Məcburi | Qaydalar |
|------|-----|---------|----------|
| name | string | Bəli | structure_types cədvəlində unikal olmalıdır |

**Sorğu Nümunəsi:**
```json
{
  "name": "İdarə"
}
```

**Uğurlu Cavab (201):**
```json
{
  "name": "İdarə",
  "updated_at": "2026-03-16T10:30:00.000000Z",
  "created_at": "2026-03-16T10:30:00.000000Z",
  "id": 4
}
```

**Xəta Cavabı (422) — Təkrar ad:**
```json
{
  "message": "The name has already been taken.",
  "errors": {
    "name": ["The name has already been taken."]
  }
}
```

---

### 3.4 Struktur Tipini Yenilə

| | |
|---|---|
| **URL** | `PUT /api/structure-types/{id}` |
| **Autentifikasiya** | Bearer Token |

**Sorğu Gövdəsi:**

| Sahə | Tip | Məcburi | Qaydalar |
|------|-----|---------|----------|
| name | string | Bəli | Unikal olmalıdır (cari qeyd istisna) |

**Sorğu Nümunəsi:**
```json
{
  "name": "Baş İdarə"
}
```

**Uğurlu Cavab (200):**
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

### 3.5 Struktur Tipini Sil

Struktur tipini yumşaq silir (`deleted_at` vaxt damğası qoyulur, qeyd verilənlər bazasında qalır).

| | |
|---|---|
| **URL** | `DELETE /api/structure-types/{id}` |
| **Autentifikasiya** | Bearer Token |

**Uğurlu Cavab (200):**
```json
{
  "message": "Struktur tipi silindi"
}
```

---

## 4. Vəzifələr

İşçi vəzifələrini idarə edin (məs., "mühəndis", "direktor").

**Bütün endpointlər Bearer Token autentifikasiyası tələb edir.**

### 4.1 Bütün Vəzifələri Göstər

| | |
|---|---|
| **URL** | `GET /api/positions` |
| **Autentifikasiya** | Bearer Token |

**Uğurlu Cavab (200):**
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

### 4.2 Vəzifəni Göstər

| | |
|---|---|
| **URL** | `GET /api/positions/{id}` |
| **Autentifikasiya** | Bearer Token |

**Uğurlu Cavab (200):**
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

### 4.3 Vəzifə Yarat

| | |
|---|---|
| **URL** | `POST /api/positions` |
| **Autentifikasiya** | Bearer Token |

**Sorğu Gövdəsi:**

| Sahə | Tip | Məcburi | Qaydalar |
|------|-----|---------|----------|
| name | string | Bəli | positions cədvəlində unikal olmalıdır |

**Sorğu Nümunəsi:**
```json
{
  "name": "direktor"
}
```

**Uğurlu Cavab (201):**
```json
{
  "name": "direktor",
  "updated_at": "2026-03-16T10:30:00.000000Z",
  "created_at": "2026-03-16T10:30:00.000000Z",
  "id": 2
}
```

---

### 4.4 Vəzifəni Yenilə

| | |
|---|---|
| **URL** | `PUT /api/positions/{id}` |
| **Autentifikasiya** | Bearer Token |

**Sorğu Gövdəsi:**

| Sahə | Tip | Məcburi | Qaydalar |
|------|-----|---------|----------|
| name | string | Bəli | Unikal olmalıdır (cari qeyd istisna) |

**Sorğu Nümunəsi:**
```json
{
  "name": "baş mühəndis"
}
```

**Uğurlu Cavab (200):**
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

### 4.5 Vəzifəni Sil

| | |
|---|---|
| **URL** | `DELETE /api/positions/{id}` |
| **Autentifikasiya** | Bearer Token |

**Uğurlu Cavab (200):**
```json
{
  "message": "Vəzifə silindi"
}
```

---

## 5. Strukturlar

Korporativ təşkilati iyerarxiyanı idarə edin.

**Bütün endpointlər Bearer Token autentifikasiyası tələb edir.**

### 5.1 Bütün Strukturları Göstər

Bütün strukturları struktur tipləri ilə birlikdə qaytarır.

| | |
|---|---|
| **URL** | `GET /api/structures` |
| **Autentifikasiya** | Bearer Token |

**Uğurlu Cavab (200):**
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

### 5.2 Strukturu Göstər

| | |
|---|---|
| **URL** | `GET /api/structures/{id}` |
| **Autentifikasiya** | Bearer Token |

**Uğurlu Cavab (200):**
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

### 5.3 Struktur Yarat

| | |
|---|---|
| **URL** | `POST /api/structures` |
| **Autentifikasiya** | Bearer Token |

**Sorğu Gövdəsi:**

| Sahə | Tip | Məcburi | Qaydalar |
|------|-----|---------|----------|
| name | string | Bəli | structure cədvəlində unikal olmalıdır |
| description | string | Xeyr | Strukturun sərbəst mətn təsviri |
| structure_type_id | integer | Bəli | structure_types cədvəlində mövcud olmalıdır |
| order | integer | Bəli | Göstərmə sırası |
| parent_id | integer | Xeyr | structure cədvəlində mövcud olmalıdır (ana strukturun ID-si). Kök strukturlar üçün null |

**Sorğu Nümunəsi:**
```json
{
  "name": "İT Şöbəsi",
  "description": "İnformasiya texnologiyaları şöbəsi",
  "structure_type_id": 2,
  "order": 1,
  "parent_id": 1
}
```

**Uğurlu Cavab (201):**
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

**Xəta Cavabı (422) — Validasiya xətaları:**
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

### 5.4 Strukturu Yenilə

Qismən yeniləmə — yalnız dəyişdirmək istədiyiniz sahələri göndərin.

| | |
|---|---|
| **URL** | `PUT /api/structures/{id}` |
| **Autentifikasiya** | Bearer Token |

**Sorğu Gövdəsi:**

| Sahə | Tip | Məcburi | Qaydalar |
|------|-----|---------|----------|
| name | string | Xeyr | Unikal olmalıdır (cari qeyd istisna) |
| description | string | Xeyr | Strukturun sərbəst mətn təsviri |
| structure_type_id | integer | Xeyr | structure_types cədvəlində mövcud olmalıdır |
| order | integer | Xeyr | Göstərmə sırası |
| parent_id | integer | Xeyr | structure cədvəlində mövcud olmalıdır. Kök strukturlar üçün null |

**Sorğu Nümunəsi (yalnız adı dəyişdirmək):**
```json
{
  "name": "İnformasiya Texnologiyaları Şöbəsi"
}
```

**Uğurlu Cavab (200):**
```json
{
  "id": 2,
  "name": "İnformasiya Texnologiyaları Şöbəsi",
  "parent_id": 1,
  "description": null,
  "order": 1,
  "structure_type_id": 2,
  "deleted_at": null,
  "created_at": "2026-03-16T10:20:00.000000Z",
  "updated_at": "2026-03-16T10:40:00.000000Z"
}
```

---

### 5.5 Strukturu Sil

| | |
|---|---|
| **URL** | `DELETE /api/structures/{id}` |
| **Autentifikasiya** | Bearer Token |

**Uğurlu Cavab (200):**
```json
{
  "message": "Struktur silindi"
}
```

---

## 6. İşçilər

İşçi qeydlərini idarə edin.

**Bütün endpointlər Bearer Token autentifikasiyası tələb edir.**

### 6.1 Bütün İşçiləri Göstər

Bütün işçiləri vəzifə və struktur məlumatları ilə qaytarır.

| | |
|---|---|
| **URL** | `GET /api/employees` |
| **Autentifikasiya** | Bearer Token |

**Uğurlu Cavab (200):**
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

### 6.2 İşçini Göstər

| | |
|---|---|
| **URL** | `GET /api/employees/{id}` |
| **Autentifikasiya** | Bearer Token |

**Uğurlu Cavab (200):**
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

### 6.3 İşçi Yarat

| | |
|---|---|
| **URL** | `POST /api/employees` |
| **Autentifikasiya** | Bearer Token |

**Sorğu Gövdəsi:**

| Sahə | Tip | Məcburi | Qaydalar |
|------|-----|---------|----------|
| first_name | string | Bəli | Yalnız hərflər (alpha) |
| last_name | string | Bəli | Yalnız hərflər (alpha) |
| father_name | string | Xeyr | Yalnız hərflər (alpha) |
| email | string | Xeyr | Düzgün email formatı |
| landline_number | string | Bəli | employees cədvəlində unikal olmalıdır |
| mobile_number | string | Xeyr | employees cədvəlində unikal olmalıdır |
| order | integer | Bəli | Göstərmə sırası |
| position_id | integer | Bəli | positions cədvəlində mövcud olmalıdır |
| structure_id | integer | Bəli | structure cədvəlində mövcud olmalıdır |

**Sorğu Nümunəsi:**
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

**Uğurlu Cavab (201):**
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

**Xəta Cavabı (422):**
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

### 6.4 İşçini Yenilə

Qismən yeniləmə — yalnız dəyişdirmək istədiyiniz sahələri göndərin.

| | |
|---|---|
| **URL** | `PUT /api/employees/{id}` |
| **Autentifikasiya** | Bearer Token |

**Sorğu Gövdəsi:**

| Sahə | Tip | Məcburi | Qaydalar |
|------|-----|---------|----------|
| first_name | string | Xeyr | Yalnız hərflər (alpha) |
| last_name | string | Xeyr | Yalnız hərflər (alpha) |
| father_name | string | Xeyr | Yalnız hərflər (alpha) |
| email | string | Xeyr | Düzgün email formatı |
| landline_number | string | Xeyr | Unikal olmalıdır (cari qeyd istisna) |
| mobile_number | string | Xeyr | Unikal olmalıdır (cari qeyd istisna) |
| order | integer | Xeyr | Göstərmə sırası |
| position_id | integer | Xeyr | positions cədvəlində mövcud olmalıdır |
| structure_id | integer | Xeyr | structure cədvəlində mövcud olmalıdır |

**Sorğu Nümunəsi (yalnız telefonu yeniləmək):**
```json
{
  "mobile_number": "+994551234567"
}
```

**Uğurlu Cavab (200):**
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

### 6.5 İşçini Sil

| | |
|---|---|
| **URL** | `DELETE /api/employees/{id}` |
| **Autentifikasiya** | Bearer Token |

**Uğurlu Cavab (200):**
```json
{
  "message": "İşçi uğurla silindi"
}
```

---

## 7. Xəta Cavabları

### 401 İcazəsiz — Token yoxdur və ya etibarsız tokendir
```json
{
  "error": "Unauthorized",
  "message": "Token not provided."
}
```

### 401 İcazəsiz — Vaxtı keçmiş token
```json
{
  "error": "Unauthorized",
  "message": "Token has expired."
}
```

### 422 İşlənə Bilməyən Obyekt — Validasiya xətası
```json
{
  "message": "The name field is required.",
  "errors": {
    "name": ["The name field is required."]
  }
}
```

### 422 İşlənə Bilməyən Obyekt — Qeyd tapılmadı (exists qaydası vasitəsilə)
```json
{
  "message": "The selected id is invalid.",
  "errors": {
    "id": ["The selected id is invalid."]
  }
}
```

---

## Sürətli Arayış

| Metod | Endpoint | Autent. | Açıqlama |
|-------|----------|---------|----------|
| POST | `/api/auth/login` | Xeyr | Giriş |
| POST | `/api/auth/logout` | Bəli | Çıxış |
| POST | `/api/auth/refresh` | Token* | Tokeni yenilə |
| GET | `/api/auth/me` | Bəli | Cari istifadəçi profili |
| GET | `/api/phonebook` | Xeyr | İctimai telefon kitabçası siyahısı |
| GET | `/api/structure-types` | Bəli | Struktur tiplərini göstər |
| GET | `/api/structure-types/{id}` | Bəli | Struktur tipini göstər |
| POST | `/api/structure-types` | Bəli | Struktur tipi yarat |
| PUT | `/api/structure-types/{id}` | Bəli | Struktur tipini yenilə |
| DELETE | `/api/structure-types/{id}` | Bəli | Struktur tipini sil |
| GET | `/api/positions` | Bəli | Vəzifələri göstər |
| GET | `/api/positions/{id}` | Bəli | Vəzifəni göstər |
| POST | `/api/positions` | Bəli | Vəzifə yarat |
| PUT | `/api/positions/{id}` | Bəli | Vəzifəni yenilə |
| DELETE | `/api/positions/{id}` | Bəli | Vəzifəni sil |
| GET | `/api/structures` | Bəli | Strukturları göstər |
| GET | `/api/structures/{id}` | Bəli | Strukturu göstər |
| POST | `/api/structures` | Bəli | Struktur yarat |
| PUT | `/api/structures/{id}` | Bəli | Strukturu yenilə |
| DELETE | `/api/structures/{id}` | Bəli | Strukturu sil |
| GET | `/api/employees` | Bəli | İşçiləri göstər |
| GET | `/api/employees/{id}` | Bəli | İşçini göstər |
| POST | `/api/employees` | Bəli | İşçi yarat |
| PUT | `/api/employees/{id}` | Bəli | İşçini yenilə |
| DELETE | `/api/employees/{id}` | Bəli | İşçini sil |

*Token vaxtı keçmiş ola bilər (yeniləmə pəncərəsi daxilində)
