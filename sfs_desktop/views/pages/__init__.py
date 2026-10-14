"""Módulo de páginas principales de la interfaz SFS Access."""
from .absences_page import AbsencesPage
from .access_page import AccessPage
from .attendance_page import AttendancePage
from .dashboard_page import DashboardPage
from .fingerprints_page import FingerprintsPage
from .reports_page import ReportsPage
from .students_page import StudentsPage
from .users_page import UsersPage

__all__ = [
    "AbsencesPage",
    "AccessPage",
    "AttendancePage",
    "DashboardPage",
    "FingerprintsPage",
    "ReportsPage",
    "StudentsPage",
    "UsersPage",
]
