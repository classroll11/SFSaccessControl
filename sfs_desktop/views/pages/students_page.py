"""Vista de directorio y administración de estudiantes."""
from PySide6.QtCore import QTimer, Qt
from PySide6.QtWidgets import (
    QDialog,
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
from ..dialogs.modals import StudentDialog


class StudentsPage(QWidget):
    PAGE_SIZE = 25

    def __init__(self, repository: Repository):
        super().__init__()
        self.setAttribute(Qt.WidgetAttribute.WA_StyledBackground, True)
        self.setAutoFillBackground(True)
        self.setStyleSheet("background: #ffffff;")
        self.repository = repository
        self.page = 0
        self.total = 0

        layout = QVBoxLayout(self)
        hint = QLabel(
            "Busca y administra estudiantes. Los cambios se guardan en la base de datos compartida."
        )
        hint.setObjectName("pageHint")
        layout.addWidget(hint)

        search_row = QHBoxLayout()
        self.search = QLineEdit()
        self.search.setPlaceholderText("Buscar por nombre, documento, matrícula o grado")
        self.search.setClearButtonEnabled(True)
        add_button = QPushButton("Registrar estudiante")
        add_button.setObjectName("primaryButton")
        search_row.addWidget(self.search, 1)
        search_row.addWidget(add_button)
        layout.addLayout(search_row)

        actions = QHBoxLayout()
        edit_button = QPushButton("Editar")
        remove_button = QPushButton("Eliminar")
        refresh_button = QPushButton("Actualizar")
        for button in (edit_button, remove_button, refresh_button):
            actions.addWidget(button)
        actions.addStretch(1)
        self.page_label = QLabel("Página 1 de 1 · 0 estudiantes")
        actions.addWidget(self.page_label)
        previous_button = QPushButton("‹ Anterior")
        next_button = QPushButton("Siguiente ›")
        actions.addWidget(previous_button)
        actions.addWidget(next_button)
        layout.addLayout(actions)

        self.table = make_table(
            ["ID", "Matrícula", "Nombre", "Grado / grupo", "Estado", "Huellas"]
        )
        self.table.setColumnHidden(0, True)
        header = self.table.horizontalHeader()
        header.setStretchLastSection(False)
        header.setSectionResizeMode(1, QHeaderView.ResizeMode.ResizeToContents)
        header.setSectionResizeMode(2, QHeaderView.ResizeMode.Stretch)  # Nombre se expande
        header.setSectionResizeMode(3, QHeaderView.ResizeMode.ResizeToContents)
        header.setSectionResizeMode(4, QHeaderView.ResizeMode.ResizeToContents)
        header.setSectionResizeMode(5, QHeaderView.ResizeMode.ResizeToContents)
        layout.addWidget(self.table, 1)

        self.search_timer = QTimer(self)
        self.search_timer.setSingleShot(True)
        self.search_timer.setInterval(250)
        self.search_timer.timeout.connect(self._reset_search)
        self.search.textChanged.connect(lambda _text: self.search_timer.start())
        add_button.clicked.connect(self.add_student)
        edit_button.clicked.connect(self.edit_student)
        remove_button.clicked.connect(self.remove_student)
        refresh_button.clicked.connect(self.refresh)
        previous_button.clicked.connect(self.previous_page)
        next_button.clicked.connect(self.next_page)
        self.refresh()

    def _reset_search(self) -> None:
        self.page = 0
        self.refresh()

    def refresh(self) -> None:
        try:
            query = self.search.text().strip()
            self.total = self.repository.count_students(query)
            last_page = max(0, (self.total - 1) // self.PAGE_SIZE)
            self.page = min(self.page, last_page)
            rows = self.repository.list_students_page(
                query, self.PAGE_SIZE, self.page * self.PAGE_SIZE,
            )
            populate_table(
                self.table, rows,
                ["id", "matricula", "nombre", "grado", "estado", "finger_summary"],
            )
            self.page_label.setText(
                f"Página {self.page + 1} de {last_page + 1} · {self.total} estudiantes"
            )
        except Exception as error:
            show_error(self, error)

    def previous_page(self) -> None:
        if self.page > 0:
            self.page -= 1
            self.refresh()

    def next_page(self) -> None:
        if (self.page + 1) * self.PAGE_SIZE < self.total:
            self.page += 1
            self.refresh()

    def add_student(self) -> None:
        dialog = StudentDialog()
        if dialog.exec() != QDialog.DialogCode.Accepted:
            return
        try:
            self.repository.save_student(dialog.values())
        except Exception as error:
            show_error(self, error)
            return
        self.refresh()

    def edit_student(self) -> None:
        student = selected_row(self.table)
        if not student:
            QMessageBox.information(self, "Editar estudiante", "Selecciona un estudiante.")
            return
        dialog = StudentDialog(student)
        if dialog.exec() != QDialog.DialogCode.Accepted:
            return
        try:
            self.repository.save_student(dialog.values(), int(student["id"]))
        except Exception as error:
            show_error(self, error)
            return
        self.refresh()

    def remove_student(self) -> None:
        student = selected_row(self.table)
        if not student:
            QMessageBox.information(self, "Eliminar estudiante", "Selecciona un estudiante.")
            return
        answer = QMessageBox.question(
            self, "Confirmar eliminación",
            f"¿Eliminar a {student['nombre']}? Se eliminarán también sus huellas y asistencias.",
        )
        if answer != QMessageBox.StandardButton.Yes:
            return
        try:
            self.repository.delete_student(int(student["id"]))
        except Exception as error:
            show_error(self, error)
            return
        self.refresh()
