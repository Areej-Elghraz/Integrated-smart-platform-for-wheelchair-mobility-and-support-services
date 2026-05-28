# ROS & MQTT Architecture (Manual vs Autonomous Trips)

This document explains how the Flutter application, the Laravel API, and the Wheelchair Hardware (Python/ROS) communicate during different trip modes. 

**Zero-Latency Design Goal:** To ensure the safety of the user, any continuous control operations (like joystick movements) must not experience server-side network lag. Therefore, the Laravel API acts as a stateless coordinator, not a proxy for high-frequency hardware commands.

---

## 1. Hardware Boot (Handshake)
When the Wheelchair powers on, the Python script needs to know the context of the user sitting in the chair to adjust its AI models (e.g., fall detection thresholds based on weight, or anomaly detection based on pre-existing diseases).

1. **Python Script** boots and calls `GET /api/wheelchairs/handshake` passing its local MAC address or `serial_number`.
2. **Laravel API** looks up the wheelchair, finds the assigned Patient, and returns the patient's profile (height, weight, medical conditions).
3. **Python Script** saves this data in a local "Shared State" in memory.
4. From now on, whenever Python sends a telemetry update (e.g., a high heart rate), the local AI already considered the patient's diseases, making the alert much more accurate.

---

## 2. Manual Trip (Joystick Control)
In manual mode, the user controls the wheelchair using a joystick (either hardware on the chair or a virtual joystick on the Flutter app).

### Flow:
1. **Flutter** sends `POST /api/trips/{wheelchair_id}/start` with `mode: "manual"`.
2. **Laravel API** records the trip in the database, sets its status to `started`, and returns a `trip_id`.
3. **Flutter** stores the `trip_id`.
4. **Flutter** establishes a direct local connection to the Wheelchair's internal **MQTT Broker**.
5. As the user moves the joystick, **Flutter** publishes `cmd_vel` (movement commands) directly to the local MQTT broker. 
6. **Python/ROS** receives the commands instantly and moves the motors.
7. *Why this way?* **Zero Latency.** Passing joystick commands through the Laravel cloud API would introduce dangerous delays.

---

## 3. Autonomous Trip (Auto-Navigation)
In autonomous mode, the user selects a destination (Place) on the indoor map, and the wheelchair navigates itself there avoiding obstacles.

### Flow:
1. **Flutter** sends `POST /api/trips/{wheelchair_id}/start` with `mode: "autonomous"` and `place_id: 5` (for example, "Rehab Room A").
2. **Laravel API** records the trip. It looks up "Rehab Room A" in the database and retrieves its `x` and `y` coordinates.
3. **Laravel API** returns the `trip_id` along with the destination's coordinates (`place.x`, `place.y`) to the Flutter app.
4. **Flutter** takes these coordinates and publishes a "Navigation Goal" directly to the Wheelchair's local **MQTT Broker**.
5. **Python/ROS** receives the goal, calculates the path, and begins moving.
6. As the wheelchair moves, Python publishes its current indoor coordinates (X, Y, Angle) to the Laravel API via `POST /api/wheelchairs/{id}/location`.
7. **Laravel API** broadcasts these coordinates via WebSockets (`WheelchairLocationUpdated`) back to the Flutter app to animate the wheelchair marker on the map in real-time.
