import { NextRequest, NextResponse } from 'next/server';
import articles from '@/mocks/support/articles.json';

export async function GET(req: NextRequest) {
  const category = req.nextUrl.searchParams.get('category');
  const results = category ? articles.filter((a) => a.category === category) : articles;
  return NextResponse.json({
    data: results,
    meta: { current_page: 1, last_page: 1, per_page: results.length, total: results.length },
    links: { first: null, last: null, next: null, prev: null },
  });
}
