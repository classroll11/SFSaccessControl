"""Vista de panel de control y resumen métrico institucional."""
from PySide6.QtCore import Qt
from PySide6.QtWidgets import (
    QFrame,
    QHBoxLayout,
    QLabel,
    QPushButton,
    QVBoxLayout,
    QWidget,
)

from ...repository import Repository
from ..common import show_error


class DashboardPage(QWidget):
    def __init__(self, repository: Repository):
        super().__init__()
        self.setAttribute(Qt.WidgetAttribute.WA_StyledBackground, True)
        self.setAutoFillBackground(True)
        self.setStyleSheet("background: #ffffff;")
        self.repository = repository

        layout = QVBoxLayout(self)
        heading = QLabel("Resumen institucional de hoy")
        heading.setStyleSheet("font-size: 17pt; font-weight: 700; color: #14233b;")
        layout.addWidget(heading)

        subtitle = QLabel("Indicadores actualizados desde la base de datos compartida.")
        subtitle.setObjectName("mutedText")
        layout.addWidget(subtitle)

        cards = QHBoxLayout()
        self.metrics = {}
        for key, label in (
            ("estudiantes_dentro", "Estudiantes actualmente dentro"),
            ("entradas", "Entradas aprobadas"),
            ("salidas", "Salidas aprobadas"),
            ("rechazados", "Accesos rechazados"),
        ):
            card = QFrame()
            card.setObjectName("statCard")
            card_layout = QVBoxLayout(card)
            name = QLabel(label)
            name.setWordWrap(True)
            value = QLabel("—")
            value.setObjectName("statValue")
            card_layout.addWidget(name)
            card_layout.addWidget(value)
            card_layout.addStretch(1)
            cards.addWidget(card, 1)
            self.metrics[key] = value

        layout.addLayout(cards)
        layout.addStretch(1)

        refresh_button = QPushButton("Actualizar resumen")
        refresh_button.setObjectName("primaryButton")
        refresh_button.clicked.connect(self.refresh)
        layout.addWidget(refresh_button, alignment=Qt.AlignmentFlag.AlignRight)
        self.refresh()

    def refresh(self) -> None:
        try:
            metrics = self.repository.dashboard_metrics()
            for key, value in metrics.items():
                self.metrics[key].setText(str(value))
        except Exception as error:
            for value in self.metrics.values():
                value.setText("Error")
            show_error(self, error)
