# Project: Metallica Marcon Logistics Platform

## Overview

Metallica Marcon operates in industrial waste collection (mainly metal and technological waste).

The software being developed manages:

- Pickup requests
- Trip planning and logistics
- Warehouse intake and material classification
- Commercial dashboard (materials, margins, performance)
- Natural Language Query interface (logistics + analytics)

## Strategic Goals

1. Optimize logistics planning time.
2. Increase truck saturation and route efficiency.
3. Provide commercial insights through structured analytics.
4. Build a competitive, AI-enhanced vertical platform.

## Non-Goals

- The system does not rely on AI for route optimization (this remains deterministic).
- AI is used strictly for structured interpretation of user intent.

## Tech Stack

- Laravel (API + domain logic)
- Vue + Inertia (frontend)
- MySQL (primary DB)
- OpenAI API (LLM interpretation layer)
- Codex CLI for structured development workflow