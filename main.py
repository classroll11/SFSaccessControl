from pathlib import Path

from sfs_desktop.ui import run_application


if __name__ == "__main__":
    raise SystemExit(run_application(Path(__file__).resolve().parent))
