# Use official Python image
FROM python:3.10-slim

# Set working directory inside container
WORKDIR /app

# Install system dependencies to avoid missing dependencies during pip install
RUN apt-get update && apt-get install -y \
    build-essential \
    gcc \
    && rm -rf /var/lib/apt/lists/*

# Copy everything in analytics folder to /app
COPY . /app/

# Install Python dependencies from requirements.txt in analytics folder
RUN pip install --no-cache-dir -r requirements.txt

# Default command to run your engine
CMD ["python", "smart_care_engine.py"]
