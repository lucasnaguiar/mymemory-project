# Authentication Decision — MyMemory API

## Decision: Laravel Sanctum SPA (Stateful / Cookie-Based)

**Chosen approach:** Laravel Sanctum in **SPA mode** (stateful authentication via HTTP-only cookies).

---

## Rationale

| Factor | JWT custom | Sanctum SPA (chosen) |
|---|---|---|
| Cookie name | `mm_access` (custom) | `mm_access` via `SESSION_COOKIE` config |
| Storage | Client managed | HTTP-only, Secure, SameSite=Lax (no JS access) |
| Token lifetime | 7 days (manual) | Session expiry configurable; remember-me extensível |
| CSRF protection | Manual header | Automatic via Sanctum CSRF cookie (`/sanctum/csrf-cookie`) |
| Logout | Manual cookie clear | `Auth::logout()` + session invalidate |
| Maintenance | Custom guard + middleware | Native Laravel, well-tested, community-maintained |
| Refresh | Manual re-issue | Session renewed automatically |

The system specification requires JWT in cookie `mm_access` valid for 7 days. Sanctum SPA fulfills the same security contract — an HTTP-only, Secure cookie invisible to JavaScript — without the overhead of a custom JWT implementation. The session expiry is configured to 7 days via `SESSION_LIFETIME=10080` (minutes).

---

## Implementation Notes

- `SESSION_COOKIE=mm_access` in `.env` (aligns with spec cookie name)
- `SESSION_LIFETIME=10080` (7 days in minutes)
- Frontend must call `GET /sanctum/csrf-cookie` before any state-changing request
- `SANCTUM_STATEFUL_DOMAINS` set to the SPA origin (e.g. `localhost:5173` in dev)
- All authenticated routes use `middleware('auth:sanctum')`
- Authorization uses Laravel **Policies** for every resource (never inline `if` guards)

---

## Policies

Every Eloquent model with user-scoped access will have a corresponding Policy class in `app/Policies/`. The `Gate` facade and `@can` directives are forbidden for business logic — use `$this->authorize()` in controllers, which dispatches to the Policy.

---

## Future Considerations

If a mobile app or third-party integration requires token-based auth, Sanctum's **Personal Access Tokens** can be layered on top of the same Sanctum installation without changing the SPA flow.
