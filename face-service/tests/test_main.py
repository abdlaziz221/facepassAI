"""
Tests unitaires du microservice face-service.

Lancement :
    docker compose run --rm face-service pytest -v
ou (en local avec venv activé) :
    pytest -v
"""

from __future__ import annotations

from unittest.mock import patch

import numpy as np


# ============================================================
# /health
# ============================================================

class TestHealth:
    def test_health_returns_200(self, client):
        r = client.get("/health")
        assert r.status_code == 200

    def test_health_payload_structure(self, client):
        data = client.get("/health").json()
        assert data["status"] == "ok"
        assert data["service"] == "facepass-ai-face-service"
        assert "version" in data
        assert "threshold_default" in data
        assert isinstance(data["threshold_default"], float)


# ============================================================
# /encode — validation des entrées
# ============================================================

class TestEncodeValidation:
    def test_encode_rejects_non_image_content_type(self, client, not_an_image_bytes):
        """Content-Type text/plain → 415."""
        files = {"file": ("oups.txt", not_an_image_bytes, "text/plain")}
        r = client.post("/encode", files=files)
        assert r.status_code == 415
        assert "image" in r.json()["detail"].lower()

    def test_encode_rejects_empty_file(self, client):
        """Image vide → 400."""
        files = {"file": ("vide.jpg", b"", "image/jpeg")}
        r = client.post("/encode", files=files)
        assert r.status_code == 400

    def test_encode_rejects_corrupted_image(self, client):
        """Bytes invalides avec Content-Type image → 415."""
        files = {"file": ("corrompu.jpg", b"\x00\x01\x02\x03 not an image", "image/jpeg")}
        r = client.post("/encode", files=files)
        assert r.status_code == 415

    def test_encode_requires_file_field(self, client):
        """Pas de fichier envoyé → 422 (validation FastAPI)."""
        r = client.post("/encode")
        assert r.status_code == 422


# ============================================================
# /encode — détection de visages (mock de face_recognition)
# ============================================================

class TestEncodeDetection:
    def test_encode_no_face_detected(self, client, fake_png_bytes):
        """0 visage détecté → 400."""
        with patch("main.face_recognition.face_locations", return_value=[]):
            files = {"file": ("noir.png", fake_png_bytes, "image/png")}
            r = client.post("/encode", files=files)
        assert r.status_code == 400
        assert "aucun visage" in r.json()["detail"].lower()

    def test_encode_multiple_faces_rejected(self, client, fake_jpeg_bytes):
        """≥ 2 visages → 400 (anti-fraude, on n'accepte qu'1 seul visage)."""
        # Simule 2 visages détectés
        fake_locations = [(0, 100, 100, 0), (50, 150, 150, 50)]
        with patch("main.face_recognition.face_locations", return_value=fake_locations):
            files = {"file": ("groupe.jpg", fake_jpeg_bytes, "image/jpeg")}
            r = client.post("/encode", files=files)
        assert r.status_code == 400
        assert "un seul" in r.json()["detail"].lower() or "2 visages" in r.json()["detail"].lower()

    def test_encode_single_face_returns_embedding(self, client, fake_jpeg_bytes):
        """1 visage → 200 avec embedding 128D."""
        fake_locations = [(0, 100, 100, 0)]
        fake_encoding = np.array([0.01 * i for i in range(128)])

        with patch("main.face_recognition.face_locations", return_value=fake_locations), \
             patch("main.face_recognition.face_encodings", return_value=[fake_encoding]):
            files = {"file": ("selfie.jpg", fake_jpeg_bytes, "image/jpeg")}
            r = client.post("/encode", files=files)

        assert r.status_code == 200
        data = r.json()
        assert data["detected"] is True
        assert data["faces_found"] == 1
        assert len(data["embedding"]) == 128
        assert all(isinstance(v, float) for v in data["embedding"])

    def test_encode_face_located_but_encoding_fails(self, client, fake_jpeg_bytes):
        """Cas limite : visage détecté mais aucun encoding → 500."""
        fake_locations = [(0, 100, 100, 0)]
        with patch("main.face_recognition.face_locations", return_value=fake_locations), \
             patch("main.face_recognition.face_encodings", return_value=[]):
            files = {"file": ("selfie.jpg", fake_jpeg_bytes, "image/jpeg")}
            r = client.post("/encode", files=files)
        assert r.status_code == 500


# ============================================================
# /match
# ============================================================

class TestMatch:
    def test_match_identical_embeddings(self, client, sample_embedding_128):
        """Deux embeddings identiques → match=True, distance=0."""
        payload = {
            "embedding1": sample_embedding_128,
            "embedding2": sample_embedding_128,
        }
        r = client.post("/match", json=payload)
        assert r.status_code == 200
        data = r.json()
        assert data["match"] is True
        assert data["distance"] == 0.0
        assert data["confidence"] == 1.0

    def test_match_close_embeddings(self, client, sample_embedding_128, sample_embedding_128_close):
        """Embeddings proches → match=True, distance < threshold."""
        payload = {
            "embedding1": sample_embedding_128,
            "embedding2": sample_embedding_128_close,
        }
        r = client.post("/match", json=payload)
        assert r.status_code == 200
        data = r.json()
        assert data["match"] is True
        assert data["distance"] < data["threshold"]

    def test_match_far_embeddings(self, client, sample_embedding_128, sample_embedding_128_far):
        """Embeddings éloignés → match=False, distance > threshold."""
        payload = {
            "embedding1": sample_embedding_128,
            "embedding2": sample_embedding_128_far,
        }
        r = client.post("/match", json=payload)
        assert r.status_code == 200
        data = r.json()
        assert data["match"] is False
        assert data["distance"] > data["threshold"]

    def test_match_custom_threshold(self, client, sample_embedding_128, sample_embedding_128_close):
        """Threshold custom prend le pas sur la valeur par défaut."""
        payload = {
            "embedding1": sample_embedding_128,
            "embedding2": sample_embedding_128_close,
            "threshold": 0.001,  # ultra-strict
        }
        r = client.post("/match", json=payload)
        data = r.json()
        assert data["threshold"] == 0.001
        # Très probable que match soit False maintenant
        assert data["match"] is False

    def test_match_rejects_wrong_size_embedding(self, client):
        """Embedding ≠ 128 → 400."""
        payload = {
            "embedding1": [0.1, 0.2, 0.3],  # taille 3, pas 128
            "embedding2": [0.0] * 128,
        }
        r = client.post("/match", json=payload)
        assert r.status_code == 400
        assert "128" in r.json()["detail"]

    def test_match_rejects_missing_field(self, client, sample_embedding_128):
        """Champ manquant → 422 (validation Pydantic)."""
        payload = {"embedding1": sample_embedding_128}
        r = client.post("/match", json=payload)
        assert r.status_code == 422
