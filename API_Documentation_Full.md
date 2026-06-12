# ChairPal - Complete API Documentation

## Global Standards

- **Base URL:** `https://chairpal-api.duckdns.org`
- **Default Headers:**
    - `Accept: application/json`
    - `Authorization: Bearer {token}` _(Required for all protected endpoints)_
- **Data Formats:**
    - `GET`: Parameters are sent in the **URL (Query Parameters)**.
    - `POST / PUT / DELETE`: Data is sent in the **Body (JSON)**.
    - **File Uploads**: Requests with files must use `Content-Type: multipart/form-data`.

---

## 1. Authentication

### Register

- **Endpoint:** `POST /api/signup`
- **Authorization:** Not Required

**Body Data:**
| Field | Type | Required | Description | Options |
|---|---|---|---|---|
| `name` | String | Yes | User's full name | |
| `email` | String | Yes | User's email | Valid email format |
| `password` | String | Yes | Password | Min 8 characters |
| `password_confirmation` | String | Yes | Confirm password | Must match password |
| `role` | String | Yes | Type of user account | `user`, `admin`, `org_owner` |
| `medical_condition_ids` | Array[Int] | No | User's medical conditions IDs | e.g., `[1, 2]` |

**Response (200 OK):**

```json
{
    "message": "Welcome to ChairPal! Please verify your email with the code we’ve sent to you.",
    "data": []
}
```

### Login

- **Endpoint:** `POST /api/login`
- **Authorization:** Not Required

**Body Data:**
| Field | Type | Required | Description | Options |
|---|---|---|---|---|
| `email` | String | Yes | Registered email | |
| `password` | String | Yes | Account password | |

**Response (200 OK):**

```json
{
    "message": "Logged in successfully.",
    "data": {
        "data": {
            "id": 1,
            "name": "Jane Doe",
            "email": "jane@example.com",
            "role": "user"
        },
        "access_token": "1|token...",
        "access_token_expires_in": 7200,
        "remember_token": "2|token...",
        "remember_token_expires_in": 1209600
    }
}
```

### Refresh Token

- **Endpoint:** `POST /api/refresh-token`
- **Authorization:** Required (Send the `remember_token` as Bearer)

**Body Data:** None

**Response (200 OK):**

```json
{
    "message": "Token Refreshed successfully.",
    "data": {
        "access_token": "3|new_token...",
        "access_token_expires_in": 7200,
        "remember_token": "4|new_remember...",
        "remember_token_expires_in": 1209600
    }
}
```

### Forget Password

- **Endpoint:** `POST /api/forget-password`
- **Authorization:** Not Required

**Body Data:**
| Field | Type | Required | Description | Options |
|---|---|---|---|---|
| `email` | String | Yes | Registered email to send OTP | |

**Response (200 OK):**

```json
{
    "message": "The Verification code (OTP) has been sent to your email.",
    "data": []
}
```

### Resend OTP (Password)

- **Endpoint:** `POST /api/resend-otp`
- **Authorization:** Not Required

**Body Data:**
| Field | Type | Required | Description | Options |
|---|---|---|---|---|
| `email` | String | Yes | Registered email | |

**Response (200 OK):**

```json
{
    "message": "Verification code (OTP) has been resent successfully.",
    "data": []
}
```

### Verify OTP (Password)

- **Endpoint:** `POST /api/verify-otp`
- **Authorization:** Not Required

**Body Data:**
| Field | Type | Required | Description | Options |
|---|---|---|---|---|
| `email` | String | Yes | Registered email | |
| `otp` | String | Yes | 4 or 6 digit code sent to email | |

**Response (200 OK):**

```json
{
    "message": "Awesome! Your Verification code (OTP) has been verified successfully.",
    "data": []
}
```

### Reset Password

- **Endpoint:** `POST /api/reset-password`
- **Authorization:** Not Required

**Body Data:**
| Field | Type | Required | Description | Options |
|---|---|---|---|---|
| `email` | String | Yes | Registered email | |
| `password` | String | Yes | New password | Min 8 characters |
| `password_confirmation` | String | Yes | Confirm new password | |
| `token` | String | Yes | Token received after verifying OTP | |

**Response (200 OK):**

```json
{
    "message": "Password has been reset successfully.",
    "data": []
}
```

### Verify Email

- **Endpoint:** `POST /api/verify-email`
- **Authorization:** Not Required

**Body Data:**
| Field | Type | Required | Description | Options |
|---|---|---|---|---|
| `email` | String | Yes | Registered email | |
| `otp` | String | Yes | Verification code sent upon signup | |

**Response (200 OK):**

```json
{
    "message": "Awesome! Your email has been verified successfully.",
    "data": []
}
```

### Logout

- **Endpoint:** `POST /api/logout`
- **Authorization:** Required

**Body Data:** None

**Response (200 OK):**

```json
{
    "message": "You have been logged out successfully.",
    "data": []
}
```

---

## 2. Profile

### Update Profile

- **Endpoint:** `PUT /api/profile/update`
- **Authorization:** Required
- **Headers:** `Content-Type: multipart/form-data` (if uploading image)

