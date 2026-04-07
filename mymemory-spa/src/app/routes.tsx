import { lazy } from 'react';
import { Navigate, type RouteObject } from 'react-router-dom';

const UserPreferencesPage = lazy(() => import('../features/me/pages/UserPreferencesPage'));

/**
 * Route map — mymemory-spa
 *
 * Screens identified from spa-old/src/pages/:
 *
 * Public (no auth required):
 *   /login                  → LoginPage           (spa-old: LoginPage.tsx)
 *   /register               → RegisterPage        (spa-old: RegisterPage.tsx)
 *   /forgot-password        → ForgotPasswordPage  (spa-old: ForgotPasswordPage.tsx)
 *   /reset-password         → ResetPasswordPage   (not yet in spa-old — implied by auth flow)
 *   /verify-email           → VerifyEmailPage     (not yet in spa-old — implied by auth flow)
 *   /group-invites/accept   → GroupInviteAcceptPage (spa-old: GroupInviteAcceptPage.tsx)
 *
 * Authenticated:
 *   /                       → HomePage            (spa-old: HomePage.tsx)
 *   /memos/search           → MemoSearchPage      (spa-old: MemoSearchPage.tsx)
 *   /memos/text/review      → MemoTextReviewPage  (spa-old: MemoTextReviewPage.tsx)
 *   /memos/image/review     → MemoImageReviewPage (spa-old: MemoImageReviewPage.tsx)
 *   /memos/audio/review     → MemoAudioReviewPage (spa-old: MemoAudioReviewPage.tsx)
 *   /memos/video/review     → MemoVideoReviewPage (spa-old: MemoVideoReviewPage.tsx)
 *   /memos/document/review  → MemoDocumentReviewPage (spa-old: MemoDocumentReviewPage.tsx)
 *   /memos/:id/edit         → MemoEditPage        (spa-old: MemoEditPage.tsx)
 *   /groups/create          → GroupCreatePage     (spa-old: GroupCreatePage.tsx)
 *   /groups/:id/panel       → GroupOwnerPanelPage (spa-old: GroupOwnerPanelPage.tsx)
 *   /memo-context           → MemoContextPage     (spa-old: MemoContextPage.tsx)
 *   /preferences            → UserPreferencesPage (spa-old — implied by profile flow)
 *
 * Admin (role: admin):
 *   /admin                  → AdminPage           (spa-old: AdminPage.tsx)
 *   /admin/media-settings   → AdminMediaSettingsPage (spa-old: AdminMediaSettingsPage.tsx)
 *   /admin/document-ai      → AdminDocumentAiPage (spa-old: AdminDocumentAiPage.tsx)
 *
 * Dev-only:
 *   /dev/health             → HealthCheckPage     (validates API connectivity)
 *
 * Pages will be implemented progressively from Etapa 2 onwards.
 * Lazy imports are commented out until the feature is scaffolded.
 */

// Dev utilities (always available in all envs for now)
const HealthCheckPage = lazy(() => import('../features/dev/HealthCheckPage'));

export const routes: RouteObject[] = [
  // Dev
  { path: '/dev/health', element: <HealthCheckPage /> },

  // Me / account
  { path: '/preferences', element: <UserPreferencesPage /> },

  // Fallback — redirect everything to health until more pages are implemented
  { path: '*', element: <Navigate to="/dev/health" replace /> },
];
