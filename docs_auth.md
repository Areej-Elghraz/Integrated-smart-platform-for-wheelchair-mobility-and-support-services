## Authentication Endpoints

### 1. Register a new user

#### Endpoint Information

- **HTTP Method:** `POST`
- **Full URL:** `/api/signup`
- **Description:** Registers a new user account with ChairPal.

#### Authentication & Authorization

- **Requires Authentication:** No
- **Authorization Logic:** None
- **Allowed Roles:** Guest
- **Role-based Actions:** N/A

#### Request Details

- **Headers:** `Accept: application/json`
- **Query Parameters:** None
- **Path Parameters:** None

#### Request Body

**For User Role (`role: "user"`):**

```json
{
    "name": "Jane Doe",
    "email": "jane@example.com",
    "password": "password123",
    "password_confirmation": "password123",
    "role": "user",
    "phone": "+201111111111",
    "age": 25,
    "username": "janedoe99",
    "gender": "female",
    "birth_date": "2001-05-20",
    "weight": 62.5,
    "height": 165.0,
    "medical_condition_ids": [1, 2],
    "doctor_username": "dr_smith"
}
```

**For Companion Role (`role: "companion"`):**

```json
{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123",
    "password_confirmation": "password123",
    "role": "companion",
    "target_username": "janedoe99"
}
```

**For Doctor Role (`role: "doctor"`):**

```json
{
    "name": "Dr. Smith",
    "email": "dr_smith@example.com",
    "password": "password123",
    "password_confirmation": "password123",
    "role": "doctor",
    "username": "dr_smith"
}
```

**For Organization Role (`role: "organization"`):**

```json
{
    "name": "Main Org",
    "email": "org@example.com",
    "password": "password123",
    "password_confirmation": "password123",
    "role": "organization",
    "latitude": 30.0444,
    "longitude": 31.2357,
    "country_name": "Egypt",
    "city_name": "Cairo",
    "country_id": 1, // Optional, can send this instead of names
    "city_id": 1, // Optional, can send this instead of names
    "category_name": "Healthcare",
    "image": "org_banner.png",
    "description": "Premium accessibility services."
}
```

**Fields:**

- `name` (string, required): Full name of the user or organization. Validation: `required, string, max:255`
- `email` (string, required): Unique email address. Validation: `required, email, max:255, unique:users`
- `password` (string, required): Password. Validation: `required, string, min:6, confirmed`
- `password_confirmation` (string, required): Must match `password`.
- `role` (string, required): The role being registered (`user`, `companion`, `doctor`, `organization`). Validation: `required, in:user,companion,doctor,organization`
- `phone` (string, required if `user`): User's phone number. Validation: `required_if:role,user|phone:AUTO,MOBILE|unique:users`
- `age` (integer, required if `user`): User's age. Validation: `required_if:role,user|integer`
- `username` (string, required if `user` or `doctor`): Unique username. Validation: `required_if:role,user,doctor|string|unique:users|max:255`
- `gender` (string, required if `user`): Gender (`male` or `female`). Validation: `required_if:role,user|in:male,female`
- `birth_date` (date, required if `user`): Birth date. Validation: `required_if:role,user|date|before:today`
- `weight` (numeric, required if `user`): Weight in kg. Validation: `required_if:role,user|numeric|min:1`
- `medical_condition_ids` (array, optional): Array of medical condition IDs. Validation: `array|exists:medical_conditions,id`
- `height` (numeric, required if `user`): Height in cm. Validation: `required_if:role,user|numeric|min:1`
- `target_username` (string, required if `companion`): The username of the patient the companion is caring for. Must exist with role `user`.
- `doctor_username` (string, optional): The username of the doctor the patient is linking to. Must exist with role `doctor`.

- `latitude` (numeric, required if `organization`): Organization latitude. Validation: `required_if:role,organization|numeric|between:-90,90`
- `longitude` (numeric, required if `organization`): Organization longitude. Validation: `required_if:role,organization|numeric|between:-180,180`
- `country_name` (string, required if `organization` without `country_id`): Country of organization.
- `city_name` (string, required if `organization` without `city_id`): City of organization.
- `country_id` (integer, required if `organization` without `country_name`): Existing country ID.
- `city_id` (integer, required if `organization` without `city_name`): Existing city ID.
- `category_id` (integer, optional): Existing category ID.
- `category_name` (string, required if `organization` without `category_id`): New category name.
- `image` (file, required if `organization`): Image banner file.
- `description` (string, optional): Organization description.

#### Validation Rules

- Validations are conditional on the `role` parameter using Laravel's `required_if:role,...` rule structure.

#### Responses

**Success (201 Created):**

```json
{
    "message": "Welcome to ChairPal! Please verify your email with the code we’ve sent to you.",
    "data": []
}
```

**Error Responses:**

- **422 Unprocessable Entity:** Validation failure (e.g., missing profile fields, duplicate email/username).

#### Business Logic Notes

- Creates a new `User` record.
- Triggers an email verification code (OTP) to be sent to the registered email.
- Account is initially unverified until `/api/verify-otp` is completed.
- For users/companions/doctors, links their accounts via the `Friendship` model initially as `pending` so they can accept later.
- For organizations, creates an `Organization` entry with location and categorization.

#### Additional Notes

- Passwords are encrypted before storing.

### 2. Login User

#### Endpoint Information

- **HTTP Method:** `POST`
- **Full URL:** `/api/login`
- **Description:** Authenticates a user and returns an access token.

#### Authentication & Authorization

- **Requires Authentication:** No
- **Authorization Logic:** None
- **Allowed Roles:** Guest

