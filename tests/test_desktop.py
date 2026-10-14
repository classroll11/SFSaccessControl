import json
import tempfile
import unittest
from pathlib import Path
from unittest.mock import Mock, patch

import bcrypt

from sfs_desktop.biometric import BiometricBridge
from sfs_desktop.repository import Repository


class FakeCursor:
    def __init__(self, rows):
        self.rows = iter(rows)
        self.rowcount = 1
        self.queries = []

    def execute(self, query, values=()):
        self.queries.append((query, values))

    def fetchone(self):
        return next(self.rows)

    def fetchall(self):
        return list(self.rows)


class FakeConnection:
    def __init__(self, rows):
        self.db_cursor = FakeCursor(rows)

    def cursor(self, dictionary=False):
        return self.db_cursor

    def close(self):
        pass

    def commit(self):
        pass

    def rollback(self):
        pass


class DesktopAuthTests(unittest.TestCase):
    def test_authenticates_php_bcrypt_hash_and_document(self):
        password = b"secret-pass"
        php_hash = b"$2y$" + bcrypt.hashpw(password, bcrypt.gensalt())[4:]
        user = {
            "id": 1,
            "documento": "12345",
            "correo": "person@example.test",
            "nombre": "Person",
            "grado": None,
            "password": php_hash.decode("ascii"),
            "rol": "ADMINISTRADOR",
            "estado": "ACTIVO",
            "debe_cambiar_password": 0,
        }
        connection = FakeConnection([None, user])
        with patch("sfs_desktop.repository.connect", return_value=connection):
            result = Repository().authenticate("12345", password.decode("ascii"))
        self.assertEqual(result["id"], 1)
        self.assertEqual(len(connection.db_cursor.queries), 2)

    def test_rejects_inactive_account(self):
        user = {
            "password": bcrypt.hashpw(b"secret", bcrypt.gensalt()).decode("ascii"),
            "estado": "INACTIVO",
        }
        with patch("sfs_desktop.repository.connect", return_value=FakeConnection([user])):
            self.assertIsNone(Repository().authenticate("test", "secret"))

    def test_user_query_includes_registered_fingerprint_summary(self):
        connection = FakeConnection([])
        with patch("sfs_desktop.repository.connect", return_value=connection):
            self.assertEqual(Repository().list_users(), [])
        query, _ = connection.db_cursor.queries[0]
        self.assertIn("finger_summary", query)
        self.assertIn("GROUP_CONCAT", query)
        self.assertIn("Huella Principal", query)

    def test_student_list_shows_single_fingerprint_limit(self):
        connection = FakeConnection([])
        with patch("sfs_desktop.repository.connect", return_value=connection):
            self.assertEqual(Repository().list_students_page(), [])
        query, _ = connection.db_cursor.queries[0]
        self.assertIn("LEAST(COALESCE(fingers.finger_count, 0), 1)", query)
        self.assertIn("'/1'", query)

    def test_student_fingerprint_is_saved_to_single_slot_and_legacy_mirror(self):
        connection = FakeConnection([("ESTUDIANTE",)])
        with patch("sfs_desktop.repository.connect", return_value=connection):
            Repository().save_fingerprint(5, 4, "Índice derecho", "template")

        queries = connection.db_cursor.queries
        self.assertIn("DELETE FROM huellas_dactilares", queries[1][0])
        self.assertEqual(queries[2][1], (5, 1, "Índice derecho", "template"))
        self.assertIn("UPDATE usuarios SET huella_template", queries[3][0])

    def test_staff_fingerprint_keeps_requested_slot(self):
        connection = FakeConnection([("DOCENTE",)])
        with patch("sfs_desktop.repository.connect", return_value=connection):
            Repository().save_fingerprint(5, 4, "Índice derecho", "template")

        self.assertEqual(len(connection.db_cursor.queries), 2)
        self.assertEqual(connection.db_cursor.queries[1][1], (5, 4, "Índice derecho", "template"))


