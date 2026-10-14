"""Diálogos modales auxiliares para gestión de usuarios, estudiantes y huellas."""
from PySide6.QtWidgets import (
    QComboBox,
    QDialog,
    QDialogButtonBox,
    QFormLayout,
    QLabel,
    QLineEdit,
    QMessageBox,
    QProgressBar,
    QPushButton,
    QVBoxLayout,
)

from ...repository import Repository
from ...styles import STAFF_ROLES
from ..common import show_error


class EnrollmentDialog(QDialog):
    def __init__(self, person_name: str, finger: str, cancel_callback):
        super().__init__()
        self._cancel_callback = cancel_callback
        self._finished = False
        self._cancel_requested = False
        self.setWindowTitle("Registro de huella")
        self.setModal(True)
        self.setMinimumWidth(390)

        layout = QVBoxLayout(self)
        layout.addWidget(QLabel(f"Registrando huella de {person_name}"))
        self.message = QLabel(
            f"Coloca el dedo {finger} en el lector SecuGen y mantenlo quieto."
        )
        self.message.setWordWrap(True)
        layout.addWidget(self.message)

        self.progress = QProgressBar()
        self.progress.setRange(0, 0)
        self.progress.setTextVisible(False)
        layout.addWidget(self.progress)

        self.action_button = QPushButton("Cancelar captura")
        self.action_button.clicked.connect(self._handle_action)
        layout.addWidget(self.action_button)

    @property
    def is_finished(self) -> bool:
        return self._finished

    def update_progress(self, message: str) -> None:
        if not self._finished and not self._cancel_requested and message:
            self.message.setText(message)

    def show_success(self, message: str) -> None:
        self._finish(message or "La huella se registró correctamente.", True)

    def show_failure(self, message: str) -> None:
        self._finish(message or "No se pudo completar el registro de la huella.", False)

    def show_cancelled(self, message: str) -> None:
        self._finish(message or "El registro de la huella fue cancelado.", False)

    def _finish(self, message: str, succeeded: bool) -> None:
        self._finished = True
        self.progress.setRange(0, 1)
        self.progress.setValue(1 if succeeded else 0)
        self.message.setText(message)
        self.action_button.setText("Cerrar")

    def _handle_action(self) -> None:
        if self._finished:
            self.accept()
            return
        if not self._cancel_requested:
            self._cancel_requested = True
            self.action_button.setEnabled(False)
            self.message.setText("Cancelando la captura...")
            self._cancel_callback()

    def reject(self) -> None:
        if self._finished:
            super().reject()
        else:
            self._handle_action()


class PasswordDialog(QDialog):
    def __init__(self, repository: Repository, user: dict):
        super().__init__()
        self.repository = repository
        self.user = user
        self.setWindowTitle("Actualizar contraseña")
        layout = QVBoxLayout(self)
        layout.addWidget(QLabel("Debes cambiar tu contraseña para continuar."))
        form = QFormLayout()
        self.password = QLineEdit()
        self.password.setEchoMode(QLineEdit.EchoMode.Password)
        self.confirm = QLineEdit()
        self.confirm.setEchoMode(QLineEdit.EchoMode.Password)
        form.addRow("Nueva contraseña:", self.password)
        form.addRow("Confirmar:", self.confirm)
        layout.addLayout(form)
        buttons = QDialogButtonBox(QDialogButtonBox.StandardButton.Save | QDialogButtonBox.StandardButton.Cancel)
        buttons.accepted.connect(self._save)
        buttons.rejected.connect(self.reject)
        layout.addWidget(buttons)

    def _save(self) -> None:
        password = self.password.text()
        if len(password) < 6 or password == "123456":
            QMessageBox.warning(self, "Contraseña inválida", "Usa al menos 6 caracteres y no uses 123456.")
            return
        if password != self.confirm.text():
            QMessageBox.warning(self, "Contraseña inválida", "Las contraseñas no coinciden.")
            return
        try:
            self.repository.change_password(int(self.user["id"]), password)
        except Exception as error:
            show_error(self, error)
            return
        self.accept()