**Body Data:**
| Field | Type | Required | Description | Options |
|---|---|---|---|---|
| `name` | String | No | Full name | |
| `phone` | String | No | Phone number | |
| `gender` | String | No | Gender | `male`, `female` |
| `birth_date` | Date | No | Date of birth | Format: `Y-m-d` |
| `weight` | Float | No | User weight in KG | |
| `height` | Float | No | User height in CM | |
| `medical_condition_ids` | Array[Int] | No | IDs of medical conditions | e.g., `[1, 2]` |
| `image` | File | No | Profile picture | jpeg, png, jpg |

**Response (200 OK):**

```json
{
    "message": "Profile updated successfully.",
    "data": { "id": 1, "name": "Jane Doe", "gender": "female" }
}
```

### Change Password

- **Endpoint:** `PUT /api/profile/change-password`
- **Authorization:** Required

**Body Data:**
| Field | Type | Required | Description | Options |
|---|---|---|---|---|
| `current_password` | String | Yes | Current account password | |
| `password` | String | Yes | New password | Min 8 chars |
| `password_confirmation`| String | Yes | Confirm new password | |

**Response (200 OK):**

```json
{
    "message": "Password changed successfully.",
    "data": []
}
```

---

## 3. Chatbot (Context-Aware AI Assistant)

### Get All Sessions

- **Endpoint:** `GET /api/chatbot/sessions`
- **Authorization:** Required

**Query Parameters:** None

**Response (200 OK):**

```json
{
    "message": "Chat sessions retrieved successfully.",
    "data": [{ "id": 1, "title": "New Chat", "user_id": 1 }]
}
```

### Create Session

- **Endpoint:** `POST /api/chatbot/sessions`
- **Authorization:** Required

**Body Data:**
| Field | Type | Required | Description | Options |
|---|---|---|---|---|
| `title` | String | No | Custom title for the chat | Default: "New Chat" |

**Response (200 OK):**

```json
{
    "message": "Chat session created",
    "data": { "id": 1, "title": "My first chat" }
}
```

### Get Session Details (Messages)

- **Endpoint:** `GET /api/chatbot/sessions/{session}`
- **Authorization:** Required

**Path Parameters:**
| Field | Type | Required | Description | Options |
|---|---|---|---|---|
| `session` | Integer | Yes | The ID of the session | |

**Response (200 OK):**

```json
{
    "message": "Session retrieved successfully.",
    "data": {
        "id": 1,
        "title": "My first chat",
        "messages": [
            { "id": 1, "sender_type": "user", "content": "انا حاسس بتعب" },
            {
                "id": 2,
                "sender_type": "bot",
                "content": "سلامتك يا أحمد. ألاحظ أن نبضات قلبك مرتفعة."
            }
        ]
    }
}
```

### Send a Message

- **Endpoint:** `POST /api/chatbot/sessions/{session}/chat`
- **Authorization:** Required

**Path Parameters:**
| Field | Type | Required | Description | Options |
|---|---|---|---|---|
| `session` | Integer | Yes | The ID of the session | |

**Body Data:**
| Field | Type | Required | Description | Options |
|---|---|---|---|---|
| `message` | String | Yes | The text message to send | |
| `media` | Array[File]| No | Any attachments/images | |

**Response (200 OK):**

```json
{
    "user_message": {
        "id": 1,
        "sender_type": "user",
        "content": "انا حاسس بتعب ومش عارف اروح فين"
    },
    "bot_message": {
        "id": 2,
        "sender_type": "bot",
        "content": "سلامتك يا أحمد. هل تحب أبعت رسالة لمرافقك؟"
    },
    "intent": "health_complaint",
    "confidence": 0.98,
    "language": "ar"
}
```

---

## 4. Wheelchairs & Hardware

### Connect Wheelchair (Flutter -> Backend)

- **Endpoint:** `POST /api/wheelchairs/connect`
- **Authorization:** Required (User Token)

**Body Data:**
| Field | Type | Required | Description | Options |
|---|---|---|---|---|
| `serial_number` | String | Yes | Unique ID of the wheelchair | e.g. `CHAIR-001` |

**Response (200 OK):**

```json
{
    "message": "Wheelchair connected successfully.",
    "data": {
        "wheelchair_id": 1,
        "api_key": "wh_xyz123...",
        "user_id": 5
    }
}
```

---

### IoT Movement Telemetry (Wheelchair -> Backend)

- **Endpoint:** `POST /api/trip/movement/update`
- **Authorization:** API Key (`api-key: wh_...`)

**Body Data:**
| Field | Type | Required | Description |
|---|---|---|---|
| `trip_id` | Integer | No | ID of active trip |
| `position` | Object | Yes | `x` and `y` |
| `theta` | Numeric | Yes | angle |

> **Note:** Wheelchair is identified from `api-key` header. No `wheelchair_id` needed in body.

---

### IoT Health Status (Wheelchair -> Backend)

- **Endpoint:** `POST /api/wheelchair/health`
- **Authorization:** API Key (`api-key: wh_...`)

**Body Data:**
Includes `heart_rate`, `temperature`, `mpu_angle`, `fall_status`, etc. Triggers SOS if critical.

