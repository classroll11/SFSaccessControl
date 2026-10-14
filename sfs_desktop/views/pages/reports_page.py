"""Vista de consulta y exportación de reportes."""
import csv

from PySide6.QtCore import QDate, Qt
from PySide6.QtWidgets import (
    QComboBox,
    QDateEdit,
    QFileDialog,
    QFrame,
    QHBoxLayout,
    QLabel,
    QMessageBox,
    QPushButton,
    QVBoxLayout,
    QWidget,
)

from ...repository import Repository
from ..common import make_table, populate_table, show_error


class ReportsPage(QWidget):
    ATTENDANCE_COLUMNS = [
        "fecha", "documento", "matricula", "estudiante", "grado",
        "materia", "estado_asistencia", "docente", "observaciones",
    ]
    ACCESS_COLUMNS = [
        "fecha_hora", "tipo_evento", "estado_acceso", "nombre",
        "documento", "grado", "observaciones",
    ]

    def __init__(self, repository: Repository):
        super().__init__()
        self.setAttribute(Qt.WidgetAttribute.WA_StyledBackground, True)
        self.setAutoFillBackground(True)
        self.setStyleSheet("background: #ffffff;")
        self.repository = repository
        self.rows: list[dict] = []
        self.columns: list[str] = []

        layout = QVBoxLayout(self)
        filter_panel = QFrame()
        filter_panel.setObjectName("filterBar")
        panel_layout = QHBoxLayout(filter_panel)
        filters = QHBoxLayout()

        self.report_type = QComboBox()
        self.report_type.addItems(["Asistencia de aula", "Accesos"])
        self.start_date = QDateEdit(QDate.currentDate().addDays(-30))
        self.end_date = QDateEdit(QDate.currentDate())
        self.start_date.setCalendarPopup(True)
        self.end_date.setCalendarPopup(True)
        self.grade = QComboBox()
        self.grade.addItem("Todos los grados")
        try:
            self.grade.addItems(repository.list_grades())
        except Exception as error:
            show_error(self, error)

        refresh_button = QPushButton("Aplicar filtros")
        refresh_button.setObjectName("filterButton")
        reset_button = QPushButton("Restablecer")
        reset_button.setObjectName("clearFilterButton")
        export_button = QPushButton("Exportar CSV")
        export_button.setObjectName("clearFilterButton")

        filters.addWidget(QLabel("Reporte:"))
        filters.addWidget(self.report_type)
        filters.addWidget(QLabel("Desde:"))
        filters.addWidget(self.start_date)
        filters.addWidget(QLabel("Hasta:"))
        filters.addWidget(self.end_date)
        filters.addWidget(self.grade)
        filters.addWidget(refresh_button)
        filters.addWidget(reset_button)
        filters.addWidget(export_button)
        panel_layout.addLayout(filters)
        layout.addWidget(filter_panel)

        self.table = make_table(self.ATTENDANCE_COLUMNS)
        layout.addWidget(self.table)

        refresh_button.clicked.connect(self.refresh)
        reset_button.clicked.connect(self.reset_filters)
        export_button.clicked.connect(self.export)
        self.report_type.currentIndexChanged.connect(self._report_changed)
        self.refresh()

    def _report_changed(self, _index: int) -> None:
        self.grade.setEnabled(self.report_type.currentIndex() == 0)
        self.refresh()

    def reset_filters(self) -> None:
        self.report_type.setCurrentIndex(0)
        self.start_date.setDate(QDate.currentDate().addDays(-30))
        self.end_date.setDate(QDate.currentDate())
        self.grade.setCurrentIndex(0)
        self.refresh()

    def refresh(self) -> None:
        start_date = self.start_date.date().toString("yyyy-MM-dd")
        end_date = self.end_date.date().toString("yyyy-MM-dd")
        if start_date > end_date:
            QMessageBox.warning(self, "Reporte", "La fecha inicial no puede ser posterior a la fecha final.")
            return
        try:
            if self.report_type.currentIndex() == 0:
                grade = self.grade.currentText()
                self.columns = self.ATTENDANCE_COLUMNS
                self.rows = self.repository.list_attendance_report(
                    start_date, end_date, None if grade == "Todos los grados" else grade,
                )
            else:
                self.columns = self.ACCESS_COLUMNS
                self.rows = self.repository.list_access_report(start_date, end_date)
            self.table.setColumnCount(len(self.columns))
            self.table.setHorizontalHeaderLabels(self.columns)
            populate_table(self.table, self.rows, self.columns)
        except Exception as error:
            show_error(self, error)

    def export(self) -> None:
        if not self.rows:
            QMessageBox.information(self, "Exportar reporte", "No hay registros para exportar.")
            return
        path, _ = QFileDialog.getSaveFileName(self, "Guardar reporte", "reporte_sfs.csv", "CSV (*.csv)")
        if not path:
            return
        try:
            with open(path, "w", encoding="utf-8-sig", newline="") as output:
                writer = csv.DictWriter(output, fieldnames=self.columns, extrasaction="ignore")
                writer.writeheader()
                for row in self.rows:
                    writer.writerow({
                        key: "'" + value if isinstance(value, str)
                        and value.startswith(("=", "+", "-", "@", "\t", "\r"))
                        else value
                        for key, value in row.items()
                    })
        except OSError as error:
            show_error(self, error)
            return
        QMessageBox.information(self, "Exportar reporte", "Reporte CSV guardado correctamente.")
