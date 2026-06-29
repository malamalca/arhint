# AI System Documentation

> **Arhint4** — AI-Powered Business Management Assistant

This document describes the architecture, components, tools, and configuration of the AI system integrated into Arhint4.

---

## Table of Contents

1. [Overview](#1-overview)
2. [Architecture](#2-architecture)
3. [Core Components](#3-core-components)
4. [Tool System](#4-tool-system)
5. [Available AI Tools](#5-available-ai-tools)
6. [Queue Processing](#6-queue-processing)
7. [Project Intelligence (Vector Search)](#7-project-intelligence-vector-search)
8. [Frontend Chat UI](#8-frontend-chat-ui)
9. [Configuration](#9-configuration)
10. [API Endpoints](#10-api-endpoints)
11. [Security & Authorization](#11-security--authorization)
12. [Extensibility](#12-extensibility)
13. [Troubleshooting](#13-troubleshooting)

---

## 1. Overview

Arhint4's AI system provides a conversational assistant that can:

- **Answer questions** about projects, contacts, invoices, documents, and travel orders
- **Perform actions** — create invoices, update tasks, send emails, search records
- **Analyze activity logs** — automatically extract risks, blockers, and summaries from project events
- **Semantic search** — answer natural-language questions about project status using vector embeddings

The system uses a **plugin-based tool architecture** where each module (Crm, Documents, Projects) registers its own tools via CakePHP events. The AI assistant selects relevant tools at runtime based on the user's request context.

### Key Design Decisions

| Aspect | Approach |
|--------|----------|
| **Tool calling** | Dual-mode: native `tool_calls` (OpenAI-compatible) or JSON-prompt-based (local models) |
| **Execution** | Asynchronous via CakePHP Queue (`bin/cake queue worker`) |
| **Conversation history** | Stored in PHP session, compacted with rolling summaries |
| **Tool routing** | AI-driven module detection + keyword fallback |
| **Authorization** | All tool executions run under the current user's authorization scope |

---

## 2. Architecture

```
┌─────────────────────────────────────────────────────────────────────┐
│                         Frontend (Browser)                          │
│  ┌──────────┐   AJAX POST/GET    ┌──────────────────────────────┐  │
│  │ aiChat.js │ ◄────────────────► │      AiController            │  │
│  │           │                    │  chat() / chatStatus()       │  │
│  └──────────┘                    └──────────┬───────────────────┘  │
└─────────────────────────────────────────────┼──────────────────────┘
                                              │ Queue (QueueManager)
                                              ▼
┌─────────────────────────────────────────────────────────────────────┐
│                     Queue Worker Process                            │
│                    ┌──────────────────┐                             │
│                    │   AiChatJob      │  (processes user chat)     │
│                    └────────┬─────────┘                             │
│                             │                                       │
│                    ┌────────▼─────────┐                             │
│                    │  AIAssistant     │  ← Core orchestrator       │
│                    │                  │                            │
│                    │  ┌───────────┐   │                            │
│                    │  │ Tool      │   │                            │
│                    │  │ Selection │   │  Module detection +        │
│                    │  │ & Routing │   │  keyword scoring           │
│                    │  └─────┬─────┘   │                            │
│                    │        │          │                            │
│                    │  ┌─────▼─────┐   │                            │
│                    │  │ Tool      │   │  Event-driven execution    │
│                    │  │ Execution │   │  via App.AIAssistant.     │
│                    │  │ (Event)   │   │  executeTool               │
│                    │  └─────┬─────┘   │                            │
│                    └────────┼──────────┘                             │
└─────────────────────────────┼───────────────────────────────────────┘
                              │
              ┌───────────────┼───────────────┐
              ▼               ▼               ▼
       ┌────────────┐ ┌────────────┐ ┌────────────┐
       │  Crm       │ │ Documents  │ │ Projects   │
       │  AITools   │ │  AITools   │ │  AITools   │
       │  Events    │ │  Events    │ │  Events    │
       └────────────┘ └────────────┘ └────────────┘
```

### Data Flow (Chat Request)

1. User types a message in the dashboard chat panel
2. `aiChat.js` POSTs to `AiController::chat()`
3. Controller pushes an `AiChatJob` onto the queue and returns immediately with a `job_id`
4. Frontend polls `AiController::chatStatus(job_id)` every 1 second
5. Queue worker picks up `AiChatJob`, loads user + history, calls `AIAssistant::getResponse()`
6. `AIAssistant` selects relevant tools, makes API request to LLM, executes tool calls iteratively
7. Result is written to `tmp/ai_jobs/{job_id}_result.json`
8. Frontend picks up the result and renders it as HTML (Markdown → GFM conversion)

---

## 3. Core Components

### 3.1 `AIAssistant` (`src/Lib/AIAssistant.php`)

The central orchestrator class. Responsible for:

- Maintaining conversation history (with automatic compaction)
- Selecting the most relevant tools for each request
- Making API calls to the LLM provider
- Executing tool calls (both native and prompt-based)
- Compacting tool results before feeding them back to the model

**Key constants:**

| Constant | Value | Purpose |
|----------|-------|---------|
| `MAX_TOOL_CALLS` | 5 | Maximum sequential tool calls per request |
| `MAX_TOOLS_PER_REQUEST` | 8 | Maximum non-App tools sent to the model per turn |
| `MAX_HISTORY_MESSAGES` | 8 | Messages kept before compaction into summary |
| `MAX_HISTORY_SUMMARY_CHARS` | 1500 | Max chars in rolling conversation summary |
| `MAX_PROMPT_TOOL_RESULT_ITEMS` | 20 | Max items from tool results in prompt context |
| `MAX_TOOL_RESULT_CHARS` | 800 | Max chars per tool result string |

**Tool Selection Algorithm:**

1. **App tools** (prefixed `App.`) are always included and don't count toward the limit
2. If total module tools ≤ `MAX_TOOLS_PER_REQUEST`, all are sent
3. Otherwise:
   - Group tools by module prefix (`Crm`, `Documents`, `Projects`)
   - Send a lightweight AI routing prompt to detect which module(s) the request targets
   - Include the **recently used module** for follow-up context (e.g., "delete #4")
   - Fall back to keyword scoring if AI detection fails

**Native vs Prompt-Based Tool Calling:**

| Provider | Mode | Details |
|----------|------|---------|
| OpenAI | Native `tool_calls` | Uses OpenAI's function calling API with sanitized names (`.` → `__`) |
| Local/Other | Prompt-based (default) | Tools listed in system prompt; model responds with JSON `{tool, arguments}` |
| Other + config | Configurable | Set `ai_assistant.native_tool_calls = true` on user to enable for any provider |

**One-shot completions:**

`AIAssistant::complete(array $messages, int $timeoutSeconds = 180): string` runs a single
stateless chat completion — no tool selection, no conversation history — reusing the same
provider/endpoint/model/API-key resolution as the conversational flow. Messages are passed
through verbatim, so callers may include multimodal content parts (text + `image_url` or
`file`) where the configured provider/model supports them. This is the entry point for
non-chat AI features such as PDF invoice import (see below).

**PDF invoice import (`Documents\Lib\PdfInvoiceImport`):**

Converts an uploaded PDF invoice into Slovenian e-SLOG 2.0 INVOIC XML by sending the PDF
(base64 `file` content part) plus a strict mapping prompt + template through
`AIAssistant::complete()`. The returned XML is then fed into the regular `EslogImport`
pipeline, so the rest of the import (client lookup, edit-form prefill) is identical to a
plain XML upload. Requires a multimodal provider/model (e.g. OpenAI `gpt-*`); when the model
cannot read the invoice it returns a `CANNOT_PARSE` sentinel and the user is notified.

### 3.2 `AITool` (`src/Lib/AITool.php`)

A simple data class representing one tool:

```php
class AITool {
    public function __construct(
        public string $name,        // e.g. "Crm.search_contacts"
        public array $arguments,    // JSON Schema properties (name → {type, description})
        public string $description, // Natural language description for the model
    ) {}
}
```

### 3.3 `AISerializableInterface` (`src/Lib/AISerializableInterface.php`)

Marker interface for entities that want to control their AI-facing serialization:

```php
interface AISerializableInterface {
    public function toAIArray(): array;
}
```

When tool results contain objects implementing this interface, `toAIArray()` is called instead of generic `toArray()`, allowing fine-grained control over what fields the model sees.

---

## 4. Tool System

### 4.1 Event-Based Registration

Tools are registered through CakePHP's event system. Each plugin listens to three events:

| Event | Purpose |
|-------|---------|
| `App.AIAssistant.registerModule` | Register module name + description for routing |
| `App.AIAssistant.tools` | Append `AITool` instances to the global tools list |
| `App.AIAssistant.executeTool` | Handle execution when the AI invokes a tool |

### 4.2 Tool Naming Convention

Tools follow the pattern: `{Module}.{action}`

Examples:
- `Crm.search_contacts`
- `Documents.create_invoice`
- `Projects.log_workhours`
- `App.get_users`
- `App.qdrant_search`

### 4.3 Tool Execution Flow

```
AI decides to call tool
    │
    ▼
App.AIAssistant.executeTool event dispatched
    │
    ├── Event data: [tool_name, arguments, currentUser]
    │
    ▼
Plugin's aiAssistantExecuteTool() handler invoked
    │
    ├── Validates authorization (currentUser->can())
    ├── Applies scope filters (currentUser->applyScope())
    ├── Executes database operations
    └── Sets result via $event->setResult(...)
    │
    ▼
Result compacted and fed back to AI as context
```

### 4.4 Redirect Support

Tools can return a `redirect_url` in their result. The AI assistant captures this and the frontend redirects the user to that URL after the response is rendered. Used by tools like `Crm.navigate_to_contact` and `Documents.navigate_to_document`.

---

## 5. Available AI Tools

### 5.1 App Module (Core)

| Tool | Description |
|------|-------------|
| `App.get_users` | List users in the current company (filter by active/search) |
| `App.qdrant_search` | Semantic search across project intelligence logs |

### 5.2 Crm Module

| Tool | Description |
|------|-------------|
| `Crm.search_contacts` | Search contacts by name or phone |
| `Crm.get_contact` | Full contact details (emails, phones, addresses, accounts) |
| `Crm.create_contact` | Create a new person or company contact |
| `Crm.update_contact` | Update existing contact fields |
| `Crm.navigate_to_contact` | Navigate to a contact's detail page |
| `Crm.get_contact_logs` | Get interaction history for a contact |
| `Crm.add_contact_log` | Add an interaction log entry |
| `Crm.lookup_company` | Look up Slovenian company by VAT number (inetis registry) |
| `Crm.add/edit/delete_contact_phone` | Manage phone numbers |
| `Crm.add/edit/delete_contact_email` | Manage email addresses |
| `Crm.add/edit/delete_contact_address` | Manage addresses |
| `Crm.add/edit/delete_contact_account` | Manage bank accounts |

### 5.3 Documents Module

| Tool | Description |
|------|-------------|
| `Documents.search_invoices` | Search invoices by counter, date range, search term, overdue status |
| `Documents.get_invoice` | Full invoice details (items, taxes, totals) |
| `Documents.create_invoice` | Create a new invoice with line items or tax summaries |
| `Documents.add_invoice_item` | Add a line item to an invoice |
| `Documents.update_invoice_item` | Update an invoice line item |
| `Documents.delete_invoice_item` | Remove a line item from an invoice |
| `Documents.get_vat_rates` | List available VAT rates with UUIDs |
| `Documents.get_invoice_report` | Financial summary (count, net total, gross total) |
| `Documents.search_documents` | Search generic documents |
| `Documents.get_document` | Full document details |
| `Documents.navigate_to_document` | Navigate to a document detail page |
| `Documents.search_travel_orders` | Search travel orders by status, employee, date range |
| `Documents.get_travel_order` | Full travel order details |
| `Documents.create_travel_order` | Create a new travel order |
| `Documents.add_travel_expense` | Add expense to a travel order |
| `Documents.submit_travel_order` | Advance travel order through approval workflow |
| `Documents.send_document_email` | Generate PDF and email it with attachments |
| `Documents.get_document_counters` | List available document number sequences |

### 5.4 Projects Module

| Tool | Description |
|------|-------------|
| `Projects.search_projects` | Search/list accessible projects |
| `Projects.get_project` | Full project details (description, status, team, milestones) |
| `Projects.get_project_tasks` | List tasks with filters (status, milestone, user) |
| `Projects.get_task` | Full task details with optional comments |
| `Projects.create_task` | Create a new task in a project |
| `Projects.update_task` | Update task (title, description, milestone, assignee, closed state) |
| `Projects.add_task_comment` | Add a comment to a task |
| `Projects.add_project_log` | Add manual activity log entry |
| `Projects.log_workhours` | Log time worked on a project |
| `Projects.create_milestone` | Create a new milestone |
| `Projects.get_project_logs` | List activity logs for a project |
| `Projects.get_project_users` | List team members assigned to a project |
| `Projects.get_project_documents` | List linked invoices, documents, and travel orders |

---

## 6. Queue Processing

### 6.1 `AiChatJob` (`src/Job/AiChatJob.php`)

Processes user chat requests asynchronously.

**Input (queue message):**
```json
{
    "user_id": "uuid",
    "message": "User's question or command",
    "history": [...],
    "job_id": "uuid"
}
```

**Output (file at `tmp/ai_jobs/{job_id}_result.json`):**
```json
{
    "user_id": "uuid",
    "status": "done",
    "response": "<html>AI response</html>",
    "redirect": null,
    "history": [...]
}
```

**Processing steps:**
1. Load user from database
2. Set up authorization service on user entity
3. Create `AIAssistant` with user context
4. Restore conversation history
5. Call `getResponse()` — handles tool selection, API calls, and execution
6. Convert Markdown response to HTML via `GithubFlavoredMarkdownConverter`
7. Write result file

### 6.2 `AiProcessLogJob` (`src/Job/AiProcessLogJob.php`)

Automatically analyzes activity log entries for project intelligence.

**Triggered when:** A new log entry is saved (via `AppEvents::updateModelAttachments()` on `Model.afterSave`).

**Processing steps:**
1. Load user and convert entity to text
2. Call LLM directly with a system prompt requesting structured JSON:
   ```json
   {
       "summary": "",
       "risks": [],
       "blockers": [],
       "next_steps": [],
       "priority": "",
       "sentiment": ""
   }
   ```
3. Save analysis to `logs_analysis` table
4. Embed the summary and upsert into Qdrant (best-effort)

**Priority mapping:**
| String | Integer |
|--------|---------|
| high / urgent / critical | 1 |
| medium | 2 |
| low | 3 |

### 6.3 Starting the Queue Worker

```bash
# From project root
bin/cake queue worker

# With specific queue name
bin/cake queue worker --queue=ai_chat,ai_process_log

# Daemonic mode (Linux)
bin/cake queue runserver
```

The worker health is tracked via a heartbeat file at `tmp/ai_jobs/worker_heartbeat`. The frontend checks this and shows a warning banner if the worker appears offline.

---

## 7. Project Intelligence (Vector Search)

### 7.1 Architecture

```
Log Event Created
       │
       ▼
┌─────────────────────┐
│  AiProcessLogJob    │ ← AI analyzes the log entry
└──────────┬──────────┘
           │ Produces: {summary, risks, blockers, priority}
           ▼
┌─────────────────────┐
│  logs_analysis       │ ← Structured analysis stored in DB
│  (MySQL table)       │
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│  EmbeddingService    │ ← Text → Vector
│  (external API)      │
└──────────┬──────────┘
           │ Produces: float[] embedding vector
           ▼
┌─────────────────────┐
│  QdrantService       │ ← Vector + metadata stored
│  (Qdrant DB)         │    for semantic search
└─────────────────────┘
```

### 7.2 `EmbeddingService` (`src/Lib/EmbeddingService.php`)

Generates embedding vectors by calling an external API.

**Configuration:** `Configure::read('Embedding')`

| Key | Default | Description |
|-----|---------|-------------|
| `url` | `http://127.0.0.1:8000/embed` | Embedding API endpoint |
| `timeout` | 30 | cURL timeout in seconds |

**Supported response formats:**
```json
{"vector": [0.1, 0.2, ...]}           // Direct vector key
{"embedding": [0.1, 0.2, ...]}        // OpenAI-style top-level
{"data": [{"embedding": [...]}]}      // OpenAI data array format
```

### 7.3 `QdrantService` (`src/Lib/QdrantService.php`)

Interacts with a Qdrant vector database.

**Configuration:** `Configure::read('Qdrant')`

| Key | Default | Description |
|-----|---------|-------------|
| `host` | *(required)* | Host and port, e.g. `127.0.0.1:6333` |
| `scheme` | `http` | HTTP or HTTPS |
| `collection` | `events` | Default collection name |
| `api_key` | `""` | Optional API key |
| `timeout` | 30 | cURL timeout in seconds |

**Payload schema stored per point:**
```json
{
    "log_id": "uuid",
    "log_model": "Projects.Project",
    "log_foreign_id": "project-uuid",
    "log_user_id": "user-uuid",
    "log_action": "Comment",
    "summary": "AI-generated summary text",
    "priority": 1,
    "model": "Projects.Project"
}
```

### 7.4 `QdrantSearchTool` (`src/Lib/QdrantSearchTool.php`)

Semantic search tool that combines embedding + Qdrant query + LLM synthesis.

**Flow:**
1. Embed the user's natural-language question
2. Query Qdrant for top-N similar log entries (with optional filter)
3. Extract summaries from results, formatted as bullet points with priority labels
4. Call LLM to synthesize a concise answer from the context

---

## 8. Frontend Chat UI

### 8.1 JavaScript (`webroot/js/aiChat.js`)

The chat widget is initialized on `#DashboardAIAssistant`. Key behaviors:

| Feature | Implementation |
|---------|---------------|
| **Message submission** | POST to `chatUrl` with CSRF token |
| **Response polling** | GET `chatStatusUrl?job_id=...` every 1000ms, max 180 polls (~3 min) |
| **Thinking indicator** | Animated bouncing dots shown while waiting |
| **Markdown rendering** | AI responses are rendered as HTML (converted server-side by GFM converter) |
| **Worker health check** | After 3 pending polls, checks `workerStatusUrl`; shows warning banner if offline |
| **Redirect handling** | If response includes `redirect`, navigates the browser to that URL |
| **Clear history** | POST to `clearUrl` empties the message panel and session |
| **Keyboard shortcuts** | Enter = submit, Ctrl+Enter / Shift+Enter = new line |

### 8.2 CSS/SCSS (`webroot/sass/components/_ai-assistant.scss`)

Styled components:

- `.ai-message--user` — Right-aligned, primary color background
- `.ai-message--assistant` — Left-aligned, teal accent with left border
- `.ai-message--error` — Red tinted error messages
- `.ai-thinking` — Bouncing dot animation (`@keyframes ai-bounce`)
- `.ai-worker-warning` — Orange warning banner for offline queue worker

---

## 9. Configuration

### 9.1 User-Level AI Settings

Each user's AI configuration is stored in the `properties` JSON column of the `users` table, under the `ai_assistant` key:

```json
{
    "ai_assistant": {
        "provider": "local",
        "url": "http://192.168.68.58:8080/v1/chat/completions",
        "model": "qwen",
        "api_key": "",
        "native_tool_calls": false
    }
}
```

| Field | Type | Default | Description |
|-------|------|---------|-------------|
| `provider` | string | `"local"` | AI provider identifier. `"openai"` enables native tool calls automatically |
| `url` | string | `http://192.168.68.58:8080/v1/chat/completions` | API endpoint URL (OpenAI URL is used when provider=`openai`) |
| `model` | string | `"qwen"` (local) / `"gpt-4o"` (OpenAI) | Model name sent to the API |
| `api_key` | string | `""` | Bearer token for authenticated APIs |
| `native_tool_calls` | boolean | auto | Force native tool calling on/off (auto = OpenAI=yes, others=no) |

### 9.2 Application-Level Configuration (`config/app.php`)

```php
// Embedding service configuration
'Embedding' => [
    'url' => 'http://127.0.0.1:8000/embed',
    'timeout' => 30,
],

// Qdrant vector database configuration
'Qdrant' => [
    'host' => '192.168.88.30:6333',
    'scheme' => 'http',
    'collection' => 'events',
    'api_key' => '',
    'timeout' => 30,
],
```

### 9.3 External Services Required

| Service | Purpose | Default Endpoint | Protocol |
|---------|---------|-----------------|----------|
| **LLM API** | Chat completions (OpenAI-compatible) | `http://192.168.68.58:8080/v1/chat/completions` | OpenAI chat completions format |
| **Embedding API** | Text → vector embedding | `http://127.0.0.1:8000/embed` | Custom JSON (accepts `{text}`, returns `{vector}`) |
| **Qdrant** | Vector database for semantic search | `http://192.168.88.30:6333` | Qdrant REST API |

---

## 10. API Endpoints

### 10.1 Chat Endpoints

#### `POST /ai/chat`

Submit a chat message. Pushes a job to the queue.

**Request body:**
```json
{"message": "User's question"}
```

**Response (200):**
```json
{"job_id": "uuid", "status": "pending"}
```

#### `GET /ai/chatStatus?job_id={uuid}`

Poll for job result.

**Responses:**

| Status | Body |
|--------|------|
| Pending | `{"status": "pending"}` |
| Done | `{"status": "done", "response": "<html>...", "redirect": null}` |
| Error | `{"status": "error", "error": "Error message"}` |

#### `GET /ai/workerStatus`

Check if the queue worker is alive.

**Response:**
```json
{"running": true}
```

Worker is considered running if `tmp/ai_jobs/worker_heartbeat` exists and was modified within the last 35 minutes.

#### `POST /ai/clearHistory`

Clear the conversation history for the current user.

**Response:**
```json
{"cleared": true}
```

---

## 11. Security & Authorization

### 11.1 Authorization Enforcement

Every tool execution runs under the **current user's authorization context**:

```php
// All queries use applyScope for row-level filtering
$currentUser->applyScope('index', $table->find())

// All mutations check permissions
if (!$currentUser->can('edit', $entity)) {
    $event->setResult(['error' => 'Access denied.']);
}
```

### 11.2 Job Security

- Job results are validated against the requesting user's ID (`user_id` must match)
- UUID format is validated to prevent path traversal in `chatStatus()`
- Old job files are cleaned up after 1 hour

### 11.3 API Key Handling

- API keys are stored in user properties (encrypted at rest if configured)
- Keys are never logged — the logging layer strips authorization headers
- cURL requests use the key only for the duration of the HTTP call

---

## 12. Extensibility

### 12.1 Adding a New Tool Module

Create an event listener in your plugin:

```php
namespace MyPlugin\Event;

use App\Lib\AITool;
use ArrayObject;
use Cake\Event\Event;
use Cake\Event\EventListenerInterface;

class MyPluginAIToolsEvents implements EventListenerInterface
{
    public function implementedEvents(): array
    {
        return [
            'App.AIAssistant.registerModule' => 'aiAssistantRegisterModule',
            'App.AIAssistant.tools' => 'aiAssistantTools',
            'App.AIAssistant.executeTool' => 'aiAssistantExecuteTool',
        ];
    }

    public function aiAssistantRegisterModule(Event $event, ArrayObject $modulesList): void
    {
        $modulesList['MyPlugin'] = 'Description of what this module does.';
    }

    public function aiAssistantTools(Event $event, ArrayObject $toolsList): void
    {
        $toolsList->append(new AITool(
            name: 'MyPlugin.do_something',
            arguments: [
                'param' => ['type' => 'string', 'description' => 'Parameter description'],
            ],
            description: 'What this tool does.',
        ));
    }

    public function aiAssistantExecuteTool(Event $event, string $tool, array $arguments): void
    {
        if ($tool === 'MyPlugin.do_something') {
            // Execute logic...
            $event->setResult(['success' => true]);
        }
    }
}
```

Register the listener in your plugin's `bootstrap.php` or `Application.php`:

```php
EventManager::instance()->on(new MyPluginAIToolsEvents());
```

### 12.2 Custom System Prompt

Override the default system prompt:

```php
$assistant = new AIAssistant($user);
$assistant->setSystemPrompt("Your custom system instructions here.");
```

### 12.3 AISerializableInterface

Implement on any entity to control what fields are exposed to the AI:

```php
class MyEntity implements AISerializableInterface
{
    public function toAIArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            // Only include fields relevant for AI context
        ];
    }
}
```

---

## 13. Troubleshooting

### 13.1 Common Issues

| Symptom | Cause | Fix |
|---------|-------|-----|
| Chat returns `pending` forever | Queue worker not running | Start with `bin/cake queue worker` |
| Worker heartbeat warning shown | Worker crashed or not started | Check `tmp/ai_jobs/worker_heartbeat` and queue logs |
| Tool calls fail silently | Model doesn't support native tool calls | Set `native_tool_calls: false` on user config, or use a compatible model |
| Qdrant search returns empty results | Embedding API unreachable or collection not populated | Check `Embedding.url` and verify Qdrant collection has points |
| Log analysis not running | `AiProcessLogJob` not being queued | Verify `Model.afterSave` event fires on the log table; check for `isNew()` / `isDirty()` conditions |
| Conversation history lost | Session expired or cleared | History is stored in PHP session — ensure session lifetime is sufficient |

### 13.2 Debugging Tool Selection

To see which tools are being sent to the model, enable CakePHP debug logging:

```php
// In AIAssistant::getSelectedTools() result is logged at debug level
debug($selectedTools);```

The `App.AIAssistant.tools` event can be intercepted to log the full tool list before selection.

### 13.3 Verifying Queue Jobs

```bash
# List pending jobs
bin/cake queue info

# Check job status by ID
bin/cake queue status <job_id>

# Run a single job manually
bin/cake queue run AiChatJob '{"user_id": "...", "message": "test", "history": [], "job_id": "..."}'
```

---

## Appendix A: File Reference

| File | Path | Purpose |
|------|------|---------|
| AI Assistant Core | `src/Lib/AIAssistant.php` | Main orchestrator — tool selection, API calls, execution |
| Tool Definition | `src/Lib/AITool.php` | Data class for tool metadata (name, args, description) |
| Serialization Interface | `src/Lib/AISerializableInterface.php` | Marker interface for AI-facing entity serialization |
| Chat Controller | `src/Controller/AiController.php` | API endpoints for chat, status, worker health |
| Chat Job | `src/Job/AiChatJob.php` | Queue job — processes user messages via AIAssistant |
| Log Analysis Job | `src/Job/AiProcessLogJob.php` | Queue job — analyzes log entries and embeds summaries |
| Embedding Service | `src/Lib/EmbeddingService.php` | Text → vector embedding via external API |
| Qdrant Service | `src/Lib/QdrantService.php` | Qdrant vector database client |
| Qdrant Search Tool | `src/Lib/QdrantSearchTool.php` | Semantic search combining embedding + Qdrant + LLM |
| App Tools Listener | `src/Event/AppAIToolsEvents.php` | Core tools (users, qdrant_search) |
| Crm Tools Listener | `plugins/Crm/src/Event/CrmAIToolsEvents.php` | Contact management tools |
| Documents Tools | `plugins/Documents/src/Event/DocumentsAIToolsEvents.php` | Invoices, documents, travel orders |
| Projects Tools | `plugins/Projects/src/Event/ProjectsAIToolsEvents.php` | Projects, tasks, milestones, logs |
| Frontend JS | `webroot/js/aiChat.js` | Chat UI — polling, rendering, keyboard shortcuts |
| Frontend SCSS | `webroot/sass/components/_ai-assistant.scss` | Chat UI styles and animations |
| App Events | `src/Event/AppEvents.php` | Triggers AiProcessLogJob on log saves |
| Logs Table | `src/Model/Table/LogsTable.php` | Activity log model with static `log()` helper |
| Logs Analysis Table | `src/Model/Table/LogsAnalysisTable.php` | AI analysis results table |

## Appendix B: Database Schema (AI-Related)

### `logs`

| Column | Type | Description |
|--------|------|-------------|
| `id` | UUID | Primary key |
| `model` | varchar(50) | Model name that generated the log |
| `foreign_id` | UUID | Related entity ID |
| `user_id` | UUID | User who performed the action |
| `action` | varchar(255) | Action description |
| `descript` | text (16MB) | Detailed description |
| `created` | datetime | Timestamp |
| `modified` | datetime | Last modified |

### `logs_analysis`

| Column | Type | Description |
|--------|------|-------------|
| `id` | UUID | Primary key |
| `log_id` | UUID | FK to `logs.id` |
| `summary` | text | AI-generated summary |
| `risks` | json | Array of risk descriptions |
| `blockers` | json | Array of blocker descriptions |
| `next_steps` | json | Array of next step suggestions |
| `priority` | tinyint | 1=high, 2=medium, 3=low |
| `sentiment` | varchar(50) | Sentiment label |
| `created` | datetime | Timestamp |

### Qdrant Collection (`events`)

| Field | Type | Description |
|-------|------|-------------|
| *(vector)* | float[] | Embedding vector of the summary text |
| `log_id` | string | UUID of the log entry |
| `log_model` | string | Model name (e.g. `Projects.Project`) |
| `log_foreign_id` | string | Related entity UUID |
| `log_user_id` | string | User UUID |
| `log_action` | string | Action type |
| `summary` | string | AI summary text |
| `priority` | integer | Priority level (1–3) |
| `model` | string | Model name for filtering |

---

## Appendix C: Configuration Examples

### OpenAI Provider

```json
{
    "ai_assistant": {
        "provider": "openai",
        "url": "https://api.openai.com/v1/chat/completions",
        "model": "gpt-4o",
        "api_key": "sk-..."
    }
}
```

### Local Ollama Provider

```json
{
    "ai_assistant": {
        "provider": "local",
        "url": "http://192.168.68.58:8080/v1/chat/completions",
        "model": "qwen",
        "api_key": ""
    }
}
```

### Local with Forced Native Tool Calls

```json
{
    "ai_assistant": {
        "provider": "local",
        "url": "http://192.168.68.58:8080/v1/chat/completions",
        "model": "qwen",
        "api_key": "",
        "native_tool_calls": true
    }
}
```

---

*Document generated from source code analysis of Arhint4 v4.*