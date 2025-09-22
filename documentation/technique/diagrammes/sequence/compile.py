#!/usr/bin/env python3
import os
import glob
import sys
import argparse
from shutil import which

def ensure_mermaid_cli():
    if which("mmdc") is None:
        sys.exit("Error: Mermaid CLI not found. Install with npm install -g @mermaid-js/mermaid-cli")

def compile_mermaid(file_path, scale, width, height):
    base, _ = os.path.splitext(file_path)
    png_out = f"{base}.png"
    svg_out = f"{base}.svg"

    # 1) Always produce the SVG (vector).
    cmd_svg = f'mmdc -i "{file_path}" -o "{svg_out}"'
    if os.system(cmd_svg) != 0:
        print(f" ✖ SVG compile failed for {file_path}", file=sys.stderr)
    else:
        print(f" ✔ {file_path} → {svg_out}")

    # 2) Produce the PNG at requested scale/size.
    cmd_png = [ "mmdc", "-i", file_path, "-o", png_out, "-s", str(scale) ]
    if width:
        cmd_png += ["-w", str(width)]
    if height:
        cmd_png += ["-H", str(height)]

    cmd_png = " ".join(f'"{c}"' if " " in c else c for c in cmd_png)
    if os.system(cmd_png) != 0:
        print(f" ✖ PNG compile failed for {file_path}", file=sys.stderr)
    else:
        print(f" ✔ {file_path} → {png_out}  (scale={scale}, "
              f"{'w='+str(width) if width else ''}{' H='+str(height) if height else ''})")

def main():
    parser = argparse.ArgumentParser(
        description="Batch-compile .mmd files to high-res PNG + SVG"
    )
    parser.add_argument("-s", "--scale", type=float, default=4,
                        help="Scale factor for PNG (default: 4×)")
    parser.add_argument("-w", "--width", type=int, default=0,
                        help="Target width in pixels (overrides intrinsic)")
    parser.add_argument("-H", "--height", type=int, default=0,
                        help="Target height in pixels")
    args = parser.parse_args()

    ensure_mermaid_cli()
    mmd_files = glob.glob("*.mmd")
    if not mmd_files:
        print("No .mmd files found in the current directory.")
        return

    print(f"Found {len(mmd_files)} .mmd file(s). Compiling to PNG/SVG with scale={args.scale}...")
    for f in mmd_files:
        compile_mermaid(f, args.scale, args.width, args.height)

if __name__ == "__main__":
    main()
