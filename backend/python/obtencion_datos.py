import psutil 
import json 

# aqui se captura el uso del cpu 
def metricas ():
    cpu_uso = psutil.cpu_percent(interval=1)         #el intervalo sirve para medir la diferencia de uso, sin el intervalo, el primer valor que devolveria esta accion seria de 0

    # aqui se captura el uso de la ram
    ram = psutil.virtual_memory()
    uso_ram = ram.percent

    # aqui se captura el uso del disco
    disco = psutil.disk_usage('/') # para usar el directorio raiz del sistema
    uso_disco = disco.percent

    # diccionario con clave y valor con los datos recopilados con psutil
    datos = {
         "cpu": cpu_uso,
        "ram": uso_ram,
        "disco": uso_disco
    }

    print (json.dumps(datos))

if __name__ == "__main__":
    metricas()
                                               
