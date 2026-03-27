from __future__ import annotations

import argparse
from pathlib import Path
from typing import Optional

from pypdf import PdfReader

PAGE_MARKER_PREFIX = "=== PAGE "
PAGE_MARKER_SUFFIX = " ==="


def default_pdf_path() -> Path:
    return (Path(__file__).resolve().parent.parent / "uploads" / "ksay-say layout fin1 final 6-8-23.pdf").resolve()


def normalize_whitespace(text: str) -> str:
    lines = [line.rstrip() for line in text.splitlines()]
    cleaned = "\n".join(lines).strip()
    return cleaned


def default_output_path(pdf_path: Path) -> Path:
    return pdf_path.with_suffix(".txt")


def convert_pdf_to_text(pdf_path: Path, output_path: Optional[Path] = None) -> Path:
    if not pdf_path.exists():
        raise FileNotFoundError(f"PDF not found: {pdf_path}")

    reader = PdfReader(str(pdf_path))
    out_path = output_path or default_output_path(pdf_path)
    out_path.parent.mkdir(parents=True, exist_ok=True)

    chunks = []
    for idx, page in enumerate(reader.pages, start=1):
        raw = page.extract_text() or ""
        text = normalize_whitespace(raw)
        if not text:
            text = "[No extractable text on this page]"
        chunks.append(f"{PAGE_MARKER_PREFIX}{idx}{PAGE_MARKER_SUFFIX}\n{text}")

    out_path.write_text("\n\n".join(chunks), encoding="utf-8")
    return out_path


def main() -> None:
    parser = argparse.ArgumentParser(description="Convert a text-based PDF file into a page-annotated .txt file.")
    parser.add_argument("pdf", type=str, nargs="?", default=str(default_pdf_path()), help="Path to source PDF")
    parser.add_argument("--out", type=str, default=None, help="Optional output text file path")
    args = parser.parse_args()

    pdf_path = Path(args.pdf).resolve()
    output_path = Path(args.out).resolve() if args.out else None

    txt_path = convert_pdf_to_text(pdf_path, output_path)
    print(str(txt_path))


if __name__ == "__main__":
    main()
