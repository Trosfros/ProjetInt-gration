import finnhub
import os 
from dotenv import load_dotenv

load_dotenv("../.env")

api_key = os.getenv("FinnhubKey")

finnhub_client = finnhub.Client(api_key=api_key)
symbols = ["AAPL", "GOOGL", "AMZN", "MSFT", "TSLA", "NVDA"]

def get_stock_price(symbol: str):  
    if symbol is not None: 
        try: 
            quote = finnhub_client.quote(symbol)
            return quote  # Return the current price
        except Exception as e:
            print(f"Error fetching stock price for {symbol}: {e}")
            return None
    else :
        print("Symbol is null cannot fetch stock price.")
        return None

print(get_stock_price("AAPL"))  # Example usage