"""
FacePass AI — Microservice de reconnaissance faciale
====================================================

Sprint 3, US-030. Microservice FastAPI consommé par l'application Laravel.

Endpoints :
- GET  /health        → healthcheck (status, version)
- POST /encode        → reçoit une image (multipart), retourne l'embedding (128 floats)
- POST /match         → compare 2 embeddings, retourne {match, distance}

Lancement local (dev) :
    uvicorn main:app --host 0.0.0.0 --port 8001 --reload

Documentation auto :
    http://localhost:8001/docs   (Swagger UI)
    http://localhost:8001/redoc  (Redoc)
"""

from __future__ import annotations

import io
import os
from typing import List

import face_recognition
import numpy as np
from fastapi import FastAPI, File, HTTPException, UploadFile
from fastapi.middleware.cors import CORSMiddleware
from PIL import Image
from pydantic import BaseModel, Field

# ============================================================
# Configuration
# ============================================================

APP_VERSION = "0.1.0"
APP_NAME = "facepass-ai-face-service"

# Seuil de match (distance euclidienne entre embeddings).
# face_recognition : < 0.6 = match probable, plus bas = plus strict.
DEFAULT_THRESHOLD = float(os.getenv("MATCH_THRESHOLD", "0.6"))

# Origine Laravel autorisée pour CORS
LARAVEL_ORIGIN = os.getenv("LARAVEL_ORIGIN", "http://127.0.0.1:8000")

app = FastAPI(
    title="FacePass AI — Face Recognition Service",
    description="Microservice de reconnaissance faciale pour la plateforme FacePass AI (ESP Dakar).",
    version=APP_VERSION,
)

# CORS — autoriser uniquement le backend Laravel local + variantes
app.add_middleware(
    CORSMiddleware,
    allow_origins=[
        LARAVEL_ORIGIN,
        "http://localhost:8000",
        "http://127.0.0.1:8000",
    ],
    allow_credentials=True,
    allow_methods=["GET", "POST"],
    allow_headers=["*"],
)


# ============================================================
# Modèles Pydantic
# ============================================================

class HealthResponse(BaseModel):
    status: str = "ok"
    service: str = APP_NAME
    version: str = APP_VERSION
    threshold_default: float = DEFAULT_THRESHOLD


class EncodeResponse(BaseModel):
    detected: bool = Field(..., description="True si un visage a été détecté")
    embedding: List[float] = Field(
        default_factory=list,
        description="Vecteur 128D représentant le visage (vide si non détecté)",
    )
    faces_found: int = Field(..., description="Nombre total de visages détectés")
    message: str = Field("", description="Message d'erreur si applicable")


class MatchRequest(BaseModel):
    embedding1: List[float] = Field(..., description="Premier vecteur 128D")
    embedding2: List[float] = Field(..., description="Second vecteur 128D (référence)")
    threshold: float | None = Field(
        None,
        description="Seuil de distance (défaut 0.6). Plus bas = plus strict.",
    )


class MatchResponse(BaseModel):
    match: bool = Field(..., description="True si les 2 visages correspondent")
    distance: float = Field(..., description="Distance euclidienne (0 = identique)")
    threshold: float = Field(..., description="Seuil utilisé")
    confidence: float = Field(
        ...,
        description="Score de confiance approx. (1 - distance), entre 0 et 1",
    )


# ============================================================
# Endpoints
# ============================================================

@app.get("/health", response_model=HealthResponse, tags=["status"])
def health() -> HealthResponse:
    """Healthcheck simple. Utilisé par Docker, Laravel ou tout monitoring."""
    return HealthResponse()


@app.post("/encode", response_model=EncodeResponse, tags=["face"])
async def encode(file: UploadFile = File(..., description="Image JPG/PNG du visage")) -> EncodeResponse:
    """
    Reçoit une image, détecte UN visage, retourne son embedding 128D.

    - Erreur 400 si aucun visage détecté
    - Erreur 400 si plusieurs visages (un seul attendu, anti-fraude)
    - Erreur 415 si format d'image invalide
    """
    # 1) Lire & valider l'image
    if not file.content_type or not file.content_type.startswith("image/"):
        raise HTTPException(status_code=415, detail="Le fichier doit être une image (jpeg/png).")

    raw = await file.read()
    if len(raw) == 0:
        raise HTTPException(status_code=400, detail="Image vide.")

    try:
        image = Image.open(io.BytesIO(raw)).convert("RGB")
    except Exception as exc:
        raise HTTPException(status_code=415, detail=f"Image invalide : {exc}")

    img_array = np.array(image)

    # 2) Détecter les visages
    face_locations = face_recognition.face_locations(img_array, model="hog")
    faces_count = len(face_locations)

    if faces_count == 0:
        raise HTTPException(
            status_code=400,
            detail="Aucun visage détecté sur l'image. Veuillez recadrer.",
        )

    if faces_count > 1:
        raise HTTPException(
            status_code=400,
            detail=f"{faces_count} visages détectés. Un seul visage est accepté.",
        )

    # 3) Générer l'embedding
    encodings = face_recognition.face_encodings(img_array, known_face_locations=face_locations)
    if not encodings:
        raise HTTPException(
            status_code=500,
            detail="Visage détecté mais impossible de générer l'embedding.",
        )

    return EncodeResponse(
        detected=True,
        embedding=encodings[0].tolist(),
        faces_found=faces_count,
        message="OK",
    )


@app.post("/match", response_model=MatchResponse, tags=["face"])
def match(req: MatchRequest) -> MatchResponse:
    """
    Compare 2 embeddings et retourne s'ils correspondent.

    Renvoie :
    - match    : bool, True si distance < threshold
    - distance : float, distance euclidienne (0 = identique, 1+ = très différents)
    - threshold: float, seuil utilisé pour la décision
    - confidence : float, 1 - distance (entre 0 et 1)
    """
    if len(req.embedding1) != 128 or len(req.embedding2) != 128:
        raise HTTPException(
            status_code=400,
            detail="Les embeddings doivent avoir une taille de 128 (générés par /encode).",
        )

    e1 = np.array(req.embedding1, dtype=np.float64)
    e2 = np.array(req.embedding2, dtype=np.float64)

    distance = float(np.linalg.norm(e1 - e2))
    threshold = req.threshold if req.threshold is not None else DEFAULT_THRESHOLD
    is_match = distance < threshold
    confidence = max(0.0, 1.0 - distance)

    return MatchResponse(
        match=is_match,
        distance=round(distance, 4),
        threshold=threshold,
        confidence=round(confidence, 4),
    )


# ============================================================
# Point d'entrée pour lancement direct (python main.py)
# ============================================================

if __name__ == "__main__":
    import uvicorn

    uvicorn.run("main:app", host="0.0.0.0", port=8001, reload=True)
