# AttendSense — Smart Bluetooth Attendance System

## 👥 Team

| Name | Role |
|------|------|
| **MUGISHA Julien** | Group Lead |
| **MUGABO K Bonheur** | Member |
| **Tuyishime Savior** | Member |
| **Nshuti Deus** | Member |

## 🌐 Live Demo

**[https://attendsense.netlify.app](https://attendsense.netlify.app)**

> ⚠️ **Note:** This is a frontend interface demonstration only. The PHP backend is not deployed, so API calls will not return real data. To run the full system locally, follow the setup guide below.

---

An IoT-based attendance tracking system using Bluetooth (NRF Connect) to automatically detect student presence. Built with Vue.js 3, PHP, SQLite, and Arduino Nano 33 BLE.



## 🗂 Project Structure

```
AttendSense/
├── src/                        # Vue.js Frontend
│   ├── components/             # Navbar
│   ├── views/                  # Dashboard, Classes, Sessions, Reports
│   ├── stores/                 # Pinia state management
│   ├── services/               # API service layer
│   └── router/                 # Vue Router
├── backend/                    # PHP Backend
│   ├── api/                    # REST API endpoints
│   │   ├── classes.php
│   │   ├── sessions.php
│   │   └── reports.php
│   ├── config/                 # Database config
│   └── setup_sqlite.php        # DB setup helper
├── database/                   # SQLite schema
│   └── schema.sql
├── hardware/                   # Arduino
│   ├── nrf_connect_scanner.ino # Main Arduino sketch
│   └── SETUP.md                # Hardware setup guide
├── bridge.php                  # Arduino serial bridge
├── setup.php                   # Database initialization
├── index.html                  # Vite entry point
├── vite.config.js
├── tailwind.config.js
└── package.json
```

## ⚙️ Tech Stack

| Layer | Technology |
|-------|-----------|
| Frontend | Vue.js 3, Pinia, Vue Router, Tailwind CSS, Vite |
| Backend | PHP 8+, SQLite (PDO) |
| Hardware | Arduino Nano 33 BLE, ArduinoBLE library |
| Detection | NRF Connect app (device name advertising) |

## 🚀 Getting Started

### Prerequisites
- Node.js 16+
- XAMPP (PHP + Apache)
- Arduino IDE
- Arduino Nano 33 BLE board
- NRF Connect app on student phones

### 1. Frontend Setup
```bash
npm install
npm run dev
```
Frontend runs at: `http://localhost:5173`

### 2. Backend Setup
- Start XAMPP Apache
- Visit: `http://localhost/attendsense/setup.php` to initialize database

### 3. Arduino Setup
- Open `hardware/nrf_connect_scanner.ino` in Arduino IDE
- Select: Tools → Board → Arduino Nano 33 BLE
- Select your COM port
- Upload the sketch

### 4. Start Arduino Bridge
```bash
c:\xampp\php\php.exe bridge.php COM3
```

## 📱 How It Works

```
Student Phone (NRF Connect advertising "StudentName")
        ↓  Bluetooth 2.4GHz
Arduino Nano 33 BLE (scans for device names)
        ↓  USB Serial
bridge.php (matches name → student → marks attendance)
        ↓  SQLite Database
Vue.js Web Interface (shows real-time attendance)
```

### Student Setup (NRF Connect App)
1. Download **nRF Connect for Mobile** (Android/iOS)
2. Open app → **Advertiser** tab
3. Create new advertiser with unique name (e.g., `StudentJohn`)
4. Start advertising

### Teacher Setup (Web Interface)
1. Create a class
2. Register students with their NRF Connect device names
3. Create an attendance session
4. Start the session
5. Run `bridge.php` to begin automatic detection

## 🔌 Arduino Bridge Commands
```
start    → Start continuous scanning
stop     → Stop scanning
session  → Show active session info
devices  → Show registered device names
quit     → Exit bridge
```

## 🌐 API Endpoints

### Classes
```
GET    /api/classes
POST   /api/classes
GET    /api/classes/{id}/students
POST   /api/classes/{id}/students
DELETE /api/classes/{id}/students/{sid}
```

### Sessions
```
GET    /api/sessions
POST   /api/sessions
PUT    /api/sessions/{id}/start
PUT    /api/sessions/{id}/end
GET    /api/sessions/{id}/attendance
```

### Reports
```
GET    /api/reports/attendance
GET    /api/reports/class-performance
GET    /api/reports/summary
GET    /api/reports/dashboard-stats
```

## 🔒 Privacy

- MAC addresses are stored for reference only, never used for identification
- Students are identified by their chosen NRF Connect device name
- Attendance data is stored locally in SQLite

## 🛠 Troubleshooting

| Issue | Solution |
|-------|----------|
| Device not detected | Check NRF Connect is advertising |
| Name not recognized | Ensure exact name match (case-sensitive) |
| No active session | Create and start session in web interface |
| Foreign key error | Run `setup.php` to reset database |
| COM port not found | Check Arduino IDE → Tools → Port |

## 📄 License

MIT License — see [LICENSE](LICENSE) file.

## 👤 Author

**Julien M.** — [@Julienmj](https://github.com/Julienmj)
