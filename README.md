# 🎀 notes app api 🎀

> *a cute little restful api for managing your personal notes, built with love using laravel 12* ✨

---

## 🌸 features

- 🔐 google oauth 2.0 login & register
- 💌 manual register & login with email verification
- 🔑 forgot & reset password via email
- 🔒 change password
- 🎟️ jwt authentication
- 📓 full crud for personal notes
- 🏷️ tagging system (per user, many-to-many)
- 🔍 search notes by title or content
- 📄 pagination with configurable page size
- 🆔 uuid primary keys
- 💅 standardized json responses
- 🧪 feature tests with phpunit

---

## 🩰 tech stack

| | |
|---|---|
| **framework** | laravel 12 |
| **auth** | jwt (tymon/jwt-auth) + google oauth (laravel/socialite) |
| **database** | mysql |
| **testing** | phpunit |

---

## 🎀 requirements

- php >= 8.2
- composer
- mysql
- google oauth credentials

---

## 🌷 installation & setup

### 1. clone repository

```bash
git clone https://github.com/naandptr/notes-app-api.git
cd notes-app-api
```

### 2. install dependencies

```bash
composer install
```

### 3. copy environment file

```bash
cp .env.example .env
```

### 4. generate app key

```bash
php artisan key:generate
```

### 5. generate jwt secret

```bash
php artisan jwt:secret
```

### 6. configure `.env` 🌸

```env
APP_NAME="Notes App"
APP_URL=http://localhost:8000
FRONTEND_URL=http://localhost:3000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=db_notes_app
DB_USERNAME=root
DB_PASSWORD=

GOOGLE_CLIENT_ID=your-google-client-id
GOOGLE_CLIENT_SECRET=your-google-client-secret
GOOGLE_REDIRECT_URI=http://localhost:8000/api/auth/google/callback

MAIL_MAILER=smtp
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your-mailtrap-username
MAIL_PASSWORD=your-mailtrap-password
MAIL_FROM_ADDRESS=noreply@notesapp.com
MAIL_FROM_NAME="Notes App"
```

### 7. run migrations

```bash
php artisan migrate
```

### 8. start server 🎀

```bash
php artisan serve
```

---

## 🌹 google oauth setup

