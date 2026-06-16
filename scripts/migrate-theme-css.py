#!/usr/bin/env python3
"""Migrate legacy CSS in style/*.css to farm-to-table theme tokens."""
from __future__ import annotations

import re
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
FILES = [ROOT / "style" / "index.css", ROOT / "style" / "pages.css"]

# (pattern, replacement) — order matters for some rules
VAR_REPLACEMENTS = [
    ("var(--color-navy)", "var(--color-primary)"),
    ("var(--color-brand-700)", "var(--color-primary)"),
    ("var(--color-brand-500)", "var(--color-primary-light)"),
    ("var(--color-brand-rgb)", "var(--color-primary-light-rgb)"),
    ("var(--color-accent-700)", "var(--color-earth)"),
    ("var(--color-accent-500)", "var(--color-accent)"),
    ("var(--color-warm-500)", "var(--color-accent)"),
    ("var(--color-warm-rgb)", "var(--color-accent-rgb)"),
    ("var(--color-ink)", "var(--color-text)"),
    ("var(--color-offwhite)", "var(--color-bg)"),
    ("var(--color-charcoal)", "var(--color-text)"),
    ("var(--font-sans)", "var(--font-body)"),
    ("var(--shadow-1)", "var(--shadow-md)"),
    ("var(--shadow-2)", "var(--shadow-lg)"),
    ("var(--color-danger-bg)", "var(--color-error-bg)"),
    ("var(--color-danger)", "var(--color-error)"),
    ("var(--container)", "var(--container-xl)"),
    ("var(--space-5)", "var(--space-6)"),
    ("var(--space-7)", "var(--space-12)"),
    ("var(--text-md)", "var(--text-base)"),
    ("var(--svc-navy)", "var(--color-primary)"),
    ("var(--svc-charcoal)", "var(--color-text)"),
    ("var(--svc-offwhite)", "var(--color-bg)"),
    ("var(--svc-accent-2)", "var(--color-primary-light)"),
    ("var(--svc-accent)", "var(--color-accent)"),
    ("var(--svc-shadow-hover)", "var(--shadow-lg)"),
    ("var(--svc-shadow)", "var(--shadow-md)"),
]

REGEX_REPLACEMENTS = [
    (r"rgba\(11,\s*31,\s*58,\s*([\d.]+)\)", r"rgb(var(--color-primary-rgb) / \1)"),
    (r"rgba\(11, 31, 58, ([\d.]+)\)", r"rgb(var(--color-primary-rgb) / \1)"),
    (r"rgba\(31,\s*41,\s*55,\s*([\d.]+)\)", r"rgb(var(--color-text-rgb) / \1)"),
    (r"rgba\(31, 41, 55, ([\d.]+)\)", r"rgb(var(--color-text-rgb) / \1)"),
    (r"rgba\(31,41,55,([\d.]+)\)", r"rgb(var(--color-text-rgb) / \1)"),
    (r"rgba\(47,\s*128,\s*237,\s*([\d.]+)\)", r"rgb(var(--color-accent-rgb) / \1)"),
    (r"rgba\(47, 128, 237, ([\d.]+)\)", r"rgb(var(--color-accent-rgb) / \1)"),
    (r"rgba\(180,\s*35,\s*24,\s*([\d.]+)\)", r"rgb(var(--color-error) / \1)"),
    (r"rgba\(180, 35, 24, ([\d.]+)\)", r"rgb(var(--color-error) / \1)"),
    (r"rgba\(11,31,58,([\d.]+)\)", r"rgb(var(--color-primary-rgb) / \1)"),
    (r"#ffffff\b", "var(--color-surface)"),
    (r"#fff\b(?![a-fA-F0-9])", "var(--color-text-on-dark)"),
    (r"\b#08162a\b", "color-mix(in srgb, var(--color-primary) 75%, black)"),
    (r"linear-gradient\(180deg,\s*var\(--color-primary\)\s*0%,\s*color-mix\(in srgb, var\(--color-primary\) 75%, black\)\s*100%\)",
     "linear-gradient(180deg, var(--color-primary) 0%, color-mix(in srgb, var(--color-primary) 75%, black) 100%)"),
]

