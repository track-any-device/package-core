# SignalObject Fields

`TrackAnyDevice\Drivers\ValueObjects\SignalObject` — immutable value object passed to `SignalService::record()`.

## Core fields

| Field | Type | Description |
|---|---|---|
| `eventType` | `SignalEventType` | `location`, `registration`, `alarm`, `heartbeat`, `command_response` |
| `source` | `SignalSource` | `sms`, `tcp`, `http`, `internal` |
| `serverTime` | `CarbonImmutable\|null` | UTC timestamp set by SignalService if null |
| `deviceTime` | `CarbonImmutable\|null` | Timestamp reported by the device itself |

## Position

| Field | Type | Description |
|---|---|---|
| `latitude` | `float\|null` | WGS-84 |
| `longitude` | `float\|null` | WGS-84 |
| `altitude` | `float\|null` | Metres |
| `speed` | `float\|null` | km/h |
| `direction` | `float\|null` | Degrees (0–360) |
| `gpsFixed` | `bool\|null` | Whether GPS has a fix |
| `satellites` | `int\|null` | Number of satellites in view |
| `hdop` | `float\|null` | Horizontal dilution of precision |
| `positioningType` | `string\|null` | `gps`, `lbs`, `wifi` |

## Power

| Field | Type | Description |
|---|---|---|
| `batteryPercent` | `int\|null` | 0–100 |
| `batteryVoltage` | `float\|null` | Volts |
| `batteryCapacityMah` | `int\|null` | mAh |

## GSM / Network

| Field | Type | Description |
|---|---|---|
| `gsmSignal` | `int\|null` | 0–31 |
| `networkSignal` | `int\|null` | dBm |
| `mcc` | `int\|null` | Mobile country code |
| `mnc` | `int\|null` | Mobile network code |
| `lac` | `int\|null` | Location area code |
| `cellId` | `int\|null` | Cell tower ID |

## Alarms / Status

| Field | Type | Description |
|---|---|---|
| `alarmFlags` | `int\|null` | Bitmask of active alarms |
| `statusFlags` | `int\|null` | Bitmask of device status bits |
| `workingMode` | `WorkingMode\|null` | `normal`, `sleep`, `vibration` |

## Extra

| Field | Type | Description |
|---|---|---|
| `rawPayload` | `string\|null` | Original bytes from device (hex or base64) |
| `extra` | `array` | Protocol-specific fields not mapped above |

## Check for location

```php
if ($signal->hasLocation()) {
    // latitude and longitude are both non-null
}
```
