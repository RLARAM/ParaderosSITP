
import requests

# Endpoint del servicio geográfico de Bogotá.
endpoint = (
    "https://serviciosgis.catastrobogota.gov.co/arcgis/rest/services/"
    "Mapa_Referencia/Mapa_Referencia/MapServer/8/query"
)

# Seleccionamos la localidad que queremos consultar.
localidad = input("Escribe la localidad (ejemplo: Suba): ").strip()

# Parámetros de la consulta.
parametros = {
    "where": f"LOCALIDAD = '{localidad}'",
    "outFields": "NOMBRE,DIRECCION_,LOCALIDAD,LATITUD,LONGITUD",
    "returnGeometry": "false",
    "resultRecordCount": 100,
    "f": "json"
}

try:
    respuesta = requests.get(endpoint, params=parametros, timeout=30)
    print("\nCódigo HTTP:", respuesta.status_code)
    respuesta.raise_for_status()

    datos = respuesta.json()

    if "error" in datos:
        print("Error informado por la API:")
        print(datos["error"])
    else:
        paraderos = datos.get("features", [])
        print("\nParaderos encontrados:", len(paraderos))

        for numero, elemento in enumerate(paraderos, start=1):
            atributos = elemento.get("attributes", {})
            print(f"\nParadero {numero}")
            print("Nombre:", atributos.get("NOMBRE"))
            print("Dirección:", atributos.get("DIRECCION_"))
            print("Localidad:", atributos.get("LOCALIDAD"))
            print("Latitud:", atributos.get("LATITUD"))
            print("Longitud:", atributos.get("LONGITUD"))

        if not paraderos:
            print("La consulta no devolvió registros para esa localidad.")

except requests.exceptions.RequestException as error:
    print("Error de conexión:", error)