LITERAL_REPLACEMENTS = [
    ("background: #fff;", "background: var(--color-surface);"),
    ("background-color: #fff;", "background-color: var(--color-surface);"),
    ("background: #ffffff", "background: var(--color-surface)"),
    ("0%, #ffffff 100%", "0%, var(--color-surface) 100%"),
    ("0%, #ffffff 100%)", "0%, var(--color-surface) 100%)"),
    ("border: 1px solid #ccc;", "border: 1px solid var(--color-border);"),
    ("border: 1px solid #ccc", "border: 1px solid var(--color-border)"),
    ("border-radius: 999px;", "border-radius: var(--radius-full);"),
    ("border-radius: 999px", "border-radius: var(--radius-full)"),
    ("border-radius: 50%;", "border-radius: var(--radius-full);"),
    ("box-shadow: 0 10px 28px rgba(0,0,0,0.14);", "box-shadow: var(--shadow-md);"),
    ("box-shadow: 0 10px 25px rgba(0,0,0,0.18);", "box-shadow: var(--shadow-md);"),
    ("box-shadow: 0 12px 24px rgb(var(--color-primary-light-rgb) / 0.18);",
     "box-shadow: var(--shadow-md);"),
    ("box-shadow: 0 12px 24px rgb(var(--color-primary-light-rgb) / 0.20);",
     "box-shadow: var(--shadow-md);"),
    ("box-shadow: 0 18px 50px rgba(0,0,0,0.22);", "box-shadow: var(--shadow-lg);"),
    ("box-shadow: 0 20px 60px rgba(2,6,23,0.25);", "box-shadow: var(--shadow-lg);"),
    ("box-shadow: 0 30px 90px rgba(2, 6, 23, 0.35);", "box-shadow: var(--shadow-lg);"),
    ("box-shadow: -20px 0 60px rgba(2, 6, 23, 0.25);", "box-shadow: var(--shadow-lg);"),
    ("box-shadow: 0 18px 45px rgba(2,6,23,0.10);", "box-shadow: var(--shadow-md);"),
    ("transition: 0.3s;", "transition: var(--transition-base);"),
    ("transition: 0.2s ease;", "transition: var(--transition-fast);"),
    ("transition: transform 0.2s ease;", "transition: transform var(--transition-fast);"),
    ("transition: transform 0.15s ease,", "transition: transform var(--transition-fast),"),
    ("transition: background 0.15s ease;", "transition: background var(--transition-fast);"),
    ("transition: background 0.2s ease,", "transition: background var(--transition-fast),"),
    ("gap: 8px;", "gap: var(--space-2);"),
    ("gap: 8px", "gap: var(--space-2)"),
    ("gap: 10px;", "gap: var(--space-3);"),
    ("gap: 10px", "gap: var(--space-3)"),
    ("gap: 12px;", "gap: var(--space-3);"),
    ("gap: 12px", "gap: var(--space-3)"),
    ("gap: 14px;", "gap: var(--space-4);"),
    ("gap: 16px;", "gap: var(--space-4);"),
    ("gap: 16px", "gap: var(--space-4)"),
    ("gap: 22px;", "gap: var(--space-6);"),
    ("gap: 24px;", "gap: var(--space-6);"),
    ("gap: 24px", "gap: var(--space-6)"),
    ("gap: 28px;", "gap: var(--space-8);"),
    ("gap: 30px;", "gap: var(--space-8);"),
    ("gap: 40px;", "gap: var(--space-8);"),
    ("padding: 10px 18px;", "padding: var(--space-3) var(--space-6);"),
    ("padding: 10px 12px;", "padding: var(--space-3) var(--space-3);"),
    ("padding: 12px 14px;", "padding: var(--space-3) var(--space-4);"),
    ("padding: 14px 16px;", "padding: var(--space-4) var(--space-4);"),
    ("padding: 18px;", "padding: var(--space-6);"),
    ("padding: 70px 20px 30px;", "padding: var(--space-16) var(--space-8) var(--space-8);"),
    ("padding: 50px 16px;", "padding: var(--space-12) var(--space-4);"),
    ("padding: 60px 16px;", "padding: var(--space-12) var(--space-4);"),
    ("padding: 40px 16px;", "padding: var(--space-8) var(--space-4);"),
    ("margin-bottom: 6px;", "margin-bottom: var(--space-2);"),
    ("margin-bottom: 14px;", "margin-bottom: var(--space-4);"),
    ("margin-bottom: 30px;", "margin-bottom: var(--space-8);"),
    ("margin-bottom: 60px;", "margin-bottom: var(--space-12);"),
    ("margin-top: 25px;", "margin-top: var(--space-6);"),
    ("margin-top: 6px;", "margin-top: var(--space-2);"),
    ("margin-top: 12px;", "margin-top: var(--space-3);"),
    ("margin-top: auto;", "margin-top: auto;"),
    ("font-size: 12px;", "font-size: var(--text-xs);"),
    ("font-size: 13px;", "font-size: var(--text-sm);"),
    ("font-size: 14px;", "font-size: var(--text-sm);"),
    ("font-size: 16px;", "font-size: var(--text-base);"),
    ("font-size: 18px;", "font-size: var(--text-lg);"),
    ("font-size: 20px;", "font-size: var(--text-xl);"),
    ("font-size: 22px;", "font-size: var(--text-xl);"),
    ("font-size: 32px;", "font-size: var(--text-3xl);"),
    ("font-size: 40px;", "font-size: var(--text-4xl);"),
    ("border-radius: 5px;", "border-radius: var(--radius-sm);"),
    ("border-radius: 8px;", "border-radius: var(--radius-md);"),
    ("border-radius: 10px;", "border-radius: var(--radius-md);"),
    ("border-radius: 12px;", "border-radius: var(--radius-lg);"),
    ("border-radius: 14px;", "border-radius: var(--radius-lg);"),
    ("border-radius: 16px;", "border-radius: var(--radius-lg);"),
    ("border-radius: 18px;", "border-radius: var(--radius-xl);"),
    ("z-index: 500;", "z-index: var(--z-nav);"),
    ("z-index: 900;", "z-index: var(--z-overlay);"),
    ("z-index: 960;", "z-index: calc(var(--z-modal) + 10);"),
    ("z-index: 970;", "z-index: calc(var(--z-modal) + 20);"),
    ("z-index: 980;", "z-index: var(--z-modal);"),
    ("z-index: 9999;", "z-index: calc(var(--z-nav) + 100);"),
]

