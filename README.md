#  Privacy-First Family Document Expiry Reminder Portal

A lightweight, self-hosted web application engineered to monitor critical family document milestones (Passports, Visas, National IDs, and Insurance) utilizing contextual fallback lead times and external automation triggers.

##  Key Features

- **Contextual Alert Intervals:** Dynamic business logic changes reminder metrics based on document classification (e.g., defaulting to a 180-day buffer for Passports to comply with international travel rules, while narrowing to 30 days for local vehicular certifications).
- **Asynchronous Execution Architecture:** Designed with an decoupled API endpoint (`api.php`) allowing third-party automation tools (n8n, Android Automate) to serve as a chronic trigger heartbeat, entirely bypassing restricted cron execution parameters common in shared hosting environments.
- **Bulk Data Maintenance:** Integrated multi-select validation arrays enabling clean, efficient mass data purging.
- **Color-Coded HTML-to-Excel Transports:** Formatted, server-side data compilation structures that dynamically highlight entries with less than 30 days of validity left in high-visibility bold red.
- **Privacy by Design:** Formulated to track metadata strictly through casual aliases/nicknames—eliminating the need to store sensitive passport sequences or identity strings in the clear.

## ️ System Architecture

- **Backend Logic Engine:** PHP (PDO Data Mapping Layer)
- **Database Framework:** MySQL
- **Presentation Layer:** Responsive Bootstrap 5 Engine & Native JavaScript (REST Countries Schema API integration)
- **External Cron Sync:** Android Automate Engine / n8n Workflow Gateway

##  Deployment Instructions

1. **Database Initialization:**
   - Map your localized MySQL environment using the core relational tables.
   
2. **Environment Masking:**
   - Duplicate `config.example.php`, rename it to `config.php`, and fill in your network endpoints and database parameters.
   
3. **Trigger Orchestration:**
   - Set an external GET request agent targeting `api.php?key=YOUR_KEY&user_id=TARGET_ID`.

### Dual-Platform Native Mobile Automation

To achieve enterprise-grade mobile tracking without developing standalone native apps, the platform leverages asynchronous background triggers native to both mobile operating systems:

* **iOS Automation (Apple Shortcuts):** Integrated via a native iOS Personal Automation workflow. Utilizing a `Get Contents of URL` block paired with a `Show Notification` action, the script executes silently on an automated 24-hour cycle. It pings the authenticated backend endpoint and passes the response payload directly into a native iOS status-bar notification banner.
* **Android Automation (LlamaLab Automate):** Configured via an infinite background flowchart loop. A `Time await` block handles the 24-hour daily scheduling constraint, triggering an `HTTP request` (configured to *Save as text*) which passes the data payload into a custom *Cancellable* (swipe-dismissible) `Show notification` block. This completely circumvents Android battery optimizations and shared-hosting cron restrictions.
