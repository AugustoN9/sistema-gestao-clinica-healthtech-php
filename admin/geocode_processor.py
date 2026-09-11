import sys
import json
from geopy.geocoders import Nominatim
from geopy.exc import GeocoderTimedOut

def geocode_address(address):
    """
    Busca as coordenadas (lat/lng) para um endereço usando o Nominatim (OSM).
    """
    # É obrigatório fornecer um agente de usuário (user_agent)
    geolocator = Nominatim(user_agent="clinica_healthtech_geocoder")
    
    try:
        # Adicionamos "Brasil" ao endereço para ajudar a precisão no contexto local
        full_query = f"{address}, Brasil"
        
        # Faz a requisição de geocodificação com um timeout
        location = geolocator.geocode(full_query, timeout=10)
        
        if location:
            # Retorna latitude e longitude em formato JSON
            return json.dumps({
                "status": "ok",
                "lat": location.latitude,
                "lng": location.longitude
            })
        else:
            return json.dumps({
                "status": "erro", 
                "mensagem": "Endereço não encontrado pelo Nominatim."
            })
            
    except GeocoderTimedOut:
        return json.dumps({
            "status": "erro", 
            "mensagem": "Timeout na requisição de geocodificação."
        })
    except Exception as e:
        return json.dumps({
            "status": "erro", 
            "mensagem": f"Erro inesperado: {str(e)}"
        })

if __name__ == "__main__":
    # O script espera o endereço como primeiro argumento de linha de comando
    if len(sys.argv) > 1:
        address_to_process = sys.argv[1]
        print(geocode_address(address_to_process))
    else:
        # Saída de erro se nenhum argumento for fornecido
        print(json.dumps({
            "status": "erro", 
            "mensagem": "Nenhum endereço fornecido como argumento."
        }))