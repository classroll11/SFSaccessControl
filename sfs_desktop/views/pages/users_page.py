"""Vista de administración de usuarios y personal del colegio."""
from PySide6.QtCore import QTimer, Qt
from PySide6.QtWidgets import (
    QDialog,
    QHBoxLayout,
    QHeaderView,
    QInputDialog,
    QLineEdit,
    QMessageBox,
    QPushButton,
    QVBoxLayout,
    QWidget,
)

from ...repository import Repository
from ..common import make_table, populate_table, selected_row, show_error
from ..dialogs.modals import UserDialog


class UsersPage(QWidget):
    def __init__(self, repository: Repository, user: dict):
        super().__init__()
        self.setAttribute(Qt.WidgetAttribute.WA_StyledBackground, True)
        self.setAutoFillBackground(True)
        self.setStyleSheet("background: #ffffff;")
        self.repository = repository
        self.current_user = user

        layout = QVBoxLayout(self)
        self.search = QLineEdit()
        self.search.setPlaceholderText("Buscar por nombre, documento, matrícula, correo, rol o estado")
        self.search.setClearButtonEnabled(True)
        layout.addWidget(self.search)

        actions = QHBoxLayout()
        self.table = make_table(
            ["ID", "Matrícula", "Nombre", "Grado / cargo", "Rol", "Estado", "Huellas"]
        )
        self.table.setColumnHidden(0, True)
        header = self.table.horizontalHeader()
        header.setStretchLastSection(False)
        header.setSectionResizeMode(1, QHeaderView.ResizeMode.ResizeToContents)
        header.setSectionResizeMode(2, QHeaderView.ResizeMode.Stretch)  # Nombre se expande
        header.setSectionResizeMode(3, QHeaderView.ResizeMode.ResizeToContents)
        header.setSectionResizeMode(4, QHeaderView.ResizeMode.ResizeToContents)
        header.setSectionResizeMode(5, QHeaderView.ResizeMode.ResizeToContents)
        header.setSectionResizeMode(6, QHeaderView.ResizeMode.ResizeToContents)

        add_button = QPushButton("Registrar")
        edit_button = QPushButton("Editar")
        remove_button = QPushButton("Eliminar")
        reset_button = QPushButton("Reiniciar contraseña")
        fingerprint_button = QPushButton("Registrar huella")
        refresh_button = QPushButton("Actualizar")

        for button in (
            add_button, edit_button, remove_button, reset_button, fingerprint_button, refresh_button,
        ):
            actions.addWidget(button)
        actions.addStretch(1)

        layout.addLayout(actions)
        layout.addWidget(self.table)

        add_button.clicked.connect(self.add_user)
        edit_button.clicked.connect(self.edit_user)
        remove_button.clicked.connect(self.remove_user)
        reset_button.clicked.connect(self.reset_password)
        fingerprint_button.clicked.connect(self.enroll_fingerprint)
        refresh_button.clicked.connect(self.refresh)

        self.search_timer = QTimer(self)
        self.search_timer.setSingleShot(True)
        self.search_timer.setInterval(250)
        self.search_timer.timeout.connect(self._filter_users)
        self.search.textChanged.connect(lambda _text: self.search_timer.start())
        self.refresh()

    def refresh(self) -> None:
        try:
            self._filter_users()
        except Exception as error:
            show_error(self, error)

    def _filter_users(self, _text: str = "") -> None:
        try:
            users = self.repository.list_users()
            query = self.search.text().strip().casefold()
            if query:
                searchable_fields = ("nombre", "documento", "matricula", "correo", "grado", "rol", "estado")
                users = [
                    user for user in users
                    if query in " ".join(str(user.get(key) or "") for key in searchable_fields).casefold()
                ]
            populate_table(
                self.table, users,
                ["id", "matricula", "nombre", "grado", "rol", "estado", "finger_summary"],
            )
        except Exception as error:
            show_error(self, error)

    def add_user(self) -> None:
        dialog = UserDialog()
        if dialog.exec() != QDialog.DialogCode.Accepted:
            return
        try:
            self.repository.save_user(dialog.values())
        except Exception as error:
            show_error(self, error)
            return
        self.refresh()
        QMessageBox.information(
            self, "Usuario registrado",
            "Personal nuevo inicia con la contraseña temporal 123456 y deberá cambiarla. "
            "Los estudiantes no reciben acceso de inicio de sesión.",
        )

    def edit_user(self) -> None:
        user = selected_row(self.table)
        if not user:
            QMessageBox.information(self, "Editar usuario", "Selecciona un usuario.")
            return
        dialog = UserDialog(user)
        if dialog.exec() != QDialog.DialogCode.Accepted:
            return
        try:
            self.repository.save_user(dialog.values(), int(user["id"]))
        except Exception as error:
            show_error(self, error)
            return
        self.refresh()

    def remove_user(self) -> None:
        user = selected_row(self.table)
        if not user:
            QMessageBox.information(self, "Eliminar usuario", "Selecciona un usuario.")
            return
        if int(user["id"]) == int(self.current_user["id"]):
            QMessageBox.warning(self, "Acción no permitida", "No puedes eliminar tu propia cuenta.")
            return
        answer = QMessageBox.question(
            self, "Confirmar eliminación",
            f"Eliminar a {user['nombre']}? La base de datos también eliminará sus huellas y asistencias.",
        )
        if answer != QMessageBox.StandardButton.Yes:
            return
        try:
            self.repository.delete_user(int(user["id"]))
        except Exception as error:
            show_error(self, error)
            return
        self.refresh()

    def reset_password(self) -> None:
        user = selected_row(self.table)
        if not user:
            QMessageBox.information(self, "Contraseña", "Selecciona un usuario.")
            return
        if user["rol"] == "ESTUDIANTE":
            QMessageBox.information(self, "Contraseña", "El estudiante no tiene una cuenta de acceso.")
            return
        answer = QMessageBox.question(
            self, "Reiniciar contraseña",
            f"¿Asignar 123456 a {user['nombre']} y exigir el cambio al iniciar sesión?",
        )
        if answer != QMessageBox.StandardButton.Yes:
            return
        try:
            self.repository.reset_password(int(user["id"]))
        except Exception as error:
            show_error(self, error)
            return
        QMessageBox.information(self, "Contraseña", "Contraseña temporal restablecida.")

    def enroll_fingerprint(self) -> None:
        user = selected_row(self.table)
        if not user:
            QMessageBox.information(self, "Registrar huella", "Selecciona primero una persona.")
            return
        is_student = user.get("rol") == "ESTUDIANTE"
        prompt = "Espacio de huella (único):" if is_student else "Número de dedo (1-6):"
        slot, accepted = QInputDialog.getInt(
            self, "Registrar huella", prompt, 1, 1, 1 if is_student else 6,
        )
        if not accepted:
            return
        finger, accepted = QInputDialog.getText(self, "Registrar huella", "Nombre del dedo:")
        if not accepted or not finger.strip():
            return
        window = self.window()
        if hasattr(window, "start_enrollment"):
            window.start_enrollment(int(user["id"]), slot, finger.strip(), str(user["nombre"]))
