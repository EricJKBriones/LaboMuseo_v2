import os
from pathlib import Path
from typing import List

from fastapi import FastAPI, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel

from langchain_community.document_loaders import PyPDFLoader
from langchain_text_splitters import RecursiveCharacterTextSplitter
from langchain_huggingface import HuggingFaceEmbeddings
from langchain_community.vectorstores import FAISS
from langchain_ollama import ChatOllama
import ollama


class AskRequest(BaseModel):
    question: str


class AskResponse(BaseModel):
    answer: str
    sources: List[str]


def get_pdf_path() -> Path:
    custom = os.getenv("PDF_PATH")
    if custom:
        return Path(custom).resolve()
    return (Path(__file__).resolve().parent.parent / "uploads" / "ksay-say layout fin1 final 6-8-23.pdf").resolve()


def build_vectorstore(pdf_path: Path) -> FAISS:
    loader = PyPDFLoader(str(pdf_path))
    docs = loader.load()

    splitter = RecursiveCharacterTextSplitter(
        chunk_size=800,
        chunk_overlap=120,
        separators=["\n\n", "\n", ". ", " ", ""],
    )
    chunks = splitter.split_documents(docs)

    embeddings = HuggingFaceEmbeddings(model_name="sentence-transformers/all-MiniLM-L6-v2")
    return FAISS.from_documents(chunks, embeddings)


def make_prompt(question: str, context: str) -> str:
    return (
        "You are an offline museum document assistant. "
        "Answer only from the provided document context. "
        "If the answer is not in the context, say you cannot find it in this document.\n\n"
        f"Question: {question}\n\n"
        f"Context:\n{context}\n\n"
        "Answer in concise plain English."
    )


def get_installed_ollama_models() -> List[str]:
    try:
        data = ollama.list()
        models = data.get("models", []) if isinstance(data, dict) else []
        names = []
        for model in models:
            name = model.get("model") or model.get("name")
            if name:
                # Normalize "phi3:latest" -> "phi3"
                names.append(str(name).split(":", 1)[0])
        return names
    except Exception:
        return []


def resolve_model_name() -> str:
    requested = os.getenv("OLLAMA_MODEL", "llama3").strip() or "llama3"
    installed = get_installed_ollama_models()

    if requested in installed:
        return requested
    if "phi3" in installed:
        return "phi3"
    if "llama3" in installed:
        return "llama3"
    if installed:
        return installed[0]

    # Keep requested if no models are installed; error will instruct user to pull one.
    return requested


app = FastAPI(title="LaboMuseo Offline AI", version="1.0.0")

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

PDF_PATH = get_pdf_path()
if not PDF_PATH.exists():
    raise FileNotFoundError(f"PDF not found: {PDF_PATH}")

VECTORSTORE = build_vectorstore(PDF_PATH)
RETRIEVER = VECTORSTORE.as_retriever(search_kwargs={"k": 4})

OLLAMA_MODEL = resolve_model_name()
LLM = ChatOllama(model=OLLAMA_MODEL, temperature=0.1)


@app.get("/health")
def health() -> dict:
    return {
        "ok": True,
        "model": OLLAMA_MODEL,
        "installed_models": get_installed_ollama_models(),
        "pdf": str(PDF_PATH),
    }


@app.post("/ask", response_model=AskResponse)
def ask(req: AskRequest) -> AskResponse:
    question = (req.question or "").strip()
    if not question:
        raise HTTPException(status_code=400, detail="Question is required.")

    docs = RETRIEVER.invoke(question)
    if not docs:
        return AskResponse(
            answer="I cannot find relevant context in this document.",
            sources=[],
        )

    context = "\n\n".join(d.page_content for d in docs)
    prompt = make_prompt(question, context)

    try:
        result = LLM.invoke(prompt)
        answer_text = getattr(result, "content", str(result)).strip()
    except Exception:
        # Graceful fallback: return a concise extractive answer from retrieved text.
        first = " ".join(docs[0].page_content.split())[:360] if docs else ""
        answer_text = (
            "Local LLM is currently unavailable. Showing the most relevant passage from the document: "
            + (first or "No passage available.")
        )

    sources = []
    for d in docs[:3]:
        snippet = " ".join(d.page_content.split())[:220]
        if snippet:
            sources.append(snippet)

    if not answer_text:
        answer_text = "I could not generate an answer from the document context."

    return AskResponse(answer=answer_text, sources=sources)


if __name__ == "__main__":
    import uvicorn

    uvicorn.run("rag_server:app", host="127.0.0.1", port=8008, reload=False)
