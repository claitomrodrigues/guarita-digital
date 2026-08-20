"""Treina um detector próprio quando o dataset da guarita estiver rotulado."""

from __future__ import annotations

import argparse
from pathlib import Path


def main() -> None:
    parser = argparse.ArgumentParser()
    parser.add_argument("--dados", default="dataset.yaml", help="arquivo YAML do dataset YOLO")
    parser.add_argument("--epocas", type=int, default=80)
    parser.add_argument("--modelo", default="yolo11n.pt")
    args = parser.parse_args()
    if not Path(args.dados).is_file():
        raise SystemExit(f"Dataset não encontrado: {args.dados}")
    from ultralytics import YOLO
    modelo = YOLO(args.modelo)
    modelo.train(
        data=args.dados, epochs=args.epocas, imgsz=960, batch=8,
        project="treinamentos", name="placas_iffar", patience=20,
        degrees=5, perspective=0.0005, fliplr=0.0,
    )


if __name__ == "__main__":
    main()
