## Profile Endpoints

### 1. Update Profile
#### Endpoint Information
- **HTTP Method:** `PUT`
- **Full URL:** `/api/profile/update`
- **Description:** Updates the authenticated user's profile information.

#### Authentication & Authorization
- **Requires Authentication:** Yes (Bearer Token)
- **Authorization Logic:** Requires active Sanctum session.
- **Allowed Roles:** All authenticated users.

#### Request Details
- **Headers:** `Accept: application/json`, `Authorization: Bearer {token}`

#### Request Body
```json
{
  "name": "Jane Doe Updated",
  "phone": "+1234567890"
}
```
**Fields:**
- `name` (string, optional): New full name.
- `phone` (string, optional): New phone number.
- `gender` (string, optional): `male` or `female`.
- `birth_date` (date, optional): YYYY-MM-DD.
- `weight` (numeric, optional): Weight in kg.
- `height` (numeric, optional): Height in cm.
- `medical_condition_ids` (array of integers, optional): Array of medical condition IDs.
- `image` (file, optional): Profile picture update.

#### Validation Rules
- `name`: `sometimes|string|max:255`
- `phone`: `sometimes|string|max:20`

#### Responses
**Success (200 OK):**
```json
{
  "message": "Profile updated successfully.",
  "data": {
    "id": 1,
    "name": "Jane Doe Updated",
    "phone": "+1234567890"
  }
}
```

### 2. Change Password
#### Endpoint Information
- **HTTP Method:** `PUT`
- **Full URL:** `/api/profile/change-password`
- **Description:** Changes the authenticated user's password.

#### Request Details
- **Headers:** `Accept: application/json`, `Authorization: Bearer {token}`

#### Request Body
```json
{
  "current_password": "oldpassword123",
  "password": "newpassword123",
  "password_confirmation": "newpassword123"
}
```

#### Responses
**Success (200 OK):**
```json
{
  "message": "Password changed successfully.",
  "data": []
}
```

### 3. Delete Account
#### Endpoint Information
- **HTTP Method:** `DELETE`
- **Full URL:** `/api/profile`
- **Description:** Deletes the authenticated user's account and associated data.

#### Authentication & Authorization
- **Requires Authentication:** Yes (Bearer Token)
- **Allowed Roles:** All authenticated users.

#### Responses
**Success (200 OK):**
```json
{
  "message": "Account deleted successfully."
}
```

### 4. Get Favorites
#### Endpoint Information
- **HTTP Method:** `GET`
- **Full URL:** `/api/profile/favorites`
- **Description:** Retrieves all places and organizations the user has favorited.

#### Authentication & Authorization
- **Requires Authentication:** Yes
- **Allowed Roles:** User (only `user` role has favorites capability).

#### Responses
**Success (200 OK):**
```json
{
  "message": "Favorites retrieved successfully.",
  "data": [
    {
      "id": 1,
      "name": "Wheelchair Ramp",
      ...
    }
  ]
}
```

## Locations Endpoints

### 1. List Countries
#### Endpoint Information
- **HTTP Method:** `GET`
- **Full URL:** `/api/countries`
- **Description:** Returns a list of all supported countries.

#### Authentication & Authorization
- **Requires Authentication:** Yes

#### Responses
**Success (200 OK):**
```json
{
  "message": "Countries retrieved successfully.",
  "data": [
    {
      "id": 1,
      "name": "USA"
    }
  ]
}
```

### 2. List Cities
#### Endpoint Information
- **HTTP Method:** `GET`
- **Full URL:** `/api/cities`
- **Description:** Returns a list of cities, optionally filtered by country.

#### Request Details
- **Query Parameters:**
  - `country_id` (integer, optional): Filter cities by Country.

#### Responses
**Success (200 OK):**
```json
{
  "message": "Cities retrieved successfully.",
  "data": [
    {
      "id": 1,
      "name": "New York",
      "country_id": 1
    }
  ]
}
```

## Lookup Endpoints (For Profiles)

### 1. List Medical Conditions
#### Endpoint Information
- **HTTP Method:** `GET`
- **Full URL:** `/api/medical-conditions`
- **Description:** Returns a list of all supported medical conditions. Localized by the `Accept-Language` header (default `en`).

#### Request Details
- **Headers:** `Accept-Language` (string, optional - e.g., `en`, `ar`).

#### Responses
**Success (200 OK):**
```json
{
  "data": [
    {
      "id": 1,
      "name": "Heart Diseases",
      "description": "Chronic conditions affecting the heart and blood vessels."
    }
  ]
}
```
