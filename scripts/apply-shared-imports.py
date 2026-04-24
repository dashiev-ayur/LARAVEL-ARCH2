#!/usr/bin/env python3
"""Однопроходная замена импортов на @/shared/* (без обхода generated actions/routes)."""

from pathlib import Path

ROOT = Path("resources/js")

# Порядок важен: button-new-post и длинные @/hooks/* — до @/components/ui/.
REPLACEMENTS: list[tuple[str, str]] = [
    ("@/components/ui/button-new-post", "@/features/post"),
    ("@/hooks/use-mobile-navigation", "@/shared/hooks/use-mobile-navigation"),
    ("@/hooks/use-appearance", "@/shared/hooks/use-appearance"),
    ("@/hooks/use-clipboard", "@/shared/hooks/use-clipboard"),
    ("@/hooks/use-current-url", "@/shared/hooks/use-current-url"),
    ("@/hooks/use-flash-toast", "@/shared/hooks/use-flash-toast"),
    ("@/hooks/use-initials", "@/shared/hooks/use-initials"),
    ("@/hooks/use-mobile", "@/shared/hooks/use-mobile"),
    ("@/lib/utils", "@/shared/lib/utils"),
    ("@/components/ui/", "@/shared/ui/"),
    ("@/components/table", "@/shared/ui/table"),
]

# Только исходники проекта, без сгенерированных wayfinder.
SCAN_DIRS = [
    ROOT / "components",
    ROOT / "pages",
    ROOT / "layouts",
    ROOT / "shared",
    ROOT / "features",
    ROOT / "types",
    ROOT / "entities",
    ROOT / "widgets",
]
FILES = [ROOT / "app.tsx", ROOT / "hooks" / "use-two-factor-auth.ts"]

# Только реэкспорт; внешние кривые замены ломают такие файлы — не трогать.
EXCLUDE: set[Path] = {(ROOT / "features" / "post" / "index.ts").resolve()}


def iter_ts_files() -> list[Path]:
    out: list[Path] = []
    for d in SCAN_DIRS:
        if d.is_dir():
            for p in d.rglob("*"):
                if p.suffix in (".ts", ".tsx") and p.is_file() and p.resolve() not in EXCLUDE:
                    out.append(p)
    for f in FILES:
        if f.is_file() and f.resolve() not in EXCLUDE:
            out.append(f)
    return out


def main() -> None:
    n = 0
    for path in iter_ts_files():
        text = path.read_text(encoding="utf-8")
        new = text
        for old, repl in REPLACEMENTS:
            new = new.replace(old, repl)
        if new != text:
            path.write_text(new, encoding="utf-8")
            n += 1
    print(f"apply-shared-imports: updated {n} files")


if __name__ == "__main__":
    main()
