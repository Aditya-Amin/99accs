'use client';
import { useState, useRef, useEffect, useCallback, FormEvent } from 'react';
import Link from 'next/link';
import { IconChevronLeft, IconRefresh } from '@/components/icons';
import type { SupportTicket, SupportTicketMessage, Game, SupportTicketStatus } from '@/lib/api/types';

const POLL_MS = 5000;

const GAME_ICON: Record<Game, string> = {
  valorant: '/img/icons/valorant.svg',
  fortnite: '/img/icons/fortnite.svg',
  legends:  '/img/icons/league.svg',
};

// Status badge color modifier shared with the support table — sits next to
// the ticket title in the header bar.
const STATUS_BADGE_CLASS: Record<SupportTicketStatus, string> = {
  new:    'country__code eu',
  open:   'country__code br',
  closed: 'country__code latam',
};

const STATUS_LABEL: Record<SupportTicketStatus, string> = {
  new:    'New',
  open:   'Open',
  closed: 'Closed',
};

const RELATIVE_FMT = new Intl.RelativeTimeFormat('en', { numeric: 'auto' });

function timeAgo(iso: string): string {
  const diffMs = Date.now() - new Date(iso).getTime();
  const minutes = Math.round(diffMs / 60000);
  if (minutes < 1) return 'just now';
  if (minutes < 60) return RELATIVE_FMT.format(-minutes, 'minute');
  const hours = Math.round(minutes / 60);
  if (hours < 24) return RELATIVE_FMT.format(-hours, 'hour');
  const days = Math.round(hours / 24);
  if (days < 30) return RELATIVE_FMT.format(-days, 'day');
  const months = Math.round(days / 30);
  return RELATIVE_FMT.format(-months, 'month');
}

// Signature of a thread used to skip redundant re-renders while polling.
function signature(messages: SupportTicketMessage[], status: SupportTicketStatus): string {
  return `${status}:${messages.length}:${messages[messages.length - 1]?.id ?? 0}`;
}

interface Props {
  ticket: SupportTicket;
}

