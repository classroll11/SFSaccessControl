import bcrypt

from .database import connect


class Repository:
    FINGERPRINT_SUMMARY_JOIN = """
        LEFT JOIN (
            SELECT registered.user_id,
                   COUNT(*) AS finger_count,
                   GROUP_CONCAT(
                       CONCAT(registered.slot_numero, ': ', registered.dedo)
                       ORDER BY registered.slot_numero SEPARATOR ', '
                   ) AS finger_names
            FROM (
                SELECT usuario_id AS user_id, slot_numero, dedo
                FROM huellas_dactilares
                UNION ALL
                SELECT u0.id AS user_id, 1 AS slot_numero, 'Huella Principal' AS dedo
                FROM usuarios u0
                WHERE u0.huella_template IS NOT NULL AND u0.huella_template <> ''
                  AND NOT EXISTS (
                      SELECT 1 FROM huellas_dactilares h0
                      WHERE h0.usuario_id=u0.id AND h0.huella_template=u0.huella_template
                  )
            ) registered
            GROUP BY registered.user_id
        ) fingers ON fingers.user_id=u.id
    """

    def authenticate(self, login: str, password: str) -> dict | None:
        connection = connect()
        try:
            cursor = connection.cursor(dictionary=True)
            cursor.execute(
                """SELECT id, documento, matricula, correo, nombre, grado, password,
                          rol, debe_cambiar_password, estado
                   FROM usuarios WHERE correo = %s LIMIT 1""",
                (login.strip().lower(),),
            )
            user = cursor.fetchone()
            if user is None:
                cursor.execute(
                    """SELECT id, documento, matricula, correo, nombre, grado, password,
                              rol, debe_cambiar_password, estado
                       FROM usuarios WHERE documento = %s LIMIT 1""",
                    (login.strip(),),
                )
                user = cursor.fetchone()
            if not user or user["estado"] != "ACTIVO" or not user["password"]:
                return None
            encoded = user["password"].encode("utf-8")
            if encoded.startswith(b"$2y$"):
                encoded = b"$2b$" + encoded[4:]
            try:
                valid = bcrypt.checkpw(password.encode("utf-8"), encoded)
            except ValueError:
                valid = False
            if not valid:
                return None
            user["debe_cambiar_password"] = bool(user["debe_cambiar_password"]) or bcrypt.checkpw(
                b"123456", encoded
            )
            return user
        finally:
            connection.close()

    def change_password(self, user_id: int, password: str) -> None:
        password_hash = bcrypt.hashpw(password.encode("utf-8"), bcrypt.gensalt()).decode("ascii")
        connection = connect()
        try:
            cursor = connection.cursor()
            cursor.execute(
                "UPDATE usuarios SET password = %s, debe_cambiar_password = 0 WHERE id = %s",
                (password_hash, user_id),
            )
            if cursor.rowcount != 1:
                raise RuntimeError("No se pudo actualizar la contraseña del usuario.")
            connection.commit()
        except Exception:
            connection.rollback()
            raise
        finally:
            connection.close()

    def list_users(self, roles: tuple[str, ...] | None = None) -> list[dict]:
        connection = connect()
        try:
            cursor = connection.cursor(dictionary=True)
            query = f"""SELECT u.id, u.documento, u.matricula, u.correo, u.nombre,
                               u.grado, u.rol, u.estado,
                               CONCAT(
                                   CASE WHEN u.rol='ESTUDIANTE'
                                       THEN LEAST(COALESCE(fingers.finger_count, 0), 1)
                                       ELSE COALESCE(fingers.finger_count, 0)
                                   END,
                                   CASE WHEN u.rol='ESTUDIANTE' THEN '/1' ELSE '/6' END
                               ) AS finger_summary,
                               COALESCE(fingers.finger_names, '') AS finger_names
                        FROM usuarios u
                        {self.FINGERPRINT_SUMMARY_JOIN}"""
            values: tuple = ()
            if roles:
                marks = ",".join(["%s"] * len(roles))
                query += f" WHERE u.rol IN ({marks})"
                values = roles
            query += " ORDER BY u.nombre"
            cursor.execute(query, values)
            return cursor.fetchall()
        finally:
            connection.close()

    def save_user(self, values: dict, user_id: int | None = None) -> None:
        connection = connect()
        try:
            cursor = connection.cursor()
            if user_id is None:
                user_values = tuple(
                    values[key] for key in
                    ("documento", "matricula", "correo", "nombre", "grado", "rol", "estado")
                )
                if values["rol"] == "ESTUDIANTE":
                    cursor.execute(
                        """INSERT INTO usuarios
                           (documento, matricula, correo, nombre, grado, rol, estado)
                           VALUES (%s, %s, %s, %s, %s, %s, %s)""",
                        user_values,
                    )
                else:
                    password_hash = bcrypt.hashpw(b"123456", bcrypt.gensalt()).decode("ascii")
                    cursor.execute(
                        """INSERT INTO usuarios
                           (documento, matricula, correo, nombre, grado, rol, estado,
                            password, debe_cambiar_password)
                           VALUES (%s, %s, %s, %s, %s, %s, %s, %s, 1)""",
                        user_values + (password_hash,),
                    )
            else:
                cursor.execute("SELECT rol FROM usuarios WHERE id=%s", (user_id,))
                existing = cursor.fetchone()
                if existing is None:
                    raise RuntimeError("El usuario seleccionado ya no existe.")
                old_role = existing[0]
                user_values = tuple(
                    values[key] for key in
                    ("documento", "matricula", "correo", "nombre", "grado", "rol", "estado")
                )
                if old_role == "ESTUDIANTE" and values["rol"] != "ESTUDIANTE":
                    password_hash = bcrypt.hashpw(b"123456", bcrypt.gensalt()).decode("ascii")
                    cursor.execute(
                        """UPDATE usuarios SET documento=%s, matricula=%s, correo=%s,
                           nombre=%s, grado=%s, rol=%s, estado=%s, password=%s,
                           debe_cambiar_password=1 WHERE id=%s""",
                        user_values + (password_hash, user_id),
                    )
                elif old_role != "ESTUDIANTE" and values["rol"] == "ESTUDIANTE":
                    cursor.execute(
                        """UPDATE usuarios SET documento=%s, matricula=%s, correo=%s,
                           nombre=%s, grado=%s, rol=%s, estado=%s, password=NULL,
                           debe_cambiar_password=0 WHERE id=%s""",
                        user_values + (user_id,),
                    )
                else:
                    cursor.execute(
                        """UPDATE usuarios SET documento=%s, matricula=%s, correo=%s,
                           nombre=%s, grado=%s, rol=%s, estado=%s WHERE id=%s""",
                        user_values + (user_id,),
                    )
            connection.commit()
        except Exception:
            connection.rollback()
            raise
        finally:
            connection.close()

    def delete_user(self, user_id: int) -> None:
        connection = connect()
        try:
            cursor = connection.cursor()
            cursor.execute("DELETE FROM usuarios WHERE id=%s", (user_id,))
            if cursor.rowcount != 1:
                raise RuntimeError("El usuario seleccionado ya no existe.")
            connection.commit()
        except Exception:
            connection.rollback()
            raise
        finally:
            connection.close()

    def reset_password(self, user_id: int) -> None:
        password_hash = bcrypt.hashpw(b"123456", bcrypt.gensalt()).decode("ascii")
        connection = connect()
        try:
            cursor = connection.cursor()
            cursor.execute(
                """UPDATE usuarios SET password=%s, debe_cambiar_password=1 WHERE id=%s""",
                (password_hash, user_id),
            )
            if cursor.rowcount != 1:
                raise RuntimeError("El usuario seleccionado ya no existe.")
            connection.commit()
        except Exception:
            connection.rollback()
            raise
        finally:
            connection.close()

    def list_students(self, grade: str | None = None) -> list[dict]:
        connection = connect()
        try:
            cursor = connection.cursor(dictionary=True)
            query = """SELECT id, documento, matricula, nombre, grado, estado
                       FROM usuarios WHERE rol='ESTUDIANTE'"""
            values: tuple = ()
            if grade:
                query += " AND grado=%s"
                values = (grade,)
            cursor.execute(query + " ORDER BY grado, nombre", values)
            return cursor.fetchall()
        finally:
            connection.close()

    def count_students(self, search: str = "") -> int:
        connection = connect()
        try:
            cursor = connection.cursor()
            query = "SELECT COUNT(*) FROM usuarios WHERE rol='ESTUDIANTE'"
            params: tuple = ()
            if search.strip():
                query += " AND (nombre LIKE %s OR documento LIKE %s OR matricula LIKE %s OR grado LIKE %s)"
                pattern = f"%{search.strip()}%"
                params = (pattern, pattern, pattern, pattern)
            cursor.execute(query, params)
            return int(cursor.fetchone()[0])
        finally:
            connection.close()

    def list_students_page(self, search: str = "", limit: int = 25, offset: int = 0) -> list[dict]:
        if not 1 <= limit <= 200 or offset < 0:
            raise ValueError("Parámetros de paginación no válidos.")
        connection = connect()
        try:
            cursor = connection.cursor(dictionary=True)
            query = f"""SELECT u.id, u.documento, u.matricula, u.nombre, u.grado, u.estado,
                               CONCAT(LEAST(COALESCE(fingers.finger_count, 0), 1), '/1') AS finger_summary,
                               COALESCE(fingers.finger_names, '') AS finger_names
                        FROM usuarios u
                        {self.FINGERPRINT_SUMMARY_JOIN}
                        WHERE u.rol='ESTUDIANTE'"""
            params: tuple = ()
            if search.strip():
                query += """ AND (u.nombre LIKE %s OR u.documento LIKE %s
                              OR u.matricula LIKE %s OR u.grado LIKE %s)"""
                pattern = f"%{search.strip()}%"
                params = (pattern, pattern, pattern, pattern)
            query += " ORDER BY u.nombre, u.id LIMIT %s OFFSET %s"
            cursor.execute(query, params + (limit, offset))
            return cursor.fetchall()
        finally:
            connection.close()

    def save_student(self, values: dict, student_id: int | None = None) -> None:
        document = values["documento"].strip()
        name = values["nombre"].strip().upper()
        enrollment = values.get("matricula") or document
        grade = values["grado"].strip()
        state = values["estado"]
        if not document or not name or not grade:
            raise ValueError("Documento, nombre y grado son obligatorios.")
        if state not in {"ACTIVO", "INACTIVO", "SUSPENDIDO"}:
            raise ValueError("El estado del estudiante no es válido.")
        connection = connect()
        try:
            cursor = connection.cursor()
            if student_id is None:
                cursor.execute(
                    """INSERT INTO usuarios (documento, matricula, nombre, grado, rol, estado)
                       VALUES (%s, %s, %s, %s, 'ESTUDIANTE', %s)""",
                    (document, enrollment, name, grade, state),
                )
            else:
                cursor.execute(
                    """UPDATE usuarios SET documento=%s, matricula=%s, nombre=%s,
                       grado=%s, estado=%s WHERE id=%s AND rol='ESTUDIANTE'""",
                    (document, enrollment, name, grade, state, student_id),
                )
                if cursor.rowcount == 0:
                    cursor.execute(
                        "SELECT id FROM usuarios WHERE id=%s AND rol='ESTUDIANTE'",
                        (student_id,),
                    )
                    if cursor.fetchone() is None:
                        raise RuntimeError("El estudiante seleccionado ya no existe.")
            connection.commit()
        except Exception:
            connection.rollback()
            raise
        finally:
            connection.close()

    def delete_student(self, student_id: int) -> None:
        connection = connect()
        try:
            cursor = connection.cursor()
            cursor.execute(
                "DELETE FROM usuarios WHERE id=%s AND rol='ESTUDIANTE'",
                (student_id,),
            )
            if cursor.rowcount != 1:
                raise RuntimeError("El estudiante seleccionado ya no existe.")
            connection.commit()
        except Exception:
            connection.rollback()
            raise
        finally:
            connection.close()

    def list_grades(self) -> list[str]:
        connection = connect()
        try:
            cursor = connection.cursor()
            cursor.execute(
                """SELECT DISTINCT grado FROM usuarios
                   WHERE rol='ESTUDIANTE' AND grado IS NOT NULL AND grado <> ''
                   ORDER BY grado"""
            )
            return [row[0] for row in cursor.fetchall()]
        finally:
            connection.close()

    def list_class_attendance(self, grade: str, attendance_date: str, subject: str) -> list[dict]:
        connection = connect()
        try:
            cursor = connection.cursor(dictionary=True)
            cursor.execute(
                """SELECT u.id, u.documento, u.matricula, u.nombre, u.grado,
                          COALESCE(a.estado_asistencia, 'SIN_REGISTRO') AS estado_asistencia,
                          a.observaciones
                   FROM usuarios u
                   LEFT JOIN asistencias_clase a ON a.estudiante_id=u.id
                     AND a.fecha=%s AND a.materia=%s
                   WHERE u.rol='ESTUDIANTE' AND u.grado=%s
                   ORDER BY u.nombre""",
                (attendance_date, subject, grade),
            )
            return cursor.fetchall()
        finally:
            connection.close()

    def save_attendance_batch(
        self, teacher_id: int, grade: str, attendance_date: str, subject: str,
        attendance: dict[int, str], observations: str = "",
    ) -> None:
        allowed_statuses = {
            "PRESENTE", "FALTA_INJUSTIFICADA", "FALTA_JUSTIFICADA", "RETARDO"
        }
        if not attendance:
            raise ValueError("No hay cambios de asistencia para guardar.")
        if any(state not in allowed_statuses for state in attendance.values()):
            raise ValueError("El plan de asistencia contiene un estado no válido.")
        connection = connect()
        try:
            cursor = connection.cursor()
            student_ids = tuple(attendance)
            placeholders = ",".join(["%s"] * len(student_ids))
            cursor.execute(
                f"""SELECT COUNT(*) FROM usuarios
                    WHERE id IN ({placeholders}) AND rol='ESTUDIANTE' AND grado=%s""",
                student_ids + (grade,),
            )
            if cursor.fetchone()[0] != len(student_ids):
                raise RuntimeError("La lista contiene estudiantes que no pertenecen al grado elegido.")
            query = """INSERT INTO asistencias_clase
                       (estudiante_id, docente_id, grado, fecha, estado_asistencia, materia, observaciones)
                       VALUES (%s, %s, %s, %s, %s, %s, %s)
                       ON DUPLICATE KEY UPDATE docente_id=VALUES(docente_id), grado=VALUES(grado),
                         estado_asistencia=VALUES(estado_asistencia), observaciones=VALUES(observaciones)"""
            for student_id, state in attendance.items():
                cursor.execute(
                    query,
                    (student_id, teacher_id, grade, attendance_date, state, subject, observations or None),
                )
            connection.commit()
        except Exception:
            connection.rollback()
            raise
        finally:
            connection.close()

    def record_attendance(
        self, student_id: int, teacher_id: int, grade: str, subject: str,
        status: str, observations: str, attendance_date: str,
    ) -> None:
        connection = connect()
        try:
            cursor = connection.cursor()
            cursor.execute(
                """INSERT INTO asistencias_clase
                   (estudiante_id, docente_id, grado, fecha, estado_asistencia, materia, observaciones)
                   VALUES (%s, %s, %s, %s, %s, %s, %s)
                   ON DUPLICATE KEY UPDATE docente_id=VALUES(docente_id), grado=VALUES(grado),
                     estado_asistencia=VALUES(estado_asistencia), observaciones=VALUES(observaciones)""",
                (student_id, teacher_id, grade, attendance_date, status, subject, observations or None),
            )
            connection.commit()
        except Exception:
            connection.rollback()
            raise
        finally:
            connection.close()

    def fingerprint_candidates(self) -> list[dict]:
        connection = connect()
        try:
            cursor = connection.cursor(dictionary=True)
            cursor.execute(
                """SELECT usuario_id AS userId, slot_numero AS slot, dedo AS finger,
                          huella_template AS template, 'huellas' AS source
                   FROM huellas_dactilares WHERE huella_template IS NOT NULL AND huella_template <> ''
                   UNION ALL
                   SELECT u.id AS userId, 1 AS slot, 'Huella Principal' AS finger,
                          u.huella_template AS template, 'usuarios' AS source
                   FROM usuarios u
                   WHERE u.huella_template IS NOT NULL AND u.huella_template <> ''
                     AND NOT EXISTS (
                       SELECT 1 FROM huellas_dactilares h
                       WHERE h.usuario_id=u.id AND h.huella_template=u.huella_template
                     )"""
            )
            return cursor.fetchall()
        finally:
            connection.close()

    def save_fingerprint(self, user_id: int, slot: int, finger: str, template: str) -> None:
        connection = connect()
        try:
            cursor = connection.cursor()
            cursor.execute("SELECT rol FROM usuarios WHERE id=%s", (user_id,))
            user = cursor.fetchone()
            if not user:
                raise RuntimeError("No se encontró la persona a la que se asignará la huella.")
            role = user["rol"] if isinstance(user, dict) else user[0]
            is_student = role == "ESTUDIANTE"
            if is_student:
                slot = 1
                cursor.execute(
                    "DELETE FROM huellas_dactilares WHERE usuario_id=%s AND slot_numero<>1",
                    (user_id,),
                )
            cursor.execute(
                """INSERT INTO huellas_dactilares (usuario_id, slot_numero, dedo, huella_template)
                   VALUES (%s, %s, %s, %s)
                   ON DUPLICATE KEY UPDATE dedo=VALUES(dedo), huella_template=VALUES(huella_template)""",
                (user_id, slot, finger, template),
            )
            if is_student:
                cursor.execute("UPDATE usuarios SET huella_template=%s WHERE id=%s", (template, user_id))
            connection.commit()
        except Exception:
            connection.rollback()
            raise
        finally:
            connection.close()

    def get_access_user(self, user_id: int) -> dict | None:
        connection = connect()
        try:
            cursor = connection.cursor(dictionary=True)
            cursor.execute(
                "SELECT id, nombre, documento, grado, rol, estado FROM usuarios WHERE id=%s",
                (user_id,),
            )
            return cursor.fetchone()
        finally:
            connection.close()

    def find_access_user(self, identifier: str) -> dict | None:
        connection = connect()
        try:
            cursor = connection.cursor(dictionary=True)
            cursor.execute(
                """SELECT id, nombre, documento, matricula, grado, rol, estado
                   FROM usuarios WHERE documento=%s OR matricula=%s LIMIT 1""",
                (identifier.strip(), identifier.strip()),
            )
            return cursor.fetchone()
        finally:
            connection.close()

    def record_access(self, user_id: int | None, event: str, approved: bool, note: str) -> None:
        connection = connect()
        try:
            cursor = connection.cursor()
            cursor.execute(
                """INSERT INTO registros_acceso
                   (usuario_id, tipo_evento, estado_acceso, observaciones, fecha_hora)
                   VALUES (%s, %s, %s, %s, NOW())""",
                (user_id, event, "APROBADO" if approved else "RECHAZADO", note[:255]),
            )
            connection.commit()
        except Exception:
            connection.rollback()
            raise
        finally:
            connection.close()

    def list_access(self, limit: int = 200) -> list[dict]:
        connection = connect()
        try:
            cursor = connection.cursor(dictionary=True)
            cursor.execute(
                """SELECT r.fecha_hora, r.tipo_evento, r.estado_acceso, r.observaciones,
                          u.nombre, u.documento
                   FROM registros_acceso r LEFT JOIN usuarios u ON u.id=r.usuario_id
                   ORDER BY r.fecha_hora DESC LIMIT %s""",
                (limit,),
            )
            return cursor.fetchall()
        finally:
            connection.close()

    def dashboard_metrics(self) -> dict[str, int]:
        connection = connect()
        try:
            cursor = connection.cursor(dictionary=True)
            cursor.execute(
                """SELECT
                     SUM(estado_acceso='APROBADO' AND tipo_evento='ENTRADA') AS entradas,
                     SUM(estado_acceso='APROBADO' AND tipo_evento='SALIDA') AS salidas,
                     SUM(estado_acceso='RECHAZADO') AS rechazados
                   FROM registros_acceso WHERE DATE(fecha_hora)=CURDATE()"""
            )
            stats = cursor.fetchone() or {}
            cursor.execute(
                """SELECT COUNT(*) AS dentro FROM (
                     SELECT r.usuario_id, r.tipo_evento
                     FROM registros_acceso r
                     JOIN (
                       SELECT usuario_id, MAX(id) AS max_id
                       FROM registros_acceso
                       WHERE DATE(fecha_hora)=CURDATE() AND estado_acceso='APROBADO'
                       GROUP BY usuario_id
                     ) latest ON latest.max_id=r.id
                     JOIN usuarios u ON u.id=r.usuario_id
                     WHERE u.rol='ESTUDIANTE' AND r.tipo_evento='ENTRADA'
                   ) active_students"""
            )
            inside = cursor.fetchone()
            return {
                "entradas": int(stats.get("entradas") or 0),
                "salidas": int(stats.get("salidas") or 0),
                "rechazados": int(stats.get("rechazados") or 0),
                "estudiantes_dentro": int((inside or {}).get("dentro") or 0),
            }
        finally:
            connection.close()

    def list_attendance_report(
        self, start_date: str, end_date: str, grade: str | None = None,
    ) -> list[dict]:
        connection = connect()
        try:
            cursor = connection.cursor(dictionary=True)
            query = """SELECT a.fecha, u.documento, u.matricula, u.nombre AS estudiante,
                              a.grado, a.materia, a.estado_asistencia, d.nombre AS docente,
                              a.observaciones
                       FROM asistencias_clase a
                       JOIN usuarios u ON u.id=a.estudiante_id
                       LEFT JOIN usuarios d ON d.id=a.docente_id
                       WHERE a.fecha BETWEEN %s AND %s"""
            params: tuple = (start_date, end_date)
            if grade:
                query += " AND a.grado=%s"
                params += (grade,)
            cursor.execute(query + " ORDER BY a.fecha DESC, a.grado, u.nombre", params)
            return cursor.fetchall()
        finally:
            connection.close()

    def list_access_report(self, start_date: str, end_date: str) -> list[dict]:
        connection = connect()
        try:
            cursor = connection.cursor(dictionary=True)
            cursor.execute(
                """SELECT r.fecha_hora, r.tipo_evento, r.estado_acceso, u.documento,
                          u.nombre, u.grado, r.observaciones
                   FROM registros_acceso r LEFT JOIN usuarios u ON u.id=r.usuario_id
                   WHERE r.fecha_hora >= %s AND r.fecha_hora < DATE_ADD(%s, INTERVAL 1 DAY)
                   ORDER BY r.fecha_hora DESC""",
                (start_date, end_date),
            )
            return cursor.fetchall()
        finally:
            connection.close()

    def list_absences(self, attendance_date: str, grade: str | None = None) -> list[dict]:
        connection = connect()
        try:
            cursor = connection.cursor(dictionary=True)
            query = """SELECT a.id, a.fecha, a.grado, a.materia, a.estado_asistencia,
                              a.observaciones, u.documento, u.nombre AS estudiante,
                              d.nombre AS docente
                       FROM asistencias_clase a
                       JOIN usuarios u ON u.id=a.estudiante_id
                       LEFT JOIN usuarios d ON d.id=a.docente_id
                       WHERE a.fecha=%s
                         AND a.estado_asistencia IN
                           ('FALTA_INJUSTIFICADA', 'FALTA_JUSTIFICADA', 'RETARDO')"""
            params: tuple = (attendance_date,)
            if grade:
                query += " AND a.grado=%s"
                params += (grade,)
            cursor.execute(query + " ORDER BY a.grado, u.nombre", params)
            return cursor.fetchall()
        finally:
            connection.close()

    def justify_absence(self, attendance_id: int, reason: str) -> None:
        connection = connect()
        try:
            cursor = connection.cursor()
            cursor.execute(
                """UPDATE asistencias_clase
                   SET estado_asistencia='FALTA_JUSTIFICADA',
                       observaciones=CONCAT(COALESCE(observaciones, ''), ' [JUSTIFICADA: ', %s, ']')
                   WHERE id=%s""",
                (reason.strip(), attendance_id),
            )
            if cursor.rowcount != 1:
                raise RuntimeError("El registro de ausencia ya no existe.")
            connection.commit()
        except Exception:
            connection.rollback()
            raise
        finally:
            connection.close()
