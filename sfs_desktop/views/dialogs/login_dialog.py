"""Diálogo de inicio de sesión institucional."""
from pathlib import Path

from PySide6.QtCore import QEasingCurve, QPropertyAnimation, Qt
from PySide6.QtGui import QColor, QPainter, QPixmap
from PySide6.QtWidgets import (
    QDialog,
    QDialogButtonBox,
    QFormLayout,
    QFrame,
    QHBoxLayout,
    QLabel,
    QLineEdit,
    QMessageBox,
    QPushButton,
    QVBoxLayout,
    QWidget,
)

from ...repository import Repository
from ..common import show_error


class LoginDialog(QDialog):
    def __init__(self, repository: Repository, root: Path | None = None):
        super().__init__()
        self.repository = repository
        self.user: dict | None = None
        self.setWindowTitle("Iniciar sesión - SFS Access")
        self.setObjectName("loginDialog")
        self.setMinimumSize(680, 520)
        self.resize(1160, 720)
        self._show_animation: QPropertyAnimation | None = None

        layout = QVBoxLayout(self)
        layout.setContentsMargins(28, 24, 28, 22)
        layout.setSpacing(18)

        header = QFrame()
        header.setObjectName("loginHeader")
        header_layout = QHBoxLayout(header)
        header_layout.setContentsMargins(22, 14, 24, 14)
        emblem = QLabel()
        emblem.setFixedSize(54, 54)
        emblem.setAlignment(Qt.AlignmentFlag.AlignCenter)
        emblem.setStyleSheet(
            "background: #eaf3fc; border: 2px solid #ffffff; border-radius: 27px;"
        )
        if root:
            logo = QPixmap(str(root / "assets" / "img" / "logo-transparent.png"))
            if logo.isNull():
                logo = QPixmap(str(root / "assets" / "img" / "logo.png"))
            if not logo.isNull():
                logo = logo.scaled(
                    38, 38, Qt.AspectRatioMode.KeepAspectRatio,
                    Qt.TransformationMode.SmoothTransformation,
                )
                colored_logo = QPixmap(logo.size())
                colored_logo.fill(Qt.GlobalColor.transparent)
                painter = QPainter(colored_logo)
                painter.drawPixmap(0, 0, logo)
                painter.setCompositionMode(QPainter.CompositionMode.CompositionMode_SourceIn)
                painter.fillRect(colored_logo.rect(), QColor("#173b63"))
                painter.end()
                emblem.setPixmap(colored_logo)
        header_layout.addWidget(emblem)
        brand = QVBoxLayout()
        brand_name = QLabel("SFS ACCESS")
        brand_name.setObjectName("brandTitle")
        brand_subtitle = QLabel("CONTROL ESCOLAR Y BIOMÉTRICO")
        brand_subtitle.setObjectName("brandSubtitle")
        brand.addWidget(brand_name)
        brand.addWidget(brand_subtitle)
        header_layout.addLayout(brand)
        header_layout.addStretch(1)
        school = QLabel("I.E. Jorge Robledo  ·  Medellín")
        school.setStyleSheet("color: #d6e2f2; font-weight: 600;")
        header_layout.addWidget(school)
        layout.addWidget(header)

        content = QHBoxLayout()
        content.setSpacing(18)

        about_panel = QFrame()
        about_panel.setObjectName("loginAboutPanel")
        about_panel.setMinimumWidth(240)
        about_layout = QVBoxLayout(about_panel)
        about_layout.setContentsMargins(24, 28, 24, 24)
        about_layout.setSpacing(16)
        eyebrow = QLabel("NUESTRA INSTITUCIÓN")
        eyebrow.setStyleSheet("color: #7fc5ff; font-weight: 700; letter-spacing: 1px;")
        about_layout.addWidget(eyebrow)
        about_title = QLabel("Tecnología al servicio de la comunidad educativa")
        about_title.setObjectName("loginInfoTitle")
        about_title.setWordWrap(True)
        about_layout.addWidget(about_title)
        about_text = QLabel(
            "SFS Access acompaña a la I.E. Jorge Robledo en la gestión segura de "
            "asistencia y acceso escolar, conectando estudiantes, docentes y equipo institucional."
        )
        about_text.setObjectName("loginInfoBody")
        about_text.setWordWrap(True)
        about_layout.addWidget(about_text)
        about_layout.addStretch(1)
        about_rule = QFrame()
        about_rule.setFrameShape(QFrame.Shape.HLine)
        about_rule.setStyleSheet("color: #40516b;")
        about_layout.addWidget(about_rule)
        location = QLabel("⌖  Sede Central · Medellín, Colombia")
        location.setObjectName("loginInfoBody")
        location.setWordWrap(True)
        about_layout.addWidget(location)
        content.addWidget(about_panel, 4)

        form_card = QFrame()
        form_card.setObjectName("loginFormCard")
        form_layout = QVBoxLayout(form_card)
        form_layout.setContentsMargins(30, 28, 30, 28)
        form_layout.setSpacing(12)
        icon = QLabel("◈")
        icon.setAlignment(Qt.AlignmentFlag.AlignCenter)
        icon.setStyleSheet(
            "color: #1769aa; background: #eaf3fc; border-radius: 28px; "
            "font-size: 26pt; font-weight: 700;"
        )
        icon.setFixedSize(56, 56)
        form_layout.addWidget(icon, alignment=Qt.AlignmentFlag.AlignHCenter)
        login_eyebrow = QLabel("PORTAL INSTITUCIONAL")
        login_eyebrow.setObjectName("loginEyebrow")
        login_eyebrow.setAlignment(Qt.AlignmentFlag.AlignCenter)
        form_layout.addWidget(login_eyebrow)
        login_title = QLabel("Bienvenido")
        login_title.setObjectName("loginTitle")
        login_title.setAlignment(Qt.AlignmentFlag.AlignCenter)
        form_layout.addWidget(login_title)
        login_description = QLabel("Ingresa tus credenciales para continuar")
        login_description.setObjectName("mutedText")
        login_description.setAlignment(Qt.AlignmentFlag.AlignCenter)
        form_layout.addWidget(login_description)

        form = QFormLayout()
        form.setContentsMargins(0, 10, 0, 0)
        form.setVerticalSpacing(12)
        self.login = QLineEdit()
        self.login.setPlaceholderText("Correo institucional o documento")
        self.login.setClearButtonEnabled(True)
        self.password = QLineEdit()
        self.password.setPlaceholderText("Contraseña")
        self.password.setEchoMode(QLineEdit.EchoMode.Password)
        self.password.returnPressed.connect(self._login)
        form.addRow("Usuario", self.login)
        password_row = QHBoxLayout()
        password_row.setSpacing(6)
        password_row.addWidget(self.password)
        self.password_visibility = QPushButton("Ver")
        self.password_visibility.setCheckable(True)
        self.password_visibility.setFixedWidth(54)
        self.password_visibility.setToolTip("Mostrar contraseña")
        self.password_visibility.setAccessibleName("Mostrar contraseña")
        self.password_visibility.toggled.connect(self._toggle_password_visibility)
        password_row.addWidget(self.password_visibility)
        form.addRow("Contraseña", password_row)
        form_layout.addLayout(form)

        buttons = QDialogButtonBox(QDialogButtonBox.StandardButton.Ok | QDialogButtonBox.StandardButton.Cancel)
        login_button = buttons.button(QDialogButtonBox.StandardButton.Ok)
        login_button.setText("Ingresar al sistema  →")
        login_button.setObjectName("primaryButton")
        login_button.setMinimumHeight(42)
        buttons.button(QDialogButtonBox.StandardButton.Cancel).setText("Salir")
        buttons.accepted.connect(self._login)
        buttons.rejected.connect(self.reject)
        form_layout.addWidget(buttons)
        access_note = QLabel(
            "Acceso institucional restringido. Si necesitas una cuenta, contacta con la administración."
        )
        access_note.setObjectName("loginFooter")
        access_note.setWordWrap(True)
        access_note.setAlignment(Qt.AlignmentFlag.AlignCenter)
        form_layout.addWidget(access_note)
        content.addWidget(form_card, 5)

        features_panel = QVBoxLayout()
        features_panel.setSpacing(12)
        features_heading = QLabel("Una comunidad conectada")
        features_heading.setObjectName("loginTitle")
        features_heading.setWordWrap(True)
        features_panel.addWidget(features_heading)
        feature_items = [
            ("⌁", "Acceso biométrico", "Identificación ágil y segura con SecuGen."),
            ("✓", "Asistencia escolar", "Seguimiento de clases y jornadas en tiempo real."),
            ("↗", "Información centralizada", "Registros disponibles para el equipo autorizado."),
        ]
        for symbol, title, description in feature_items:
            feature_card = QFrame()
            feature_card.setObjectName("loginFeatureCard")
            feature_layout = QHBoxLayout(feature_card)
            feature_layout.setContentsMargins(14, 12, 14, 12)
            feature_icon = QLabel(symbol)
            feature_icon.setAlignment(Qt.AlignmentFlag.AlignCenter)
            feature_icon.setStyleSheet(
                "color: #1769aa; background: #eaf3fc; border-radius: 18px; "
                "font-size: 16pt; font-weight: 700;"
            )
            feature_icon.setFixedSize(38, 38)
            feature_layout.addWidget(feature_icon, alignment=Qt.AlignmentFlag.AlignTop)
            feature_copy = QVBoxLayout()
            feature_title = QLabel(title)
            feature_title.setObjectName("loginFeatureTitle")
            feature_body = QLabel(description)
            feature_body.setObjectName("loginFeatureBody")
            feature_body.setWordWrap(True)
            feature_copy.addWidget(feature_title)
            feature_copy.addWidget(feature_body)
            feature_layout.addLayout(feature_copy, 1)
            features_panel.addWidget(feature_card)
        features_panel.addStretch(1)
        features_widget = QWidget()
        features_widget.setLayout(features_panel)
        content.addWidget(features_widget, 4)
        layout.addLayout(content, 1)

        footer = QLabel("SFS Access  ·  Seguridad, tecnología y acompañamiento escolar")
        footer.setObjectName("loginFooter")
        footer.setAlignment(Qt.AlignmentFlag.AlignCenter)
        layout.addWidget(footer)

    def showEvent(self, event) -> None:
        super().showEvent(event)
        available = self.screen().availableGeometry()
        width = min(1160, max(self.minimumWidth(), available.width() - 40))
        height = min(680, max(self.minimumHeight(), available.height() - 40))
        self.resize(width, height)
        self.move(
            available.x() + (available.width() - width) // 2,
            available.y() + (available.height() - height) // 2,
        )
        self.setWindowOpacity(0.0)
        self._show_animation = QPropertyAnimation(self, b"windowOpacity", self)
        self._show_animation.setDuration(320)
        self._show_animation.setStartValue(0.0)
        self._show_animation.setEndValue(1.0)
        self._show_animation.setEasingCurve(QEasingCurve.Type.OutCubic)
        self._show_animation.start()

    def _toggle_password_visibility(self, visible: bool) -> None:
        self.password.setEchoMode(
            QLineEdit.EchoMode.Normal if visible else QLineEdit.EchoMode.Password
        )
        label = "Ocultar contraseña" if visible else "Mostrar contraseña"
        self.password_visibility.setToolTip(label)
        self.password_visibility.setAccessibleName(label)

    def _login(self) -> None:
        try:
            user = self.repository.authenticate(self.login.text(), self.password.text())
        except Exception as error:
            show_error(self, error)
            return
        if user is None:
            QMessageBox.warning(self, "Acceso denegado", "Credenciales incorrectas o cuenta inactiva.")
            return
        self.user = user
        self.accept()
