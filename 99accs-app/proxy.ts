import { NextResponse, type NextRequest } from 'next/server';

// Routes that require an authenticated user. (account)/layout.tsx also does a
// server-side getToken() check for defense in depth; the proxy is the fast path.
//
// /checkout/* is intentionally NOT in this list — guest checkout is allowed.
// The guest contact form is collected client-side and the order is gated by
// the unguessable checkout_token UUID instead of an auth cookie.
//
// /order/* is intentionally NOT in this list — /order/[token]/received is
// a public guest page gated only by the unguessable checkout_token UUID.
// The (account)/order/[token]/confirmation page is protected by its layout.
//
// /support/* is also browsable by guests; only the "Create ticket" action
// requires auth.
const PROTECTED = [
  /^\/account(\/|$)/,
];

// Auth-flow pages — block already-authenticated users from re-entering them.
const AUTH_PAGES = [/^\/login(\/|$)/, /^\/register(\/|$)/, /^\/forgot-password(\/|$)/];

export function proxy(req: NextRequest) {
  const authToken = req.cookies.get('99accs_token')?.value;
  const path = req.nextUrl.pathname;

  // NOTE: legacy/migrated users are NOT trapped here. When they try to log in,
  // Laravel returns LEGACY_PASSWORD_RESET_REQUIRED and the login UI shows a
  // reset notice inline. They remain guests (no auth cookie) and can browse the
  // whole site freely until they reset their password and sign in.

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
