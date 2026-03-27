import os
import base64
import re
from pathlib import Path
from typing import List, Optional

from fastapi import FastAPI, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel

from langchain_core.documents import Document
from langchain_text_splitters import RecursiveCharacterTextSplitter
from langchain_huggingface import HuggingFaceEmbeddings
from langchain_community.vectorstores import FAISS
from langchain_ollama import ChatOllama
import ollama
from pypdf import PdfReader

from pdf_to_text import convert_pdf_to_text


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


def get_text_path(pdf_path: Path) -> Path:
    custom = os.getenv("PDF_TEXT_PATH")
    if custom:
        return Path(custom).resolve()
    return pdf_path.with_suffix(".txt")


def ensure_text_source(pdf_path: Path, text_path: Path) -> Path:
    # Auto-generate the text source when missing or when the PDF has changed.
    if (not text_path.exists()) or (pdf_path.stat().st_mtime > text_path.stat().st_mtime):
        return convert_pdf_to_text(pdf_path, text_path)
    return text_path


def load_text_documents(text_path: Path) -> List[Document]:
    content = text_path.read_text(encoding="utf-8", errors="ignore")
    if not content.strip():
        return []

    pattern = re.compile(r"=== PAGE\s+(\d+)\s+===")
    matches = list(pattern.finditer(content))

    docs: List[Document] = []
    if not matches:
        docs.append(Document(page_content=content.strip(), metadata={"source": str(text_path)}))
        return docs

    for i, match in enumerate(matches):
        page = int(match.group(1))
        start = match.end()
        end = matches[i + 1].start() if i + 1 < len(matches) else len(content)
        page_text = content[start:end].strip()
        if not page_text:
            continue
        docs.append(
            Document(
                page_content=page_text,
                metadata={"source": str(text_path), "page": page - 1},
            )
        )

    return docs


def build_vectorstore(text_path: Path) -> FAISS:
    docs = load_text_documents(text_path)
    if not docs:
        raise ValueError(f"No text content found in source file: {text_path}")

    splitter = RecursiveCharacterTextSplitter(
        chunk_size=800,
        chunk_overlap=120,
        separators=["\n\n", "\n", ". ", " ", ""],
    )
    chunks = splitter.split_documents(docs)

    embeddings = HuggingFaceEmbeddings(model_name="sentence-transformers/all-MiniLM-L6-v2")
    return FAISS.from_documents(chunks, embeddings)


