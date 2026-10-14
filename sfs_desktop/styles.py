"""Estilos y constantes de interfaz para SFS Access."""

MANAGERS = {"ADMINISTRADOR", "RECTOR", "COORDINADOR"}

STAFF_ROLES = [
    "ESTUDIANTE", "DOCENTE", "CELADOR", "RECTOR", "COORDINADOR",
    "ADMINISTRADOR", "SENSOR",
]

APP_STYLESHEET = """
QWidget {
    color: #17253c;
    font-family: "Segoe UI";
    font-size: 10pt;
}
QMainWindow, QDialog {
    background: #f3f6fb;
}
QDialog#loginDialog {
    background: #f3f6fb;
}
QFrame#loginHeader {
    background: #14233b;
    border-radius: 16px;
}
QFrame#loginAboutPanel {
    background: #14233b;
    border-radius: 16px;
}
QFrame#loginFormCard, QFrame#loginFeatureCard {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
}
QLabel#loginTitle {
    color: #17253c;
    font-size: 21pt;
    font-weight: 700;
}
QLabel#loginEyebrow {
    color: #3b82c4;
    font-size: 9pt;
    font-weight: 700;
    letter-spacing: 1px;
}
QLabel#loginInfoTitle {
    color: #ffffff;
    font-size: 15pt;
    font-weight: 700;
}
QLabel#loginInfoBody {
    color: #cfdbeb;
    font-size: 10pt;
}
QLabel#loginFeatureTitle {
    color: #203550;
    font-weight: 700;
}
QLabel#loginFeatureBody {
    color: #667892;
}
QLabel#loginFooter {
    color: #71819a;
    font-size: 9pt;
}
QLabel#brandTitle {
    color: #ffffff;
    font-size: 19pt;
    font-weight: 700;
}
QLabel#brandSubtitle, QLabel#mutedText {
    color: #92a4c3;
}
QLabel#pageHint {
    background: #eaf1fb;
    border: 1px solid #d6e2f2;
    border-radius: 10px;
    padding: 12px;
    color: #344761;
}
QFrame#topBar {
    background: #14233b;
    border-radius: 14px;
}
QFrame#statCard, QFrame#contentCard {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
}
QLabel#statValue {
    color: #1262b3;
    font-size: 23pt;
    font-weight: 700;
}
QTabWidget::pane {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    top: -1px;
}
QTabWidget > QWidget, QTabWidget::pane > QWidget {
    background: #ffffff;
}
QTabWidget::tab-bar {
    left: 8px;
}
QTabBar::tab {
    background: transparent;
    color: #52647c;
    padding: 10px 14px;
    margin: 3px 4px;
    border-radius: 8px;
    min-width: 90px;
}
QTabBar::tab:selected {
    background: #233b5f;
    color: #ffffff;
    font-weight: 600;
}
QTabBar::tab:hover:!selected {
    background: #e8eef6;
    color: #1d3555;
}
QPushButton {
    background: #e8eef6;
    color: #1d3555;
    border: 1px solid #d5dfeb;
    border-radius: 7px;
    padding: 8px 12px;
    min-height: 18px;
}
QPushButton:hover {
    background: #dce7f4;
}
QPushButton:pressed {
    background: #cbd9eb;
    padding-top: 9px;
    padding-bottom: 7px;
}
QPushButton#filterButton {
    background: #1769aa;
    color: white;
    border: 0;
    font-weight: 600;
    min-width: 112px;
}
QPushButton#filterButton:hover {
    background: #10588f;
}
QPushButton#clearFilterButton {
    background: #ffffff;
    color: #52647c;
    border: 1px solid #cbd5e1;
}
QPushButton#clearFilterButton:hover {
    background: #edf2f8;
    color: #1d3555;
}
QPushButton#primaryButton {
    background: #1769aa;
    color: white;
    border: 0;
    font-weight: 600;
}
QPushButton#primaryButton:hover {
    background: #10588f;
}
QLineEdit, QComboBox, QDateEdit {
    background: #ffffff;
    border: 1px solid #cbd5e1;
    border-radius: 7px;
    padding: 7px 9px;
    min-height: 20px;
    selection-background-color: #1769aa;
}
QLineEdit:focus, QComboBox:focus, QDateEdit:focus {
    border: 1px solid #3182ce;
}
QComboBox QAbstractItemView {
    background: #ffffff;
    color: #17253c;
    selection-background-color: #dceeff;
    selection-color: #14233b;
    outline: 0;
}
QTableWidget {
    background: #ffffff;
    alternate-background-color: #f5f8fc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    gridline-color: #edf1f6;
    selection-background-color: #dceeff;
    selection-color: #14233b;
}
QHeaderView::section {
    background: #edf2f8;
    color: #344761;
    border: 0;
    padding: 9px;
    font-weight: 600;
}
QFrame#filterBar {
    background: #ffffff;
    border: 1px solid #dfe7f1;
    border-radius: 12px;
}
QLabel#attendanceStatusBanner {
    background: #f8fafc;
    color: #334155;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 7px 12px;
    font-size: 9.5pt;
    font-weight: 500;
}
QTableWidget QComboBox {
    padding: 2px 6px;
    border-radius: 6px;
    min-height: 22px;
}
QMessageBox, QInputDialog {
    background: #f3f6fb;
}
"""
