import os
from pathlib import Path
from dotenv import load_dotenv

# Load .env file
env_path = Path(__file__).resolve().parent / ".env"
load_dotenv(dotenv_path=env_path)

SMTP_HOST = os.getenv("SMTP_HOST", "smtp.gmail.com")
SMTP_PORT = int(os.getenv("SMTP_PORT", 587))
SMTP_USER = os.getenv("SMTP_USER", "umarfaruksuratwala@gmail.com")
SMTP_PASSWORD = os.getenv("SMTP_PASSWORD", "xftvavqrgxiikhwf").replace(" ", "")
RECIPIENT_EMAIL = os.getenv("RECIPIENT_EMAIL", "umarfaruksuratwala@gmail.com")
SENDER_NAME = os.getenv("SENDER_NAME", "GLAIMAGAIN Concierge")
FASTAPI_PORT = int(os.getenv("FASTAPI_PORT", 8001))
