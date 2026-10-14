"""Vista de registro y gestión de huellas dactilares."""
from PySide6.QtCore import Qt
from PySide6.QtWidgets import (
    QComboBox,
    QHBoxLayout,
    QHeaderView,
    QLabel,
    QLineEdit,
    QMessageBox,
    QPushButton,
    QVBoxLayout,
    QWidget,
)

from ...repository import Repository
from ..common import make_table, populate_table, selected_row, show_error


class FingerprintsPage(QWidget):
    FINGERS = [
        "Pulgar derecho", "Indice derecho", "Medio derecho",
        "Anular derecho", "Menique derecho", "Pulgar izquierdo",
        "Indice izquierdo", "Medio izquierdo", "Anular izquierdo",
        "Menique izquierdo",
    ]

    def __init__(self, repository: Repository):
        super().__init__()
        self.setAttribute(Qt.WidgetAttribute.WA_StyledBackground, True)
        self.setAutoFillBackground(True)
        self.setStyleSheet("background: #ffffff;")
        self.repository = repository

        layout = QVBoxLayout(self)
        intro = QLabel(
            "Los estudiantes admiten una sola huella; el personal puede registrar hasta seis. "
            "Selecciona una persona y el dedo, luego colócalo en el SecuGen Hamster Pro."
        )
        intro.setWordWrap(True)
        intro.setObjectName("pageHint")
        layout.addWidget(intro)

        filters = QHBoxLayout()
        self.search = QLineEdit()
        self.search.setPlaceholderText("Buscar por nombre, documento o matrícula")
        refresh_button = QPushButton("Actualizar personas")
        filters.addWidget(self.search, 1)
        filters.addWidget(refresh_button)
        layout.addLayout(filters)

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
        layout.addWidget(self.table, 1)

        enroll_controls = QHBoxLayout()
        self.slot = QComboBox()
        self.slot.addItems([str(number) for number in range(1, 7)])
        self.finger = QComboBox()
        self.finger.setEditable(True)
        self.finger.addItems(self.FINGERS)
        enroll_button = QPushButton("Registrar huella seleccionada")
        enroll_button.setObjectName("primaryButton")
        enroll_controls.addWidget(QLabel("Espacio:"))
        enroll_controls.addWidget(self.slot)
        enroll_controls.addWidget(QLabel("Dedo:"))
        enroll_controls.addWidget(self.finger, 1)
        enroll_controls.addWidget(enroll_button)
        layout.addLayout(enroll_controls)

        self.help_text = QLabel("Selecciona a una persona para ver sus dedos registrados.")
        self.help_text.setObjectName("mutedText")
        layout.addWidget(self.help_text)

        self.search.textChanged.connect(self.refresh)
        self.table.itemSelectionChanged.connect(self.show_selected_fingers)
        refresh_button.clicked.connect(self.refresh)
        enroll_button.clicked.connect(self.enroll)
        self.refresh()

    def refresh(self, _text: str = "") -> None:
        try:
            users = self.repository.list_users()
            query = self.search.text().strip().casefold()
            if query:
                users = [
                    user for user in users
                    if query in " ".join(
                        str(user.get(key) or "")
                        for key in ("nombre", "documento", "matricula", "grado", "rol")
                    ).casefold()
                ]
            populate_table(
                self.table, users,
                ["id", "matricula", "nombre", "grado", "rol", "estado", "finger_summary"],
            )
        except Exception as error:
            show_error(self, error)

    def show_selected_fingers(self) -> None:
        user = selected_row(self.table)
        if not user:
            self.help_text.setText("Selecciona a una persona para ver sus dedos registrados.")
            return
        is_student = user.get("rol") == "ESTUDIANTE"
        self.slot.clear()
        self.slot.addItems(["1"] if is_student else [str(number) for number in range(1, 7)])
        registered = user.get("finger_names") or "Ninguna"
        self.help_text.setText(
            f"Huellas registradas: {user.get('finger_summary', '0/1' if is_student else '0/6')} — {registered}"
        )

    def enroll(self) -> None:
        user = selected_row(self.table)
        if not user:
            QMessageBox.information(self, "Registrar huella", "Selecciona primero una persona.")
            return
        finger = self.finger.currentText().strip()
        if not finger:
            QMessageBox.warning(self, "Registrar huella", "Indica el nombre del dedo.")
            return
        answer = QMessageBox.question(
            self, "Confirmar enrolamiento",
            (
                f"¿Registrar o reemplazar la única huella ({finger}) para {user['nombre']}?"
                if user.get("rol") == "ESTUDIANTE"
                else f"¿Registrar el espacio {self.slot.currentText()} ({finger}) para {user['nombre']}?"
            ),
        )
        if answer != QMessageBox.StandardButton.Yes:
            return
        window = self.window()
        if hasattr(window, "start_enrollment"):
            window.start_enrollment(
                int(user["id"]), int(self.slot.currentText()), finger, str(user["nombre"])
            )
