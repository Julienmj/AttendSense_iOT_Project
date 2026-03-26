<?php
/**
 * Arduino Serial Reader for AttendSense
 * Reads data from Arduino Nano 33 BLE via USB serial
 */

class ArduinoSerialReader {
    private $serialPort;
    private $baudRate = 9600;
    private $isReading = false;
    
    public function __construct($port = 'COM3') {
        $this->serialPort = $port;
    }
    
    public function startReading() {
        $this->isReading = true;
        
        // Open serial port
        $handle = fopen($this->serialPort, 'r+b');
        if (!$handle) {
            throw new Exception("Cannot open serial port: " . $this->serialPort);
        }
        
        // Configure serial port
        stream_set_blocking($handle, 0); // Non-blocking mode
        
        echo "Arduino Serial Reader Started\n";
        echo "Listening on port: " . $this->serialPort . "\n";
        
        while ($this->isReading) {
            $line = fgets($handle);
            if ($line !== false) {
                $line = trim($line);
                if (!empty($line)) {
                    $this->processData($line);
                }
            }
            usleep(100000); // 100ms delay
        }
        
        fclose($handle);
    }
    
    private function processData($data) {
        echo "Received: " . $data . "\n";
        
        // Parse data format: SESSION:123,MAC:A4:C1:38:2B:5E:9F,NAME:Device,RSSI:-45
        if (strpos($data, 'DATA:') === 0) {
            $data = substr($data, 5);
            $parts = explode(',', $data);
            
            $sessionData = [];
            foreach ($parts as $part) {
                $kv = explode(':', $part, 2);
                if (count($kv) == 2) {
                    $sessionData[$kv[0]] = $kv[1];
                }
            }
            
            if (isset($sessionData['SESSION']) && isset($sessionData['MAC'])) {
                $this->processDeviceDetection($sessionData);
            }
        }
    }
    
    private function processDeviceDetection($data) {
        // Include database connection
        require_once '../config/config.php';
        
        try {
            $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
            
            // Insert detected device
            $stmt = $pdo->prepare("
                INSERT INTO detected_devices (session_id, mac_address, detected_at, rssi) 
                VALUES (?, ?, NOW(), ?)
                ON DUPLICATE KEY UPDATE detected_at = NOW(), rssi = ?
            ");
            
            $stmt->execute([
                $data['SESSION'],
                $data['MAC'],
                $data['RSSI'] ?? null,
                $data['RSSI'] ?? null
            ]);
            
            echo "Device detection recorded: " . $data['MAC'] . "\n";
            
        } catch (PDOException $e) {
            echo "Database error: " . $e->getMessage() . "\n";
        }
    }
    
    public function stopReading() {
        $this->isReading = false;
    }
}

// Usage example
if (php_sapi_name() === 'cli') {
    $reader = new ArduinoSerialReader('COM3'); // Change COM port as needed
    $reader->startReading();
}
