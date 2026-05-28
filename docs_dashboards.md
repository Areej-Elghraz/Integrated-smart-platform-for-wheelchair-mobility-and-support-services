# ChairPal Dashboards Documentation

This document explains in detail how the dashboards work for Users, Companions, and Doctors. It covers both the real-time events sent by sensors (WebSockets) and the REST API endpoints used to load historical/aggregated data.

## 1. The Real-Time Layer (WebSockets via Laravel Reverb)
**ما الذي يحدث عندما يرسل الكرسي بيانات جديدة؟**
عندما يقوم الكرسي (الـ ESP32 أو الـ ROS) بإرسال بيانات حيوية جديدة (Heart Rate, Temperature, Obstacles) إلى الـ Backend، لا نطلب من التطبيق (Flutter) أن يقوم بعمل Refresh. بدلاً من ذلك، نستخدم هيكلية مبنية على الأحداث (Event-Driven Architecture):

1. الكرسي يرسل بيانات إلى Endpoint مخصصة للـ Telemetry.
2. يقوم الـ Backend بإطلاق حدث (Event) مثل `WheelchairEventOccurred` أو `VitalStateUpdated`.
3. تقوم الـ Listeners بتحليل هذا الحدث. إذا كان هناك خطر، ترسل إشعارات (Database Notifications).
4. في نفس الوقت، يتم بث الحدث (Broadcast) عبر WebSocket (Reverb).
5. الـ Flutter App يكون متصلاً بقناة (Channel) معينة عبر WebSocket، ويستقبل البيانات في نفس اللحظة ويحدث الواجهة.

### القنوات (Channels) التي يتم الاستماع إليها
- **User / Companion / Doctor:** يستمعون إلى القناة `private-wheelchairs.{wheelchair_id}` أو `private-dashboard.{user_id}`.
- **نوع البيانات المرسلة في الوقت الفعلي (Real-time Payload):**
  - `heart_rate` و حالته (Normal, High, Low)
  - `temperature` و حالتها
  - `obstacle_distance`
  - `risk_level` الحالي.

---

## 2. The REST API Layer (Dashboard Endpoints)
بالإضافة إلى التحديثات اللحظية، يحتاج المستخدم إلى رؤية الإحصائيات، التحليلات، والتاريخ. هذا يتم عبر الـ API Endpoints.

### Endpoint: `GET /api/dashboard/user`
**لصالح من؟** User و Companion و Doctor (مع التحقق من الصلاحيات).

**المعطيات (Parameters):**
- `user_id`: رقم المستخدم (مطلوب إذا كان Companion أو Doctor يستعرض حالة مريض معين).
- `filter`: خيار لتصفية التحليلات (`today`, `last_week`, `last_month`, `last_year`).
- `limit`: لتحديد عدد الأماكن الأخيرة التي تمت زيارتها (مثلاً `5`).

**البيانات المُرجعة (Response Data):**

#### 1. `current_vitals`
يحتوي على آخر قراءة وصلت من السنسورات.
```json
{
  "heart": { "value": 75, "status": "normal" },
  "temperature": { "value": 37.2, "status": "normal" },
  "obstacle": { "movement": "Moving Forward", "mpu_angle": 12.5, "status": "safe" }
}
```

#### 2. `overviews` (التحليلات - Trends)
هنا نرسل بيانات تصلح لرسم خطوط بيانية (Charts) للإحصائيات المجمعة.
**ملاحظة هامة جداً:** البيانات هنا لا تأتي من Data وهمية ثابتة، بل يتم عمل **SQL Aggregation حقيقي** (Grouping by hour/day/month) من جدول `sensor_readings_aggregated` لضمان عرض رسم بياني دقيق لحالة القلب والحرارة والزوايا على مدار 24 ساعة.
```json
{
  "health_rate": [
    { "x_axis": "10:00 AM", "y_axis": 72 },
    { "x_axis": "11:00 AM", "y_axis": 75 }
  ],
  "temperature": [...],
  "movement": [...]
}
```

#### 3. `recent_alerts`
آخر تنبيه أو خطر من كل نوع (آخر تنبيه قلب، آخر تنبيه حرارة، آخر تنبيه حركة/سقوط).
```json
[
  { "type": "heart_rate_spike", "timestamp": "2026-05-26 14:00:00", "message": "Heart rate reached 120 bpm" }
]
```

#### 4. `ai_recommendation`
توصيات بناءً على القراءات الحالية.
```json
{
  "risk_level": "moderate",
  "recommendation": "The patient's heart rate is slightly elevated. Consider resting for 15 minutes."
}
```

#### 5. `last_visited_places`
الأماكن الأخيرة التي ذهب إليها الكرسي، مرتبة من الأحدث للأقدم. وتتأثر بمتغير `limit`.
**قيود الصلاحيات:** يتم إخفاء هذا القسم بالكامل (يرجع Array فارغة) إذا كان المستخدم الحالي بصلاحية `doctor`، حفاظاً على خصوصية تحركات المريض.
```json
[
  { "place_name": "Living Room", "map_name": "Home 1st Floor", "time": "12:30 PM" },
  { "place_name": "Garden", "map_name": "Outdoor", "time": "09:00 AM" }
]
```

#### 6. `last_active_trip`
تفاصيل آخر رحلة قيد التنفيذ أو مكتملة للكرسي. يتم إخفاء هذا القسم أيضاً إذا كان المستخدم طبيباً `doctor`.

---

## 3. الفرق بين واجهات (User, Companion, Doctor)

- **User Dashboard:** 
  يستدعي الـ Endpoint بدون تمرير `user_id` (يتم أخذه من التوكن الخاص به). يمكنه رؤية كل التفاصيل الخاصة به.
  
- **Companion Dashboard:** 
  عندما يفتح الـ Companion تطبيقه، يرى قائمة بالمرضى الذين يتابعهم (تذكر: الـ Companion يمكنه متابعة User واحد فقط حسب التعديلات الأخيرة). يختار الـ User، ثم يستدعي الـ Endpoint ممرراً `user_id` الخاص بالمريض. سيرى نفس التفاصيل.

- **Doctor Dashboard:** 
  نفس فكرة الـ Companion، لكن الطبيب قد يكون لديه صلاحيات لطلب بيانات أكثر تعقيداً أو تاريخاً طبياً أطول، ويمكنه متابعة عدد كبير من المرضى.

## 4. نظام الطوارئ (SOS) والموقع المباشر (Live Location)
عندما يتم تفعيل زر الـ SOS:
1. تطبيق الـ Flutter الخاص بالـ User يقوم بإرسال الموقع المباشر (Lat, Lng) كل 5-10 ثواني إلى الـ Backend.
2. الـ Backend يستقبل هذا الموقع، ويقوم ببثه (Broadcast) عبر رسالة في مجتمع المحادثات (Community Chat) لكل الـ Companions المرتبطين بهذا الـ User.
3. الـ Companion يصله إشعار ورسالة في الشات تحتوي على: `[Emergency] Patient needs help. Last known location: [Link to Map / Coordinates]`.