1. go to [console.cloud.google.com](https://console.cloud.google.com) 🌐
2. create a new project 🆕
3. enable **google people api** ✅
4. go to **apis & services** → **oauth consent screen** → configure 🛠️
5. go to **credentials** → **create credentials** → **oauth 2.0 client ids** 🔑
6. set **authorized redirect uris** to `http://localhost:8000/api/auth/google/callback`
7. copy **client id** and **client secret** to `.env` 💌

---

## 🎀 api endpoints

### 🔐 auth

| method | endpoint | description | auth |
|--------|----------|-------------|------|
| `GET` | `/api/auth/google/redirect` | get google oauth redirect url | ✗ |
| `GET` | `/api/auth/google/callback` | handle google oauth callback | ✗ |
| `POST` | `/api/auth/register` | register with email & password | ✗ |
| `POST` | `/api/auth/login` | login with email & password | ✗ |
| `POST` | `/api/auth/logout` | logout | ✓ |
| `POST` | `/api/auth/refresh` | refresh jwt token | ✓ |
| `POST` | `/api/auth/change-password` | change password | ✓ |
| `POST` | `/api/auth/forgot-password` | send password reset link | ✗ |
| `POST` | `/api/auth/reset-password` | reset password with token | ✗ |
| `GET` | `/api/auth/verify-email/{id}/{hash}` | verify email address | ✗ |
| `POST` | `/api/auth/resend-verification` | resend verification email | ✗ |

> ⚠️ change password is not available for google oauth users without a password

### 📓 notes

| method | endpoint | description | auth |
|--------|----------|-------------|------|
| `GET` | `/api/notes` | get all notes | ✓ |
| `POST` | `/api/notes` | create a new note | ✓ |
| `GET` | `/api/notes/{id}` | get a single note | ✓ |
| `PUT/PATCH` | `/api/notes/{id}` | update a note | ✓ |
| `DELETE` | `/api/notes/{id}` | delete a note | ✓ |

#### 🔍 query parameters

| parameter | type | description | example |
|-----------|------|-------------|---------|
| `page` | integer | page number | `?page=1` |
| `per_page` | integer | items per page (default: 10) | `?per_page=5` |
| `search` | string | search by title or content | `?search=laravel` |
| `tag_id` | uuid | filter by tag | `?tag_id=019cf4d0-...` |

### 🏷️ tags

| method | endpoint | description | auth |
|--------|----------|-------------|------|
| `GET` | `/api/tags` | get all tags | ✓ |
| `POST` | `/api/tags` | create a new tag | ✓ |
| `DELETE` | `/api/tags/{id}` | delete a tag | ✓ |

---

## 💅 request & response examples

### register 🌸
```http
POST /api/auth/register
Content-Type: application/json

{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123",
    "password_confirmation": "password123"
}
```

```json
{
    "success": true,
    "code": 201,
    "message": "Registration successful. Please check your email to verify your account.",
    "data": null
}
```

### login 🔑
```http
POST /api/auth/login
Content-Type: application/json

{
    "email": "john@example.com",
    "password": "password123"
}
```

```json
{
    "success": true,
    "code": 200,
    "message": "User retrieved successfully",
    "data": {
        "token": "eyJ0eXAiOiJKV1Qi...",
        "type": "bearer",
        "expires_in": 3600,
        "user": {
            "id": "019cea71-d973-73d3-9c20-d2bea6a6826f",
            "name": "John Doe",
            "email": "john@example.com"
        }
    }
}
```

### change password 🔒
```http
POST /api/auth/change-password
Authorization: Bearer {token}
Content-Type: application/json

{
    "current_password": "password123",
    "password": "newpassword123",
    "password_confirmation": "newpassword123"
}
```

```json
{
    "success": true,
    "code": 200,
    "message": "Password changed successfully",
    "data": null
}
```

### create tag 🏷️
```http
POST /api/tags
Authorization: Bearer {token}
Content-Type: application/json

{
    "tag_name": "Laravel"
}
```

```json
{
    "success": true,
    "code": 201,
    "message": "Tag created successfully",
    "data": {
        "id": "019cf4d0-c324-7067-85dd-cfa8e926d8d2",
        "tag_name": "Laravel",
        "created_by": "019cea71-d973-73d3-9c20-d2bea6a6826f"
    }
}
```

### create note 📓
```http
POST /api/notes
Authorization: Bearer {token}
Content-Type: application/json

{
    "note_title": "My First Note",
    "note_content": "This is the content of my note.",
    "tag_ids": [
        "019cf4d0-c324-7067-85dd-cfa8e926d8d2"
    ]
}
```

```json
{
    "success": true,
    "code": 201,
    "message": "Note created successfully",
    "data": {
        "id": "019cf4d0-c324-7067-85dd-cfa8e926d8d3",
        "note_title": "My First Note",
        "note_content": "This is the content of my note.",
        "created_by": "019cea71-d973-73d3-9c20-d2bea6a6826f",
        "tags": [
            {
                "id": "019cf4d0-c324-7067-85dd-cfa8e926d8d2",
                "tag_name": "Laravel"
            }
        ]
    }
}
```

### get notes 🔍
```http
GET /api/notes?page=1&per_page=10&search=laravel&tag_id=019cf4d0-...
Authorization: Bearer {token}
```

```json
{
    "success": true,
    "code": 200,
    "message": "Note retrieved successfully",
    "data": [...],
    "meta": {
        "current_page": 1,
        "per_page": 10,
        "total": 25,
        "last_page": 3
    }
}
```

### update note ✏️
```http
PUT /api/notes/{id}
Authorization: Bearer {token}
Content-Type: application/json

{
    "note_title": "Updated Title",
    "note_content": "Updated content.",
    "tag_ids": []
}
```

---

## 🚨 standard error responses

| code | message | description |
|------|---------|-------------|
| `400` | Bad Request | invalid request parameters |
| `401` | Unauthenticated | missing or invalid token |
| `403` | Forbidden | access denied to resource |
| `404` | Resource not found | resource does not exist |
| `422` | Validation Error | request validation failed |
| `500` | Internal Server Error | server error |

---

## 🧪 running tests

```bash
# run all tests 🎀
php artisan test

# run specific test file
php artisan test --filter GoogleAuthTest
php artisan test --filter AuthTest
php artisan test --filter NoteTest
php artisan test --filter TagTest
php artisan test --filter EmailVerificationTest
php artisan test --filter ForgotResetPasswordTest
```

### 🌸 test coverage

| test file | coverage |
|-----------|----------|
| `GoogleAuthTest` | google oauth register, login, update google_id, auth failure |
| `AuthTest` | register, login, logout, change password (manual) |
| `NoteTest` | crud notes, search, pagination, filter by tag, authorization checks |
| `TagTest` | crud tags, tag & note relation, authorization checks |
| `EmailVerificationTest` | verify email, resend verification |
| `ForgotResetPasswordTest` | forgot password, reset password |

---

*made with 💝 and a lot of ☕*