def make_prompt(question: str, context: str, visual_context: str) -> str:
    return (
        "You are an offline museum document assistant for LaboMuseo. "
        "Use only the provided TEXT_CONTEXT and VISUAL_CONTEXT from this PDF. "
        "If information is missing, clearly say it is not found in this document.\n\n"
        "Response style requirements:\n"
        "- Give a detailed but readable answer in plain English.\n"
        "- Include concrete names, dates, places, and facts when present.\n"
        "- If useful, organize with short headings and bullet points.\n"
        "- End with a short 'Evidence Used' line.\n\n"
        f"Question: {question}\n\n"
        f"TEXT_CONTEXT:\n{context}\n\n"
        f"VISUAL_CONTEXT:\n{visual_context}\n\n"
        "Answer:"
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


def get_installed_ollama_models_full() -> List[str]:
    try:
        data = ollama.list()
        models = data.get("models", []) if isinstance(data, dict) else []
        names: List[str] = []
        for model in models:
            name = model.get("model") or model.get("name")
            if name:
                names.append(str(name))
        return names
    except Exception:
        return []


def resolve_model_name() -> str:
    requested = os.getenv("OLLAMA_MODEL", "llama3").strip() or "llama3"
    installed_full = get_installed_ollama_models_full()

    if not installed_full:
        # Keep requested if no models are installed; error will instruct user to pull one.
        return requested

    for name in installed_full:
        if name == requested:
            return name

    req_base = requested.split(":", 1)[0]
    for name in installed_full:
        if name.split(":", 1)[0] == req_base:
            return name

    for preferred_base in ("phi3", "llama3"):
        for name in installed_full:
            if name.split(":", 1)[0] == preferred_base:
                return name

    return installed_full[0]


def invoke_with_ollama_fallback(prompt: str) -> str:
    primary_error = None

    try:
        result = LLM.invoke(prompt)
        text = getattr(result, "content", str(result)).strip()
        if text:
            return text
    except Exception as exc:
        primary_error = exc

    try:
        alt = ollama.chat(
            model=OLLAMA_MODEL,
            messages=[{"role": "user", "content": prompt}],
            options={"temperature": 0.1},
        )
        text = ((alt or {}).get("message") or {}).get("content", "").strip()
        if text:
            return text
    except Exception as exc:
        detail = f"Primary error: {primary_error}; Fallback error: {exc}"
        raise RuntimeError(detail) from exc

    if primary_error is not None:
        raise RuntimeError(f"Primary LLM returned empty response. Last error: {primary_error}")
    raise RuntimeError("LLM returned an empty response.")


def resolve_vision_model_name() -> Optional[str]:
    requested = os.getenv("OLLAMA_VISION_MODEL", "llava").strip() or "llava"
    installed_full = get_installed_ollama_models_full()

    if not installed_full:
        return None

    for name in installed_full:
        if name == requested or name.split(":", 1)[0] == requested:
            return name

    for preferred in ("llava", "bakllava", "llama3.2-vision", "moondream"):
        for name in installed_full:
            if name.split(":", 1)[0] == preferred:
                return name

    return None


def extract_pdf_images(pdf_path: Path, max_images: int = 4) -> List[dict]:
    out: List[dict] = []
    try:
        reader = PdfReader(str(pdf_path))
    except Exception:
        return out

    for page_idx, page in enumerate(reader.pages, start=1):
        try:
            images = list(getattr(page, "images", []) or [])
        except Exception:
            images = []

        for image in images:
            if len(out) >= max_images:
                return out

            try:
                raw = image.data
                if not raw:
                    continue
                b64 = base64.b64encode(raw).decode("ascii")
                out.append(
                    {
                        "page": page_idx,
                        "name": getattr(image, "name", "image"),
                        "base64": b64,
                    }
                )
            except Exception:
                continue

    return out


def build_visual_context(question: str, pdf_path: Path, vision_model: Optional[str]) -> str:
    if not vision_model:
        return "No supported Ollama vision model installed; visual analysis unavailable."

    images = extract_pdf_images(pdf_path, max_images=4)
    if not images:
        return "No extractable embedded images were found in this PDF."

    notes: List[str] = []
    for idx, item in enumerate(images, start=1):
        try:
            prompt = (
                "You are inspecting an image extracted from a museum PDF. "
                "Describe important visual details, visible labels/text, people/objects, and historical cues. "
                "Keep it factual and brief (2-4 sentences). "
                f"User question to prioritize: {question}"
            )
            resp = ollama.chat(
                model=vision_model,
                messages=[
                    {
                        "role": "user",
                        "content": prompt,
                        "images": [item["base64"]],
                    }
                ],
            )
            msg = ((resp or {}).get("message") or {}).get("content", "").strip()
            if msg:
                notes.append(f"Image {idx} (page {item['page']}): {msg}")
        except Exception as exc:
            notes.append(f"Image {idx} (page {item['page']}): Visual analysis failed ({exc}).")

    return "\n".join(notes) if notes else "Visual analysis produced no usable details."


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

TEXT_PATH = get_text_path(PDF_PATH)
TEXT_PATH = ensure_text_source(PDF_PATH, TEXT_PATH)

VECTORSTORE = build_vectorstore(TEXT_PATH)
RETRIEVER = VECTORSTORE.as_retriever(search_kwargs={"k": 4})

OLLAMA_MODEL = resolve_model_name()
OLLAMA_VISION_MODEL = resolve_vision_model_name()
LLM = ChatOllama(model=OLLAMA_MODEL, temperature=0.1)


@app.get("/health")
def health() -> dict:
    return {
        "ok": True,
        "model": OLLAMA_MODEL,
        "vision_model": OLLAMA_VISION_MODEL,
        "installed_models": get_installed_ollama_models(),
        "pdf": str(PDF_PATH),
        "text_source": str(TEXT_PATH),
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

    text_context = "\n\n".join(d.page_content for d in docs)
    visual_context = build_visual_context(question, PDF_PATH, OLLAMA_VISION_MODEL)
    prompt = make_prompt(question, text_context, visual_context)

    llm_error: Optional[str] = None
    try:
        answer_text = invoke_with_ollama_fallback(prompt)
    except Exception as exc:
        llm_error = str(exc)
        # Graceful fallback: return an extractive answer from retrieved text.
        first = " ".join(docs[0].page_content.split())[:360] if docs else ""
        answer_text = (
            "Local LLM is currently unavailable. Showing the most relevant passage from the document: "
            + (first or "No passage available.")
        )

    sources = []
    for d in docs[:3]:
        snippet = " ".join(d.page_content.split())[:220]
        if snippet:
            page = None
            try:
                page = d.metadata.get("page")
            except Exception:
                page = None
            if isinstance(page, int):
                sources.append(f"Page {page + 1}: {snippet}")
            else:
                sources.append(snippet)

    if visual_context and visual_context.strip():
        visual_snippet = " ".join(visual_context.split())[:220]
        if visual_snippet:
            sources.append("Visual notes: " + visual_snippet)

    if llm_error:
        sources.append("LLM debug: " + " ".join(llm_error.split())[:220])

    if not answer_text:
        answer_text = "I could not generate an answer from the document context."

    return AskResponse(answer=answer_text, sources=sources)


if __name__ == "__main__":
    import uvicorn

    uvicorn.run("rag_server:app", host="127.0.0.1", port=8008, reload=False)
