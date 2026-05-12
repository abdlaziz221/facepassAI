"""
Fixtures pytest partagées pour les tests du microservice face-service.
"""

from __future__ import annotations

import io

import pytest
from fastapi.testclient import TestClient
from PIL import Image

from main import app


@pytest.fixture(scope="session")
def client() -> TestClient:
    """Client HTTP de test FastAPI (pas besoin de lancer uvicorn)."""
    return TestClient(app)


@pytest.fixture
def fake_png_bytes() -> bytes:
    """Une image PNG valide mais vide (fond noir 100x100) — aucun visage."""
    img = Image.new("RGB", (100, 100), color=(0, 0, 0))
    buf = io.BytesIO()
    img.save(buf, format="PNG")
    return buf.getvalue()


@pytest.fixture
def fake_jpeg_bytes() -> bytes:
    """Une image JPEG valide mais vide (fond gris 200x200) — aucun visage."""
    img = Image.new("RGB", (200, 200), color=(128, 128, 128))
    buf = io.BytesIO()
    img.save(buf, format="JPEG")
    return buf.getvalue()


@pytest.fixture
def not_an_image_bytes() -> bytes:
    """Du texte brut, surtout pas une image."""
    return b"ce n'est pas une image, juste du texte"


@pytest.fixture
def sample_embedding_128() -> list[float]:
    """Un faux embedding valide (128 floats)."""
    return [0.01 * i for i in range(128)]


@pytest.fixture
def sample_embedding_128_close() -> list[float]:
    """Un embedding proche du précédent (distance ~0)."""
    return [0.01 * i + 0.001 for i in range(128)]


@pytest.fixture
def sample_embedding_128_far() -> list[float]:
    """Un embedding éloigné (distance > 1)."""
    return [1.0 - 0.01 * i for i in range(128)]