class UserDialog(QDialog):
    def __init__(self, user: dict | None = None):
        super().__init__()
        self.setWindowTitle("Editar usuario" if user else "Registrar usuario")
        self.setMinimumWidth(420)
        layout = QVBoxLayout(self)
        form = QFormLayout()
        self.document = QLineEdit(str((user or {}).get("documento") or ""))
        self.name = QLineEdit(str((user or {}).get("nombre") or ""))
        self.email = QLineEdit(str((user or {}).get("correo") or ""))
        self.enrollment = QLineEdit(str((user or {}).get("matricula") or ""))
        self.grade = QLineEdit(str((user or {}).get("grado") or ""))
        self.role = QComboBox()
        self.role.addItems(STAFF_ROLES)
        if user:
            self.role.setCurrentText(user["rol"])
        self.state = QComboBox()
        self.state.addItems(["ACTIVO", "INACTIVO", "SUSPENDIDO"])
        if user:
            self.state.setCurrentText(user["estado"])
        form.addRow("Documento:", self.document)
        form.addRow("Nombre:", self.name)
        form.addRow("Correo:", self.email)
        form.addRow("Matrícula (estudiantes):", self.enrollment)
        form.addRow("Grado / cargo:", self.grade)
        form.addRow("Rol:", self.role)
        form.addRow("Estado:", self.state)
        layout.addLayout(form)
        self.role.currentTextChanged.connect(self._role_changed)
        self._role_changed(self.role.currentText())
        buttons = QDialogButtonBox(QDialogButtonBox.StandardButton.Save | QDialogButtonBox.StandardButton.Cancel)
        buttons.accepted.connect(self._accept_values)
        buttons.rejected.connect(self.reject)
        layout.addWidget(buttons)

    def _role_changed(self, role: str) -> None:
        is_student = role == "ESTUDIANTE"
        self.enrollment.setEnabled(is_student)
        self.enrollment.setPlaceholderText("Si se deja vacío, se usa el documento" if is_student else "")
        self.grade.setPlaceholderText("Ej. 10°A" if is_student else "Cargo")

    def _accept_values(self) -> None:
        if not self.document.text().strip() or not self.name.text().strip():
            QMessageBox.warning(self, "Datos incompletos", "Documento y nombre son obligatorios.")
            return
        if self.role.currentText() == "ESTUDIANTE" and not self.grade.text().strip():
            QMessageBox.warning(self, "Datos incompletos", "El grado del estudiante es obligatorio.")
            return
        self.accept()

    def values(self) -> dict:
        document = self.document.text().strip()
        email = self.email.text().strip().lower()
        return {
            "documento": document,
            "matricula": (self.enrollment.text().strip() or document)
            if self.role.currentText() == "ESTUDIANTE" else None,
            "correo": email or None,
            "nombre": self.name.text().strip(),
            "grado": self.grade.text().strip() or None,
            "rol": self.role.currentText(),
            "estado": self.state.currentText(),
        }


class StudentDialog(QDialog):
    def __init__(self, student: dict | None = None):
        super().__init__()
        self.setWindowTitle("Editar estudiante" if student else "Registrar estudiante")
        self.setMinimumWidth(420)
        layout = QVBoxLayout(self)
        form = QFormLayout()
        self.document = QLineEdit(str((student or {}).get("documento") or ""))
        self.name = QLineEdit(str((student or {}).get("nombre") or ""))
        self.enrollment = QLineEdit(str((student or {}).get("matricula") or ""))
        self.grade = QLineEdit(str((student or {}).get("grado") or ""))
        self.state = QComboBox()
        self.state.addItems(["ACTIVO", "INACTIVO", "SUSPENDIDO"])
        if student:
            self.state.setCurrentText(student["estado"])
        form.addRow("Documento:", self.document)
        form.addRow("Nombre completo:", self.name)
        form.addRow("Matrícula:", self.enrollment)
        form.addRow("Grado / grupo:", self.grade)
        form.addRow("Estado:", self.state)
        layout.addLayout(form)
        buttons = QDialogButtonBox(QDialogButtonBox.StandardButton.Save | QDialogButtonBox.StandardButton.Cancel)
        buttons.accepted.connect(self._accept_values)
        buttons.rejected.connect(self.reject)
        layout.addWidget(buttons)

    def _accept_values(self) -> None:
        if not self.document.text().strip() or not self.name.text().strip() or not self.grade.text().strip():
            QMessageBox.warning(
                self, "Datos incompletos", "Documento, nombre y grado son obligatorios."
            )
            return
        self.accept()

    def values(self) -> dict:
        return {
            "documento": self.document.text().strip(),
            "nombre": self.name.text().strip(),
            "matricula": self.enrollment.text().strip() or self.document.text().strip(),
            "grado": self.grade.text().strip(),
            "estado": self.state.currentText(),
        }
