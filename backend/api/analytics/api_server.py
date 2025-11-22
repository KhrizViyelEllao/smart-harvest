from fastapi import FastAPI, HTTPException
from pydantic import BaseModel
import subprocess, sys, json, os, tempfile

app = FastAPI(title="Smart Care Engine API")

class PredictionPayload(BaseModel):
    N: float
    P: float
    K: float
    ph: float
    city: str | None = None

ENGINE_PATH = os.path.join(os.path.dirname(__file__), "smart_care_engine.py")

@app.post("/predict")
def predict(payload: PredictionPayload):
    if not os.path.exists(ENGINE_PATH):
        raise HTTPException(status_code=500, detail="Engine script missing.")

    proc = subprocess.Popen(
        [sys.executable, ENGINE_PATH],
        stdin=subprocess.PIPE,
        stdout=subprocess.PIPE,
        stderr=subprocess.PIPE,
        text=True,
        cwd=os.path.dirname(ENGINE_PATH),
        env={**os.environ, "PYTHONUNBUFFERED": "1"}
    )
    out, err = proc.communicate(payload.model_dump_json())

    if proc.returncode != 0:
        raise HTTPException(status_code=502, detail=err or "Engine failed.")
    try:
        return json.loads(out)
    except json.JSONDecodeError:
        raise HTTPException(status_code=502, detail="Invalid engine response.")

@app.get("/health")
def health():
    return {"status": "ok"}
