// AttendSense - Arduino Nano 33 BLE Bluetooth Attendance Scanner
// Hardware: Arduino Nano 33 BLE (built-in Bluetooth)
// Purpose: Scan for registered Bluetooth devices and send MAC addresses to web interface

#include <ArduinoBLE.h>
#include <Arduino_LSM9DS1.h>

// BLE Service and Characteristic UUIDs
#define SERVICE_UUID          "19B10000-E8F2-537E-4F6C-D104768A1214"
#define SCAN_CHARACTERISTIC_UUID "19B10001-E8F2-537E-4F6C-D104768A1214"
#define CONTROL_CHARACTERISTIC_UUID "19B10002-E8F2-537E-4F6C-D104768A1214"

// Status LED
#define STATUS_LED 13

// Known student MAC addresses (in production, these would be loaded from backend)
String registeredDevices[] = {
  "A4:C1:38:2B:5E:9F",  // Alice Johnson
  "B5:D2:49:3C:6F:0A",  // Bob Smith
  "C6:E3:5A:4D:7G:1B",  // Charlie Brown
  "D7:F4:6B:5E:8H:2C"   // Diana Prince
};
const int deviceCount = 4;

// Scanning variables
unsigned long lastScanTime = 0;
const unsigned long scanInterval = 3000; // Scan every 3 seconds
bool scanningEnabled = false;
String currentSessionId = "";

// BLE Service
BLEService attendanceService(SERVICE_UUID);
BLECharacteristic scanCharacteristic(SCAN_CHARACTERISTIC_UUID, BLERead | BLEWrite | BLENotify, 20);
BLECharacteristic controlCharacteristic(CONTROL_CHARACTERISTIC_UUID, BLERead | BLEWrite, 20);

void setup() {
  Serial.begin(9600);
  pinMode(STATUS_LED, OUTPUT);
  
  // Initialize BLE
  if (!BLE.begin()) {
    Serial.println("Failed to initialize BLE!");
    while (1);
  }
  
  Serial.println("AttendSense - Arduino Nano 33 BLE Scanner");
  Serial.println("Hardware: Arduino Nano 33 BLE (Built-in Bluetooth)");
  Serial.println("Ready for commands...");
  
  // Setup BLE service
  BLE.setLocalName("AttendSense Scanner");
  BLE.setAdvertisedService(attendanceService);
  
  // Add characteristics
  attendanceService.addCharacteristic(scanCharacteristic);
  attendanceService.addCharacteristic(controlCharacteristic);
  
  // Set callbacks
  controlCharacteristic.setWriteCallbacks(controlWritten);
  
  // Add service
  BLE.addService(attendanceService);
  
  // Start advertising
  BLE.advertise();
  
  // Status LED blink to indicate ready
  blinkLED(3, 200);
  Serial.println("BLE Service Started. Ready to scan...");
}

void loop() {
  // Poll for BLE events
  BLE.poll();
  
  // Check for commands from Serial Monitor (for testing)
  if (Serial.available()) {
    String command = Serial.readStringUntil('\n');
    command.trim();
    handleSerialCommand(command);
  }
  
  // Perform BLE scanning if enabled
  if (scanningEnabled && millis() - lastScanTime > scanInterval) {
    performBLEScan();
    lastScanTime = millis();
  }
  
  // Status LED indicates scanning state
  digitalWrite(STATUS_LED, scanningEnabled ? HIGH : LOW);
}

void handleSerialCommand(String command) {
  Serial.println("Received command: " + command);
  
  if (command == "START_SCAN") {
    startScanning();
  } else if (command == "STOP_SCAN") {
    stopScanning();
  } else if (command == "STATUS") {
    sendStatus();
  } else if (command.startsWith("SESSION:")) {
    currentSessionId = command.substring(8);
    Serial.println("Session ID set: " + currentSessionId);
  } else if (command == "SCAN_DEVICES") {
    performBLEScan();
  } else {
    Serial.println("Unknown command. Available commands:");
    Serial.println("  START_SCAN - Start continuous scanning");
    Serial.println("  STOP_SCAN - Stop scanning");
    Serial.println("  SCAN_DEVICES - Single scan");
    Serial.println("  SESSION:123 - Set session ID");
    Serial.println("  STATUS - Show current status");
  }
}

