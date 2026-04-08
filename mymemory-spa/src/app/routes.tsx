import { lazy } from 'react';
import { type RouteObject } from 'react-router-dom';

const UserPreferencesPage    = lazy(() => import('../features/me/pages/UserPreferencesPage'));
const MemoTextReviewPage     = lazy(() => import('../features/memos/pages/MemoTextReviewPage'));
const MemoImageReviewPage    = lazy(() => import('../features/memos/pages/MemoImageReviewPage'));
const MemoAudioReviewPage    = lazy(() => import('../features/memos/pages/MemoAudioReviewPage'));
const MemoVideoReviewPage    = lazy(() => import('../features/memos/pages/MemoVideoReviewPage'));
const MemoDocumentReviewPage = lazy(() => import('../features/memos/pages/MemoDocumentReviewPage'));
const HomePage               = lazy(() => import('../features/home/HomePage'));
const MemoSearchPage         = lazy(() => import('../features/memos/pages/MemoSearchPage'));
const MemoEditPage           = lazy(() => import('../features/memos/pages/MemoEditPage'));
const GroupCreatePage        = lazy(() => import('../features/groups/pages/GroupCreatePage'));
const GroupOwnerPanelPage    = lazy(() => import('../features/groups/pages/GroupOwnerPanelPage'));
const GroupInviteAcceptPage  = lazy(() => import('../features/groups/pages/GroupInviteAcceptPage'));

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
  // Home / dashboard
  { path: '/', element: <HomePage /> },

  // Dev
  { path: '/dev/health', element: <HealthCheckPage /> },

  // Me / account
  { path: '/preferences', element: <UserPreferencesPage /> },

  // Memos — review pages
  { path: '/memos/text/review',     element: <MemoTextReviewPage /> },
  { path: '/memos/image/review',    element: <MemoImageReviewPage /> },
  { path: '/memos/audio/review',    element: <MemoAudioReviewPage /> },
  { path: '/memos/video/review',    element: <MemoVideoReviewPage /> },
  { path: '/memos/document/review', element: <MemoDocumentReviewPage /> },

  // Memos — search and CRUD
  { path: '/buscar',            element: <MemoSearchPage /> },
  { path: '/memos/:id/editar', element: <MemoEditPage /> },

  // Groups
  { path: '/grupos/novo',         element: <GroupCreatePage /> },
  { path: '/grupos/:id/painel',   element: <GroupOwnerPanelPage /> },
  { path: '/convite/grupo',       element: <GroupInviteAcceptPage /> },
];