// Single-ticket conversation — a chat-style thread (Telegram/WhatsApp feel)
// that live-updates by polling the BFF every 5s. Own messages sit on the
// right in the brand-green bubble, staff replies on the left with their avatar.
export default function SupportTicketThread({ ticket }: Props) {
  const [messages, setMessages] = useState<SupportTicketMessage[]>(ticket.messages ?? []);
  const [status, setStatus] = useState<SupportTicketStatus>(ticket.status);
  const [reply, setReply] = useState('');
  const [closeTicket, setCloseTicket] = useState(false);
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const isClosed = status === 'closed';

  const windowRef = useRef<HTMLDivElement>(null);
  const firstScrollRef = useRef(true);
  // Last-seen thread signature — guards against re-rendering on unchanged polls.
  const sigRef = useRef(signature(messages, status));

  // Pull the latest thread from the BFF and reconcile only when something changed.
  const sync = useCallback(async () => {
    try {
      const res = await fetch(`/api/support/tickets/${ticket.id}`, { credentials: 'include' });
      if (!res.ok) return;
      const json = await res.json();
      const fresh = json.data as SupportTicket;
      const next = fresh.messages ?? [];
      const sig = signature(next, fresh.status);
      if (sig !== sigRef.current) {
        sigRef.current = sig;
        setMessages(next);
        setStatus(fresh.status);
      }
    } catch {
      // Transient network/API error — keep the current view; next tick retries.
    }
  }, [ticket.id]);

  // Poll every 5s, paused while the tab is hidden (and refreshed on re-focus).
  useEffect(() => {
    let timer: ReturnType<typeof setInterval> | null = null;
    const start = () => { if (!timer) timer = setInterval(sync, POLL_MS); };
    const stop = () => { if (timer) { clearInterval(timer); timer = null; } };
    const onVisibility = () => {
      if (document.hidden) { stop(); }
      else { void sync(); start(); }
    };

    if (!document.hidden) start();
    document.addEventListener('visibilitychange', onVisibility);
    return () => { stop(); document.removeEventListener('visibilitychange', onVisibility); };
  }, [sync]);

  // Auto-scroll: snap to bottom on first paint; afterwards only if the user is
  // already near the bottom (don't yank them up-thread while they're reading).
  useEffect(() => {
    const el = windowRef.current;
    if (!el) return;
    if (firstScrollRef.current) {
      firstScrollRef.current = false;
      el.scrollTop = el.scrollHeight;
      return;
    }
    const nearBottom = el.scrollHeight - el.scrollTop - el.clientHeight < 140;
    if (nearBottom) el.scrollTop = el.scrollHeight;
  }, [messages.length]);

  const onSubmit = async (e: FormEvent<HTMLFormElement>) => {
    e.preventDefault();
    if (!reply.trim()) return;
    setError(null);
    setSubmitting(true);
    try {
      const res = await fetch(`/api/support/tickets/${ticket.id}/replies`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ body: reply, close_ticket: closeTicket }),
        credentials: 'include',
      });
      if (!res.ok) throw new Error('Failed to send reply');
      setReply('');
      setCloseTicket(false);
      await sync(); // pull the new message (+ any status change) authoritatively
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to send reply');
    } finally {
      setSubmitting(false);
    }
  };

  const onCloseTicket = async () => {
    if (isClosed) return;
    setError(null);
    try {
      const res = await fetch(`/api/support/tickets/${ticket.id}`, {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ status: 'closed' }),
        credentials: 'include',
      });
      if (!res.ok) throw new Error('Failed to close ticket');
      setStatus('closed');
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to close ticket');
    }
  };

  return (
    <section className="support__area section-pb-130">
      <style dangerouslySetInnerHTML={{ __html: CHAT_CSS }} />
      <div className="container">
        <div className="support__table-wrap chat-shell">

          {/* ── Header bar ─────────────────────────────────────────────── */}
          <div className="support__table-top-two chat-header">
            <div className="support__table-top-left">
              <Link href="/support/tickets" className="icon" aria-label="Back to tickets">
                <IconChevronLeft />
              </Link>
              <div className="order-info">
                <div className="thumb">
                  <img src={GAME_ICON[ticket.game]} alt={ticket.game} />
                </div>
                <div className="content">
                  <h2 className="title">
                    {ticket.subject}{' '}
                    <span className={STATUS_BADGE_CLASS[status]}>{STATUS_LABEL[status]}</span>
                  </h2>
                  <ul className="list-wrap">
                    <li>ID: {ticket.ticket_number}</li>
                    {ticket.order_number && <li>Order Number: {ticket.order_number}</li>}
                  </ul>
                </div>
              </div>
            </div>
            <div className="support__table-top-action">
              <ul className="list-wrap">
                <li>
                  <button type="button" className="compere" onClick={() => void sync()} aria-label="Refresh">
                    <IconRefresh />
                  </button>
                </li>
                <li>
                  <Link href="/support/tickets" className="all">All</Link>
                </li>
                <li>
                  <button type="button" className="tg-btn" onClick={onCloseTicket} disabled={isClosed}>
                    {isClosed ? 'Ticket Closed' : 'Close Ticket'}
                  </button>
                </li>
              </ul>
            </div>
          </div>

          {/* ── Chat window ────────────────────────────────────────────── */}
          <div className="chat-window" ref={windowRef}>
            {messages.length === 0 && (
              <p className="chat-empty">No messages yet.</p>
            )}
            {messages.map((m) => {
              const own = m.is_owner;
              return (
                <div key={m.id} className={`chat-row ${own ? 'chat-row--own' : 'chat-row--other'}`}>
                  {!own && (
                    <img className="chat-avatar" src={m.author_avatar} alt="" />
                  )}
                  <div className="chat-bubble-wrap">
                    <div className="chat-meta">
                      <span className="chat-author">{own ? 'You' : m.author_name}</span>
                      <span className="chat-time">{timeAgo(m.created_at)}</span>
                    </div>
                    <div className="chat-bubble">
                      {m.body.split('\n').map((line, i, arr) => (
                        <span key={i}>
                          {line}
                          {i < arr.length - 1 && <br />}
                        </span>
                      ))}
                    </div>
                  </div>
                </div>
              );
            })}
          </div>

          {/* ── Composer ───────────────────────────────────────────────── */}
          {isClosed && (
            <div className="chat-closed-note">
              This ticket is closed — sending a message will reopen it.
            </div>
          )}
          <form className="chat-composer" onSubmit={onSubmit}>
            <textarea
              className="chat-input"
              name="message"
              placeholder={isClosed ? 'Reply to reopen this ticket…' : 'Write a message…'}
              value={reply}
              onChange={(e) => setReply(e.target.value)}
              disabled={submitting}
              rows={1}
              onKeyDown={(e) => {
                // Enter sends, Shift+Enter makes a newline (chat convention).
                if (e.key === 'Enter' && !e.shiftKey) {
                  e.preventDefault();
                  (e.currentTarget.form as HTMLFormElement)?.requestSubmit();
                }
              }}
            />
            <div className="chat-composer-side">
              {!isClosed && (
                <label className="chat-close-check">
                  <input
                    type="checkbox"
                    checked={closeTicket}
                    onChange={(e) => setCloseTicket(e.target.checked)}
                  />
                  Close after reply
                </label>
              )}
              {error && <span className="chat-error">{error}</span>}
              <button type="submit" className="tg-btn chat-send" disabled={submitting || !reply.trim()}>
                {submitting ? 'Sending…' : 'Send'}
              </button>
            </div>
          </form>

        </div>
      </div>
    </section>
  );
}

