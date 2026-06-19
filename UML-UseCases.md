# Chairpal - PlantUML Use Case Diagrams

This document provides **6 PlantUML Use Case Diagrams** fully aligned with the [Chairpal-Use-Cases.md](file:///d:/apiCourse/API_Projects/Chairpal/Chairpal-Use-Cases.md) CRUD tables. Each diagram covers one actor with their connected secondary actors, followed by a Full System diagram. All diagrams use proper `<<include>>`, `<<extend>>`, and `<<generalization>>` relationships.

> **Conventions used:**
> - `<<include>>` = Mandatory sub-step (always happens).
> - `<<extend>>` = Conditional behavior (only under specific conditions).
> - `--|>` (Generalization) = A specialized version of another use case.

---

## 1. Patient (User) Use Case Diagram

```plantuml
@startuml

left to right direction
skinparam packageStyle rectangle
skinparam actorStyle awesome
skinparam usecase {
  BackgroundColor #E8F4FD
  BorderColor #2B7A9E
  FontSize 11
}

actor "Patient" as P <<Primary>>
actor "Companion" as C
actor "Doctor" as D
actor "Wheelchair" as W <<IoT>>
actor "AI Chatbot" as AI

rectangle "Chairpal System" {

  ' ── Account & Profile ──
  package "Account & Profile" {
    usecase "Register Account" as UC_Reg
    usecase "Update Profile" as UC_UpdProf
    usecase "Delete Account" as UC_DelAcc
    usecase "View Profile" as UC_ViewProf
  }

  ' ── Wheelchair & Trips ──
  package "Wheelchair & Trips" {
    usecase "Pair Wheelchair" as UC_Pair
    usecase "Start Trip" as UC_StartTrip
    usecase "Start Manual Trip" as UC_Manual
    usecase "Start Autonomous Trip" as UC_Auto
    usecase "Verify Map Exists" as UC_VerifyMap
    usecase "Select Destination Place" as UC_SelectPlace
    usecase "Control Movement via LAN" as UC_LAN
    usecase "End / Fail Trip" as UC_EndTrip
    usecase "Unassign Wheelchair" as UC_Unassign
    usecase "View Trip History" as UC_ViewTrips
    usecase "View Sensor Readings" as UC_ViewSensors
  }

  ' ── Spatial Data ──
  package "Spatial Data" {
    usecase "Manage Private Organization" as UC_PrivOrg
    usecase "Add Private Building/Floor/Map" as UC_PrivBld
    usecase "Add Places to Public Floor" as UC_PubPlace
    usecase "View Public Maps & Buildings" as UC_ViewPub
  }

  ' ── Connections ──
  package "Connections" {
    usecase "Send Doctor Request" as UC_SendDoc
    usecase "Accept Companion Request" as UC_AccComp
    usecase "Remove Connection" as UC_RemoveConn
    usecase "View Connections List" as UC_ViewConn
  }

  ' ── Community ──
  package "Community" {
    usecase "Create Post" as UC_Post
    usecase "Comment on Post" as UC_Comment
    usecase "Like Post / Comment" as UC_Like
    usecase "Edit Own Post / Comment" as UC_EditPost
    usecase "Delete Own Post / Comment" as UC_DelPost
    usecase "Hide Other User Post" as UC_HidePost
  }

  ' ── Chat & Support ──
  package "Chat & Support" {
    usecase "Send Message" as UC_SendMsg
    usecase "Edit Message" as UC_EditMsg
    usecase "Delete Message" as UC_DelMsg
    usecase "Chat with AI Chatbot" as UC_Chatbot
  }

  ' ── Emergency ──
  package "Emergency" {
    usecase "Trigger Manual SOS" as UC_SOS
    usecase "Cancel SOS" as UC_CancelSOS
  }
}

' ── Patient Associations ──
P --> UC_Reg
P --> UC_UpdProf
P --> UC_DelAcc
P --> UC_ViewProf

P --> UC_Pair
P --> UC_StartTrip
P --> UC_Unassign
P --> UC_ViewTrips
P --> UC_ViewSensors

P --> UC_PrivOrg
P --> UC_PubPlace
P --> UC_ViewPub

P --> UC_SendDoc
P --> UC_AccComp
P --> UC_RemoveConn
P --> UC_ViewConn

P --> UC_Post
P --> UC_Comment
P --> UC_Like
P --> UC_EditPost
P --> UC_DelPost
P --> UC_HidePost

P --> UC_SendMsg
P --> UC_EditMsg
P --> UC_DelMsg
P --> UC_Chatbot

P --> UC_SOS
P --> UC_CancelSOS

' ── Relationships ──
UC_Manual --|> UC_StartTrip : <<generalization>>
UC_Auto --|> UC_StartTrip : <<generalization>>
UC_Auto ..> UC_VerifyMap : <<include>>
UC_Auto ..> UC_SelectPlace : <<include>>
UC_StartTrip ..> UC_Pair : <<include>>
UC_LAN <.. UC_Manual : <<extend>>

UC_PrivOrg ..> UC_PrivBld : <<include>>

UC_SOS <.. UC_StartTrip : <<extend>>\n(If emergency occurs during trip)

' ── Connected Actors ──
UC_LAN -- W
UC_EndTrip -- W
UC_SOS -- C : <<notifies>>
UC_SendDoc -- D : <<target>>
UC_AccComp -- C : <<from>>
UC_Chatbot -- AI

@enduml
```

---

## 2. Companion Use Case Diagram

```plantuml
@startuml

left to right direction
skinparam packageStyle rectangle
skinparam actorStyle awesome
skinparam usecase {
  BackgroundColor #E8F8E8
  BorderColor #2E7D32
  FontSize 11
}

actor "Companion" as C <<Primary>>
actor "Patient" as P

rectangle "Chairpal System" {

  ' ── Account ──
  package "Account & Profile" {
    usecase "Register as Companion" as UC_Reg
    usecase "Update Profile" as UC_UpdProf
    usecase "Delete Account" as UC_DelAcc
    usecase "View Profile" as UC_ViewProf
  }

  ' ── Connections ──
  package "Connections" {
    usecase "Send Follow Request to Patient" as UC_Follow
    usecase "Remove Patient from List" as UC_Remove
    usecase "View Assigned Patient" as UC_ViewPatient
  }

  ' ── Monitoring Dashboard ──
  package "Monitoring Dashboard" {
    usecase "View Patient Dashboard" as UC_Dash
    usecase "View Live Location" as UC_Loc
    usecase "View Vital Signs" as UC_Vitals
    usecase "Receive Active SOS Alert" as UC_SOS
    usecase "View SOS Emergency Details" as UC_SOSDetails
  }

  ' ── Wheelchair Control ──
  package "Wheelchair Control" {
    usecase "Control Wheelchair" as UC_WControl
    note right of UC_WControl : NOT ALLOWED\nfor Companion role
  }

  ' ── Community & Chat ──
  package "Community & Chat" {
    usecase "Create Post / Comment / Like" as UC_Post
    usecase "Edit Own Content" as UC_Edit
    usecase "Delete Own Content" as UC_Del
    usecase "Send Chat Message to Patient" as UC_Chat
    usecase "Read Community Feed" as UC_Feed
  }
}

' ── Companion Associations ──
C --> UC_Reg
C --> UC_UpdProf
C --> UC_DelAcc
C --> UC_ViewProf

C --> UC_Follow
C --> UC_Remove
C --> UC_ViewPatient

C --> UC_Dash
C --> UC_SOS

C --> UC_Post
C --> UC_Edit
C --> UC_Del
C --> UC_Chat
C --> UC_Feed

' ── Relationships ──
UC_Dash ..> UC_Loc : <<include>>
UC_Dash ..> UC_Vitals : <<include>>
UC_SOS ..> UC_SOSDetails : <<include>>

' ── Connected Actors ──
UC_Follow -- P : <<target>>
UC_Dash -- P : <<observes>>
UC_SOS -- P : <<originates from>>
UC_Chat -- P : <<with>>

@enduml
```

---

## 3. Doctor Use Case Diagram

```plantuml
@startuml

left to right direction
skinparam packageStyle rectangle
skinparam actorStyle awesome
skinparam usecase {
  BackgroundColor #FFF8E1
  BorderColor #F9A825
  FontSize 11
}

actor "Doctor" as D <<Primary>>
actor "Patient" as P
actor "AI Subsystem" as AI

rectangle "Chairpal System" {

  ' ── Account ──
  package "Account & Profile" {
    usecase "Register as Doctor" as UC_Reg
    usecase "Update Profile" as UC_UpdProf
    usecase "Delete Account" as UC_DelAcc
    usecase "View Profile" as UC_ViewProf
  }

  ' ── Patient Management ──
  package "Patient Management" {
    usecase "Accept Patient Request" as UC_Accept
    usecase "Reject Patient Request" as UC_Reject
    usecase "Remove Patient from List" as UC_Remove
    usecase "View All Patients by Risk" as UC_ViewAll
  }

  ' ── Medical Dashboard ──
  package "Medical Dashboard" {
    usecase "View Medical Dashboard" as UC_Dash
    usecase "View Vital Charts" as UC_Charts
    usecase "Review AI Risk Recommendations" as UC_AIRisk
  }

  ' ── Community & Chat ──
  package "Community & Chat" {
    usecase "Post Medical Advice" as UC_Post
    usecase "Edit Own Posts / Comments" as UC_Edit
    usecase "Delete Own Posts / Comments" as UC_Del
    usecase "Chat with Supervised Patient" as UC_Chat
  }
}

' ── Doctor Associations ──
D --> UC_Reg
D --> UC_UpdProf
D --> UC_DelAcc
D --> UC_ViewProf

D --> UC_Accept
D --> UC_Reject
D --> UC_Remove
D --> UC_ViewAll

D --> UC_Dash

D --> UC_Post
D --> UC_Edit
D --> UC_Del
D --> UC_Chat

' ── Relationships ──
UC_Reject --|> UC_Accept : <<generalization>>
UC_Dash ..> UC_Charts : <<include>>
UC_Dash <.. UC_AIRisk : <<extend>>\n(If risk is Medium or Critical)

' ── Connected Actors ──
UC_Accept -- P : <<from>>
UC_Chat -- P : <<with>>
UC_ViewAll -- P : <<observes>>
UC_AIRisk -- AI : <<analyzed by>>

@enduml
```

---

## 4. Organization Admin Use Case Diagram

```plantuml
@startuml

left to right direction
skinparam packageStyle rectangle
skinparam actorStyle awesome
skinparam usecase {
  BackgroundColor #FCE4EC
  BorderColor #C62828
  FontSize 11
}

actor "Org Admin" as A <<Primary>>
actor "Wheelchair" as W <<IoT>>

rectangle "Chairpal System" {

  ' ── Account ──
  package "Account & Profile" {
    usecase "Register as Org Admin" as UC_Reg
    usecase "Update Profile" as UC_UpdProf
    usecase "Delete Account" as UC_DelAcc
    usecase "View Profile" as UC_ViewProf
  }

  ' ── Spatial Hierarchy ──
  package "Spatial Hierarchy (CRUD)" {
    usecase "Create Organization" as UC_Org
    usecase "Manage Buildings" as UC_Bld
    usecase "Manage Floors" as UC_Floor
    usecase "Manage Places" as UC_Places
    usecase "Assign Coordinates (X, Y)" as UC_Coords
    usecase "Upload / Delete Floor Map" as UC_Map
    usecase "Approve Mapping Permission" as UC_Approve
    usecase "Update Spatial Details" as UC_UpdSpatial
    usecase "Delete Spatial Entity" as UC_DelSpatial
  }

  ' ── Admin Dashboard ──
  package "Admin Dashboard" {
    usecase "View Visitor Statistics" as UC_Stats
    usecase "View Place Reviews" as UC_Reviews
  }
}

' ── Admin Associations ──
A --> UC_Reg
A --> UC_UpdProf
A --> UC_DelAcc
A --> UC_ViewProf

A --> UC_Org
A --> UC_Bld
A --> UC_Floor
A --> UC_Places
A --> UC_Map
A --> UC_UpdSpatial
A --> UC_DelSpatial

A --> UC_Stats
A --> UC_Reviews

' ── Relationships ──
UC_Org ..> UC_Bld : <<include>>
UC_Bld ..> UC_Floor : <<include>>
UC_Floor ..> UC_Map : <<include>>
UC_Places ..> UC_Coords : <<include>>
UC_Map <.. UC_Approve : <<extend>>\n(If user requests mapping permission)

' ── Connected Actors ──
UC_Map -- W : <<uploads via LIDAR>>

@enduml
```

---

## 5. IoT Wheelchair Use Case Diagram

```plantuml
@startuml

left to right direction
skinparam packageStyle rectangle
skinparam actorStyle awesome
skinparam usecase {
  BackgroundColor #F5F5F5
  BorderColor #616161
  FontSize 11
}

actor "Wheelchair" as W <<IoT Primary>>
actor "Patient" as P
actor "Companion" as C
actor "AI Subsystem" as AI
actor "Backend" as B <<System>>

rectangle "Chairpal System" {

  ' ── Authentication ──
  package "IoT Authentication" {
    usecase "Authenticate via API-Key" as UC_Auth
  }

  ' ── Telemetry & Sensors ──
  package "Telemetry & Sensors" {
    usecase "Stream Telemetry Data" as UC_Stream
    usecase "Push Live Coordinates (X, Y)" as UC_Coords
    usecase "Push Sensor Vitals" as UC_Vitals
    usecase "Receive Connection State" as UC_ConnState
  }

  ' ── Trip Lifecycle ──
  package "Trip Lifecycle" {
    usecase "Execute Trip Navigation" as UC_Trip
    usecase "Report Trip Completed" as UC_End
    usecase "Report Trip Failed" as UC_Fail
    usecase "Receive Start Command" as UC_Receive
  }

  ' ── Event Logging ──
  package "Event Logging" {
    usecase "Log Hardware Event" as UC_Event
    usecase "Log Obstacle Encounter" as UC_Obstacle
    usecase "Deduplicate Repeated Event" as UC_Dedup
    usecase "Trigger Automatic SOS" as UC_AutoSOS
  }

  ' ── Mapping ──
  package "Mapping (LIDAR)" {
    usecase "Upload Generated Floor Map" as UC_UpMap
    usecase "Receive Mapping Init Signal" as UC_MapInit
  }
}

' ── Wheelchair Associations ──
W --> UC_Auth
W --> UC_Stream
W --> UC_ConnState
W --> UC_Trip
W --> UC_Event
W --> UC_UpMap

' ── Relationships ──
UC_Stream ..> UC_Coords : <<include>>
UC_Stream ..> UC_Vitals : <<include>>

UC_Trip ..> UC_Receive : <<include>>
UC_End --|> UC_Trip : <<generalization>>
UC_Fail --|> UC_Trip : <<generalization>>

UC_Obstacle --|> UC_Event : <<generalization>>
UC_Event <.. UC_Dedup : <<extend>>\n(If identical event within timeframe)
UC_Event <.. UC_AutoSOS : <<extend>>\n(If fall or critical tilt detected)

UC_UpMap ..> UC_MapInit : <<include>>

' ── Connected Actors ──
UC_Vitals -- AI : <<feeds data to>>
UC_AutoSOS -- C : <<notifies>>
UC_Receive -- P : <<initiates from>>
UC_Trip -- B : <<reports to>>

@enduml
```

---

## 6. Full System Unified Use Case Diagram

This diagram aggregates all actors and their core use cases into one cohesive view. To prevent clutter, use cases are grouped by domain modules with cross-module `<<include>>` and `<<extend>>` relationships clearly marked.

```plantuml
@startuml

left to right direction
skinparam packageStyle rectangle
skinparam actorStyle awesome
skinparam usecase {
  BackgroundColor #F0F4FF
  BorderColor #3F51B5
  FontSize 10
}

' ══════════════ ACTORS ══════════════
actor "Patient" as P
actor "Companion" as C
actor "Doctor" as D
actor "Org Admin" as A
actor "Wheelchair" as W <<IoT>>
actor "AI Subsystem" as AI

rectangle "Chairpal System" {

  ' ── Identity & Access ──
  package "Identity & Access" {
    usecase "Register / Login" as UC_Auth
    usecase "Manage Profile" as UC_Prof
    usecase "Delete Account" as UC_DelAcc
  }

  ' ── Wheelchair & Navigation ──
  package "Wheelchair & Navigation" {
    usecase "Pair Wheelchair" as UC_Pair
    usecase "Start Manual Trip" as UC_Manual
    usecase "Start Autonomous Trip" as UC_Auto
    usecase "Verify Map & Select Place" as UC_Verify
    usecase "Control via LAN" as UC_LAN
    usecase "Stream Live Telemetry" as UC_Stream
    usecase "End / Fail Trip" as UC_EndTrip
  }

  ' ── Spatial Hierarchy ──
  package "Spatial Hierarchy" {
    usecase "Manage Private Spaces" as UC_PrivSpace
    usecase "Manage Public Venues" as UC_PubSpace
    usecase "Upload LIDAR Map" as UC_Map
    usecase "Add Places to Floor" as UC_AddPlace
  }

  ' ── Emergency & Health ──
  package "Emergency & Health" {
    usecase "Trigger / Cancel SOS" as UC_SOS
    usecase "Monitor Patient Dashboard" as UC_Dash
    usecase "View Vital Charts" as UC_Charts
    usecase "AI Risk Classification" as UC_AIRisk
    usecase "View Sensor Readings" as UC_Sensors
  }

  ' ── Social & Community ──
  package "Social & Community" {
    usecase "Manage Connections" as UC_Conn
    usecase "Community Posts & Likes" as UC_Comm
    usecase "Private Chat" as UC_Chat
    usecase "AI Chatbot" as UC_Chatbot
  }

  ' ── Dashboards ──
  package "Role Dashboards" {
    usecase "Patient Dashboard" as UC_PDash
    usecase "Companion Dashboard" as UC_CDash
    usecase "Doctor Dashboard" as UC_DDash
    usecase "Org Admin Dashboard" as UC_ADash
  }
}

' ══════════════ ACTOR ASSOCIATIONS ══════════════

' Patient
P --> UC_Auth
P --> UC_Prof
P --> UC_DelAcc
P --> UC_Pair
P --> UC_Manual
P --> UC_Auto
P --> UC_PrivSpace
P --> UC_AddPlace
P --> UC_SOS
P --> UC_Conn
P --> UC_Comm
P --> UC_Chat
P --> UC_Chatbot
P --> UC_PDash

' Companion
C --> UC_Auth
C --> UC_Prof
C --> UC_Conn
C --> UC_Comm
C --> UC_Chat
C --> UC_CDash

' Doctor
D --> UC_Auth
D --> UC_Prof
D --> UC_Conn
D --> UC_Comm
D --> UC_Chat
D --> UC_DDash

' Org Admin
A --> UC_Auth
A --> UC_Prof
A --> UC_PubSpace
A --> UC_Map
A --> UC_ADash

' Wheelchair
W --> UC_Stream
W --> UC_EndTrip
W --> UC_Map
W --> UC_LAN

' AI
AI --> UC_AIRisk
AI --> UC_Chatbot

' ══════════════ RELATIONSHIPS ══════════════

' Navigation include/extend
UC_Auto ..> UC_Verify : <<include>>
UC_Manual <.. UC_LAN : <<extend>>\n(Direct joystick control)
UC_Auto ..> UC_Stream : <<include>>
UC_Manual ..> UC_Stream : <<include>>

' Spatial
UC_PubSpace ..> UC_AddPlace : <<include>>
UC_PrivSpace ..> UC_AddPlace : <<include>>

' Emergency
UC_Stream ..> UC_AIRisk : <<include>>\n(Vitals analyzed in real-time)
UC_SOS <.. UC_AIRisk : <<extend>>\n(Auto-triggered on Critical risk)

' Dashboards
UC_PDash ..> UC_Charts : <<include>>
UC_PDash ..> UC_Sensors : <<include>>
UC_CDash ..> UC_Dash : <<include>>
UC_DDash ..> UC_Charts : <<include>>
UC_DDash <.. UC_AIRisk : <<extend>>\n(Shows AI alerts if risk detected)

' SOS notification
UC_SOS -- C : <<notifies>>

@enduml
```
