"""Controlador para la gestión y registro de asistencia escolar."""
from typing import Any, Dict, List

from ..repository import Repository


class AttendanceController:
    """Controlador que aísla la lógica de asistencia del componente visual."""

    def __init__(self, repository: Repository):
        self.repository = repository

    def get_available_grades(self) -> List[str]:
        """Obtiene la lista de grados registrados en la institución."""
        return self.repository.list_grades()

    def load_attendance(self, grade: str, date_text: str, subject: str) -> List[Dict[str, Any]]:
        """Consulta la planilla de asistencia para un grupo, fecha y materia específicos."""
        if not grade:
            return []
        return self.repository.list_class_attendance(grade, date_text, subject or "GENERAL")

    def save_batch(
        self,
        teacher_id: int,
        grade: str,
        date_text: str,
        subject: str,
        attendance_map: Dict[int, str],
        observations: str = "",
    ) -> int:
        """Guarda en lote los registros de asistencia de los estudiantes."""
        if not grade or not attendance_map:
            return 0
        self.repository.save_attendance_batch(
            teacher_id=teacher_id,
            grade=grade,
            attendance_date=date_text,
            subject=subject or "GENERAL",
            attendance=attendance_map,
            observations=observations.strip(),
        )
        return len(attendance_map)