class BiometricBridgeTests(unittest.TestCase):
    def test_attendance_scan_requeues_identification_without_powering_down(self):
        with tempfile.TemporaryDirectory() as directory:
            bridge = BiometricBridge.__new__(BiometricBridge)
            bridge.api = Path(directory)
            bridge.repository = Mock()
            bridge.repository.fingerprint_candidates.return_value = [{"userId": 1}]
            bridge._last_handled_status = None
            bridge._pending_attendance = None
            bridge._continuous_attendance = None
            bridge._pending_access = False

            bridge._queue = Mock(return_value="first-job")
            result = bridge.start_attendance_scan(10, "10A", "2026-10-13", "MATEMÁTICAS")
            self.assertEqual(result, "first-job")
            self.assertEqual(bridge._pending_attendance, (10, "10A", "2026-10-13", "MATEMÁTICAS"))

            bridge._pending_attendance = None
            bridge._queue = Mock(return_value="next-job")
            bridge._continue_attendance_scan()

            bridge._queue.assert_called_once_with(
                "identify", candidates=[{"userId": 1}],
            )
            self.assertEqual(bridge._pending_attendance, bridge._continuous_attendance)

    def test_unknown_attendance_fingerprint_is_reported_without_stopping_continuous_scan(self):
        bridge = BiometricBridge.__new__(BiometricBridge)
        bridge._continuous_attendance = (10, "10A", "2026-10-13", "MATEMÁTICAS")
        bridge._pending_attendance = bridge._continuous_attendance
        bridge.repository = Mock()
        status = {"status": "no_registrado"}

        bridge._register_class_attendance(status)

        self.assertEqual(status["asistencia_estado"], "no_registrada")
        self.assertIn("Huella no reconocida", status["mensaje"])
        self.assertIsNone(bridge._pending_attendance)
        self.assertIsNotNone(bridge._continuous_attendance)
        bridge.repository.record_attendance.assert_not_called()

    def test_enrollment_status_is_saved_and_reports_completion(self):
        with tempfile.TemporaryDirectory() as directory:
            api = Path(directory)
            bridge = BiometricBridge.__new__(BiometricBridge)
            bridge.api = api
            bridge.repository = Mock()
            bridge._pending_enrollment = (5, 1, "Índice derecho")
            bridge._pending_attendance = None
            bridge._pending_access = False
            bridge._pending_legacy_reader_off = False
            bridge._last_handled_status = None
            (api / "fingerprint_status.json").write_text(
                json.dumps({"status": "enrolado", "identity_hex": "template"}),
                encoding="utf-8",
            )

            status = bridge.poll()

            bridge.repository.save_fingerprint.assert_called_once_with(
                5, 1, "Índice derecho", "template",
            )
            self.assertIsNone(bridge._pending_enrollment)
            self.assertIn("guardada correctamente", status["mensaje"])

    def test_enrollment_save_failure_is_reported_and_clears_pending_operation(self):
        with tempfile.TemporaryDirectory() as directory:
            api = Path(directory)
            bridge = BiometricBridge.__new__(BiometricBridge)
            bridge.api = api
            bridge.repository = Mock()
            bridge.repository.save_fingerprint.side_effect = OSError("database unavailable")
            bridge._pending_enrollment = (5, 1, "Índice derecho")
            bridge._pending_attendance = None
            bridge._pending_access = False
            bridge._pending_legacy_reader_off = False
            bridge._last_handled_status = None
            (api / "fingerprint_status.json").write_text(
                json.dumps({"status": "enrolado", "identity_hex": "template"}),
                encoding="utf-8",
            )

            with self.assertRaisesRegex(RuntimeError, "no se pudo guardar la huella"):
                bridge.poll()
            self.assertIsNone(bridge._pending_enrollment)

    def test_shutdown_queues_reader_off_for_older_idle_agent(self):
        with tempfile.TemporaryDirectory() as directory:
            api = Path(directory)
            bridge = BiometricBridge.__new__(BiometricBridge)
            bridge.api = api
            bridge._agent_is_online = lambda: True
            bridge._agent_supports_priority_off = lambda: False
            bridge._continuous_attendance = (3, "10A", "2026-10-13", "MATEMATICAS")
            bridge._pending_attendance = bridge._continuous_attendance
            bridge._pending_legacy_reader_off = False

            bridge.turn_off()

            job = json.loads((api / "fingerprint_agent_job.json").read_text(encoding="utf-8"))
            self.assertEqual(job["action"], "reader_off")
            self.assertIsNone(bridge._continuous_attendance)
            self.assertIsNone(bridge._pending_attendance)

    def test_shutdown_cancels_active_capture_then_queues_reader_off(self):
        with tempfile.TemporaryDirectory() as directory:
            api = Path(directory)
            bridge = BiometricBridge.__new__(BiometricBridge)
            bridge.api = api
            bridge._agent_is_online = lambda: True
            bridge._agent_supports_priority_off = lambda: False
            bridge._pending_enrollment = (5, 1, "Pulgar derecho")
            bridge._pending_attendance = None
            bridge._pending_access = False
            bridge._last_handled_status = None
            active_path = api / "fingerprint_agent.active"
            active_path.write_text(json.dumps({"job_id": "a" * 32}), encoding="utf-8")

            bridge.turn_off()

            cancel_path = api / f"fingerprint_cancel_{'a' * 32}.flag"
            self.assertTrue(cancel_path.exists())
            self.assertFalse((api / "fingerprint_agent_job.json").exists())
            self.assertIsNone(bridge._pending_enrollment)
            self.assertIsNone(bridge._continuous_attendance)
            active_path.unlink()
            (api / "fingerprint_status.json").write_text(
                json.dumps({"status": "cancelado"}), encoding="utf-8",
            )

            bridge.poll()

            job = json.loads((api / "fingerprint_agent_job.json").read_text(encoding="utf-8"))
            self.assertEqual(job["action"], "reader_off")
            self.assertFalse(bridge._pending_legacy_reader_off)

    def test_turn_off_bypasses_busy_job_queue(self):
        with tempfile.TemporaryDirectory() as directory:
            bridge = BiometricBridge.__new__(BiometricBridge)
            bridge.api = Path(directory)
            bridge._agent_is_online = lambda: True
            bridge._agent_supports_priority_off = lambda: True
            bridge._pending_enrollment = (5, 1, "Pulgar derecho")
            bridge._pending_attendance = (3, "10A", "2026-10-13", "MATEMATICAS")
            bridge._pending_access = True
            bridge._last_handled_status = ("capturando", "")

            message = bridge.turn_off()

            self.assertTrue((bridge.api / "fingerprint_reader_off.request").exists())
            self.assertIn("detendrá cualquier captura", message)
            self.assertIsNone(bridge._pending_enrollment)
            self.assertIsNone(bridge._pending_attendance)
            self.assertFalse(bridge._pending_access)

    def test_queues_agent_job_and_candidate_snapshot_atomically(self):
        with tempfile.TemporaryDirectory() as directory:
            api = Path(directory)
            bridge = BiometricBridge.__new__(BiometricBridge)
            bridge.api = api
            bridge._agent_is_online = lambda: True
            job_id = bridge._queue("enroll", 7, 2, "Índice derecho", [{"userId": 1}])
            job = json.loads((api / "fingerprint_agent_job.json").read_text(encoding="utf-8"))
            self.assertEqual(job["jobId"], job_id)
            self.assertEqual(job["action"], "enroll")
            self.assertEqual(job["userId"], 7)
            self.assertEqual(
                json.loads((api / job["candidatesFile"]).read_text(encoding="utf-8")),
                [{"userId": 1}],
            )
            self.assertEqual(
                json.loads((api / "fingerprint_status.json").read_text(encoding="utf-8"))["status"],
                "iniciando",
            )

    def test_registers_rejected_access_for_inactive_person(self):
        bridge = BiometricBridge.__new__(BiometricBridge)
        bridge.repository = Mock()
        bridge.repository.get_access_user.return_value = {
            "id": 7, "nombre": "Estudiante", "grado": "10A", "rol": "ESTUDIANTE", "estado": "INACTIVO"
        }
        bridge.repository.record_access = Mock()
        bridge._pending_access_event = "ENTRADA"
        status = {"usuario_id": 7, "dedo": "Pulgar"}

        bridge._register_access(status)

        bridge.repository.record_access.assert_called_once_with(
            7, "ENTRADA", False, "SecuGen - ENTRADA - Pulgar - Usuario INACTIVO"
        )
        self.assertIn("Acceso rechazado", status["mensaje"])

    def test_old_agent_identification_does_not_create_an_access_event(self):
        with tempfile.TemporaryDirectory() as directory:
            bridge = BiometricBridge.__new__(BiometricBridge)
            bridge.api = Path(directory)
            bridge.repository = Mock()
            bridge._last_handled_status = None
            bridge._pending_enrollment = None
            bridge._pending_attendance = None
            bridge._pending_legacy_reader_off = False
            bridge._pending_access = False
            (bridge.api / "fingerprint_status.json").write_text(
                json.dumps({"status": "identificado", "usuario_id": 42}),
                encoding="utf-8",
            )

            bridge.poll()

            bridge.repository.record_access.assert_not_called()

    def test_terminal_scan_statuses_do_not_restart_continuous_attendance(self):
        for state in ("error", "cancelado", "lector_apagado"):
            with self.subTest(state=state), tempfile.TemporaryDirectory() as directory:
                bridge = BiometricBridge.__new__(BiometricBridge)
                bridge.api = Path(directory)
                bridge._pending_legacy_reader_off = False
                bridge._pending_enrollment = None
                bridge._pending_attendance = (3, "10A", "2026-10-13", "MATEMATICAS")
                bridge._continuous_attendance = bridge._pending_attendance
                bridge._pending_access = False
                bridge._last_handled_status = None
                bridge._queue = Mock()
                (bridge.api / "fingerprint_status.json").write_text(
                    json.dumps({"status": state}), encoding="utf-8",
                )

                bridge.poll()

                self.assertIsNone(bridge._continuous_attendance)
                self.assertIsNone(bridge._pending_attendance)
                bridge._queue.assert_not_called()

    def test_continuous_attendance_requeues_next_scan(self):
        with tempfile.TemporaryDirectory() as directory:
            bridge = BiometricBridge.__new__(BiometricBridge)
            bridge.api = Path(directory)
            bridge.repository = Mock()
            bridge.repository.fingerprint_candidates.return_value = [{"id": 42, "template": "abc"}]
            bridge._pending_legacy_reader_off = False
            bridge._pending_enrollment = None
            bridge._pending_attendance = (3, "10A", "2026-10-13", "MATEMATICAS")
            bridge._continuous_attendance = bridge._pending_attendance
            bridge._pending_access = False
            bridge._continuous_access = None
            bridge._last_handled_status = None
            bridge._last_scan_record = None
            bridge._queue = Mock()
            bridge._register_class_attendance = Mock()

            (bridge.api / "fingerprint_status.json").write_text(
                json.dumps({"status": "identificado", "usuario_id": 42, "job_id": "job-1"}),
                encoding="utf-8",
            )

            status = bridge.poll()

            self.assertEqual(status["status"], "identificado")
            bridge._register_class_attendance.assert_called_once()
            bridge._queue.assert_called_once_with("identify", candidates=[{"id": 42, "template": "abc"}])
            self.assertIsNotNone(bridge._continuous_attendance)


if __name__ == "__main__":
    unittest.main()
