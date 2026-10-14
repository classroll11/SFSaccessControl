"""Inicializador y ciclo de vida de la aplicación de escritorio SFS Access."""
from pathlib import Path

from PySide6.QtWidgets import QApplication, QDialog

from .models import Repository
from .styles import APP_STYLESHEET
from .views import LoginDialog, MainWindow, PasswordDialog


def run_application(root: Path) -> int:
    """Inicia la aplicación de escritorio SFS Access."""
    app = QApplication([])
    app.setApplicationName("SFS Access")
    app.setStyle("Fusion")
    app.setStyleSheet(APP_STYLESHEET)

    repository = Repository()
    login = LoginDialog(repository, root)
    if login.exec() != QDialog.DialogCode.Accepted or login.user is None:
        return 0

    user = login.user
    if user["debe_cambiar_password"]:
        password_dialog = PasswordDialog(repository, user)
        if password_dialog.exec() != QDialog.DialogCode.Accepted:
            return 0

    window = MainWindow(root, repository, user)
    window.show()
    return app.exec()
