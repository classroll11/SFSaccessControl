"""Ventana principal y contenedor de pestañas para SFS Access."""
from pathlib import Path

from PySide6.QtCore import QEasingCurve, QPropertyAnimation, QTimer, Qt
from PySide6.QtWidgets import (
    QFrame,
    QGraphicsOpacityEffect,
    QHBoxLayout,
    QLabel,
    QMainWindow,
    QMessageBox,
    QPushButton,
    QTabWidget,
    QVBoxLayout,
    QWidget,
)

from ..biometric import BiometricBridge
from ..repository import Repository
from ..styles import MANAGERS
from .common import show_error
from .dialogs import EnrollmentDialog
from .pages import (
    AbsencesPage,
    AccessPage,
    AttendancePage,
    DashboardPage,
    FingerprintsPage,
    ReportsPage,
    StudentsPage,
    UsersPage,
)


class MainWindow(QMainWindow):
    def __init__(self, root: Path, repository: Repository, user: dict):
        super().__init__()
        self.user = user
        self.repository = repository
        self.biometric = BiometricBridge(root, repository)
        self._last_status_marker: tuple[str, str, str] | None = None
        self._enrollment_dialog: EnrollmentDialog | None = None
        self._unknown_fingerprint_dialog: QMessageBox | None = None
        self._tab_animation: QPropertyAnimation | None = None

        self.setWindowTitle(f"SFS Access - {user['nombre']}")
        self.resize(1200, 780)
        self.setMinimumSize(900, 600)

        tabs = QTabWidget()
        tabs.setTabPosition(QTabWidget.TabPosition.North)
        tabs.setUsesScrollButtons(True)
        tabs.addTab(DashboardPage(repository), "Resumen")

        if user["rol"] in MANAGERS:
            tabs.addTab(StudentsPage(repository), "Estudiantes")
            tabs.addTab(UsersPage(repository, user), "Usuarios")
            tabs.addTab(AbsencesPage(repository), "Faltas")
            tabs.addTab(ReportsPage(repository), "Reportes")
        if user["rol"] in MANAGERS | {"CELADOR"}:
            tabs.addTab(FingerprintsPage(repository), "Huellas")
        if user["rol"] in MANAGERS | {"DOCENTE"}:
            tabs.addTab(AttendancePage(repository, user), "Asistencia")
        if user["rol"] in MANAGERS | {"CELADOR"}:
            tabs.addTab(AccessPage(repository), "Control de acceso")

        container = QWidget()
        outer_layout = QVBoxLayout(container)
        outer_layout.setContentsMargins(18, 16, 18, 18)
        outer_layout.setSpacing(14)

        top_bar = QFrame()
        top_bar.setObjectName("topBar")
        top_layout = QHBoxLayout(top_bar)
        top_layout.setContentsMargins(20, 13, 20, 13)

        brand = QVBoxLayout()
        title = QLabel("SFS ACCESS")
        title.setObjectName("brandTitle")
        subtitle = QLabel("Control escolar · asistencia · acceso seguro")
        subtitle.setObjectName("brandSubtitle")
        brand.addWidget(title)
        brand.addWidget(subtitle)
        top_layout.addLayout(brand)
        top_layout.addStretch(1)

        identity = QVBoxLayout()
        name = QLabel(user["nombre"])
        name.setStyleSheet("color: white; font-weight: 600;")
        role = QLabel(user["rol"].replace("_", " ").title())
        role.setObjectName("brandSubtitle")
        identity.addWidget(name, alignment=Qt.AlignmentFlag.AlignRight)
        identity.addWidget(role, alignment=Qt.AlignmentFlag.AlignRight)

        reader_controls = QVBoxLayout()
        reader_buttons = QHBoxLayout()
        self.reader_on_button = QPushButton("Prender huellero")
        self.reader_on_button.setObjectName("primaryButton")
        self.reader_on_button.setToolTip(
            "Inicia lectura continua y asistencia automática en la pestaña Asistencia."
        )
        self.reader_off_button = QPushButton("Apagar")
        reader_buttons.addWidget(self.reader_on_button)
        reader_buttons.addWidget(self.reader_off_button)
        reader_controls.addLayout(reader_buttons)

        self.reader_status = QLabel("Lector SecuGen listo")
        self.reader_status.setStyleSheet("color: #c6d4e6; font-size: 9pt;")
        self.reader_status.setAlignment(Qt.AlignmentFlag.AlignRight)
        reader_controls.addWidget(self.reader_status)

        self.reader_on_button.clicked.connect(self._start_reader_from_header)
        self.reader_off_button.clicked.connect(self._stop_reader_from_header)

        top_layout.addLayout(reader_controls)
        top_layout.addLayout(identity)

        outer_layout.addWidget(top_bar)
        outer_layout.addWidget(tabs, 1)
        self.setCentralWidget(container)

        self.tabs = tabs
        for index in range(tabs.count()):
            tab_page = tabs.widget(index)
            if tab_page:
                tab_page.setAttribute(Qt.WidgetAttribute.WA_StyledBackground, True)
                tab_page.setAutoFillBackground(True)

        self.tabs.currentChanged.connect(self._animate_tab_content)
        self.timer = QTimer(self)
        self.timer.timeout.connect(self._poll)
        self.timer.start(700)

    def _animate_tab_content(self, index: int) -> None:
        page = self.tabs.widget(index)
        if page is None:
            return
        page.setAttribute(Qt.WidgetAttribute.WA_StyledBackground, True)
        page.setAutoFillBackground(True)
        effect = QGraphicsOpacityEffect(page)
        page.setGraphicsEffect(effect)
        animation = QPropertyAnimation(effect, b"opacity", self)
        animation.setDuration(160)
        animation.setStartValue(0.4)
        animation.setEndValue(1.0)
        animation.setEasingCurve(QEasingCurve.Type.OutCubic)
        animation.finished.connect(lambda: page.setGraphicsEffect(None))
        self._tab_animation = animation
        animation.start()

    def _poll(self) -> None:
        try:
            status = self.biometric.poll()
        except Exception as error:
            if self._enrollment_dialog and not self._enrollment_dialog.is_finished:
                self._enrollment_dialog.show_failure(str(error))
            self.reader_status.setText("Lector SecuGen: error")
            for index in range(self.tabs.count()):
                page = self.tabs.widget(index)
                if isinstance(page, AccessPage):
                    page.reader_status.setText("Estado del lector: error")
                    page.result.setText(str(error))
                elif isinstance(page, AttendancePage):
                    page.biometric_message.setText(f"Error biométrico: {error}")
            return
        if not status:
            return
        self._update_enrollment_dialog(status)

        if self.biometric.is_continuous_active():
            self.reader_status.setText("Lector SecuGen: Activo (Lectura continua)")
            self.reader_status.setStyleSheet("color: #4ade80; font-size: 9pt; font-weight: 600;")
            self.reader_on_button.setEnabled(False)
            self.reader_off_button.setEnabled(True)
        elif status.get("status") in {"lector_apagado", "cancelado"}:
            self.reader_status.setText("Lector SecuGen: Apagado")
            self.reader_status.setStyleSheet("color: #94a3b8; font-size: 9pt;")
            self.reader_on_button.setEnabled(True)
            self.reader_off_button.setEnabled(False)
        else:
            self.reader_status.setText(f"Lector SecuGen: {status.get('status', 'activo')}")

        marker = (
            str(status.get("status", "")),
            str(status.get("usuario_id", "")),
            str(status.get("mensaje", "")),
        )
        if marker == self._last_status_marker:
            return
        self._last_status_marker = marker

        if status.get("asistencia_estado") == "no_registrada":
            self._show_unknown_fingerprint()
        for index in range(self.tabs.count()):
            page = self.tabs.widget(index)
            if isinstance(page, AccessPage):
                page.handle_status(status)
            elif isinstance(page, AttendancePage):
                page.handle_status(status)
            elif isinstance(page, DashboardPage):
                if status.get("status") in {"identificado", "no_registrado"}:
                    page.refresh()

    def _show_unknown_fingerprint(self) -> None:
        if self._unknown_fingerprint_dialog and self._unknown_fingerprint_dialog.isVisible():
            return
        dialog = QMessageBox(self)
        dialog.setWindowTitle("Huella no registrada")
        dialog.setIcon(QMessageBox.Icon.Warning)
        dialog.setText("Esta huella no está registrada.")
        dialog.setInformativeText(
            "No se marcó asistencia. Puedes cerrar este mensaje; el huellero seguirá "
            "encendido y continuará leyendo huellas."
        )
        dialog.setStandardButtons(QMessageBox.StandardButton.Close)
        dialog.setModal(False)
        dialog.finished.connect(
            lambda _result: setattr(self, "_unknown_fingerprint_dialog", None)
        )
        self._unknown_fingerprint_dialog = dialog
        dialog.open()

    def _start_reader_from_header(self) -> None:
        current_page = self.tabs.currentWidget()
        if isinstance(current_page, AttendancePage):
            current_page.scan()
            return
        if isinstance(current_page, AccessPage):
            current_page.scan()
            return

        attendance_page = next(
            (
                self.tabs.widget(index)
                for index in range(self.tabs.count())
                if isinstance(self.tabs.widget(index), AttendancePage)
            ),
            None,
        )
        if attendance_page is not None:
            self.tabs.setCurrentWidget(attendance_page)
            attendance_page.scan()
            return

        access_page = next(
            (
                self.tabs.widget(index)
                for index in range(self.tabs.count())
                if isinstance(self.tabs.widget(index), AccessPage)
            ),
            None,
        )
        if access_page is not None:
            self.tabs.setCurrentWidget(access_page)
            access_page.scan()
            return

        try:
            self.biometric.turn_on()
            self.reader_status.setText("Lector SecuGen encendido.")
        except Exception as error:
            show_error(self, error)

    def _stop_reader_from_header(self) -> None:
        try:
            message = self.biometric.turn_off()
            self.reader_status.setText("Lector SecuGen: Apagado")
            self.reader_status.setStyleSheet("color: #94a3b8; font-size: 9pt;")
            self.reader_on_button.setEnabled(True)
            self.reader_off_button.setEnabled(False)
            for index in range(self.tabs.count()):
                page = self.tabs.widget(index)
                if isinstance(page, AttendancePage):
                    page.stop_scan()
                elif isinstance(page, AccessPage):
                    page.result.setText(message)
        except Exception as error:
            show_error(self, error)

    def _update_enrollment_dialog(self, status: dict) -> None:
        dialog = self._enrollment_dialog
        if not dialog or dialog.is_finished:
            return
        state = str(status.get("status", ""))
        message = str(status.get("mensaje", ""))
        if state == "enrolado":
            dialog.show_success(message)
            for index in range(self.tabs.count()):
                page = self.tabs.widget(index)
                if isinstance(page, FingerprintsPage):
                    page.refresh()
                    page.help_text.setText(message)
                elif isinstance(page, (StudentsPage, UsersPage)):
                    page.refresh()
        elif state in {"error"}:
            dialog.show_failure(message)
        elif state in {"cancelado", "lector_apagado"}:
            dialog.show_cancelled(message)
        else:
            dialog.update_progress(message)

    def _cancel_enrollment(self) -> None:
        try:
            self.biometric.cancel()
        except Exception as error:
            if self._enrollment_dialog:
                self._enrollment_dialog.show_failure(str(error))

    def start_enrollment(
        self, user_id: int, slot: int, finger: str, person_name: str = "la persona seleccionada",
    ) -> None:
        try:
            self.biometric.start_enrollment(user_id, slot, finger)
            dialog = EnrollmentDialog(
                person_name,
                finger,
                self._cancel_enrollment,
            )
            self._enrollment_dialog = dialog
            dialog.finished.connect(lambda _result: setattr(self, "_enrollment_dialog", None))
            dialog.show()
        except Exception as error:
            show_error(self, error)
