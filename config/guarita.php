<?php

$pythonVirtual = PHP_OS_FAMILY === 'Windows'
    ? base_path('python/.venv/Scripts/python.exe')
    : base_path('python/.venv/bin/python');

$pythonPadrao = is_file($pythonVirtual) ? $pythonVirtual : 'python';

return [
    'max_capture_kb' => (int) env('MAX_CAPTURE_KB', 10240),
    'python_executable' => env('PYTHON_PATH', env('PYTHON_EXECUTABLE', $pythonPadrao)),
    'placa_script' => env('PLACA_SCRIPT') ?: base_path('python/reconhecer_imagem.py'),
    'ocr_timeout_seconds' => (int) env('OCR_TIMEOUT_SECONDS', 180),
    'ocr_idle_timeout_seconds' => (int) env('OCR_IDLE_TIMEOUT_SECONDS', 90),
];
