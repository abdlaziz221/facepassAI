# FacePass AI — Microservice Reconnaissance Faciale

Microservice **FastAPI** qui gère la reconnaissance faciale pour la plateforme
FacePass AI. Consommé par l'application Laravel (`facepassAI/`) via HTTP.

**Sprint 3 — US-030**

---

## Architecture

```
┌────────────────┐     POST /encode       ┌──────────────────────┐
│                │ ────────────────────▶ │                      │
│  Laravel API   │                        │  face-service        │
│  (port 8000)   │ ◀────────────────────  │  (FastAPI, port 8001)│
│                │     {embedding: [...]} │                      │
└────────────────┘                        └──────────────────────┘
                                                    │
                                                    ▼
                                          ┌──────────────────────┐
                                          │  dlib + face_recognition
                                          │  (modèle pré-entraîné HOG)
                                          └──────────────────────┘
```

## Endpoints

| Route | Méthode | Quoi |
|---|---|---|
| `/health` | GET | Healthcheck (status, version, threshold) |
| `/encode` | POST | Reçoit une image (multipart), retourne embedding 128D |
| `/match` | POST | Compare 2 embeddings, retourne match + distance + confidence |
| `/docs` | GET | Swagger UI auto-généré |
| `/redoc` | GET | Documentation Redoc |

### Exemple : POST /encode

```bash
curl -X POST http://localhost:8001/encode \
     -F "file=@photo.jpg"
```

Réponse (succès) :
```json
{
  "detected": true,
  "embedding": [0.123, -0.45, ..., 0.78],
  "faces_found": 1,
  "message": "OK"
}
```

Réponse (échec, aucun visage) : HTTP 400 + JSON `{"detail": "Aucun visage détecté..."}`

### Exemple : POST /match

```bash
curl -X POST http://localhost:8001/match \
     -H "Content-Type: application/json" \
     -d '{"embedding1": [0.1, ...], "embedding2": [0.12, ...]}'
```

Réponse :
```json
{
  "match": true,
  "distance": 0.342,
  "threshold": 0.6,
  "confidence": 0.658
}
```

---

## Démarrage — Option 1 : Docker (recommandé)

**Prérequis :** Docker Desktop installé.

```bash
cd face-service
docker compose up --build
```

Premier build : ~5-10 minutes (compile dlib). Builds suivants : 10 secondes.

Le service écoute sur `http://localhost:8001`. Test :
```bash
curl http://localhost:8001/health
```

Pour arrêter :
```bash
docker compose down
```

---

## Démarrage — Option 2 : Python local

### Sur Linux / Mac

```bash
cd face-service
python3 -m venv .venv
source .venv/bin/activate
pip install -r requirements.txt
uvicorn main:app --host 0.0.0.0 --port 8001 --reload
```

### Sur Windows ⚠️

L'installation de `dlib` (sous-dépendance de `face_recognition`) demande
des outils de compilation C++. **Recommandation : utiliser Docker** (option 1)
ou WSL2.

Si tu veux quand même tenter en natif Windows :

```bash
cd face-service
python -m venv .venv
.venv\Scripts\activate
pip install --upgrade pip setuptools wheel

# Installer cmake AVANT dlib
pip install cmake

# Installer dlib (long, requiert Visual C++ Build Tools)
pip install dlib

# Le reste
pip install -r requirements.txt

uvicorn main:app --host 0.0.0.0 --port 8001 --reload
```

Si `pip install dlib` échoue, télécharger une wheel pré-compilée :
https://github.com/sachadee/Dlib

---

## Variables d'environnement

| Variable | Défaut | Quoi |
|---|---|---|
| `MATCH_THRESHOLD` | `0.6` | Seuil de distance pour considérer 2 visages identiques. Plus bas = plus strict. |
| `LARAVEL_ORIGIN` | `http://127.0.0.1:8000` | Origin CORS autorisée pour les appels Laravel |

## Tests

```bash
# Avec curl
curl http://localhost:8001/health
curl -X POST http://localhost:8001/encode -F "file=@test.jpg"

# Avec Postman / Insomnia
# Importer la collection depuis http://localhost:8001/docs
```

---

## Performances

- Détection (HOG) : ~100-300 ms par image (CPU)
- Encodage 128D : ~50-100 ms supplémentaires
- Comparaison /match : < 5 ms (calcul vectoriel)

Pour du temps réel intensif → passer au modèle `cnn` (GPU requis, modifie
`face_recognition.face_locations(img_array, model="cnn")` dans `main.py`).

---

## Sécurité (BNF-06)

- Le service **ne stocke pas** les images reçues
- Seul l'embedding (128 floats) est retourné — non-reconstructible visuellement
- CORS restreint au backend Laravel uniquement
- Healthcheck public (anonyme), endpoints face exigent un token côté Laravel

À ajouter au Sprint 7 (Qualité & Sécurité) :
- Auth Bearer token entre Laravel et le microservice
- Rate limiting
- Logging structuré

---

## Structure

```
face-service/
├── main.py              # App FastAPI + endpoints
├── requirements.txt     # Dépendances Python
├── Dockerfile           # Image conteneurisée
├── docker-compose.yml   # Orchestration (1 service pour l'instant)
├── .dockerignore
├── .gitignore
└── README.md            # Ce fichier
```

## Roadmap Sprint 3

- ✅ T1 — Initialiser le microservice (ce dossier)
- ⏳ T2 — Endpoint /encode (implémenté, à tester avec vraies images)
- ⏳ T3 — Endpoint /match (implémenté, à tester)
- ⏳ T4 — Dockeriser (Dockerfile fourni, à valider)
- ⏳ T5 — Service Laravel `FaceRecognitionService` (côté facepassAI/)
- ⏳ T6 — Migration + modèle `Pointage`
- ⏳ T7 — Page de pointage (capture caméra WebRTC)
- ⏳ T8 — Contrôleur `PointageController@store`
