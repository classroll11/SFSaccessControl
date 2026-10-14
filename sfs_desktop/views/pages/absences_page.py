"""Vista de consulta y justificación de faltas escolares."""
from PySide6.QtCore import QDate, Qt
from PySide6.QtWidgets import (
    QComboBox,
    QDateEdit,
    QFrame,
    QHBoxLayout,
    QHeaderView,
    QInputDialog,
    QLabel,
    QMessageBox,
    QPushButton,
    QVBoxLayout,
    QWidget,
)

from ...repository import Repository
from ..common import make_table, populate_table, selected_row, show_error


class AbsencesPage(QWidget):
    def __init__(self, repository: Repository):
        super().__init__()
        self.setAttribute(Qt.WidgetAttribute.WA_StyledBackground, True)
        self.setAutoFillBackground(True)
        self.setStyleSheet("background: #ffffff;")
        self.repository = repository

        layout = QVBoxLayout(self)
        filter_panel = QFrame()
        filter_panel.setObjectName("filterBar")
        panel_layout = QHBoxLayout(filter_panel)
        filters = QHBoxLayout()

        self.attendance_date = QDateEdit(QDate.currentDate())
        self.attendance_date.setCalendarPopup(True)
        self.grade = QComboBox()
        self.grade.addItem("Todos los grados")
        try:
            self.grade.addItems(repository.list_grades())
        except Exception as error:
            show_error(self, error)

        refresh_button = QPushButton("Aplicar filtros")
        refresh_button.setObjectName("filterButton")
        reset_button = QPushButton("Hoy / todos")
        reset_button.setObjectName("clearFilterButton")
        justify_button = QPushButton("Justificar falta")

        filters.addWidget(QLabel("Fecha:"))
        filters.addWidget(self.attendance_date)
        filters.addWidget(self.grade)
        filters.addWidget(refresh_button)
        filters.addWidget(reset_button)
        filters.addWidget(justify_button)
        panel_layout.addLayout(filters)
        layout.addWidget(filter_panel)

        self.table = make_table(
            ["ID", "Fecha", "Grado", "Estudiante", "Documento", "Materia", "Estado", "Docente", "Observaciones"]
        )
        self.table.setColumnHidden(0, True)
        header = self.table.horizontalHeader()
        header.setStretchLastSection(False)
        header.setSectionResizeMode(1, QHeaderView.ResizeMode.ResizeToContents)
        header.setSectionResizeMode(2, QHeaderView.ResizeMode.ResizeToContents)
        header.setSectionResizeMode(3, QHeaderView.ResizeMode.Stretch)  # Estudiante se expande
        header.setSectionResizeMode(4, QHeaderView.ResizeMode.ResizeToContents)
        header.setSectionResizeMode(5, QHeaderView.ResizeMode.ResizeToContents)
        header.setSectionResizeMode(6, QHeaderView.ResizeMode.ResizeToContents)
        header.setSectionResizeMode(7, QHeaderView.ResizeMode.ResizeToContents)
        header.setSectionResizeMode(8, QHeaderView.ResizeMode.Stretch)  # Observaciones se expande
        layout.addWidget(self.table)

        refresh_button.clicked.connect(self.refresh)
        reset_button.clicked.connect(self.reset_filters)
        self.attendance_date.dateChanged.connect(self.refresh)
        self.grade.currentTextChanged.connect(self.refresh)
        justify_button.clicked.connect(self.justify)
        self.refresh()

    def reset_filters(self) -> None:
        self.attendance_date.setDate(QDate.currentDate())
        self.grade.setCurrentIndex(0)
        self.refresh()

    def refresh(self, *_args) -> None:
        try:
            grade = self.grade.currentText()
            rows = self.repository.list_absences(
                self.attendance_date.date().toString("yyyy-MM-dd"),
                None if grade == "Todos los grados" else grade,
            )
            populate_table(
                self.table, rows,
                ["id", "fecha", "grado", "estudiante", "documento", "materia",
                 "estado_asistencia", "docente", "observaciones"],
            )
        except Exception as error:
            show_error(self, error)

    def justify(self) -> None:
        attendance = selected_row(self.table)
        if not attendance:
            QMessageBox.information(self, "Justificar falta", "Selecciona un registro.")
            return
        reason, accepted = QInputDialog.getText(self, "Justificar falta", "Motivo de justificación:")
        if not accepted:
            return
        if not reason.strip():
            QMessageBox.warning(self, "Justificar falta", "El motivo es obligatorio.")
            return
        try:
            self.repository.justify_absence(int(attendance["id"]), reason)
        except Exception as error:
            show_error(self, error)
            return
        self.refresh()