// Colocated chat styling. Dark theme; own bubbles use the brand green
// (--tg-theme-primary), staff bubbles a translucent panel. Prefixed `chat-`
// so it can't collide with the template's global classes.
const CHAT_CSS = `
.chat-shell { padding: 0; overflow: hidden; }
.chat-header { margin-bottom: 0; }

.chat-window {
  display: flex;
  flex-direction: column;
  gap: 18px;
  padding: 28px 26px;
  max-height: min(60vh, 620px);
  overflow-y: auto;
  scroll-behavior: smooth;
}
.chat-window::-webkit-scrollbar { width: 6px; }
.chat-window::-webkit-scrollbar-thumb { background: rgba(255,255,255,.14); border-radius: 6px; }

.chat-empty { text-align: center; opacity: .5; padding: 40px 0; }

.chat-row { display: flex; align-items: flex-end; gap: 10px; max-width: 100%; }
.chat-row--own { flex-direction: row-reverse; }

.chat-avatar {
  width: 38px; height: 38px; border-radius: 50%;
  object-fit: cover; flex-shrink: 0;
  border: 1px solid rgba(255,255,255,.12);
}

.chat-bubble-wrap { display: flex; flex-direction: column; max-width: 72%; min-width: 0; }

.chat-meta {
  display: flex; gap: 8px; align-items: baseline;
  margin-bottom: 5px; font-size: 12px; line-height: 1;
}
.chat-row--own .chat-meta { flex-direction: row-reverse; }
.chat-author { font-weight: 600; color: #fff; opacity: .85; }
.chat-time { color: #fff; opacity: .45; }

.chat-bubble {
  padding: 11px 15px;
  border-radius: 16px;
  line-height: 1.55;
  font-size: 15px;
  word-break: break-word;
  white-space: normal;
}
.chat-row--other .chat-bubble {
  background: rgba(255,255,255,.07);
  color: #fff;
  border-top-left-radius: 5px;
}
.chat-row--own .chat-bubble {
  background: var(--tg-theme-primary, #00FC70);
  color: var(--tg-color-dark, #000E06);
  border-top-right-radius: 5px;
  font-weight: 500;
}

.chat-closed-note {
  margin: 0 26px 26px;
  padding: 16px;
  text-align: center;
  border: 1px dashed rgba(255,255,255,.15);
  border-radius: 12px;
  opacity: .7;
}

.chat-composer {
  display: flex;
  align-items: flex-end;
  gap: 14px;
  margin: 0 26px 26px;
  padding: 12px 12px 12px 18px;
  background: rgba(255,255,255,.04);
  border: 1px solid rgba(255,255,255,.1);
  border-radius: 14px;
}
.chat-input {
  flex: 1 1 auto;
  min-width: 0;
  background: transparent;
  border: none;
  outline: none;
  color: inherit;
  resize: none;
  font-size: 15px;
  line-height: 1.5;
  max-height: 160px;
  padding: 8px 0;
}
.chat-composer-side { display: flex; align-items: center; gap: 14px; flex-shrink: 0; }
.chat-close-check {
  display: inline-flex; align-items: center; gap: 7px;
  font-size: 13px; opacity: .75; cursor: pointer; user-select: none; margin: 0;
}
.chat-close-check input { accent-color: var(--tg-theme-primary, #00FC70); }
.chat-error { color: crimson; font-size: 13px; }
.chat-send { padding: 13px 22px; }
.chat-send:disabled { opacity: .5; cursor: not-allowed; }

@media (max-width: 575px) {
  .chat-window { padding: 18px 14px; }
  .chat-bubble-wrap { max-width: 82%; }
  .chat-composer { flex-direction: column; align-items: stretch; margin: 0 14px 18px; }
  .chat-composer-side { justify-content: space-between; }
}
`;
