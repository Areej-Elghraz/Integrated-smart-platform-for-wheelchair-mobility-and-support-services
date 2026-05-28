# E-Chair & SOS Endpoints



## SOS Feature

### 1. Trigger SOS Alert

- **HTTP Method:** `POST`
- **URL:** `/api/sos`
- **Description:** Triggers an emergency SOS alert. Automatically broadcasts real-time `emergency.sos` events via Laravel Reverb to the private channels of all accepted companions and doctors, and stores a database notification.
- **Requires Authentication:** Yes
- **Body:**
    - `latitude` (numeric, optional)
    - `longitude` (numeric, optional)
    - `message` (string, optional, max 500)

#### Response (200 OK)

```json
{
    "message": "SOS alert sent to 3 connected users.",
    "triggered_to": 3
}
```

### 2. Cancel SOS Alert

- **HTTP Method:** `POST`
- **URL:** `/api/sos/cancel`
- **Description:** Cancels the SOS alert, broadcasting a cancellation event (`emergency.sos.cancelled`) to companions and doctors.
- **Requires Authentication:** Yes

#### Response (200 OK)

```json
{
    "message": "SOS alert cancelled."
}
```

---

<!--
## E-Chair Connection & Status

### 1. Verify and Connect E-Chair
- **HTTP Method:** `POST`
- **URL:** `/api/echair/verify`
- **Description:** Verifies a serial number. If valid and not assigned, assigns it to the user.
- **Requires Authentication:** Yes
- **Body:**
  - `serial_number` (string, required)

#### Response (200 OK)
```json
{
    "message": "E-Chair verified and connected successfully.",
    "data": {
        "id": 1,
        "serial_number": "E-CHAIR-X900",
        "model": "X900",
        "status": "active",
        "assigned_to_user_id": 1,
        "created_at": "...",
        "updated_at": "..."
    }
}
```
*Note: Returns 404 if invalid, 403 if already assigned to someone else.*

### 2. Update E-Chair Status
- **HTTP Method:** `POST`
- **URL:** `/api/echair/status`
- **Description:** Receives periodic status updates (like battery level) from the mobile app.
- **Requires Authentication:** Yes
- **Body:**
  - `battery` (integer, optional, 0-100)
  - `status` (string, optional)

#### Response (200 OK)
```json
{
    "message": "E-Chair status updated."
}
``` -->
