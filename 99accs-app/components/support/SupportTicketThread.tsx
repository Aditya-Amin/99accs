'use client';
import { useState, FormEvent } from 'react';
import Link from 'next/link';
import { useRouter } from 'next/navigation';
import { IconChevronLeft, IconRefresh } from '@/components/icons';
import type { SupportTicket, Game, SupportTicketStatus } from '@/lib/api/types';

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
  if (minutes < 60) return RELATIVE_FMT.format(-minutes, 'minute');
  const hours = Math.round(minutes / 60);
  if (hours < 24) return RELATIVE_FMT.format(-hours, 'hour');
  const days = Math.round(hours / 24);
  if (days < 30) return RELATIVE_FMT.format(-days, 'day');
  const months = Math.round(days / 30);
  return RELATIVE_FMT.format(-months, 'month');
}

interface Props {
  ticket: SupportTicket;
}

// Single-ticket conversation view — mirrors support-3.html. Renders the
// ticket header, the reply form, and the thread of messages (newest first
// in HTML reference, so the messages array is reversed for display).
export default function SupportTicketThread({ ticket }: Props) {
  const router = useRouter();
  const [reply, setReply] = useState('');
  const [closeTicket, setCloseTicket] = useState(false);
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const messagesDesc = [...(ticket.messages ?? [])].reverse();

  const onSubmit = async (e: FormEvent<HTMLFormElement>) => {
    e.preventDefault();
    if (!reply.trim()) return;
    setError(null);
    setSubmitting(true);
    try {
      const res = await fetch(`/api/mock/support/tickets/${ticket.id}/replies`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ body: reply, close_ticket: closeTicket }),
        credentials: 'include',
      });
      if (!res.ok) throw new Error('Failed to send reply');
      setReply('');
      setCloseTicket(false);
      router.refresh();
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to send reply');
    } finally {
      setSubmitting(false);
    }
  };

  const onCloseTicket = async () => {
    if (ticket.status === 'closed') return;
    setError(null);
    try {
      const res = await fetch(`/api/mock/support/tickets/${ticket.id}`, {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ status: 'closed' }),
        credentials: 'include',
      });
      if (!res.ok) throw new Error('Failed to close ticket');
      router.refresh();
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to close ticket');
    }
  };

  return (
    <section className="support__area section-pb-130">
      <div className="container">
        <div className="support__table-wrap">
          <div className="support__table-top-two">
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
                    <span className={STATUS_BADGE_CLASS[ticket.status]}>{STATUS_LABEL[ticket.status]}</span>
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
                  <button type="button" className="compere" onClick={() => router.refresh()} aria-label="Refresh">
                    <IconRefresh />
                  </button>
                </li>
                <li>
                  <Link href="/support/tickets" className="all">All</Link>
                </li>
                <li>
                  <button
                    type="button"
                    className="tg-btn"
                    onClick={onCloseTicket}
                    disabled={ticket.status === 'closed'}
                  >
                    {ticket.status === 'closed' ? 'Ticket Closed' : 'Close Ticket'}
                  </button>
                </li>
              </ul>
            </div>
          </div>

          <form className="support__table-form-two" onSubmit={onSubmit}>
            <div className="form-grp">
              <textarea
                name="message"
                placeholder="Click here to write a reply"
                value={reply}
                onChange={(e) => setReply(e.target.value)}
                disabled={ticket.status === 'closed' || submitting}
              />
            </div>
            <div className="support__table-form-bottom">
              <div className="left-side">
                <div className="upload-box">
                  <input type="file" id="fileInput" hidden />
                  <label htmlFor="fileInput" className="upload-btn">CLICK TO UPLOAD</label>
                </div>
                <span>Supported Types: Photos and max file size: 5.0MB</span>
              </div>
              <div className="right-side">
                <div className="ticket-check">
                  <input
                    type="checkbox"
                    id="ticket"
                    className="form-check-input"
                    checked={closeTicket}
                    onChange={(e) => setCloseTicket(e.target.checked)}
                    disabled={ticket.status === 'closed'}
                  />
                  <label htmlFor="ticket">Close Ticket</label>
                </div>
                {error && <span style={{ color: 'crimson', marginRight: 12 }}>{error}</span>}
                <button type="submit" className="tg-btn" disabled={submitting || ticket.status === 'closed' || !reply.trim()}>
                  {submitting ? 'Sending…' : 'Reply'}
                </button>
              </div>
            </div>
          </form>

          <div className="support__comment-wrap">
            {messagesDesc.map((m) => (
              <div key={m.id} className="support__comment-item">
                <div className="thumb">
                  <img src={m.author_avatar} alt="" />
                </div>
                <div className="content">
                  <div className="content-top">
                    <h2 className="title">
                      {m.author_name}{' '}
                      <span>{m.is_opening && m.is_owner ? 'started the conversation' : 'replied'}</span>
                    </h2>
                    <span className="date">{timeAgo(m.created_at)}</span>
                  </div>
                  <p>
                    {m.body.split('\n').map((line, i, arr) => (
                      <span key={i}>
                        {line}
                        {i < arr.length - 1 && <br />}
                      </span>
                    ))}
                  </p>
                </div>
              </div>
            ))}
          </div>
        </div>
      </div>
    </section>
  );
}
