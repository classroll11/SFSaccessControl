"""Componentes y funciones comunes para vistas PySide6."""
from PySide6.QtCore import Qt
from PySide6.QtWidgets import (
    QAbstractItemView,
    QHeaderView,
    QMessageBox,
    QTableWidget,
    QTableWidgetItem,
    QWidget,
)


def show_error(parent: QWidget, error: Exception | str) -> None:
    """Muestra un diálogo de error al usuario."""
    QMessageBox.critical(parent, "SFS Access", str(error))


def make_table(headers: list[str]) -> QTableWidget:
    """Crea una tabla con diseño homogéneo y cabeceras configuradas."""
    table = QTableWidget(0, len(headers))
    table.setHorizontalHeaderLabels(headers)
    table.setAlternatingRowColors(True)
    table.setSelectionBehavior(QAbstractItemView.SelectionBehavior.SelectRows)
    table.setSelectionMode(QAbstractItemView.SelectionMode.SingleSelection)
    table.setEditTriggers(QAbstractItemView.EditTrigger.NoEditTriggers)
    table.verticalHeader().setVisible(False)
    table.verticalHeader().setDefaultSectionSize(36)
    header = table.horizontalHeader()
    header.setSectionResizeMode(QHeaderView.ResizeMode.ResizeToContents)
    header.setStretchLastSection(True)
    return table


def populate_table(table: QTableWidget, rows: list[dict], columns: list[str]) -> None:
    """Llena una tabla con filas de datos de tipo dict."""
    table.setRowCount(len(rows))
    for row_index, row in enumerate(rows):
        for column_index, name in enumerate(columns):
            value = row.get(name)
            item = QTableWidgetItem("" if value is None else str(value))
            if column_index == 0:
                item.setData(Qt.ItemDataRole.UserRole, row)
            if row.get("finger_names"):
                item.setToolTip(f"Huellas registradas: {row['finger_names']}")
            elif name == "finger_summary":
                item.setToolTip("Esta persona aún no tiene huellas registradas.")
            table.setItem(row_index, column_index, item)


def selected_row(table: QTableWidget) -> dict | None:
    """Obtiene el diccionario asociado a la fila seleccionada."""
    row = table.currentRow()
    if row < 0 or not table.item(row, 0):
        return None
    return table.item(row, 0).data(Qt.ItemDataRole.UserRole)
