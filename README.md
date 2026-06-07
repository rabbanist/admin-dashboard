# Admin Dashboard Package

[![Packagist Version](https://img.shields.io/packagist/v/yourvendor/admin-dashboard.svg)](https://packagist.org/packages/yourvendor/admin-dashboard)
[![License](https://img.shields.io/packagist/l/yourvendor/admin-dashboard.svg)](https://packagist.org/packages/yourvendor/admin-dashboard)

---

## Table of Contents
- [Project Description](#project-description)
- [Features Overview](#features-overview)
- [Requirements](#requirements)
- [Quick Start](#quick-start)
- [Documentation](#documentation)
  - [Installation Guide](INSTALLATION.md)
  - [Configuration Guide](CONFIG.md)
  - [User Management](USERS.md)
  - [Developer Guide](DEVELOPER.md)
  - [Dynamic CRUD Guide](DYNAMIC_CRUD.md)
  - [API Documentation](API.md)
  - [Security Guide](SECURITY.md)
  - [Troubleshooting](TROUBLESHOOTING.md)
- [License](#license)

---

## Project Description

`admin-dashboard` is a **premium, production‑ready Laravel package** that delivers a sleek, dark‑mode‑compatible admin panel with **dynamic CRUD generation**, **role‑based access control**, **audit logging**, **user impersonation**, and **extensible architecture**. It ships with a set of reusable Blade components, Tailwind CSS v3 styling, and Alpine.js interactivity, allowing you to ship a beautiful admin UI out of the box while keeping the codebase highly customizable.

---

## Features Overview

- **Dynamic CRUD** – Define resources in a simple config array; the package automatically creates list, create, edit, show, export, and CSV streams.
- **Role & Privilege System** – Many‑to‑many role/privilege relationships, protected system roles, privilege caching, and a fluent API for permission checks.
- **Authentication & Authorization Middleware** – `CheckAdminAccess`, `CheckPrivilege`, `LogAdminActivity`.
- **Premium UI** – Tailwind CSS v3, glass‑morphism cards, responsive sidebar, dark‑mode toggle persisted via `localStorage`.
- **Reusable Blade Components** – `alert`, `card`, `table`, `form-field`, `modal`, `breadcrumb`, `stat-card`.
- **Audit Logging** – Every admin action is recorded with user, IP, browser, timestamp, and change payload.
- **Impersonation** – Super‑admins can impersonate any user securely.
- **Two‑Factor Authentication (2FA) Ready** – Hooks for Google Authenticator, SMS, or email OTP.
- **Extensible Service Layer** – Services for roles, privileges, resources, dashboards, and audit logs.
- **Full Test Suite** – 100 % unit/feature coverage with Laravel Testbench.

---

## Requirements

| Requirement | Minimum |
|------------|---------|
| PHP | **8.2** |
| Laravel | **9.x** |
| Database | MySQL 5.7+, PostgreSQL 10+, SQLite |
| Extensions | `pdo_mysql`/`pdo_pgsql`, `openssl`, `json` |
| Node / npm | Optional – only for compiled assets |

---

## Quick Start

1. **Install via Composer**
   ```bash
   composer require yourvendor/admin-dashboard
   ```
2. **Run the installer** – this publishes config, views, migrations, assets, runs migrations, seeds default roles/privileges, and (optionally) creates an admin user.
   ```bash
   php artisan admin-dashboard:install
   ```
   Follow the prompts; you can bypass interactive mode with `--no-interaction` for CI pipelines.
3. **Serve your Laravel application**
   ```bash
   php artisan serve
   ```
   Visit `http://localhost:8000/admin` and log in with the admin credentials you created.
4. **Optional: Publish assets manually**
   ```bash
   php artisan admin-dashboard:publish --tag=admin-dashboard
   ```

---

## Documentation

In‑depth documentation is split into dedicated markdown files:
- **[Installation Guide](INSTALLATION.md)** – prerequisites, step‑by‑step install, configuration, verification, and troubleshooting.
- **[Configuration Guide](CONFIG.md)** – all config options, environment variables, feature flags, and performance tuning.
- **[User Management](USERS.md)** – creating users, role/privilege assignment, 2FA, suspension, profile handling, activity feed.
- **[Developer Guide](DEVELOPER.md)** – architecture, creating custom resources, extending models, custom middleware, API reference, event hooks.
- **[Dynamic CRUD Guide](DYNAMIC_CRUD.md)** – configuration format, supported field types, validation, relationships, file uploads, custom fields, examples.
- **[API Documentation](API.md)** – authentication flow, endpoint catalog, request/response examples, error codes, rate limiting.
- **[Security Guide](SECURITY.md)** – best practices, CSRF, sanitization, file upload security, audit logging, compliance.
- **[Troubleshooting](TROUBLESHOOTING.md)** – common issues, log locations, debug mode, support channels.

---

## License

Released under the **MIT License**. See the `LICENSE` file for details.