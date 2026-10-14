"""Vista de control de accesos y portería."""
from PySide6.QtCore import Qt
from PySide6.QtWidgets import (
    QComboBox,
    QHBoxLayout,
    QLabel,
    QLineEdit,
    QMessageBox,
    QPushButton,
    QVBoxLayout,
    QWidget,
)

from ...repository import Repository
from ..common import make_table, populate_table, show_error


class AccessPage(QWidget):
    def __init__(self, repository: Repository):
        super().__init__()
        self.setAttribute(Qt.WidgetAttribute.WA_StyledBackground, True)
        self.setAutoFillBackground(True)
        self.setStyleSheet("background: #ffffff;")
        self.repository = repository

        layout = QVBoxLayout(self)
        actions = QHBoxLayout()
        self.event = QComboBox()
        self.event.addItems(["ENTRADA", "SALIDA"])
        self.reader_status = QLabel("Estado del lector: verificando...")
        on_button = QPushButton("Encender huellero")
        off_button = QPushButton("Apagar huellero")
        scan_button = QPushButton("Registrar acceso por huella")
        cancel_button = QPushButton("Cancelar captura")
        refresh_button = QPushButton("Actualizar historial")
        actions.addWidget(QLabel("Movimiento:"))
        actions.addWidget(self.event)
        for button in (on_button, off_button, scan_button, cancel_button, refresh_button):
            actions.addWidget(button)
        layout.addLayout(actions)

        manual_actions = QHBoxLayout()
        self.identifier = QLineEdit()
        self.identifier.setPlaceholderText("Documento o matrícula")
        manual_button = QPushButton("Buscar / confirmar acceso")
        manual_actions.addWidget(self.identifier)
        manual_actions.addWidget(manual_button)
        layout.addLayout(manual_actions)

        layout.addWidget(self.reader_status)
        self.result = QLabel("Listo para operar.")
        layout.addWidget(self.result)

        self.table = make_table(["Fecha y hora", "Movimiento", "Resultado", "Nombre", "Documento", "Observaciones"])
        layout.addWidget(self.table)

        on_button.clicked.connect(lambda: self._reader_action("on"))
        off_button.clicked.connect(lambda: self._reader_action("off"))
        scan_button.clicked.connect(self.scan)
        cancel_button.clicked.connect(self.cancel)
        refresh_button.clicked.connect(self.refresh)
        manual_button.clicked.connect(self.confirm_manual_access)
        self.refresh()

    def _reader_action(self, action: str) -> None:
        bridge = self.window().biometric
        try:
            if action == "on":
                bridge.start_access_scan(self.event.currentText(), continuous=True)
                self.result.setText("Lector SecuGen encendido en modo lectura continua.")
            else:
                self.result.setText(bridge.turn_off())
        except Exception as error:
            show_error(self, error)

    def scan(self) -> None:
        try:
            self.window().biometric.start_access_scan(self.event.currentText(), continuous=True)
            self.result.setText("Coloca el dedo en el lector SecuGen (Lectura continua activa).")
        except Exception as error:
            show_error(self, error)

    def cancel(self) -> None:
        try:
            self.window().biometric.cancel()
            self.result.setText("Cancelación solicitada.")
        except Exception as error:
            show_error(self, error)

    def confirm_manual_access(self) -> None:
        identifier = self.identifier.text().strip()
        if not identifier:
            QMessageBox.warning(self, "Acceso manual", "Escribe un documento o matrícula.")
            return
        try:
            user = self.repository.find_access_user(identifier)
            if not user:
                QMessageBox.information(self, "Acceso manual", "No se encontró una persona con ese dato.")
                return
            approved = user["estado"] == "ACTIVO"
            if not approved:
                self.repository.record_access(
                    int(user["id"]), self.event.currentText(), False,
                    f"Validación manual - Usuario {user['estado']}",
                )
                QMessageBox.warning(
                    self, "Acceso denegado",
                    f"{user['nombre']} tiene estado {user['estado']}.",
                )
            else:
                answer = QMessageBox.question(
                    self, "Confirmar acceso",
                    f"¿Confirmar {self.event.currentText().lower()} para {user['nombre']}?",
                )
                if answer != QMessageBox.StandardButton.Yes:
                    return
                self.repository.record_access(
                    int(user["id"]), self.event.currentText(), True, "Validación manual por portería."
                )
                QMessageBox.information(self, "Acceso confirmado", f"Acceso registrado para {user['nombre']}.")
            self.refresh()
        except Exception as error:
            show_error(self, error)

    def handle_status(self, status: dict) -> None:
        self.reader_status.setText(f"Estado del lector: {status.get('status', 'desconocido')}")
        self.result.setText(status.get("mensaje", ""))
        if status.get("status") in {
            "lector_encendido", "lector_apagado", "identificado", "no_registrado",
            "error", "cancelado",
        } and not status.get("asistencia_estado"):
            self.refresh()

    def refresh(self) -> None:
        try:
            rows = self.repository.list_access()
            populate_table(
                self.table, rows,
                ["fecha_hora", "tipo_evento", "estado_acceso", "nombre", "documento", "observaciones"],
            )
        except Exception as error:
            show_error(self, error)
