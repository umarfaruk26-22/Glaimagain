import smtplib
import logging
from email.mime.multipart import MIMEMultipart
from email.mime.text import MIMEText
from datetime import datetime
from config import (
    SMTP_HOST,
    SMTP_PORT,
    SMTP_USER,
    SMTP_PASSWORD,
    RECIPIENT_EMAIL,
    SENDER_NAME,
)

logger = logging.getLogger("fastapi_mailer")
logging.basicConfig(level=logging.INFO)


def generate_luxury_html_email(
    name: str,
    email: str,
    mobile: str,
    subject: str,
    message: str,
    timestamp_str: str,
) -> str:
    """Generates a high-end luxury branded HTML email template for GLAIMAGAIN."""
    mobile_display = mobile if mobile else "Not Provided"

    html = f"""<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GLAIMAGAIN Inquiry</title>
  <style>
    body {{
      margin: 0;
      padding: 0;
      background-color: #0d1a14;
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
      color: #2D3748;
    }}
    .email-container {{
      max-width: 620px;
      margin: 30px auto;
      background: #FFFFFF;
      border-radius: 16px;
      overflow: hidden;
      box-shadow: 0 20px 50px rgba(0,0,0,0.3);
      border: 1px solid rgba(185, 144, 54, 0.4);
    }}
    .email-header {{
      background: linear-gradient(135deg, #013C26 0%, #002417 100%);
      padding: 36px 30px;
      text-align: center;
      border-bottom: 3px solid #B99036;
    }}
    .brand-title {{
      font-size: 26px;
      font-weight: 800;
      letter-spacing: 4px;
      color: #FFFFFF;
      margin: 0;
      text-transform: uppercase;
    }}
    .brand-subtitle {{
      font-size: 11px;
      letter-spacing: 2px;
      color: #D4AF37;
      margin-top: 6px;
      text-transform: uppercase;
      font-weight: 600;
    }}
    .badge-inquiry {{
      display: inline-block;
      margin-top: 14px;
      padding: 5px 16px;
      background: rgba(185, 144, 54, 0.2);
      border: 1px solid #B99036;
      border-radius: 20px;
      color: #F3E5AB;
      font-size: 11px;
      font-weight: 700;
      letter-spacing: 1px;
    }}
    .email-body {{
      padding: 32px 30px;
    }}
    .intro-text {{
      font-size: 14px;
      color: #4A5568;
      margin-bottom: 24px;
      line-height: 1.6;
    }}
    .info-table {{
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 24px;
      background: #F8FAFC;
      border-radius: 10px;
      overflow: hidden;
      border: 1px solid #E2E8F0;
    }}
    .info-table td {{
      padding: 12px 16px;
      font-size: 13.5px;
      border-bottom: 1px solid #EDF2F7;
    }}
    .info-table td:first-child {{
      width: 32%;
      font-weight: 700;
      color: #013C26;
      text-transform: uppercase;
      font-size: 11px;
      letter-spacing: 0.8px;
      background: #EDF7F2;
    }}
    .info-table td:last-child {{
      color: #1A202C;
      font-weight: 500;
    }}
    .message-box {{
      background: #FAFAF7;
      border-left: 4px solid #B99036;
      padding: 18px 20px;
      border-radius: 0 10px 10px 0;
      margin-bottom: 28px;
    }}
    .message-label {{
      font-size: 11px;
      font-weight: 700;
      color: #B99036;
      text-transform: uppercase;
      letter-spacing: 1px;
      margin-bottom: 8px;
    }}
    .message-content {{
      font-size: 14.5px;
      color: #2D3748;
      line-height: 1.7;
      white-space: pre-line;
      margin: 0;
    }}
    .action-btn {{
      display: inline-block;
      background: #013C26;
      color: #FFFFFF !important;
      text-decoration: none;
      padding: 12px 26px;
      border-radius: 30px;
      font-size: 13px;
      font-weight: 700;
      letter-spacing: 1px;
      text-transform: uppercase;
      border: 1px solid #B99036;
    }}
    .email-footer {{
      background: #001A10;
      padding: 22px 30px;
      text-align: center;
      border-top: 1px solid rgba(185, 144, 54, 0.2);
    }}
    .footer-text {{
      font-size: 11.5px;
      color: #8C9B93;
      margin: 4px 0;
    }}
    .footer-highlight {{
      color: #D4AF37;
      font-weight: 600;
    }}
  </style>
</head>
<body>
  <div class="email-container">
    <!-- Header -->
    <div class="email-header">
      <h1 class="brand-title">GLAIMAGAIN</h1>
      <div class="brand-subtitle">Fashion Beyond Today &bull; Private Atelier</div>
      <div class="badge-inquiry">&#10022; NEW CLIENT INQUIRY RECEIVED &#10022;</div>
    </div>

    <!-- Body -->
    <div class="email-body">
      <p class="intro-text">
        A new inquiry has been transmitted through the <strong>GLAIMAGAIN Concierge Portal</strong>. Below are the full customer submission details:
      </p>

      <table class="info-table">
        <tr>
          <td>Client Name</td>
          <td><strong>{name}</strong></td>
        </tr>
        <tr>
          <td>Email Address</td>
          <td><a href="mailto:{email}" style="color: #013C26; text-decoration: none; font-weight: 600;">{email}</a></td>
        </tr>
        <tr>
          <td>Contact Number</td>
          <td><a href="tel:{mobile_display}" style="color: #013C26; text-decoration: none;">{mobile_display}</a></td>
        </tr>
        <tr>
          <td>Inquiry Subject</td>
          <td><strong style="color: #B99036;">{subject}</strong></td>
        </tr>
        <tr>
          <td>Transmission Time</td>
          <td>{timestamp_str} (IST)</td>
        </tr>
      </table>

      <div class="message-label">Transmitted Client Message:</div>
      <div class="message-box">
        <p class="message-content">{message}</p>
      </div>

      <div style="text-align: center; margin-top: 30px;">
        <a href="mailto:{email}?subject=Re:%20{subject}%20-%20GLAIMAGAIN%20Concierge" class="action-btn">
          &rarr; Reply Directly To Client
        </a>
      </div>
    </div>

    <!-- Footer -->
    <div class="email-footer">
      <p class="footer-text">This automated notification was dispatched via the <span class="footer-highlight">GLAIMAGAIN FastAPI Concierge Microservice</span>.</p>
      <p class="footer-text">&copy; 2026 GLAIMAGAIN &bull; All Rights Reserved.</p>
    </div>
  </div>
</body>
</html>"""
    return html


