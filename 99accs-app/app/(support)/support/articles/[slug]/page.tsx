import { notFound } from 'next/navigation';
import Link from 'next/link';
import { getSupportArticle } from '@/lib/api/endpoints';

interface Props {
  params: Promise<{ slug: string }>;
}

export default async function ArticlePage({ params }: Props) {
  const { slug } = await params;
  const res = await getSupportArticle(slug).catch(() => null);
  if (!res) notFound();

  const article = res.data;

  return (
    <main className="main-area fix">
      <section className="support__area section-py-120">
        <div className="container">
          <div className="row justify-content-center">
            <div className="col-lg-8">
              <Link href="/support/articles" style={{ opacity: 0.6, textDecoration: 'none', display: 'inline-flex', alignItems: 'center', gap: 4, marginBottom: 24 }}>
                ← Back to Articles
              </Link>
              <span style={{ display: 'block', fontSize: '0.8em', opacity: 0.5, padding: '2px 8px', background: 'rgba(255,255,255,0.08)', borderRadius: 3, marginBottom: 16, width: 'fit-content' }}>{article.category}</span>
              <h1 style={{ marginBottom: 24 }}>{article.title}</h1>
              <div style={{ padding: 32, background: 'rgba(255,255,255,0.04)', border: '1px solid rgba(255,255,255,0.08)', borderRadius: 8, lineHeight: 1.8 }}>
                <p>{article.content}</p>
              </div>
              <div style={{ marginTop: 32, padding: 24, background: 'rgba(255,255,255,0.02)', border: '1px solid rgba(255,255,255,0.06)', borderRadius: 8 }}>
                <p style={{ opacity: 0.7 }}>Still need help?</p>
                <Link href="/support/contact" className="tg-btn" style={{ display: 'inline-block', marginTop: 12 }}>Contact Support</Link>
              </div>
            </div>
          </div>
        </div>
      </section>
    </main>
  );
}
