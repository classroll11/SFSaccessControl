"""Vista de toma y control de asistencia escolar por aula."""
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
    QTableWidgetItem,
    QVBoxLayout,
    QWidget,
)

from ...models.entities import ATTENDANCE_STATUSES, DEFAULT_SUBJECTS
from ...repository import Repository
from ..common import make_table, show_error


class AttendancePage(QWidget):
    STATUS_VALUES = ATTENDANCE_STATUSES
    SUBJECTS = DEFAULT_SUBJECTS

    def __init__(self, repository: Repository, user: dict):
        super().__init__()
        self.setAttribute(Qt.WidgetAttribute.WA_StyledBackground, True)
        self.setAutoFillBackground(True)
        self.setStyleSheet("background: #ffffff;")
        self.repository = repository
        self.user = user

        layout = QVBoxLayout(self)

        filter_panel = QFrame()
        filter_panel.setObjectName("filterBar")
        panel_layout = QHBoxLayout(filter_panel)
        actions = QHBoxLayout()

        self.grade = QComboBox()
        self.attendance_date = QDateEdit(QDate.currentDate())
        self.attendance_date.setCalendarPopup(True)
        self.subject = QComboBox()
        self.subject.setEditable(True)
        self.subject.addItems(self.SUBJECTS)
        self.subject.setCurrentText("MATEMÁTICAS")

        refresh_button = QPushButton("Aplicar filtros")
        refresh_button.setObjectName("filterButton")
        all_present_button = QPushButton("Todos presentes")
        save_button = QPushButton("Guardar planilla")
        self.scan_button = QPushButton("Iniciar lectura continua")
        self.scan_button.setObjectName("primaryButton")
        self.reader_off_button = QPushButton("Apagar huellero")

        actions.addWidget(QLabel("Grado:"))
        actions.addWidget(self.grade)
        actions.addWidget(QLabel("Fecha:"))
        actions.addWidget(self.attendance_date)
        actions.addWidget(QLabel("Materia:"))
        actions.addWidget(self.subject)
        actions.addWidget(refresh_button)
        actions.addWidget(all_present_button)
        actions.addWidget(save_button)
        actions.addWidget(self.scan_button)
        actions.addWidget(self.reader_off_button)
        actions.addStretch(1)

        self.table = make_table(["ID", "Documento", "Matrícula", "Estudiante", "Grado", "Estado"])
        self.table.setColumnHidden(0, True)
        table_header = self.table.horizontalHeader()
        table_header.setStretchLastSection(False)
        table_header.setSectionResizeMode(1, QHeaderView.ResizeMode.ResizeToContents)
        table_header.setSectionResizeMode(2, QHeaderView.ResizeMode.ResizeToContents)
        table_header.setSectionResizeMode(3, QHeaderView.ResizeMode.Stretch)
        table_header.setSectionResizeMode(4, QHeaderView.ResizeMode.ResizeToContents)
        table_header.setSectionResizeMode(5, QHeaderView.ResizeMode.Fixed)
        self.table.setColumnWidth(5, 200)
        self.table.verticalHeader().setDefaultSectionSize(38)

        panel_layout.addLayout(actions)
        layout.addWidget(filter_panel)

        self.biometric_message = QLabel("Selecciona un grupo y fecha para consultar la planilla.")
        self.biometric_message.setObjectName("attendanceStatusBanner")
        layout.addWidget(self.biometric_message)
        layout.addWidget(self.table)

        self.grade.currentTextChanged.connect(self.refresh)
        self.attendance_date.dateChanged.connect(self.refresh)
        self.subject.activated.connect(self.refresh)
        self.subject.lineEdit().returnPressed.connect(self.refresh)
        refresh_button.clicked.connect(self.refresh)
        all_present_button.clicked.connect(self.mark_all_present)
        save_button.clicked.connect(self.save)
        self.scan_button.clicked.connect(self.scan)
        self.reader_off_button.clicked.connect(self.stop_scan)
        self.continuous_scan_active = False
        self.refresh()

    def _date_text(self) -> str:
        return self.attendance_date.date().toString("yyyy-MM-dd")

    @staticmethod
    def _apply_status_style(combo: QComboBox) -> None:
        state = combo.currentText()
        if state == "PRESENTE":
            combo.setStyleSheet("QComboBox { color: #15803d; font-weight: 600; background: #f0fdf4; border: 1px solid #86efac; border-radius: 6px; padding: 2px 6px; }")
        elif state == "RETARDO":
            combo.setStyleSheet("QComboBox { color: #b45309; font-weight: 600; background: #fffbeb; border: 1px solid #fde68a; border-radius: 6px; padding: 2px 6px; }")
        elif state in ("FALTA_INJUSTIFICADA", "FALTA_JUSTIFICADA"):
            combo.setStyleSheet("QComboBox { color: #b91c1c; font-weight: 600; background: #fef2f2; border: 1px solid #fca5a5; border-radius: 6px; padding: 2px 6px; }")
        else:
            combo.setStyleSheet("QComboBox { color: #64748b; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 6px; padding: 2px 6px; }")

    def refresh(self, *_args) -> None:
        try:
            current = self.grade.currentText()
            self.grade.blockSignals(True)
            if self.grade.count() == 0:
                grades = self.repository.list_grades()
                self.grade.addItems(grades)
                preferred = current or self.user.get("grado")
                self.grade.setCurrentText(
                    preferred if preferred in grades else (grades[0] if grades else "")
                )
            self.grade.blockSignals(False)
            grade = self.grade.currentText().strip()
            if not grade:
                self.table.setRowCount(0)
                return
            rows = self.repository.list_class_attendance(
                grade, self._date_text(), self.subject.currentText().strip() or "GENERAL",
            )
            self.table.setRowCount(len(rows))
            for row_index, row in enumerate(rows):
                for column_index, name in enumerate(("id", "documento", "matricula", "nombre", "grado")):
                    item = QTableWidgetItem("" if row.get(name) is None else str(row[name]))
                    if column_index == 0:
                        item.setData(Qt.ItemDataRole.UserRole, row)
                    self.table.setItem(row_index, column_index, item)
                status = QComboBox()
                status.addItems(self.STATUS_VALUES)
                status.setCurrentText(row["estado_asistencia"])
                self._apply_status_style(status)
                status.currentTextChanged.connect(lambda _text, cb=status: self._apply_status_style(cb))
                self.table.setCellWidget(row_index, 5, status)
        except Exception as error:
            self.grade.blockSignals(False)
            show_error(self, error)

    def mark_all_present(self) -> None:
        for row_index in range(self.table.rowCount()):
            status = self.table.cellWidget(row_index, 5)
            if status:
                status.setCurrentText("PRESENTE")
                self._apply_status_style(status)

    def save(self) -> None:
        grade = self.grade.currentText().strip()
        if not grade:
            QMessageBox.warning(self, "Asistencia", "Selecciona un grado.")
            return
        attendance = {}
        for row_index in range(self.table.rowCount()):
            student = self.table.item(row_index, 0).data(Qt.ItemDataRole.UserRole)
            status = self.table.cellWidget(row_index, 5).currentText()
            if status != "SIN_REGISTRO":
                attendance[int(student["id"])] = status
        if not attendance:
            QMessageBox.information(self, "Asistencia", "Selecciona al menos un estado para guardar.")
            return
        observations, accepted = QInputDialog.getText(
            self, "Guardar planilla", "Observación general (opcional):"
        )
        if not accepted:
            return
        try:
            self.repository.save_attendance_batch(
                int(self.user["id"]), grade, self._date_text(),
                self.subject.currentText().strip() or "GENERAL",
                attendance, observations.strip(),
            )
        except Exception as error:
            show_error(self, error)
            return
        self.refresh()
        QMessageBox.information(self, "Asistencia", f"Se guardaron {len(attendance)} registros.")

    def scan(self) -> None:
        if self.continuous_scan_active:
            return
        grade = self.grade.currentText().strip()
        if not grade:
            QMessageBox.warning(self, "Asistencia", "Selecciona primero un grado.")
            return
        try:
            self.window().biometric.start_attendance_scan(
                int(self.user["id"]), grade, self._date_text(),
                self.subject.currentText().strip() or "GENERAL",
            )
            self.continuous_scan_active = True
            self.scan_button.setEnabled(False)
            self.reader_off_button.setEnabled(True)
            self.biometric_message.setText(
                f"Lector activo para {grade}. Coloca las huellas; la asistencia se guardará automáticamente."
            )
        except Exception as error:
            show_error(self, error)

    def stop_scan(self) -> None:
        try:
            message = self.window().biometric.turn_off()
            self.continuous_scan_active = False
            self.scan_button.setEnabled(True)
            self.biometric_message.setText(message)
        except Exception as error:
            show_error(self, error)

    def handle_status(self, status: dict) -> None:
        self.biometric_message.setText(status.get("mensaje", ""))
        if status.get("status") in {"lector_apagado", "error", "cancelado"}:
            self.continuous_scan_active = False
            self.scan_button.setEnabled(True)
        if status.get("asistencia_estado"):
            if status["asistencia_estado"] == "registrada":
                self.refresh()