def send_contact_email(
    name: str,
    email: str,
    mobile: str = "",
    subject: str = "Client Inquiry",
    message: str = "",
) -> bool:
    """Sends the contact inquiry email to umarfaruksuratwala@gmail.com using Gmail SMTP."""
    try:
        timestamp_str = datetime.now().strftime("%d %B %Y, %I:%M %p")

        msg = MIMEMultipart("alternative")
        msg["Subject"] = f"✦ New Inquiry: {subject} — [{name}]"
        msg["From"] = f"{SENDER_NAME} <{SMTP_USER}>"
        msg["To"] = RECIPIENT_EMAIL
        msg["Reply-To"] = email

        # Plain text fallback
        plain_text = f"""GLAIMAGAIN - New Client Inquiry
-----------------------------------------
Client Name: {name}
Email Address: {email}
Mobile Number: {mobile or 'N/A'}
Subject: {subject}
Date/Time: {timestamp_str}

Message:
{message}
-----------------------------------------
Dispatched by GLAIMAGAIN FastAPI Service
"""

        # HTML Luxury Version
        html_content = generate_luxury_html_email(
            name=name,
            email=email,
            mobile=mobile,
            subject=subject,
            message=message,
            timestamp_str=timestamp_str,
        )

        msg.attach(MIMEText(plain_text, "plain", "utf-8"))
        msg.attach(MIMEText(html_content, "html", "utf-8"))

        logger.info(f"Connecting to SMTP {SMTP_HOST}:{SMTP_PORT} for {RECIPIENT_EMAIL}...")

        with smtplib.SMTP(SMTP_HOST, SMTP_PORT, timeout=15) as server:
            server.ehlo()
            server.starttls()
            server.ehlo()
            server.login(SMTP_USER, SMTP_PASSWORD)
            server.sendmail(SMTP_USER, [RECIPIENT_EMAIL], msg.as_string())

        logger.info(f"Successfully sent contact inquiry email for '{name}' to {RECIPIENT_EMAIL}")
        return True

    except Exception as e:
        logger.error(f"Failed to send email via SMTP: {str(e)}", exc_info=True)
        return False
