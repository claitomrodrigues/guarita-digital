<?php

return [
    'python_executable' => env(
        'PYTHON_EXECUTABLE',
        PHP_OS_FAMILY === 'Windows'
            ? base_path('python/.venv/Scripts/python.exe')
            : base_path('python/.venv/bin/python'),
    ),

    'placa_script' => env('PLACA_SCRIPT', base_path('python/placa.py')),
    'tesseract_executable' => env(
        'TESSERACT_EXECUTABLE',
        PHP_OS_FAMILY === 'Windows'
            ? 'C:\\Program Files\\Tesseract-OCR\\tesseract.exe'
            : 'tesseract',
    ),

    'ocr_timeout_seconds' => (int) env('OCR_TIMEOUT_SECONDS', 90),
    'ocr_idle_timeout_seconds' => (int) env('OCR_IDLE_TIMEOUT_SECONDS', 30),
    'max_capture_kb' => (int) env('MAX_CAPTURE_KB', 10240),

    'duplicate_window_seconds' => (int) env('ACESSO_DUPLICATE_WINDOW_SECONDS', 20),
    'access_lock_seconds' => (int) env('ACESSO_LOCK_SECONDS', 15),
    'access_lock_wait_seconds' => (int) env('ACESSO_LOCK_WAIT_SECONDS', 5),

    'login_max_attempts' => (int) env('LOGIN_MAX_ATTEMPTS', 5),
    'login_decay_seconds' => (int) env('LOGIN_DECAY_SECONDS', 60),

    'capturas_disk' => env('CAPTURAS_DISK', 'local'),
];
