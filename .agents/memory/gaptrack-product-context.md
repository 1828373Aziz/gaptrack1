---
name: GapTrack product context
description: Product goals and the boundary between demonstration data and operational shipment data.
---

GapTrack is a cooperative-training proposal prototype for shipment tracking. It distinguishes GPS points inside known weak-coverage zones from long gaps outside them, provides advance warnings before weak zones, and aggregates recurring locations to support logistics and infrastructure decisions.

The user wants the running GapTrack website implemented in HTML, PHP, CSS, and JavaScript rather than React/TypeScript.

An exported phpMyAdmin dump for the original database is available. It comes from local XAMPP MariaDB 10.4.32 on `127.0.0.1:3308` and includes shipment, coverage-zone, tracking-update, and alert tables; it does not provide a remotely reachable database host.

The user confirmed that `gaptrack` and its four expected tables are already present in their local phpMyAdmin. Their intended development setup is Windows XAMPP plus Visual Studio Code; this local database is not reachable from Replit.

**Why:** The supplied files contain PHP page source but no original MySQL export/schema or live GPS provider details. The current version therefore demonstrates those flows with clearly labeled sample records.

**How to apply:** Keep new GapTrack website behavior in HTML, PHP, CSS, and JavaScript. Use the dump to map the eventual MySQL integration, but do not treat its loopback host as available to Replit. Keep product language focused on decision support. Never describe sample records as live operations data; only claim real-time tracking after a database/provider is connected.
