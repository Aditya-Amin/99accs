import Link from 'next/link';
import { getSupportArticles } from '@/lib/api/server';

export default async function ArticlesPage() {
  const res = await getSupportArticles().catch(() => null);
  const articles = res?.data ?? [];

  return (
    <main className="main-area fix">
      <section className="support__area section-py-120">
        <div className="container">
          <div className="row justify-content-center">
            <div className="col-lg-8">
              <div className="section__title text-center mb-50">
                <h2 className="title">Help Articles</h2>
              </div>
              <div style={{ display: 'flex', flexDirection: 'column', gap: 12 }}>
                {articles.map((article) => (
                  <Link key={article.id} href={`/support/articles/${article.slug}`} style={{ padding: '20px 24px', background: 'rgba(255,255,255,0.04)', border: '1px solid rgba(255,255,255,0.08)', borderRadius: 8, textDecoration: 'none', color: 'inherit', display: 'block' }}>
                    <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                      <div>
                        <h4 style={{ marginBottom: 4 }}>{article.title}</h4>
                        <span style={{ fontSize: '0.8em', opacity: 0.5, padding: '2px 8px', background: 'rgba(255,255,255,0.08)', borderRadius: 3 }}>{article.category}</span>
                      </div>
                      <span style={{ opacity: 0.4 }}>→</span>
                    </div>
                  </Link>
                ))}
              </div>
            </div>
          </div>
        </div>
      </section>
    </main>
  );
}
