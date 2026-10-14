"""Controlador de autenticación y gestión de credenciales."""
from typing import Any, Dict, Optional

from ..repository import Repository


class AuthController:
    """Controlador que gestiona la autenticación y ciclo de vida de sesión."""

    def __init__(self, repository: Repository):
        self.repository = repository

    def authenticate(self, login: str, password: str) -> Optional[Dict[str, Any]]:
        """Valida credenciales contra la base de datos."""
        return self.repository.authenticate(login.strip(), password)

    def change_password(self, user_id: int, new_password: str) -> None:
        """Actualiza la contraseña del usuario."""
        self.repository.change_password(user_id, new_password)

    def reset_password(self, user_id: int) -> None:
        """Restablece la contraseña por defecto a 123456."""
        self.repository.reset_password(user_id)
