# Chairpal Chatbot — AWS Deployment Guide
## دليل رفع الشات بوت على AWS للباك اند

---

## نظرة عامة على الملفات

```
GitHub Repo (الكود فقط)        Google Drive (الموديل الكبير)
├── chatbot/                    └── intent_classifier.ftz (~100MB)
├── scripts/
├── config/
├── templates/
├── data/
│   ├── merged_dataset.jsonl    ← الداتاسيت الرئيسي
│   ├── health_intents_dataset.jsonl
│   └── semantic_index.pkl      ← index الـ semantic search
├── main.py
├── schemas.py
└── requirements.txt
```

> **ملاحظة مهمة**: الموديل `intent_classifier.ftz` حجمه كبير (~100MB)
> لذلك هيتحمل من Google Drive مرة واحدة فقط على السيرفر.
> **لا يحتاج إعادة تدريب** — التدريب تم بالفعل وهو جاهز للاستخدام.

---

## أولاً: إعداد AWS EC2 (مرة واحدة فقط)

### المواصفات الموصى بها
- **Instance Type**: `t3.medium` أو أعلى (2 vCPU, 4GB RAM)
- **OS**: Ubuntu 22.04 LTS
- **Storage**: 20GB على الأقل
- **Security Group**: افتح Port `5000` للـ API

### الخطوات

```bash
# 1. اتصل بالسيرفر
ssh -i your-key.pem ubuntu@YOUR_EC2_IP

# 2. ثبّت Python والأدوات
sudo apt update && sudo apt upgrade -y
sudo apt install python3-pip python3-venv git -y

# 3. نزّل الكود من GitHub
git clone https://github.com/YOUR_USERNAME/Chairpal_Chatbot.git
cd Chairpal_Chatbot

# 4. أنشئ بيئة Python
python3 -m venv .venv
source .venv/bin/activate

# 5. ثبّت التبعيات
pip install -r requirements.txt

# 6. نزّل الموديل من Google Drive
pip install gdown
gdown "YOUR_GOOGLE_DRIVE_SHARE_LINK" -O models/intent_classifier.ftz

# 7. اضبط متغيرات البيئة
export CHAIRPAL_CORS_ORIGINS="*"
export PYTHONIOENCODING="utf-8"

# 8. شغّل السيرفر
python main.py
```

---

## ثانياً: تشغيل السيرفر بشكل دائم (systemd)

بدل ما السيرفر يقف لو الـ SSH اتقطع، اعمله service:

```bash
# أنشئ ملف الـ service
sudo nano /etc/systemd/system/chairpal.service
```

الصقي الكود ده جوا:

```ini
[Unit]
Description=Chairpal Chatbot API
After=network.target

[Service]
User=ubuntu
WorkingDirectory=/home/ubuntu/Chairpal_Chatbot
Environment="CHAIRPAL_CORS_ORIGINS=*"
Environment="PYTHONIOENCODING=utf-8"
ExecStart=/home/ubuntu/Chairpal_Chatbot/.venv/bin/python main.py
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
```

```bash
# فعّل الـ service
sudo systemctl daemon-reload
sudo systemctl enable chairpal
sudo systemctl start chairpal

# تحقق إنه شغّال
sudo systemctl status chairpal
```

### الـ API هيكون متاح على:
```
http://YOUR_EC2_IP:5000/chat
http://YOUR_EC2_IP:5000/health
```

---

## ثالثاً: ماذا يحدث عند إعادة التدريب بداتا جديدة؟

### السيناريو: جمعتِ داتا جديدة وأعدتِ التدريب

```
الخطوات على جهازك (Local):         ثم ترسلي للباك اند:
                                    
1. أضيفي الداتا الجديدة            
2. شغّلي scripts بالترتيب:         
   01_generate_health_data.py       
   02_train_intent_classifier.py  → ينتج intent_classifier.bin جديد
   03_add_article_data.py           
   04_build_semantic_index.py     → ينتج semantic_index.pkl جديد
   05_add_lifestyle_data.py         
   06_deduplicate_dataset.py        
3. شغّلي التكميم:
   fasttext quantize → ينتج intent_classifier.ftz جديد
                                    
4. ارفعي على GitHub:               5. الباك اند يعمل على السيرفر:
   - merged_dataset.jsonl              git pull
   - data/semantic_index.pkl          
                                       
5. ارفعي على Google Drive:            gdown "NEW_LINK" -O models/intent_classifier.ftz
   - intent_classifier.ftz جديد       
                                       sudo systemctl restart chairpal
```

### أوامر الباك اند عند التحديث (3 أوامر بس):

```bash
# 1. جيب الكود والداتا الجديدة
git pull

# 2. استبدل الموديل بالجديد
gdown "NEW_GOOGLE_DRIVE_LINK" -O models/intent_classifier.ftz

# 3. أعد تشغيل السيرفر
sudo systemctl restart chairpal
```

---

## رابعاً: التحقق إن كل شيء شغّال

```bash
# اختبر الـ API
curl -X POST http://YOUR_EC2_IP:5000/chat \
  -H "Content-Type: application/json" \
  -d '{"message": {"text": "ازاي اربط الكرسي"}, "user": {"id": 1, "full_name": "Test"}}'

# اتوقع رد JSON فيه:
# {"response": "...", "intent": "connect_wheelchair", "confidence": 0.95, ...}
```

---

## خلاصة للباك اند (ملخص سريع)

| الخطوة | الأمر |
|--------|-------|
| أول مرة: نزّل الكود | `git clone ...` |
| أول مرة: ثبّت التبعيات | `pip install -r requirements.txt` |
| أول مرة: نزّل الموديل | `gdown "DRIVE_LINK" -O models/intent_classifier.ftz` |
| شغّل السيرفر | `python main.py` أو `systemctl start chairpal` |
| عند تحديث الموديل | `git pull` + `gdown "NEW_LINK"` + `systemctl restart chairpal` |

---

## ملفات مهمة يعرفها الباك اند

| الملف | الدور |
|--------|-------|
| `main.py` | نقطة دخول الـ API (FastAPI على Port 5000) |
| `models/intent_classifier.ftz` | الموديل المدرّب (يُنزَّل من Drive) |
| `data/merged_dataset.jsonl` | الداتاسيت الرئيسي (في GitHub) |
| `data/semantic_index.pkl` | الـ Semantic Search Index (في GitHub) |
| `config/intent_overrides.yaml` | قواعد الـ Intent Override |
| `config/health_thresholds.json` | حدود التحذيرات الصحية |
