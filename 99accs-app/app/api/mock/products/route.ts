import { NextRequest, NextResponse } from 'next/server';
import valorant from '@/mocks/products/valorant.json';
import fortnite from '@/mocks/products/fortnite.json';
import legends from '@/mocks/products/legends.json';
import type { Product } from '@/lib/api/types';

const all = [...valorant, ...fortnite, ...legends] as unknown as Product[];

export async function GET(req: NextRequest) {
  const { searchParams } = req.nextUrl;
  const game = searchParams.get('game');
  const accountType = searchParams.get('account_type');
  const section = searchParams.get('section');
  const country = searchParams.get('country');
  const minPrice = searchParams.get('min_price');
  const maxPrice = searchParams.get('max_price');
  const rank = searchParams.get('rank');
  const region = searchParams.get('region');
  const sort = searchParams.get('sort');
  const search = searchParams.get('search');
  const page = parseInt(searchParams.get('page') ?? '1');
  const perPage = parseInt(searchParams.get('per_page') ?? '24');

  let results: Product[] = game ? all.filter((p) => p.game === game) : all;
  if (accountType) results = results.filter((p) => p.account_type === accountType);
  if (section) results = results.filter((p) => p.section?.slug === section);
  if (country) results = results.filter((p) => p.country.code === country.toUpperCase());
  if (minPrice) results = results.filter((p) => p.price >= parseFloat(minPrice));
  if (maxPrice) results = results.filter((p) => p.price <= parseFloat(maxPrice));
  if (rank) results = results.filter((p) => p.rank?.toLowerCase() === rank.toLowerCase());
  if (region) results = results.filter((p) => p.region === region);
  if (search) {
    const q = search.toLowerCase();
    results = results.filter(
      (p) => p.title.toLowerCase().includes(q) || p.description.toLowerCase().includes(q)
    );
  }

  results = [...results];
  if (sort === 'price_asc')  results.sort((a, b) => a.price - b.price);
  if (sort === 'price_desc') results.sort((a, b) => b.price - a.price);
  if (sort === 'newest')     results.sort((a, b) => b.created_at.localeCompare(a.created_at));
  if (sort === 'oldest')     results.sort((a, b) => a.created_at.localeCompare(b.created_at));

  const total = results.length;
  const lastPage = Math.max(1, Math.ceil(total / perPage));
  const offset = (page - 1) * perPage;
  const data = results.slice(offset, offset + perPage);

  return NextResponse.json({
    data,
    meta: { current_page: page, last_page: lastPage, per_page: perPage, total },
    links: {
      first: null,
      last: null,
      next: page < lastPage ? `?page=${page + 1}` : null,
      prev: page > 1 ? `?page=${page - 1}` : null,
    },
  });
}
