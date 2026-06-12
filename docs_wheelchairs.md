# Wheelchair & Real-time Hardware Endpoints

---

## Architecture Overview (IoT Integration)
- The **Flutter App (Patient)** communicates with the Backend using standard User Authentication (`auth:sanctum`).
- The **Wheelchair (Hardware/Python)** communicates with the Backend using an API Key (`api-key` header).
- **Control Flow:** Flutter sends movement commands directly to the Wheelchair over the local network (zero-latency). The Wheelchair continuously sends telemetry data to the Backend API.

---

### 1. Connect Wheelchair (Flutter -> Backend)
- **HTTP Method:** `POST`
- **URL:** `/api/wheelchairs/connect`
- **Auth:** `Bearer Token` (User Sanctum)
- **Description:** Flutter requests to connect to a wheelchair by its serial number. If it does not exist, it creates it. Binds the user to the wheelchair and returns the `api_key` that the Flutter app must pass to the hardware locally.

#### Request Body
| Attribute | Validation | Description/Options | Example |
|---|---|---|---|
| `serial_number` | `required\|string` | Unique serial number of the wheelchair. | `CHAIR-001` |

#### Response (200 OK)
```json
{
  "message": "Wheelchair connected successfully.",
  "data": {
    "wheelchair_id": 1,
    "api_key": "wh_xyz123randomkey...",
    "user_id": 5,
    "user_weight": 70.5,
    "user_height": 175.0,
    "diseases": ["Hypertension"]
  }
}
```

---

## IoT Hardware Endpoints (Wheelchair -> Backend)

**IMPORTANT:** All endpoints below require the `api-key` header instead of a Bearer token.
`api-key: wh_xyz123...`

### 2. Update Movement Status
- **HTTP Method:** `POST`
- **URL:** `/api/trip/movement/update`
- **Header:** `api-key`
- **Description:** Wheelchair sends continuous movement telemetry. Updates the wheelchair's (x, y, theta) live and broadcasts to the UI. If a `trip_id` is provided, logs a movement state for the trip.

#### Request Body
| Attribute | Type | Description |
|---|---|---|
| `trip_id` | `integer\|null` | Optional. The ID of the active trip. |
| `movement_status` | `string` | `moving` or `idle`. |
| `speed` | `numeric` | Current speed. |
| `position` | `object` | JSON containing `x` and `y`. |
| `theta` | `numeric` | Current rotation/angle. |
| `mode` | `string` | `autonomous` or `manual`. |
| `risk_level` | `string` | `low`, `medium`, `high`. |
| `obstacle_detected` | `boolean` | `true` or `false`. |
| `obstacle_distance` | `numeric` | Distance to obstacle in cm. |

> **Note:** The wheelchair is identified automatically from the `api-key` header. No need to send `wheelchair_id` in the body.

#### Example Payload
```json
{
  "trip_id": 10,
  "movement_status": "moving",
  "speed": 0.5,
  "position": {
    "x": -0.17,
    "y": 3.57
  },
  "theta": 1.75,
  "obstacle_detected": false,
  "obstacle_distance": 83.4,
  "risk_level": "medium",
  "mode": "manual"
}
```

---

### 3. Store Event (AI / System Events)
- **HTTP Method:** `POST`
- **URL:** `/api/trip/events`
- **Header:** `api-key`
- **Description:** Wheelchair sends an event (e.g., Obstacle, SOS). Broadcasts immediately to UI.

#### Request Body
| Attribute | Type | Description |
|---|---|---|
| `trip_id` | `integer\|null` | Optional. |
| `type` | `string` | `health`, `obstacle`, `sos`. |
| `severity` | `string` | `low`, `medium`, `high`. |
| `message` | `string` | Human-readable string. |
| `data` | `object` | JSON payload. |

#### Example Payload
```json
{
  "trip_id": 10,
  "type": "obstacle",
  "severity": "high",
  "message": "Obstacle detected at 45.3 cm",
  "data": {
    "distance_cm": 45.3
  }
}
```

---

### 4. Health Status Update
- **HTTP Method:** `POST`
- **URL:** `/api/wheelchair/health`
- **Header:** `api-key`
- **Description:** Wheelchair sends live health sensor data and AI evaluation. If `fall_status == 'critical'`, it automatically triggers an SOS broadcast to the patient's friends.

#### Request Body
| Attribute | Type | Description |
|---|---|---|
| `trip_id` | `integer\|null` | Optional. |
| `heart_rate` | `numeric` | User heart rate. |
| `heart_rate_status` | `string` | `normal`, `medium`, `critical`. |
| `temperature` | `numeric` | Body temperature. |
| `temperature_status` | `string` | `normal`, `medium`, `critical`. |
| `mpu_angle` | `numeric` | Tilt angle to detect falls. |
| `fall_status` | `string` | `normal`, `medium`, `critical`. Triggers SOS if critical. |
| `risk_level` | `string` | Overall AI risk level. |
| `reason` | `string` | Explanation. |

#### Example Payload
```json
{
  "trip_id": 10,
  "heart_rate": 80.5,
  "heart_rate_status": "normal",
  "temperature": 37.0,
  "temperature_status": "normal",
  "mpu_angle": 1.5,
  "fall_status": "normal",
  "type": "health",
  "risk_level": "normal",
  "reason": "Stable",
  "recommendation": "None"
}
```

---

### 5. Disconnect Wheelchair (Flutter -> Backend)
- **HTTP Method:** `POST`
- **URL:** `/api/wheelchairs/{wheelchairId}/disconnect`
- **Auth:** `Bearer Token`
- **Description:** Marks the wheelchair as offline.

#### Response (200 OK)
```json
{
  "message": "Wheelchair disconnected successfully.",
  "data": {
    "wheelchair_id": 1,
    "connection_state": "offline"
  }
}
```
