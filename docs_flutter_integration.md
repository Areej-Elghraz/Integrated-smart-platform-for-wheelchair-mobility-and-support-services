# ChairPal - Flutter Integration Guide (WebSockets & APIs)

This document is the ultimate reference for the Flutter team to integrate with the ChairPal Laravel Backend. It covers Authentication, Sensors (Vitals), Trips, Wheelchair Status, and Chatbot.

---

## 1. Authentication (Hardware & Flutter)

### User Authentication (Flutter App)
Flutter uses standard API endpoints (`/api/login`) to receive a Sanctum token. All requests from Flutter must include the header:
```http
Authorization: Bearer 1|your_token_here
Accept: application/json
```

### Wheelchair Authentication (Hardware/Python)
The wheelchair is considered a **Device**, not a User. 
To send sensor data to the backend, the wheelchair does NOT log in with an email. Instead, it must hit the Connect endpoint:
- **Endpoint:** `POST /api/wheelchairs/connect`
- **Request Body:**
```json
{
  "serial_number": "CHAIR-001" // (example: CHAIR-001, RX-999)
}
```
**Connection Logic:**
1. If the `serial_number` doesn't exist, it is created and linked to the user.
2. If it exists and is unassigned, it is linked to the user.
3. If it exists and is assigned to another user, you get a `403 Forbidden` error.

---

## 2. Receiving Real-time Updates (Laravel Reverb WebSockets)

The backend uses **Laravel Reverb**, a high-performance WebSocket server compatible with Pusher SDK.
To listen for real-time updates (like the wheelchair moving, or heart rate changing), you connect to Reverb.

### Configuration for Flutter (using `pusher_channels_flutter` or similar):
- **Host:** `chairpal-api.duckdns.org` (or IP if running locally, NEVER `localhost` on mobile device)
- **Port:** `443`
- **Scheme:** `https` (or `wss`)
- **App Key:** `6u9v9exow3pmm1fliknu`
- **Cluster:** `mt1` (default pusher cluster, Reverb ignores this but SDK might require it)

### Listening to Wheelchair Updates (Battery, Connection)
- **Channel Name:** `wheelchair.{id}` (Public channel)
- **Event Name:** `WheelchairUpdated`
- **Payload Example:**
```json
{
  "wheelchair": {
    "id": 1,
    "serial_number": "CHAIR-001",
    "battery": 85,
    "voltage": 24,
    "current": 1.5,
    "temperature": 36.5,
    "connection_state": "online" // Options: "online", "offline"
  }
}
```

### Listening to Vitals / Sensor Alerts
When the Python hardware sends a vital reading to the API, Laravel broadcasts it to Flutter instantly.
- **Channel Name:** `wheelchair.{id}`
- **Event Name:** `WheelchairEventOccurred`
- **Payload Example:**
```json
{
  "event": {
    "id": 10,
    "wheelchair_id": 1,
    "type": "heart", // Options: "heart", "temperature", "mpu_monitoring", "obstacle", "sos", "battery"
    "severity": "critical", // Options: "normal", "medium", "critical"
    "message": "High Heart Rate Detected",
    "data": {
      "heart_rate": 120,
      "temperature": 37.5,
      "mpu_angle": 45,
      "fall_status": "critical" // Options: "normal", "medium", "critical"
    },
    "created_at": "2026-05-28T13:10:00Z"
  }
}
```

---

## 3. Trips (Manual vs Autonomous)

### Manual Trip (Joystick)
1. Flutter calls `POST /api/trips` with `navigation_mode: "manual"`.
2. Flutter connects directly to the Wheelchair's local **MQTT Broker** (e.g., `mqtt://192.168.1.100`) to send `cmd_vel` (joystick commands) with zero latency.

### Autonomous Trip (Map Navigation)
1. Flutter calls `POST /api/trips` with `navigation_mode: "autonomous"` and the destination `end_location`.
2. Flutter sends the X,Y coordinates to the Wheelchair via MQTT.
3. Wheelchair navigates and continuously sends its location to Laravel via `POST /api/wheelchairs/{id}/location`.
4. Laravel broadcasts this to Flutter to move the marker on the map.
- **Channel Name:** `wheelchair.{id}`
- **Event Name:** `WheelchairLocationUpdated`
- **Payload Example:**
```json
{
  "wheelchair_id": 1,
  "x_coordinate": 12.5,
  "y_coordinate": 30.1,
  "floor": 1
}
```

---

## 4. Chatbot Endpoints (FastText AI)

Flutter only sends the user's text message. Laravel handles collecting all sensors and user data and sending it to the Python AI.

### Send a Message
- **Endpoint:** `POST /api/chatbot/sessions/{session}/chat`
- **Request Body (Flutter -> Laravel):**
```json
{
  "message": "انا حاسس بتعب", // Type: String, Required
  "media": [] // Optional Array of files
}
```
- **Response (Laravel -> Flutter):**
```json
{
  "user_message": {
    "id": 5,
    "sender_type": "user",
    "content": "انا حاسس بتعب"
  },
  "bot_message": {
    "id": 6,
    "sender_type": "bot",
    "content": "سلامتك يا أحمد. ألاحظ أن نبضات قلبك مرتفعة قليلاً. هل تحب أن أرسل رسالة استغاثة؟"
  },
  "intent": "health_complaint", // Type: String (e.g., "greeting", "health_complaint", "navigation")
  "confidence": 0.98, // Type: Float (0.0 to 1.0)
  "language": "ar" // Type: String ("ar", "en")
}
```
