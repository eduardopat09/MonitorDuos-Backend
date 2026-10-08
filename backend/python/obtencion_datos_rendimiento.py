import psutil
import json
import sys

def get_metrics():
    try:
        # El intervalo de 1 segundo en CPU asegura una lectura precisa
        cpu = psutil.cpu_percent(interval=1)
        ram = psutil.virtual_memory().percent
        disk = psutil.disk_usage('/').percent

        data = {
            "status": "success",
            "cpu_uso": cpu,
            "ram_uso": ram,
            "disco_uso": disk
        }
        print(json.dumps(data))
    except Exception as e:
        print(json.dumps({"status": "error", "message": str(e)}))
        sys.exit(1)

if __name__ == "__main__":
    get_metrics()