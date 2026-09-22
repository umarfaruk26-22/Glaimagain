import os
import uvicorn
from fastapi import FastAPI, BackgroundTasks, HTTPException, status
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel, EmailStr
from typing import Optional

from config import FASTAPI_PORT, RECIPIENT_EMAIL
from mailer import send_contact_email

app = FastAPI(
    title="GLAIMAGAIN Concierge Mailer API",
    description="High-performance FastAPI microservice for customer inquiry dispatch and luxury email notifications.",
    version="1.0.0",
)

# Enable CORS for local and web requests
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)


class ContactInquiryRequest(BaseModel):
    name: str
    email: EmailStr
    mobile: Optional[str] = ""
    subject: str
    message: str


class NewsletterRequest(BaseModel):
    email: EmailStr


@app.get("/")
def root():
    return {
        "service": "GLAIMAGAIN FastAPI Concierge Suite",
        "status": "online",
        "recipient": RECIPIENT_EMAIL,
    }


@app.get("/api/contact/health")
def health_check():
    return {"status": "healthy", "service": "FastAPI Mailer"}


@app.post("/api/contact/send-inquiry")
def handle_contact_inquiry(inquiry: ContactInquiryRequest, background_tasks: BackgroundTasks):
    """
    Receives contact form inquiry from PHP backend, logs it, and dispatches
    the luxury branded email to umarfaruksuratwala@gmail.com in the background.
    """
    # Dispatch email in background task for sub-millisecond response time
    background_tasks.add_task(
        send_contact_email,
        name=inquiry.name,
        email=inquiry.email,
        mobile=inquiry.mobile or "",
        subject=inquiry.subject,
        message=inquiry.message,
    )

    return {
        "status": "success",
        "message": "Inquiry accepted and queued for instant luxury email delivery.",
        "recipient": RECIPIENT_EMAIL,
        "client_name": inquiry.name,
    }


@app.post("/api/newsletter/subscribe")
def handle_newsletter(sub: NewsletterRequest, background_tasks: BackgroundTasks):
    """Handles VIP newsletter subscription notifications."""
    background_tasks.add_task(
        send_contact_email,
        name="VIP Subscriber",
        email=sub.email,
        mobile="N/A",
        subject="New VIP Circle Newsletter Subscription",
        message=f"User with email {sub.email} has subscribed to the GLAIMAGAIN VIP Circle.",
    )

    return {
        "status": "success",
        "message": "Newsletter subscription dispatched.",
    }


@app.get("/api/contact/test-email")
def test_email_sync():
    """Synchronous test endpoint to immediately verify SMTP credentials and deliver a test email."""
    success = send_contact_email(
        name="Faruk Suratwala (Test)",
        email="umarfaruksuratwala@gmail.com",
        mobile="+91 98765 43210",
        subject="FastAPI Mailer Integration Test",
        message="This is a live test verifying that the GLAIMAGAIN FastAPI microservice is successfully communicating with Google SMTP and dispatching luxury concierge alerts.",
    )
    if success:
        return {"status": "success", "message": f"Test email successfully dispatched to {RECIPIENT_EMAIL}"}
    else:
        raise HTTPException(
            status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
            detail="Failed to send test email. Please check SMTP credentials.",
        )


if __name__ == "__main__":
    uvicorn.run("main:app", host="127.0.0.1", port=FASTAPI_PORT, reload=True)