HEADING_FONT_RULES = [
    ".products-title",
    ".blogs-title",
    ".content-subtext-text h3",
    ".modal__title",
    ".cart-drawer__title",
    ".footer-title",
    ".services__title",
]

def count_changes(before: str, after: str) -> int:
    """Rough count of changed lines."""
    b, a = before.splitlines(), after.splitlines()
    return sum(1 for i in range(max(len(b), len(a))) if (b[i] if i < len(b) else "") != (a[i] if i < len(a) else ""))


def migrate(content: str) -> tuple[str, int]:
    original = content
    changes = 0

    for old, new in VAR_REPLACEMENTS:
        n = content.count(old)
        if n:
            content = content.replace(old, new)
            changes += n

    for pattern, repl in REGEX_REPLACEMENTS:
        content, n = re.subn(pattern, repl, content, flags=re.IGNORECASE)
        changes += n

    for old, new in LITERAL_REPLACEMENTS:
        n = content.count(old)
        if n:
            content = content.replace(old, new)
            changes += n

    # Section / product titles → heading font
    for sel in [".products-title", ".blogs-title", ".content-subtext-text h3", ".modal__title", ".footer-title", ".services__title"]:
        content = re.sub(
            rf"({re.escape(sel)}\s*\{{[^}}]*?)font-family:\s*var\(--font-body\)",
            r"\1font-family: var(--font-heading)",
            content,
            flags=re.DOTALL,
        )

    content = re.sub(
        r"(\.content-text h1\s*\{[^}}]*?)font-family:\s*var\(--font-body\)",
        r"\1font-family: var(--font-heading)",
        content,
        flags=re.DOTALL,
    )

    line_changes = count_changes(original, content)
    return content, changes + max(0, line_changes - changes) // 2


def main() -> None:
    total = 0
    for path in FILES:
        if not path.exists():
            continue
        text = path.read_text(encoding="utf-8")
        new_text, n = migrate(text)
        path.write_text(new_text, encoding="utf-8")
        print(f"{path.relative_to(ROOT)}: {n} remplacements")
        total += n
    print(f"TOTAL: {total}")


if __name__ == "__main__":
    main()
