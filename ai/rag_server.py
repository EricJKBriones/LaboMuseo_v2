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

OLLAMA_MODEL = os.getenv("OLLAMA_MODEL", "llama3")
LLM = ChatOllama(model=OLLAMA_MODEL, temperature=0.1)


@app.get("/health")
def health() -> dict:
    return {"ok": True, "model": OLLAMA_MODEL, "pdf": str(PDF_PATH)}


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
    except Exception as ex:
        raise HTTPException(status_code=500, detail=f"Ollama error: {ex}")

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
