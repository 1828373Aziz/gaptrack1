---
name: GapTrack product context
description: Product goals and the boundary between demonstration data and operational shipment data.
---

GapTrack is a cooperative-training proposal prototype for shipment tracking. It distinguishes GPS points inside known weak-coverage zones from long gaps outside them, provides advance warnings before weak zones, and aggregates recurring locations to support logistics and infrastructure decisions.

The user wants the running GapTrack website implemented in HTML, PHP, CSS, and JavaScript rather than React/TypeScript.

**Why:** The supplied files contain PHP page source but no original MySQL export/schema or live GPS provider details. The current version therefore demonstrates those flows with clearly labeled sample records.

**How to apply:** Keep new GapTrack website behavior in HTML, PHP, CSS, and JavaScript. Keep product language focused on decision support. Never describe sample records as live operations data; only import historical data or claim real-time tracking after the source database/provider has been supplied and connected.
