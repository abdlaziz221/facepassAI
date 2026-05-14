# Image Python avec build tools pour dlib + git pour cloner face_recognition_models
FROM python:3.11-slim

# ============================================================
# 1) Dépendances système nécessaires pour dlib + face_recognition
#    + git (pour installer face_recognition_models depuis GitHub)
# ============================================================
RUN apt-get update && apt-get install -y --no-install-recommends \
    build-essential \
    cmake \
    git \
    libboost-all-dev \
    libgtk-3-dev \
    libjpeg-dev \
    libpng-dev \
    libopenblas-dev \
    liblapack-dev \
    gfortran \
    && rm -rf /var/lib/apt/lists/*

# ============================================================
# 2) Setup app
# ============================================================
WORKDIR /app

COPY requirements.txt .

# # Installation des deps Python (étape la plus longue : dlib + clone face_recognition_models)
# RUN pip install --no-cache-dir --upgrade pip setuptools wheel && \
#     pip install --no-cache-dir -r requirements.txt
# Installation des deps Python (étape la plus longue : dlib + clone face_recognition_models)
RUN pip install --no-cache-dir --upgrade pip "setuptools<81" wheel && \
    pip install --no-cache-dir -r requirements.txt

COPY . .

# ============================================================
# 3) Exposition du port + healthcheck + lancement
# ============================================================
EXPOSE 8001

HEALTHCHECK --interval=30s --timeout=5s --start-period=20s --retries=3 \
    CMD python -c "import urllib.request; urllib.request.urlopen('http://localhost:8001/health')" || exit 1

CMD ["uvicorn", "main:app", "--host", "0.0.0.0", "--port", "8001"]
