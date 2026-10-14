"""Capa de compatibilidad hacia atrás para sfs_desktop.ui.

Redirige a la nueva arquitectura modular MVC (views, controllers, models, styles).
"""
from .app import run_application
from .styles import APP_STYLESHEET, MANAGERS, STAFF_ROLES
from .views import (
    AbsencesPage,
    AccessPage,
    AttendancePage,
    DashboardPage,
    EnrollmentDialog,
    FingerprintsPage,
    LoginDialog,
    MainWindow,
    PasswordDialog,
    ReportsPage,
    StudentDialog,
    StudentsPage,
    UserDialog,
    UsersPage,
    make_table,
    populate_table,
    selected_row,
    show_error,
)

__all__ = [
    "run_application",
    "APP_STYLESHEET",
    "MANAGERS",
    "STAFF_ROLES",
    "MainWindow",
    "LoginDialog",
    "PasswordDialog",
    "EnrollmentDialog",
    "UserDialog",
    "StudentDialog",
    "DashboardPage",
    "StudentsPage",
    "UsersPage",
    "FingerprintsPage",
    "AttendancePage",
    "AccessPage",
    "ReportsPage",
    "AbsencesPage",
    "make_table",
    "populate_table",
    "selected_row",
    "show_error",
]