void controlWritten(BLEDevice central, BLECharacteristic characteristic) {
  String command = "";
  byte value[20];
  int len = characteristic.readValue(value, 20);
  
  for (int i = 0; i < len; i++) {
    command += (char)value[i];
  }
  
  command.trim();
  Serial.println("BLE Command received: " + command);
  
  if (command == "START") {
    startScanning();
  } else if (command == "STOP") {
    stopScanning();
  } else if (command.startsWith("SESSION:")) {
    currentSessionId = command.substring(8);
  }
}

void startScanning() {
  scanningEnabled = true;
  Serial.println("Scanning started...");
  
  // Send status via BLE
  String status = "SCANNING:1";
  scanCharacteristic.writeValue(status.c_str());
}

void stopScanning() {
  scanningEnabled = false;
  Serial.println("Scanning stopped.");
  
  // Send status via BLE
  String status = "SCANNING:0";
  scanCharacteristic.writeValue(status.c_str());
}

void performBLEScan() {
  Serial.println("Scanning for BLE devices...");
  
  // Start scanning
  BLE.scan();
  
  BLEDevice peripheral;
  int foundDevices = 0;
  
  while ((peripheral = BLE.available()) && foundDevices < 10) {
    String deviceAddress = BLEAddress(peripheral.address()).toString();
    String deviceName = peripheral.advertisedDeviceName();
    
    // Check if this is a registered device
    for (int i = 0; i < deviceCount; i++) {
      if (deviceAddress.equalsIgnoreCase(registeredDevices[i])) {
        Serial.println("✓ REGISTERED DEVICE FOUND: " + deviceName + " (" + deviceAddress + ")");
        
        // Send detection data to backend
        sendDeviceDetection(deviceAddress, deviceName);
        foundDevices++;
        break;
      }
    }
    
    peripheral.disconnect();
  }
  
  BLE.stopScan();
  
  if (foundDevices == 0) {
    Serial.println("No registered devices found in this scan.");
  }
}

void sendDeviceDetection(String macAddress, String deviceName) {
  // Format: SESSION:123,MAC:A4:C1:38:2B:5E:9F,NAME:Alice iPhone,RSSI:-45
  String data = "SESSION:" + currentSessionId + ",MAC:" + macAddress + ",NAME:" + deviceName + ",RSSI:" + String(random(-30, -80));
  
  // Send via Serial (for PHP backend)
  Serial.println("DATA:" + data);
  
  // Send via BLE (for mobile apps)
  scanCharacteristic.writeValue(data.c_str());
  
  // Blink LED to indicate detection
  blinkLED(2, 100);
}

void sendStatus() {
  Serial.println("=== AttendSense Status ===");
  Serial.println("Hardware: Arduino Nano 33 BLE");
  Serial.println("Scanning: " + String(scanningEnabled ? "Enabled" : "Disabled"));
  Serial.println("Session ID: " + (currentSessionId.length() > 0 ? currentSessionId : "Not set"));
  Serial.println("Registered Devices: " + String(deviceCount));
  Serial.println("BLE Status: " + String(BLE.connected() ? "Connected" : "Waiting"));
  Serial.println("========================");
}

void blinkLED(int times, int delayMs) {
  for (int i = 0; i < times; i++) {
    digitalWrite(STATUS_LED, HIGH);
    delay(delayMs);
    digitalWrite(STATUS_LED, LOW);
    delay(delayMs);
  }
}

// Legacy functions for compatibility with original code
void initBluetooth() {
  // BLE is already initialized in setup()
}

void handleCommand(String command) {
  handleSerialCommand(command);
}

// Helper function to get signal strength (simulated)
int getRSSI() {
  return random(-30, -80); // Simulate RSSI between -30 (strong) and -80 (weak)
}
