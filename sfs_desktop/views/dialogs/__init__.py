"""Módulo de diálogos de la interfaz."""
from .login_dialog import LoginDialog
from .modals import EnrollmentDialog, PasswordDialog, StudentDialog, UserDialog

__all__ = [
    "LoginDialog",
    "EnrollmentDialog",
    "PasswordDialog",
    "StudentDialog",
    "UserDialog",
]
