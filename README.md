# LaboMuseo

## Offline AI Search For PDF Reader

This project supports offline AI answers inside the PDF reader popup using:

- Language: Python
- Framework: LangChain
- Local LLM Engine: Ollama (Llama 3 or Phi-3)
- Embedding Model: HuggingFace all-MiniLM-L6-v2

### 1) Install Ollama model

Run one of these:

```powershell
ollama pull llama3
```

or

```powershell
ollama pull phi3
```

### 2) Setup Python environment

From project root:

```powershell
cd ai
python -m venv .venv
.\.venv\Scripts\Activate.ps1
pip install -r requirements.txt
```

### 3) Start offline AI server

Default model is llama3. To use phi3, set OLLAMA_MODEL=phi3 before run.

```powershell
# optional model switch
$env:OLLAMA_MODEL = "phi3"

# run local API server
python rag_server.py
```

Server runs at:

```text
http://127.0.0.1:8008
```

### 4) Use in the app

Open PDF reader page and click the floating AI button. Questions are answered from the local PDF context.

### Troubleshooting: Failed to fetch / connection refused

If the popup says it cannot reach local AI:

1. Make sure API server is running:

```powershell
python ai/rag_server.py
```

2. Make sure Ollama is installed and running:

```powershell
ollama serve
ollama pull llama3
```

3. If `ollama` command is not found, install Ollama from:

```text
https://ollama.com/download/windows
```

4. Re-open your browser page after starting both services.