#### Request Details

- **Headers:** `Accept: application/json`

#### Request Body

```json
{
    "email": "jane@example.com",
    "password": "password123",
    "remember": true
}
```

**Fields:**

- `email` (string, required): User's email address.
- `password` (string, required): User's password.
- `remember` (boolean, optional): Whether to issue a long-lived remember token.

#### Validation Rules

- `email`: `required|email|exists:users,email`
- `password`: `required|string|min:6`
- `remember`: `sometimes|boolean`

#### Responses

**Success (200 OK):**

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

**Error Responses:**

- **401 Unauthorized:** Invalid credentials.
- **422 Unprocessable Entity:** Fields missing or invalid format.

#### Business Logic Notes

- Validates credentials against DB.
- Returns short-lived `access_token` via Laravel Sanctum and optionally `remember_token`.

#### Additional Notes

- If the account isn't verified, it might restrict certain actions later, but login itself parses.

### 3. Refresh Token

#### Endpoint Information

- **HTTP Method:** `POST`
- **Full URL:** `/api/refresh-token`
- **Description:** Get a new access token using a valid remember token.

#### Authentication & Authorization

- **Requires Authentication:** Yes (Bearer Token)
- **Authorization Logic:** Requires Sanctum token with `remember` ability.
- **Allowed Roles:** User, Organization, Admin

#### Request Details

- **Headers:** `Accept: application/json`, `Authorization: Bearer {remember_token}`

#### Request Body

_None_

#### Validation Rules

- _No custom body fields._

#### Responses

**Success (200 OK):**

```json
{
    "message": "Token Refreshed successfully.",
    "data": {
        "access_token": "3|new_token...",
        "access_token_expires_in": 7200
    }
}
```

**Error Responses:**

- **401 Unauthorized:** Missing or invalid token.

#### Business Logic Notes

- Re-issues an `access_token` so the user stays logged in without prompting password again.

### 4. Logout User

#### Endpoint Information

- **HTTP Method:** `POST`
- **Full URL:** `/api/logout`
- **Description:** Logs out the currently authenticated user by revoking tokens.

#### Authentication & Authorization

- **Requires Authentication:** Yes (Bearer Token)
- **Authorization Logic:** Sanctum auth verification.

#### Request Details

- **Headers:** `Accept: application/json`, `Authorization: Bearer {access_token}`

#### Request Body

_None_

#### Responses

**Success (200 OK):**

```json
{
    "message": "You have been logged out successfully.",
    "data": []
}
```

#### Business Logic Notes

- Revokes the current access token. Devices using this token will need to log in again.

### 5. Forget Password

#### Endpoint Information

- **HTTP Method:** `POST`
- **Full URL:** `/api/forget-password`
- **Description:** Triggers an OTP code to the user's email for password resetting.

#### Authentication & Authorization

- **Requires Authentication:** No

#### Request Details

- **Headers:** `Accept: application/json`

#### Request Body

```json
{
    "email": "jane@example.com"
}
```

**Fields:**

- `email` (string, required): The target email account.

#### Responses

**Success (200 OK):**

```json
{
    "message": "The Verification code (OTP) has been sent to your email.",
    "data": []
}
```

### 6. Verify OTP

#### Endpoint Information

- **HTTP Method:** `POST`
- **Full URL:** `/api/verify-otp`
- **Description:** Verifies an OTP code for resetting passwords.

#### Request Details

- **Headers:** `Accept: application/json`

#### Request Body

```json
{
    "email": "jane@example.com",
    "otp": "123456"
}
```

**Fields:**

- `email` (string, required)
- `otp` (string, required): The 4-6 digit code received via email.

#### Responses

**Success (200 OK):**

```json
{
    "message": "Awesome! Your Verification code (OTP) has been verified successfully.",
    "data": []
}
```

### 6. resend-otp

#### Endpoint Information

- **HTTP Method:** `POST`
- **Full URL:** `/api/resend-otp`
- **Description:** Triggers an OTP code to the user's email for password resetting.

#### Authentication & Authorization

- **Requires Authentication:** No

#### Request Details

- **Headers:** `Accept: application/json`

#### Request Body

```json
{
    "email": "jane@example.com"
}
```

**Fields:**

- `email` (string, required): The target email account.

#### Responses

**Success (200 OK):**

```json
{
    "message": "The Verification code (OTP) has been sent to your email.",
    "data": []
}
```

### 7. Reset Password

#### Endpoint Information

- **HTTP Method:** `POST`
- **Full URL:** `/api/reset-password`
- **Description:** Sets a new password after successful OTP verification.

#### Request Details

- **Headers:** `Accept: application/json`

#### Request Body

```json
{
    "email": "jane@example.com",
    "password": "newpassword123",
    "password_confirmation": "newpassword123",
    "token": "reset_token_here"
}
```

**Fields:**

- `email` (string, required)
- `password` (string, required): Minimum 6 characters.
- `password_confirmation` (string, required)
- `token` (string, required): The token from password reset workflow.

#### Responses

**Success (200 OK):**

```json
{
    "message": "Password has been reset successfully.",
    "data": []
}
```

### 8. Verify Email

#### Endpoint Information

- **HTTP Method:** `POST`
- **Full URL:** `/api/verify-email`
- **Description:** Verifies user account email after signup.

#### Request Details

- **Headers:** `Accept: application/json`

#### Request Body

```json
{
    "email": "jane@example.com",
    "otp": "123456"
}
```

#### Responses

**Success (200 OK):**

```json
{
    "message": "Awesome! Your email has been verified successfully.",
    "data": []
}
```
