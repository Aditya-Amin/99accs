'use client';

import { useEffect, useRef, useState, type MutableRefObject } from 'react';
import { usePathname } from 'next/navigation';

// YouTube-style top progress bar for App Router.
//
// App Router doesn't expose router.events like Pages Router did. We detect
// nav START by intercepting clicks on internal <a> tags (capture phase, so
// we beat Next's own click handler) and `popstate` for back/forward. We
// detect nav END by watching the `pathname` returned by usePathname() —
// when it changes, the navigation has resolved.
//
// Anti-flicker:
//   - DEBOUNCE_MS: ignore navs that finish in under 150ms (don't show the bar
//     at all for cached/instant routes).
//   - MIN_VISIBLE_MS: once we've decided to show, keep it on screen at least
//     200ms so the user actually sees it rather than a 1-frame flash.
//
// Watches pathname only (not searchParams) — using useSearchParams() here
// would opt the entire app out of static rendering. ?query-only nav events
// are uncommon in this app; clicking a link that only changes the query
// still triggers the click handler and animates briefly.

const DEBOUNCE_MS = 150;
const MIN_VISIBLE_MS = 200;
const FINISH_FADE_MS = 200;
const TRICKLE_TARGET = 80;
const TRICKLE_INTERVAL_MS = 300;
// Maximum lifetime of a single nav. If pathname never changes (cancelled
// nav, network error, replaceState without real navigation) the bar
// auto-completes after this so it never gets stuck on screen.
const SAFETY_TIMEOUT_MS = 8000;

