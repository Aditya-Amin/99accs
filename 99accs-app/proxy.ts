import { NextResponse, type NextRequest } from 'next/server';

// Routes that require an authenticated user. (account)/layout.tsx also does a
// server-side getToken() check for defense in depth; the proxy is the fast path.
//
// /support/* is intentionally NOT in this list. The portal is browsable by
// guests; only the "Create ticket" action requires auth, and
// SupportPortalCta opens the AuthModal for unauthenticated clicks.
// /support/tickets and /support/tickets/new have their own server-side
// guards that redirect guests back to /support (where the modal CTA lives).
const PROTECTED = [
  /^\/account(\/|$)/,
  /^\/checkout(\/|$)/,
  /^\/order(\/|$)/,
];

// Auth-flow pages — block already-authenticated users from re-entering them.
// /reset-password is intentionally excluded: a user with the reset cookie but
// no auth cookie MUST be allowed to reach this page (it's their only path out).
const AUTH_PAGES = [/^\/login(\/|$)/, /^\/register(\/|$)/, /^\/forgot-password(\/|$)/];

export function proxy(req: NextRequest) {
  const authToken = req.cookies.get('99accs_token')?.value;
  const resetToken = req.cookies.get('99accs_reset_token')?.value;
  const path = req.nextUrl.pathname;

  // Hard block: a user mid-migration (reset cookie set, no real auth) can only
  // reach /reset-password. Every other URL bounces them back to it. This is
  // the strict block-all-other-routes behaviour the spec requires.
  if (resetToken && !authToken && !path.startsWith('/reset-password')) {
    const url = req.nextUrl.clone();
    url.pathname = '/reset-password';
    url.search = '';
    return NextResponse.redirect(url);
  }

  // Unauthenticated user trying to reach a protected route — send to /login
  // with a redirect param so login can return them after success.
  if (!authToken && PROTECTED.some((r) => r.test(path))) {
    const url = req.nextUrl.clone();
    url.pathname = '/login';
    url.search = `?redirect=${encodeURIComponent(path + req.nextUrl.search)}`;
    return NextResponse.redirect(url);
  }

  // Already authenticated — don't show login/register again.
  if (authToken && AUTH_PAGES.some((r) => r.test(path))) {
    const url = req.nextUrl.clone();
    url.pathname = '/account';
    url.search = '';
    return NextResponse.redirect(url);
  }

  return NextResponse.next();
}

// Broad matcher so the hard-block rule can fire on ANY page request. Excludes
// API routes (those check auth server-side themselves), Next internals, and
// favicon/static folders. The path-to-regexp parser dislikes complex regex
// alternations with end-anchors, so we keep this canonical-doc style.
export const config = {
  matcher: ['/((?!api|_next|favicon.ico|img|fonts).*)'],
};
