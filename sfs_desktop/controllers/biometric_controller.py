"""Controlador para la orquestación del lector biométrico SecuGen."""
from typing import Any, Dict, Optional

from ..biometric import BiometricBridge


class BiometricController:
    """Controla los comandos y lecturas del dispositivo biométrico."""

    def __init__(self, bridge: BiometricBridge):
        self.bridge = bridge

    def start_attendance(self, teacher_id: int, grade: str, date_text: str, subject: str) -> None:
        """Inicia lectura continua para marcado automático de asistencia."""
        self.bridge.start_attendance_scan(teacher_id, grade, date_text, subject or "GENERAL")

    def start_access(self, event_type: str) -> None:
        """Inicia captura para control de acceso en portería."""
        self.bridge.start_access_scan(event_type)

    def start_enrollment(self, user_id: int, slot: int, finger: str) -> None:
        """Inicia el enrolamiento o registro de una nueva huella."""
        self.bridge.start_enrollment(user_id, slot, finger)

    def turn_on(self) -> None:
        """Enciende el lector SecuGen."""
        self.bridge.turn_on()

    def turn_off(self) -> str:
        """Apaga el lector SecuGen y cancela cualquier operación pendiente."""
        return self.bridge.turn_off()

    def cancel(self) -> None:
        """Cancela la captura actual."""
        self.bridge.cancel()

    def poll_status(self) -> Optional[Dict[str, Any]]:
        """Verifica el estado del huellero SecuGen."""
        return self.bridge.poll()
