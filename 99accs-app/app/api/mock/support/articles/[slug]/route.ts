import { NextRequest, NextResponse } from 'next/server';
import articles from '@/mocks/support/articles.json';

export async function GET(_req: NextRequest, { params }: { params: Promise<{ slug: string }> }) {
  const { slug } = await params;
  const article = articles.find((a) => a.slug === slug);
  if (!article) return NextResponse.json({ message: 'Not found' }, { status: 404 });
  return NextResponse.json({ data: article });
}
