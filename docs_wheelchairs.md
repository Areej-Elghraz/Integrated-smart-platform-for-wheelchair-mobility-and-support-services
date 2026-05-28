# Wheelchair & Real-time Hardware Endpoints

---

### 1. Connect Wheelchair
- **HTTP Method:** `POST`
- **URL:** `/api/wheelchairs/connect`
- **Description:** First connect to a wheelchair. If it does not exist, it creates it and marks it online. Otherwise, sets it to online.

#### Request Body
| Attribute | Validation | Description/Options | Example |
|---|---|---|---|
| `serial_number` | `required\|string\|max:100` | The unique serial number of the wheelchair to connect. | `CHAIR-001` |

#### Response (200 OK or 201 Created)
```json
{
  "message": "Wheelchair connected successfully.",
  "data": {
    "wheelchair": {
      "id": 1,
      "serial_number": "CHAIR-001",
      "connection_state": "online"
    }
  }
}
```

---

### 2. Update Wheelchair Data
- **HTTP Method:** `PUT`
- **URL:** `/api/wheelchairs/{wheelchairId}`
- **Description:** Update core wheelchair data (battery, voltage, current, etc.). Pushes `WheelchairUpdated` event via Reverb. Creates an emergency event if battery <= 20.

#### Request Body
| Attribute | Validation | Description/Options | Example |
|---|---|---|---|
| `battery` | `nullable\|numeric\|min:0\|max:100` | Battery percentage. | `85.5` |
| `voltage` | `nullable\|numeric\|min:0` | Battery voltage in Volts. | `24.0` |
| `current` | `nullable\|numeric\|min:0` | Current draw in Amperes. | `2.3` |
| `temperature` | `nullable\|numeric` | Hardware temperature in C. | `32.1` |
| `connection_state` | `nullable\|string\|in:online,offline` | Connection status. | `online` |

#### Response (200 OK)
```json
{
  "message": "Wheelchair updated successfully.",
  "data": {
    "wheelchair": {
      "id": 1,
      "battery": 85.5,
      "voltage": 24.0,
      "connection_state": "online"
    }
  }
}
```

---

### 3. Update Current Vital States (AI Recommendations)
- **HTTP Method:** `POST`
- **URL:** `/api/wheelchairs/{wheelchairId}/current-vital-states`
- **Description:** Updates the current live vital signs and AI recommendations. Uses `updateOrCreate` to maintain exactly ONE row per wheelchair in the database, preventing bloat. The hardware can send this every 5 seconds safely. Also triggers a critical SOS if `fall_status` is `critical`.

#### Request Body
| Attribute | Validation | Description/Options | Example |
|---|---|---|---|
| `heart_rate` | `required\|numeric` | Current heart rate | `80` |
| `heart_rate_status` | `required\|string\|in:normal,medium,critical` | Status of HR | `normal` |
| `temperature` | `required\|numeric` | Current temperature | `36.5` |
| `temperature_status` | `required\|string\|in:normal,medium,critical` | Status of Temp | `normal` |
| `mpu_angle` | `required\|numeric` | Current tilt angle | `2` |
| `fall_status` | `required\|string\|in:normal,medium,critical` | Fall detection status | `normal` |
| `type` | `nullable\|string` | Type of recommendation | `health_alert` |
| `risk_level` | `required\|string\|in:normal,medium,critical` | Overall risk level | `normal` |
| `reason` | `nullable\|string` | Reason for alert | `None` |
| `recommendation` | `nullable\|string` | Actionable advice | `All good` |

#### Response (200 OK)
```json
{
  "message": "Vital state updated successfully.",
  "data": {
    "id": 1,
    "wheelchair_id": 1,
    "heart_rate": 80,
    "fall_status": "normal"
  }
}
```

---

### 3. Store Sensor Readings (Hardware Raw Data)
- **HTTP Method:** `POST`
- **URL:** `/api/wheelchairs/{wheelchairId}/sensor-readings`
- **Description:** Receives pre-aggregated sensor min/max/avg windows directly from the hardware. 

