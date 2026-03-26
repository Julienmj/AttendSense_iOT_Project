# Hardware Setup Guide - Arduino Nano 33 BLE

## Required Components

### Essential Hardware
- **Arduino Nano 33 BLE** (you have this!)
- **HC-05 Bluetooth Module** (for scanning other devices)
- **Jumper Wires** (male-to-female and male-to-male)
- **USB Cable** (for Arduino programming)
- **Breadboard** (optional, for prototyping)
- **LED + 220Ω Resistor** (for status indicator)

### Tools & Software
- **Arduino IDE** (already installed ✓)
- **Arduino Nano 33 BLE Board Support** (install in IDE)
- **Serial Monitor** (built into Arduino IDE)

## Why Arduino Nano 33 BLE?

The Arduino Nano 33 BLE has built-in Bluetooth Low Energy, which is perfect for:
- Scanning nearby Bluetooth devices
- Low power consumption
- Better range than HC-05 alone
- No external Bluetooth module needed for scanning

## Option 1: Using Built-in BLE (Recommended)
The Nano 33 BLE can scan for devices directly without HC-05 module.

## Option 2: Using HC-05 Module
If you want to use HC-05 for compatibility with existing setup:

### Wiring Diagram (Nano 33 BLE + HC-05)
```
Arduino Nano 33 BLE  →  HC-05 Bluetooth Module
─────────────────────────────────────────────
3.3V           →      VCC
GND            →      GND
D2 (RX)        →      TXD
D3 (TX)        →      RXD
```

### Detailed Connections

| Arduino Pin | HC-05 Pin | Description |
|-------------|-----------|-------------|
| 3.3V        | VCC       | Power supply (3.3V for Nano 33 BLE) |
| GND         | GND       | Common ground |
| D2          | TXD       | Arduino receives data |
| D3          | RXD       | Arduino sends data |

### Status LED (Optional)
| Arduino Pin | LED | Resistor | Description |
|-------------|-----|----------|-------------|
| D13         | +   | 220Ω     | Status indicator |
| GND         | -   | -        | Ground |

## Step-by-Step Beginner Guide

### Step 1: Install Arduino Nano 33 BLE Support
1. Open Arduino IDE
2. Go to **Tools** → **Board** → **Boards Manager**
3. Search for "**Arduino Mbed OS Nano Boards**"
4. Click **Install**
5. Select **Tools** → **Board** → **Arduino Mbed OS Nano Boards** → **Arduino Nano 33 BLE**

### Step 2: Connect Your Hardware

#### For Built-in BLE (Recommended):
1. Connect Arduino Nano 33 BLE to USB
2. (Optional) Connect LED to D13 and GND with 220Ω resistor

#### For HC-05 Module:
1. Place HC-05 on breadboard
2. Connect 3.3V from Nano to VCC on HC-05
3. Connect GND from Nano to GND on HC-05
4. Connect D2 (Nano) to TXD on HC-05
5. Connect D3 (Nano) to RXD on HC-05
6. Connect USB cable to Nano

### Step 3: Upload the Code
1. Open Arduino IDE
2. Copy the code from `arduino_sketch.ino`
3. Select correct port: **Tools** → **Port** → (your Nano 33 BLE port)
4. Click **Upload** button (→)
5. Wait for "Done uploading" message

### Step 4: Test the Connection
1. Open **Serial Monitor** (magnifying glass icon)
2. Set baud rate to **9600**
3. You should see "AttendSense Scanner Ready"
4. The system will start scanning for devices

### Step 5: Link with AttendSense Project

#### Method 1: Direct Serial Connection (Easiest)
1. Keep Arduino connected to your computer via USB
2. The Arduino will send detected devices via Serial
3. The PHP backend will read from serial port
4. No additional configuration needed

#### Method 2: WiFi Bridge (Advanced)
1. Use Nano 33 BLE's WiFi capabilities
2. Send data to your backend via HTTP
3. Configure WiFi credentials in the Arduino code

### Step 6: Test with Real Devices
1. Enable Bluetooth on your phone/laptop
2. Make sure devices are discoverable
3. Run the Arduino scanner
4. Check Serial Monitor for detected MAC addresses
5. Verify MAC addresses appear in your AttendSense web interface

## Troubleshooting for Beginners

### Common Issues & Solutions

**❌ "Port not found"**
- Check USB cable connection
- Install Arduino drivers
- Try different USB port

**❌ "Upload failed"**
- Press reset button on Nano before uploading
- Check correct board selection
- Verify port selection

**❌ "No devices detected"**
- Ensure target devices have Bluetooth enabled
- Check if devices are discoverable
- Verify wiring (if using HC-05)
- Move closer to devices (within 10 meters)

**❌ "Garbage text in Serial Monitor"**
- Check baud rate (must be 9600)
- Verify RX/TX connections
- Check for loose wires

**❌ "HC-05 not powering on"**
- Use 3.3V instead of 5V for Nano 33 BLE
- Check ground connection
- Verify HC-05 is not damaged

## Next Steps After Setup

1. **Add Test Students**: In AttendSense web interface, create students with your device MAC addresses
2. **Create Session**: Start an attendance session
3. **Start Scanning**: Begin Bluetooth scanning
4. **Verify Detection**: Check if your devices appear as "present"

## Safety Tips

- Double-check all connections before powering on
- Don't connect 5V to HC-05 (use 3.3V)
- Keep devices within reasonable range
- Handle Arduino carefully (static sensitive)

## Need Help?

- Check Arduino IDE console for error messages
- Verify each connection step-by-step
- Test with known working devices first
- Check the Serial Monitor output for debugging info

