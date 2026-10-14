"""Módulo de vistas y presentación de SFS Access."""
from .common import make_table, populate_table, selected_row, show_error
from .dialogs import (
    EnrollmentDialog,
    LoginDialog,
    PasswordDialog,
    StudentDialog,
    UserDialog,
)
from .main_window import MainWindow
from .pages import (
    AbsencesPage,
    AccessPage,
    AttendancePage,
    DashboardPage,
    FingerprintsPage,
    ReportsPage,
    StudentsPage,
    UsersPage,
)

__all__ = [
    "MainWindow",
    "make_table",
    "populate_table",
    "selected_row",
    "show_error",
    "LoginDialog",
    "EnrollmentDialog",
    "PasswordDialog",
    "StudentDialog",
    "UserDialog",
    "AbsencesPage",
    "AccessPage",
    "AttendancePage",
    "DashboardPage",
    "FingerprintsPage",
    "ReportsPage",
    "StudentsPage",
    "UsersPage",
]
