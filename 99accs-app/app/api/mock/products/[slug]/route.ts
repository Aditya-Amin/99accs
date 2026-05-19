import { NextRequest, NextResponse } from 'next/server';
import valorant from '@/mocks/products/valorant.json';
import fortnite from '@/mocks/products/fortnite.json';
import legends from '@/mocks/products/legends.json';

const all = [...valorant, ...fortnite, ...legends];

export async function GET(_req: NextRequest, { params }: { params: Promise<{ slug: string }> }) {
  const { slug } = await params;
  const product = all.find((p) => p.slug === slug);
  if (!product) return NextResponse.json({ message: 'Not found' }, { status: 404 });

  const related = all.filter((p) => p.game === product.game && p.id !== product.id).slice(0, 4);
  return NextResponse.json({ data: { ...product, related } });
}