## Setup Instructions

### 1. Hardware Assembly

1. **Connect Power**
   - Connect HC-05 VCC to Arduino 5V
   - Connect HC-05 GND to Arduino GND

2. **Connect Data Lines**
   - Cross-connect TX/RX: Arduino RX → HC-05 TX, Arduino TX → HC-05 RX
   - Use SoftwareSerial pins D10 (RX) and D11 (TX) as defined in sketch

3. **Secure Connections**
   - Ensure all connections are secure
   - Avoid loose wires that could cause intermittent connections

### 2. Software Setup

1. **Install Arduino IDE**
   - Download from [arduino.cc](https://www.arduino.cc/en/software)
   - Install drivers for your Arduino board

2. **Configure Arduino IDE**
   - Open Arduino IDE
   - Go to Tools → Board → Select "Arduino Uno"
   - Go to Tools → Port → Select your Arduino's COM port

3. **Upload the Sketch**
   - Open `hardware/arduino_sketch.ino`
   - Click "Upload" button
   - Wait for upload to complete

### 3. HC-05 Configuration

1. **Enter AT Command Mode**
   - Hold the HC-05 button while connecting power
   - LED should blink slowly (2Hz) indicating AT mode
   - If LED blinks quickly, power off and retry button press

2. **Test Communication**
   - Open Arduino IDE Serial Monitor
   - Set baud rate to 9600
   - Type "AT" - should receive "OK" response

3. **Configure HC-05 Settings**
   - The sketch automatically configures these settings:
   - `AT+ROLE=1` - Set as Master mode
   - `AT+CMODE=1` - Connect to any device
   - `AT+INQM=1,9,48` - Inquiry mode configuration

### 4. Testing the System

1. **Power On Test**
   - Connect Arduino to USB power
   - HC-05 LED should start blinking
   - Open Serial Monitor (9600 baud)

2. **Command Test**
   - Send "status" command - should respond with "STATUS:IDLE"
   - Send "start_scan" - should respond with "SCAN_STARTED"
   - Send "stop_scan" - should respond with "SCAN_STOPPED"

3. **Bluetooth Detection Test**
   - Enable Bluetooth on a smartphone
   - Place phone within 10 meters of HC-05
   - Start scanning with "start_scan" command
   - Watch for "DETECTED:" messages in Serial Monitor

## Troubleshooting

### Common Issues

**HC-05 Not Responding**
- Check wiring connections (especially TX/RX cross-connection)
- Verify HC-05 is in AT mode (slow blinking LED)
- Try different baud rates (38400, 115200)
- Check power supply (HC-05 needs 3.6V-6V)

**No Devices Detected**
- Ensure test device has Bluetooth enabled and is discoverable
- Check distance (maximum range ~10 meters)
- Verify HC-05 is in Master mode
- Try resetting the system

**Serial Communication Issues**
- Verify correct COM port selected in Arduino IDE
- Check baud rate (9600 for both Arduino and HC-05)
- Ensure no other programs are using the COM port
- Try different USB cable or port

**Power Issues**
- Use external power supply if USB power is insufficient
- Check for short circuits
- Verify voltage levels (5V for Arduino, 3.3V-6V for HC-05)

### Advanced Troubleshooting

**HC-05 LED Indicators**
- **Fast Blinking (1Hz)**: Normal pairing mode
- **Slow Blinking (2Hz)**: AT command mode
- **Solid On**: Connected to another device
- **Off**: No power or module failure

**AT Command Reference**
```
AT              - Test communication
AT+VERSION?     - Get firmware version
AT+ADDR?        - Get module address
AT+ROLE=1       - Set as Master
AT+CMODE=1      - Connect to any device
AT+INQ          - Start device inquiry
AT+INQC         - Cancel inquiry
```

## Production Considerations

### Power Management
- Use regulated power supply for consistent operation
- Consider battery backup for portability
- Add power-on LED indicator

### Signal Optimization
- Place HC-05 in elevated position for better range
- Avoid metal obstructions
- Consider external antenna for extended range

### Security
- Change default HC-05 PIN (default: 1234)
- Implement device whitelist in Arduino code
- Add encryption for data transmission

### Scalability
- Multiple HC-05 modules for larger classrooms
- Network multiple Arduino boards
- Consider ESP32 for integrated WiFi+Bluetooth solution

## Integration with Web Interface

### Serial Communication Protocol

**Commands from Web Interface → Arduino:**
```
start_scan      - Begin Bluetooth scanning
stop_scan       - Stop scanning
status          - Get current status
add_device:MAC  - Register new device
```

**Data from Arduino → Web Interface:**
```
DETECTED:MAC,RSSI     - Device found
SCAN_STARTED          - Scanning began
SCAN_STOPPED          - Scanning ended
STATUS:STATE          - Current status
TIMESTAMP:MS          - Time reference
```

### Node.js Bridge (Optional)
For production deployment, create a Node.js service that:
- Reads Arduino serial port
- Exposes WebSocket API for web interface
- Handles multiple Arduino connections
- Provides data persistence

## Safety and Compliance

### Electrical Safety
- Use proper voltage levels
- Avoid short circuits
- Ensure proper grounding
- Use insulated connections

### RF Compliance
- HC-05 operates in 2.4GHz ISM band
- Follow local regulations for Bluetooth devices
- Consider interference with other wireless devices
- Implement proper shielding if needed

### Data Privacy
- Secure MAC address storage
- Implement data encryption
- Follow GDPR/student privacy regulations
- Provide data deletion capabilities

---

**Next Steps**: After completing hardware setup, proceed to software integration and testing with the web interface.
