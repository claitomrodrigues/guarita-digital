"""Treina um detector próprio quando o dataset da guarita estiver rotulado."""

from __future__ import annotations

import argparse
from pathlib import Path

PASTA_PYTHON = Path(__file__).resolve().parent


def main() -> None:
    parser = argparse.ArgumentParser()
    parser.add_argument(
        "--dados",
        default=str(PASTA_PYTHON / "dataset.yaml"),
        help="arquivo YAML do dataset YOLO",
    )
    parser.add_argument("--epocas", type=int, default=80)
    parser.add_argument("--modelo", default="yolo11n.pt")
    args = parser.parse_args()
    if not Path(args.dados).is_file():
        raise SystemExit(f"Dataset não encontrado: {args.dados}")
    from ultralytics import YOLO

    modelo = YOLO(args.modelo)
    modelo.train(
        data=args.dados,
        epochs=args.epocas,
        imgsz=960,
        batch=8,
        project=str(PASTA_PYTHON / "treinamentos"),
        name="placas_iffar",
        patience=20,
        degrees=5,
        perspective=0.0005,
        fliplr=0.0,
    )


if __name__ == "__main__":
    main()
