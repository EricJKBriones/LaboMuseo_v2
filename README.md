# LaboMuseo

## Offline AI Search For PDF Reader

This project supports offline AI answers inside the PDF reader popup using:

- Language: Python
- Framework: LangChain
- Local LLM Engine: Ollama (Llama 3 or Phi-3)
- Embedding Model: HuggingFace all-MiniLM-L6-v2

Compatible platforms:

- Windows
- Linux
- Raspberry Pi OS (ARM64 recommended)

### 1) Install Ollama and pull a model

Run one of these:

```bash
ollama pull llama3
```

or

```bash
ollama pull phi3
```

For Raspberry Pi, use a smaller model if memory is limited (for example, `phi3`).

### 2) Setup Python environment

From project root:

```bash
cd ai
python -m venv .venv
```

Activate venv:

- Windows PowerShell:

```powershell
.\.venv\Scripts\Activate.ps1
```

- Linux / Raspberry Pi (bash/zsh):

```bash
source .venv/bin/activate
```

Install dependencies:

```bash
pip install -r requirements.txt
```

### 3) Start offline AI server

Default model is llama3. To use phi3, set OLLAMA_MODEL=phi3 before run.

Windows PowerShell:

```powershell
$env:OLLAMA_MODEL = "phi3"   # optional
$env:API_HOST = "127.0.0.1"  # optional
$env:API_PORT = "8008"       # optional
python rag_server.py
```

Linux / Raspberry Pi:

```bash
export OLLAMA_MODEL=phi3       # optional
export API_HOST=127.0.0.1      # optional
export API_PORT=8008           # optional
python rag_server.py
```

To allow other devices on your network to access the API (for example from another device to your Raspberry Pi), use:

```bash
export API_HOST=0.0.0.0
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

```bash
python ai/rag_server.py
```

2. Make sure Ollama is installed and running:

```bash
ollama serve
ollama pull llama3
```

3. If `ollama` command is not found, install Ollama from:

```text
https://ollama.com/download
```

4. Re-open your browser page after starting both services.

