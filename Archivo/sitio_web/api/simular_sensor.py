"""
============================================================================
PROYECTO: SFS ACCESS CONTROL - I.E. JORGE ROBLEDO
ARCHIVO: api/simular_sensor.py
DESCRIPCIÓN: Script de prueba en Python para simular la lectura de un lector
             biométrico físico (ej. Adafruit optical / ZKTeco / Grow R307)
             y enviar los datos capturados a la API REST del sistema SFS.
============================================================================
"""

import urllib.request
import json
import time

# URL del endpoint API en el servidor local XAMPP
API_URL = "http://localhost/SFS%20Access/api/registrar_huella.php"

# Lista de casos de prueba con plantillas de huellas registradas e inválidas
CASOS_PRUEBA = [
    {
        "descripcion": "Estudiante 1: Alejandra Martínez (11°A - ACTIVA)",
        "payload": {
            "huella": "HUELLA_HEX_SAMPLE_001",
            "tipo_evento": "ENTRADA"
        }
    },
    {
        "descripcion": "Estudiante 2: Carlos Rodríguez (10°B - ACTIVO)",
        "payload": {
            "huella": "HUELLA_HEX_SAMPLE_002",
            "tipo_evento": "ENTRADA"
        }
    },
    {
        "descripcion": "Estudiante 3: Manuela Correa (10°A - ACTIVA)",
        "payload": {
            "huella": "HUELLA_HEX_SAMPLE_007",
            "tipo_evento": "ENTRADA"
        }
    },
    {
        "descripcion": "Estudiante 4: Lucía Gómez (11°A - INACTIVA / DEBE RECHAZAR)",
        "payload": {
            "huella": "HUELLA_HEX_SAMPLE_003",
            "tipo_evento": "ENTRADA"
        }
    },
    {
        "descripcion": "Estudiante 5: Mariana Osorio (10°B - SUSPENDIDA / DEBE RECHAZAR)",
        "payload": {
            "huella": "HUELLA_HEX_SAMPLE_009",
            "tipo_evento": "ENTRADA"
        }
    },
    {
        "descripcion": "Persona No Registrada / Intruso (DEBE RECHAZAR)",
        "payload": {
            "huella": "HUELLA_DESCONOCIDA_9999",
            "tipo_evento": "ENTRADA"
        }
    },
    {
        "descripcion": "Docente: Lic. Fernando Arango (DOCENTE / ACTIVO)",
        "payload": {
            "huella": "FINGERPRINT_HASH_DOC_001",
            "tipo_evento": "ENTRADA"
        }
    }
]

def enviar_lectura_biometrica(payload):
    """Envía la información capturada del sensor a la API en PHP usando HTTP POST."""
    data_bytes = json.dumps(payload).encode('utf-8')
    req = urllib.request.Request(
        API_URL,
        data=data_bytes,
        headers={
            'Content-Type': 'application/json',
            'User-Agent': 'Sensor-Biometrico-SFS/1.0'
        },
        method='POST'
    )
    
    try:
        with urllib.request.urlopen(req) as response:
            status_code = response.getcode()
            response_body = response.read().decode('utf-8')
            return status_code, json.loads(response_body)
    except urllib.error.HTTPError as e:
        response_body = e.read().decode('utf-8')
        try:
            parsed = json.loads(response_body)
        except Exception:
            parsed = {"raw": response_body}
        return e.code, parsed
    except urllib.error.URLError as e:
        return 0, {"error": f"No se pudo conectar con el servidor: {e.reason}"}

def main():
    print("=" * 70)
    print(" SFS ACCESS CONTROL - SIMULADOR DE SENSOR BIOMÉTRICO (PYTHON)")
    print(" I.E. JORGE ROBLEDO")
    print(f" Destino API: {API_URL}")
    print("=" * 70)

    for i, caso in enumerate(CASOS_PRUEBA, 1):
        print(f"\n[Test {i}] {caso['descripcion']}...")
        print(f" -> Enviando: {caso['payload']}")
        
        status, respuesta = enviar_lectura_biometrica(caso['payload'])
        
        print(f" <- Código HTTP: {status}")
        print(f" <- Respuesta: {json.dumps(respuesta, indent=2, ensure_ascii=False)}")
        print("-" * 50)
        time.sleep(1) # Pequeña pausa entre lecturas

if __name__ == "__main__":
    main()