export default function TopProgressBar() {
  const pathname = usePathname();

  // -1 == hidden. 0..100 visible.
  const [progress, setProgress] = useState(-1);

  const navStartRef = useRef<number | null>(null);
  const showTimerRef = useRef<number | null>(null);
  const trickleTimerRef = useRef<number | null>(null);
  const hideTimerRef = useRef<number | null>(null);
  const safetyTimerRef = useRef<number | null>(null);
  const visibleSinceRef = useRef<number | null>(null);

  const clearTimer = (ref: MutableRefObject<number | null>) => {
    if (ref.current !== null) {
      window.clearTimeout(ref.current);
      ref.current = null;
    }
  };

  const start = () => {
    // Already navigating — leave existing state alone.
    if (navStartRef.current !== null) return;
    navStartRef.current = performance.now();

    clearTimer(showTimerRef);
    clearTimer(hideTimerRef);
    clearTimer(safetyTimerRef);

    // Safety net: if no pathname change arrives within the budget, force
    // completion so the bar can't stick on screen forever.
    safetyTimerRef.current = window.setTimeout(() => {
      // Pretend we became visible if we haven't already, so complete()
      // takes the full fade-out path instead of bailing.
      if (visibleSinceRef.current === null) {
        visibleSinceRef.current = performance.now() - MIN_VISIBLE_MS;
      }
      complete();
    }, SAFETY_TIMEOUT_MS);

    // Wait the debounce window before painting anything.
    showTimerRef.current = window.setTimeout(() => {
      visibleSinceRef.current = performance.now();
      setProgress(0);
      // Next frame: jump to 30%. CSS transition smooths it.
      requestAnimationFrame(() => {
        setProgress(30);
        let p = 30;
        const trickle = () => {
          p = Math.min(TRICKLE_TARGET, p + 3 + Math.random() * 6);
          setProgress(p);
          if (p < TRICKLE_TARGET) {
            trickleTimerRef.current = window.setTimeout(trickle, TRICKLE_INTERVAL_MS);
          }
        };
        trickleTimerRef.current = window.setTimeout(trickle, TRICKLE_INTERVAL_MS);
      });
    }, DEBOUNCE_MS);
  };

  const complete = () => {
    if (navStartRef.current === null) return;
    navStartRef.current = null;
    clearTimer(showTimerRef);
    clearTimer(trickleTimerRef);
    clearTimer(safetyTimerRef);

    // Bar never reached visible state — nav was faster than debounce window.
    // Nothing to do; no flicker.
    if (visibleSinceRef.current === null) {
      setProgress(-1);
      return;
    }

    const visibleFor = performance.now() - visibleSinceRef.current;
    const wait = Math.max(0, MIN_VISIBLE_MS - visibleFor);

    hideTimerRef.current = window.setTimeout(() => {
      setProgress(100);
      hideTimerRef.current = window.setTimeout(() => {
        setProgress(-1);
        visibleSinceRef.current = null;
      }, FINISH_FADE_MS);
    }, wait);
  };

  // Four nav-START signals:
  //   1. click on internal <a>  → handles <Link> + plain anchors
  //   2. popstate               → browser back/forward
  //   3. history.pushState/replaceState patch → programmatic router.push()
  //      from anywhere in the app (auth flows, form submits, useEffect
  //      redirects). Without this, slow programmatic navs show no bar.
  //   4. beforeunload           → full page reload (F5 / Ctrl+R / browser
  //      reload button) and address-bar nav. The current page is still
  //      visible while the browser fetches new HTML, so the bar appears on
  //      top of the outgoing page until it's torn down by the new render.
  useEffect(() => {
    const handleClick = (e: MouseEvent) => {
      if (e.defaultPrevented) return;
      if (e.button !== 0) return;
      if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;

      const anchor = (e.target as HTMLElement | null)?.closest('a');
      if (!anchor) return;
      if (anchor.target && anchor.target !== '_self') return;
      if (anchor.hasAttribute('download')) return;

      const rawHref = anchor.getAttribute('href');
      if (!rawHref) return;
      if (rawHref.startsWith('#') || rawHref.startsWith('mailto:') || rawHref.startsWith('tel:')) return;

      let url: URL;
      try {
        url = new URL(anchor.href);
      } catch {
        return;
      }
      if (url.origin !== window.location.origin) return;
      // Same URL — no navigation happens, don't show the bar.
      if (url.pathname === window.location.pathname && url.search === window.location.search) return;

      start();
    };

    const handlePopState = () => start();
    const handleBeforeUnload = () => start();

    // Patch history. Stash originals so we can restore (and to call through).
    // Guard against doubled patches if this component re-mounts in StrictMode.
    type HistoryPatched = History & { __tgPatched?: boolean };
    const h = window.history as HistoryPatched;
    const origPush = window.history.pushState.bind(window.history);
    const origReplace = window.history.replaceState.bind(window.history);

    // Only trigger start() when the call would change the pathname. Both
    // Next and other libraries call pushState/replaceState with the same
    // pathname for sub-route updates (scroll position, hash changes, query-
    // only updates). Without this filter, the bar starts but pathname never
    // changes → complete() never fires → bar stays on screen.
    const maybeStart = (url: unknown) => {
      if (url == null) {
        start();
        return;
      }
      try {
        const next = new URL(String(url), window.location.href);
        if (next.pathname !== window.location.pathname) start();
      } catch {
        start();
      }
    };

    if (!h.__tgPatched) {
      h.__tgPatched = true;
      window.history.pushState = function (...args) {
        maybeStart(args[2]);
        return origPush(...args);
      } as typeof window.history.pushState;
      window.history.replaceState = function (...args) {
        maybeStart(args[2]);
        return origReplace(...args);
      } as typeof window.history.replaceState;
    }

    document.addEventListener('click', handleClick, { capture: true });
    window.addEventListener('popstate', handlePopState);
    window.addEventListener('beforeunload', handleBeforeUnload);

    return () => {
      document.removeEventListener('click', handleClick, { capture: true });
      window.removeEventListener('popstate', handlePopState);
      window.removeEventListener('beforeunload', handleBeforeUnload);
      // Restore originals on unmount so HMR cleanly resets between dev edits.
      if (h.__tgPatched) {
        window.history.pushState = origPush;
        window.history.replaceState = origReplace;
        h.__tgPatched = false;
      }
    };
  }, []);

  // pathname change → complete. (No-op on initial mount: start() was never called.)
  useEffect(() => {
    complete();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [pathname]);

  // Cleanup on unmount.
  useEffect(() => {
    return () => {
      clearTimer(showTimerRef);
      clearTimer(trickleTimerRef);
      clearTimer(hideTimerRef);
      clearTimer(safetyTimerRef);
    };
  }, []);

  if (progress < 0) return null;

  const isFinishing = progress === 100;

  return (
    <div
      aria-hidden
      style={{
        position: 'fixed',
        top: 0,
        left: 0,
        right: 0,
        height: 3,
        zIndex: 9999,
        pointerEvents: 'none',
      }}
    >
      <div
        style={{
          height: '100%',
          width: `${progress}%`,
          background: 'var(--tg-theme-primary, #00FC70)',
          boxShadow:
            '0 0 10px var(--tg-theme-primary, #00FC70), 0 0 5px var(--tg-theme-primary, #00FC70)',
          transition: isFinishing
            ? `width ${FINISH_FADE_MS}ms ease-out, opacity ${FINISH_FADE_MS}ms ease-out`
            : 'width 300ms ease-out',
          opacity: isFinishing ? 0 : 1,
        }}
      />
    </div>
  );
}