---

## 5. Trips

### Get User Trips

- **Endpoint:** `GET /api/trips`
- **Authorization:** Required

**Query Parameters:** None

**Response (200 OK):**

```json
{
    "message": "Trips retrieved successfully",
    "data": {
        "trips": [
            {
                "id": 1,
                "status": "completed",
                "start_time": "2026-05-09T10:00:00Z"
            }
        ]
    }
}
```

### Initiate a New Trip

- **Endpoint:** `POST /api/trips`
- **Authorization:** Required

**Body Data:**
| Field | Type | Required | Description | Options |
|---|---|---|---|---|
| `e_chair_id` | Integer | Yes | The ID of the connected wheelchair | |
| `start_location`| Object | Yes | Starting point data | `{ "type": "gps", "lat": 30.0, "lng": 31.0 }` |
| `end_location` | Object | No | Destination point data | `{ "type": "indoor", "x": 10.5, "y": 20.2, "floor": 1 }` |
| `navigation_mode`| String | Yes | Type of navigation | `manual`, `autonomous` |

**Response (200 OK):**

```json
{
    "message": "Trip initiated successfully",
    "data": { "trip": { "id": 1, "status": "pending" } }
}
```

---

## 6. Organizations & Places

### List Organizations

- **Endpoint:** `GET /api/organizations`
- **Authorization:** Required

**Query Parameters:**
| Field | Type | Required | Description | Options |
|---|---|---|---|---|
| `category_id` | Integer | No | Filter by category | |
| `country_id` | Integer | No | Filter by country | |
| `city_id` | Integer | No | Filter by city | |
| `include` | String | No | Include relations | `categories,places,reviews` |

**Response (200 OK):**

```json
{
    "message": "Organizations retrieved successfully.",
    "data": [{ "id": 1, "name": "Main Org", "average_rating": 4.5 }]
}
```

### List Buildings

- **Endpoint:** `GET /api/organizations/{organization}/buildings`
- **Authorization:** Required

**Response (200 OK):**

```json
{
    "message": "Buildings retrieved successfully.",
    "data": [
        {
            "id": 1,
            "name": "Main Building",
            "latitude": 30.0,
            "longitude": 31.0
        }
    ]
}
```

### Create Building

- **Endpoint:** `POST /api/organizations/{organization}/buildings`
- **Authorization:** Required

**Body Data:**
| Field | Type | Required | Description | Options |
|---|---|---|---|---|
| `name` | String | Yes | Name of the building | |
| `latitude` | Float | No | Map coordinates | |
| `longitude`| Float | No | Map coordinates | |
| `image` | File | No | Building Image | |

**Response (200 OK):**

```json
{
    "message": "Building created successfully.",
    "data": { "id": 2, "name": "East Wing" }
}
```

### List Floors

- **Endpoint:** `GET /api/buildings/{building}/floors`
- **Authorization:** Required

**Response (200 OK):**

```json
{
    "message": "Floors retrieved successfully.",
    "data": [{ "id": 1, "name": "Ground Floor", "number": 1 }]
}
```

### Create Floor

- **Endpoint:** `POST /api/buildings/{building}/floors`
- **Authorization:** Required

**Body Data:**
| Field | Type | Required | Description | Options |
|---|---|---|---|---|
| `name` | String | Yes | Name of the floor | |
| `number` | Integer | Yes | Floor number | |

**Response (200 OK):**

```json
{
    "message": "Floor created successfully.",
    "data": { "id": 2, "name": "First Floor" }
}
```

### List Places for a Floor

- **Endpoint:** `GET /api/floors/{floor}/places`
- **Authorization:** Required

**Query Parameters:**
| Field | Type | Required | Description | Options |
|---|---|---|---|---|
| `category_id` | Integer | No | Filter by category | |
| `include` | String | No | Include relations | `categories,organization` |

**Response (200 OK):**

```json
{
    "message": "Places retrieved successfully.",
    "data": [
        {
            "id": 1,
            "name": "Central Park Path",
            "average_rating": 5.0
        }
    ]
}
```

### Create Place for a Floor

- **Endpoint:** `POST /api/floors/{floor}/places`
- **Authorization:** Required

**Body Data:**
| Field | Type | Required | Description | Options |
|---|---|---|---|---|
| `name` | String | Yes | Name of the place | |
| `category_id` | Integer | Yes | ID of Category | |
| `country_name` | String | Yes | Country | |
| `city_name` | String | Yes | City | |
| `description` | String | No | Description | |
| `image` | File | Yes | Place image | |
| `points` | Array | Yes | Array of x,y coordinates defining place shape | e.g. `[{"x":10, "y":20}, ...]` |
| `x` | Float | No | Center point X coordinate (calculated if omitted) | |
| `y` | Float | No | Center point Y coordinate (calculated if omitted) | |
| `z` | Float | No | Z coordinate (elevation) | |
| `rotation` | Float | No | Rotation degree | |

**Response (200 OK):**

```json
{
    "message": "Place created successfully.",
    "data": {
        "id": 2,
        "name": "Accessibility Ramp A"
    }
}
```