#### Request Body
| Attribute | Validation | Description/Options | Example |
|---|---|---|---|
| `trip_id` | `nullable\|integer\|exists:trips,id` | ID of the active trip | `1` |
| `heart_rate_min` | `nullable\|numeric` | Minimum HR in window | `60` |
| `heart_rate_max` | `nullable\|numeric` | Maximum HR in window | `85` |
| `heart_rate_avg` | `nullable\|numeric` | Average HR in window | `72.5` |
| `temperature_min` | `nullable\|numeric` | Minimum Temp in window | `36.5` |
| `temperature_max` | `nullable\|numeric` | Maximum Temp in window | `37.0` |
| `temperature_avg` | `nullable\|numeric` | Average Temp in window | `36.7` |
| `mpu_angle_min` | `nullable\|numeric` | Minimum MPU tilt angle | `-5.0` |
| `mpu_angle_max` | `nullable\|numeric` | Maximum MPU tilt angle | `10.0` |
| `mpu_angle_avg` | `nullable\|numeric` | Average MPU tilt angle | `2.3` |
| `reading_time` | `required\|date` | Timestamp of the reading | `2026-05-25T12:00:00Z` |

#### Response (201 Created)
```json
{
  "message": "Sensor reading stored successfully.",
  "data": {
    "reading": {
      "id": 1,
      "wheelchair_id": 1,
      "heart_rate_avg": 72.5
    }
  }
}
```

---

### 4. Store Event (AI / System Events)
- **HTTP Method:** `POST`
- **URL:** `/api/trips/{tripId}/events`
- **Description:** Store an event (e.g., AI alert, obstacle detection, health anomaly). Supports deduplication via Reverb and updates unresolved duplicates.

#### Request Body
| Attribute | Validation | Description/Options | Example |
|---|---|---|---|
| `type` | `required\|string\|in:health,obstacle,sos,battery` | Event type classification. | `health` |
| `severity` | `required\|string\|in:normal,medium,critical` | Criticality of event. | `medium` |
| `message` | `required\|string` | Human-readable string. | `Elevated heart rate detected.` |
| `data` | `required\|array` | Event payload payload | `{"hr": 110}` |
| `event_source` | `nullable\|string\|in:ai,system` | Event generator. Defaults `ai`. | `ai` |

#### Response (200 OK / 201 Created)
```json
{
  "message": "Event stored successfully.",
  "data": {
    "event": {
      "id": 1,
      "type": "health",
      "severity": "medium",
      "resolved_at": null
    }
  }
}
```

---

### 5. Disconnect Wheelchair
- **HTTP Method:** `POST`
- **URL:** `/api/wheelchairs/{wheelchairId}/disconnect`
- **Description:** Disconnects the wheelchair, sets status offline.

#### Request Body
Empty

#### Response (200 OK)
```json
{
  "message": "Wheelchair disconnected successfully.",
  "data": {
    "wheelchair": {
      "id": 1,
      "connection_state": "offline"
    }
  }
}
```

---

## Live Location Tracking (WebSockets)

Live location is managed entirely via Laravel Reverb (WebSockets).

### 1. Broadcasting GPS Location (Flutter -> Companion)
- **Endpoint:** `POST /api/location/user`
- **Description:** Patient's Flutter app sends GPS coordinates (`latitude`, `longitude`).
- **Channel:** `private-user.{userId}.location`
- **Event:** `App\Events\UserLocationUpdated`
- **Audience:** Only accepted friends (Companions/Doctors) can subscribe to this channel.

### 2. Broadcasting Indoor ROS Location (Wheelchair -> Flutter)
- **Endpoint:** `POST /api/wheelchairs/{wheelchairId}/location`
- **Description:** Wheelchair's Python hardware sends local coordinates (`x`, `y`, `angle`).
- **Channel:** `private-wheelchair.{wheelchairId}.location`
- **Event:** `App\Events\WheelchairLocationUpdated`
- **Audience:** The owner (Flutter Patient) and accepted friends can subscribe to this channel.
