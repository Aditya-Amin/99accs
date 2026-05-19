'use client';
import { useEffect } from 'react';

// Sequentially injects the legacy jQuery + plugins + main.js bundle into
// <head>. Loaded via `useEffect` rather than <Script>/<script> elements so
// React 19 doesn't flag "script tag while rendering React component" — these
// libraries are static assets in /public, not Next-managed dependencies.
//
// Order matters: jQuery first, then its plugins, then main.js which calls into
// them on $(document).ready(). Each script is awaited via its `load` event
// before the next is appended so the plugins never run before jQuery is on
// window.

const VENDOR_SCRIPTS = [
  '/jquery-3.6.0.min.js',
  '/jquery.magnific-popup.min.js',
  '/jquery.appear.js',
  '/swiper-bundle.min.js',
  '/jquery.counterup.min.js',
  '/particles.min.js',
  '/jarallax.min.js',
  '/wow.min.js',
  '/main.js',
];

declare global {
  interface Window {
    __vendorScriptsLoaded?: boolean;
  }
}

export default function VendorScripts() {
  useEffect(() => {
    if (window.__vendorScriptsLoaded) return;
    window.__vendorScriptsLoaded = true;

    let cancelled = false;

    (async () => {
      for (const src of VENDOR_SCRIPTS) {
        if (cancelled) return;
        if (document.querySelector(`script[data-vendor="${src}"]`)) continue;

        await new Promise<void>((resolve, reject) => {
          const s = document.createElement('script');
          s.src = src;
          s.async = false;
          s.dataset.vendor = src;
          s.onload = () => resolve();
          s.onerror = () => reject(new Error(`Failed to load vendor script: ${src}`));
          document.head.appendChild(s);
        });
      }
    })().catch((err) => console.error('[VendorScripts]', err));

    return () => {
      cancelled = true;
    };
  }, []);

  return null;
}
