<?php

return [
    'admin_pin' => env('ADMIN_PIN', '1234'),
    'open_hour' => (int) env('QUEUE_OPEN_HOUR', 8),
    'close_hour' => (int) env('QUEUE_CLOSE_HOUR', 17),
    'slot_capacity' => (int) env('QUEUE_SLOT_CAPACITY', 10),
    'slot_duration_minutes' => (int) env('QUEUE_SLOT_DURATION', 30),
    'late_tolerance_minutes' => (int) env('QUEUE_LATE_TOLERANCE', 10),
    'walkin_qr_secret' => env('WALKIN_QR_SECRET', 'DQ-WALKIN-2024'),
];