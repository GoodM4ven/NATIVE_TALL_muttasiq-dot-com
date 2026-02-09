#!/usr/bin/env bash
set -euo pipefail

root_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")/../../.." && pwd)"

python3 - "$root_dir" <<'PY'
import re
import sys
from pathlib import Path

root_dir = Path(sys.argv[1])


def replace_kotlin_block(text: str, new_block: str) -> tuple[str, bool]:
    if "window.__nativeBackAction" in text:
        return text, False

    original_block = (
        "        onBackPressedDispatcher.addCallback(this) {\n"
        "            if (webView.canGoBack()) {\n"
        "                webView.goBack()\n"
        "            } else {\n"
        "                finish()\n"
        "            }\n"
        "        }\n"
    )

    if original_block not in text:
        return text, False

    return text.replace(original_block, new_block, 1), True


def patch_main_activity(path: Path) -> bool:
    if not path.exists():
        print(f"[native-back-handler] skip missing: {path}")
        return False

    text = path.read_text()

    new_block = (
        "        onBackPressedDispatcher.addCallback(this) {\n"
        "            val js =\n"
        "                \"(function() { try { return window.__nativeBackAction && window.__nativeBackAction(); } \" +\n"
        "                    \"catch (e) { return false; } })();\"\n"
        "\n"
        "            webView.evaluateJavascript(js) { value ->\n"
        "                val handled = value?.trim()?.trim('\"') == \"true\"\n"
        "                if (handled) {\n"
        "                    return@evaluateJavascript\n"
        "                }\n"
        "\n"
        "                if (webView.canGoBack()) {\n"
        "                    webView.goBack()\n"
        "                } else {\n"
        "                    finish()\n"
        "                }\n"
        "            }\n"
        "        }\n"
    )

    updated_text, changed = replace_kotlin_block(text, new_block)

    if changed:
        path.write_text(updated_text)
        print(f"[native-back-handler] patched: {path}")
    else:
        print(f"[native-back-handler] already ok: {path}")

    return changed


paths = [
    root_dir
    / "vendor/nativephp/mobile/resources/androidstudio/app/src/main/java/com/nativephp/mobile/ui/MainActivity.kt",
    root_dir / "nativephp/android/app/src/main/java/com/nativephp/mobile/ui/MainActivity.kt",
]

for path in paths:
    patch_main_activity(path)
PY
