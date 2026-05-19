'use client';
import { useState } from 'react';
import Link from 'next/link';
import { IconChevronLeft, IconRefresh } from '@/components/icons';

interface Comment {
  avatar: string;
  author: string;
  action: string;
  date: string;
  message: string;
}

const TICKET = {
  id: '#15941',
  orderNumber: '#AAAA12',
  title: 'Unauthorized Account Access from New Halaran',
  badge: 'New',
  badgeClass: 'country__code eu',
  icon: '/img/icons/valorant.svg',
};

const COMMENTS: Comment[] = [
  {
    avatar: '/img/images/comment_avatar01.png',
    author: 'Kelly Burn',
    action: 'replied',
    date: '5 hours ago',
    message: "That's a great question. While many boosting services take precautions (like using VPNs or playing carefully), it's important to note that boosting can violate Valorant's terms of service.",
  },
  {
    avatar: '/img/images/comment_avatar02.png',
    author: 'You',
    action: 'replied',
    date: '5 hours ago',
    message: 'Is it safe? Will my account get banned?',
  },
  {
    avatar: '/img/images/comment_avatar01.png',
    author: 'Kelly Burn',
    action: 'replied',
    date: '5 hours ago',
    message: "Hello! Thanks for reaching out 😊 I'd be happy to explain how Valorant Elo boosting works. Elo boosting is a service where a higher-ranked player helps increase your rank in Valorant by playing on your account or alongside you.",
  },
  {
    avatar: '/img/images/comment_avatar02.png',
    author: 'You',
    action: 'started the conversation',
    date: '5 hours ago',
    message: 'Hi, I want to know how Valorant Elo boosting works. Can you explain the process?',
  },
];

export function SupportPortalClient() {
  const [reply, setReply] = useState('');
  const [closeTicket, setCloseTicket] = useState(false);

  return (
    <section className="support__area section-pb-130">
      <div className="container">
        <div className="support__table-wrap">
          <div className="support__table-top-two">
            <div className="support__table-top-left">
              <Link href="/account/support" className="icon">
                <IconChevronLeft />
              </Link>
              <div className="order-info">
                <div className="thumb">
                  <img src={TICKET.icon} alt="icon" />
                </div>
                <div className="content">
                  <h2 className="title">
                    {TICKET.title}{' '}
                    <span className={TICKET.badgeClass}>{TICKET.badge}</span>
                  </h2>
                  <ul className="list-wrap">
                    <li>ID: {TICKET.id}</li>
                    <li>Order Number: {TICKET.orderNumber}</li>
                  </ul>
                </div>
              </div>
            </div>
            <div className="support__table-top-action">
              <ul className="list-wrap">
                <li>
                  <button type="button" className="compere">
                    <IconRefresh />
                  </button>
                </li>
                <li>
                  <Link href="/account/support" className="all">All</Link>
                </li>
                <li>
                  <button type="button" className="tg-btn">Close Ticket</button>
                </li>
              </ul>
            </div>
          </div>

          <form
            className="support__table-form-two"
            onSubmit={(e) => e.preventDefault()}
          >
            <div className="form-grp">
              <textarea
                name="message"
                placeholder="Click here to write a reply"
                value={reply}
                onChange={(e) => setReply(e.target.value)}
              />
            </div>
            <div className="support__table-form-bottom">
              <div className="left-side">
                <div className="upload-box">
                  <input type="file" id="fileInput" hidden />
                  <label htmlFor="fileInput" className="upload-btn">
                    CLICK TO UPLOAD
                  </label>
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
                  />
                  <label htmlFor="ticket">Close Ticket</label>
                </div>
                <button type="submit" className="tg-btn">Reply</button>
              </div>
            </div>
          </form>

          <div className="support__comment-wrap">
            {COMMENTS.map((comment, i) => (
              <div key={i} className="support__comment-item">
                <div className="thumb">
                  <img src={comment.avatar} alt="" />
                </div>
                <div className="content">
                  <div className="content-top">
                    <h2 className="title">
                      {comment.author} <span>{comment.action}</span>
                    </h2>
                    <span className="date">{comment.date}</span>
                  </div>
                  <p>{comment.message}</p>
                </div>
              </div>
            ))}
          </div>
        </div>
      </div>
    </section>
  );
}
