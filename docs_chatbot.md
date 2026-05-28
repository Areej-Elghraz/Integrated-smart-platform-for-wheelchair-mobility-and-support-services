# ChairPal specialized ChatBot Architecture (FastText AI)

## 📌 السيناريو الكامل للشات بوت (The Complete Scenario)

الشات بوت في مشروع ChairPal ليس مجرد بوت محادثة عادي، بل هو **مساعد شخصي طبي وذكي (Medical & Navigational Assistant)** مخصص تماماً لحالة المستخدمين:

1. **الفئة المستهدفة:** المرضى الذين يعانون من شلل كامل في الجزء السفلي (Lower Body Paralysis) أو البتر الكامل في الجزء السفلي (Lower Body Amputation). البوت يتحدث بلباقة ومراعاة لهذه الحالة.
2. **التعرف التلقائي على اللغة (Auto Language Detection):** بناءً على طلب فريق الـ AI، البوت مبني باستخدام خوارزمية **fastText**. هذه الخوارزمية قادرة على تحديد اللغة (عربي / English) تلقائياً من النص المرسل، لذلك تطبيق الفلاتر (Flutter) **لا يحتاج** لإرسال بارامتر `language`.
3. **الوعي الكامل بالنظام (System Context Awareness):** عندما يرسل الفلاتر رسالة المستخدم إلى السيرفر (Laravel)، يقوم سيرفر لارافل بتجميع (Context) كامل عن المستخدم وإرساله خلف الكواليس إلى سكريبت البايثون الخاص بالـ AI. هذا الـ Context يشمل:
    - بيانات المستخدم (العمر، الوزن، الحالة الطبية).
    - حالة الكرسي الحالية (البطارية، الأعطال).
    - القياسات الحيوية الحالية (نبض القلب، الحرارة).
    - موقع الكرسي أو الرحلة الحالية.
      وبالتالي يستطيع البوت إعطاء ردود مفيدة جداً (مثلاً: "بطاريتك 20% فقط، أنصحك بالعودة للمنزل يا محمد" أو "نبضات قلبك سريعة، هل تريدني أن أرسل رسالة SOS؟").

---

## 📡 Endpoints

### 1. Chat with Bot

- **HTTP Method:** `POST`
- **Full URL:** `/api/chatbot/sessions/{session}/chat`
- **Description:** يرسل رسالة المستخدم إلى السيرفر، والذي بدوره يرسلها لسكريبت الـ AI مع الـ Context ويعيد الرد للمستخدم.

#### Request Headers

- `Authorization: Bearer {token}`
- `Accept: application/json`

#### Request Body

لا حاجة لإرسال اللغة أو حالة المستخدم، السيرفر سيجلبها من الداتا بيز أوتوماتيكياً.

```json
{
    "message": "انا حاسس بتعب ومش عارف اروح فين",
    "media": [null] // اختياري لو في صورة
}
```

#### Validation Rules

- `message`: `required_without:media|string`
- `media.*`: `nullable|file|mimes:jpeg,png,jpg,pdf|max:10240`

#### Backend Processing (What Laravel does behind the scenes)

عندما يستقبل لارافل هذا الريكويست، يقوم بتجميع كائن (Payload) ضخم ودقيق جداً وإرساله لفريق الـ AI (Python fastText Service). هذا الـ Payload يضمن أن البوت يكون واعياً بنسبة 100% بكل ما يخص المريض:

```json
{
    "user_text": "انا حاسس بتعب ومش عارف اروح فين",
    "context": {
        "user_profile": {
            "name": "Ahmed",
            "medical_condition": "Lower Body Paralysis / Amputation",
            "age": 28,
            "weight": 70,
            "gender": "male"
        },
        "relations": {
            "doctor": {
                "name": "Dr. Smith",
                "phone": "+201111111"
            },
            "companions": [{ "name": "Mona", "phone": "+201222222" }]
        },
        "wheelchair_status": {
            "serial_number": "CHAIR-001",
            "battery": 80,
            "connection": "online"
        },
        "current_health_state": {
            "heart_rate": 110,
            "temperature": 37.5,
            "mpu_monitoring": {
                "angle": 45,
                "fall_detected": true,
                "fainting_risk": "high"
            }
        },
        "current_trip": {
            "is_active": true,
            "start_location": "Home",
            "destination": "Hospital",
            "current_coordinates": { "x": 10.5, "y": 20.2 }
        },
        "latest_alerts": {
            "heart": {
                "message": "High Heart Rate Detected",
                "severity": "critical",
                "timestamp": "2026-05-28T13:10:00Z"
            },
            "temperature": null,
            "mpu_monitoring": {
                "message": "High Fall Risk",
                "severity": "critical",
                "timestamp": "2026-05-28T13:15:00Z"
            },
            "obstacle": {
                "message": "Stairs ahead",
                "severity": "medium",
                "timestamp": "2026-05-28T09:50:00Z"
            },
            "sos": null,
            "battery": {
                "message": "Battery below 20%",
                "severity": "medium",
                "timestamp": "2026-05-28T08:00:00Z"
            }
        }
    }
}
```

#### Response (200 OK)

يقوم الـ Python script باكتشاف اللغة عبر `fastText`، ثم يرد بالعربي:

```json
{
    "message": "Chatbot response generated successfully.",
    "data": {
        "reply": "سلامتك يا أحمد. ألاحظ أن نبضات قلبك مرتفعة قليلاً. هل تحب أن أرسل رسالة استغاثة (SOS) للمرافق الخاص بك؟",
        "language_detected": "ar",
        "intent": "health_complaint"
    }
}
```

---

### 2. Get Chat History (Chatbot Sessions)

- **HTTP Method:** `GET`
- **Full URL:** `/api/chatbot/sessions`
- **Description:** استرجاع كل جلسات المحادثة الخاصة بالمستخدم.

#### Response (200 OK)

```json
{
    "data": [
        {
            "id": 1,
            "title": "New Chat",
            "user_id": 1,
            "created_at": "2026-05-28T10:00:00Z"
        }
    ]
}
```

---

### 3. Create ChatBot Session

- **HTTP Method:** `POST`
- **Full URL:** `/api/chatbot/sessions`
- **Description:** إنشاء جلسة محادثة جديدة مع البوت.

#### Request Body

```json
{
    "title": "My first chat"
}
```

- `title` (string, optional, default: `New Chat`)

---

### 4. View ChatBot Session (with Messages)

- **HTTP Method:** `GET`
- **Full URL:** `/api/chatbot/sessions/{session}`
- **Description:** عرض جلسة محددة مع كل الرسائل السابقة فيها.

#### Response (200 OK)

```json
{
    "data": {
        "id": 1,
        "title": "New Chat",
        "messages": [
            { "id": 1, "sender_type": "user", "content": "كيف حالك؟" },
            {
                "id": 2,
                "sender_type": "bot",
                "content": "أنا بخير، كيف يمكنني مساعدتك في توجيه الكرسي اليوم؟"
            }
        ]
    }
}
```

---

### 5. Delete ChatBot Session

- **HTTP Method:** `DELETE`
- **Full URL:** `/api/chatbot/sessions/{session}`
- **Description:** حذف جلسة محادثة بالكامل مع كل رسائلها.

---

### 6. React to Bot Message (Like / Dislike)

- **HTTP Method:** `POST`
- **Full URL:** `/api/chatbot/messages/{message}/reaction`
- **Description:** إعطاء تقييم (Like أو Dislike) لرسالة البوت.

#### Request Body

```json
{
    "reaction": "like"
}
```

- `reaction` (string, required): `like` أو `dislike`
