"""Entidades y tipos del dominio de SFS Access."""
from dataclasses import dataclass
from typing import Optional


@dataclass
class User:
    id: int
    documento: str
    nombre: str
    correo: Optional[str] = None
    matricula: Optional[str] = None
    grado: Optional[str] = None
    rol: str = "ESTUDIANTE"
    estado: str = "ACTIVO"
    debe_cambiar_password: bool = False
    finger_summary: Optional[str] = None
    finger_names: Optional[str] = None


@dataclass
class StudentAttendanceRow:
    id: int
    documento: str
    matricula: str
    nombre: str
    grado: str
    estado_asistencia: str = "SIN_REGISTRO"
    observaciones: Optional[str] = None


ATTENDANCE_STATUSES = [
    "SIN_REGISTRO",
    "PRESENTE",
    "RETARDO",
    "FALTA_INJUSTIFICADA",
    "FALTA_JUSTIFICADA",
]

DEFAULT_SUBJECTS = [
    "MATEMÁTICAS",
    "LENGUA CASTELLANA",
    "CIENCIAS NATURALES",
    "CIENCIAS SOCIALES",
    "INGLÉS",
    "EDUCACIÓN FÍSICA",
    "TECNOLOGÍA E INFORMÁTICA",
    "ÉTICA Y VALORES",
]
