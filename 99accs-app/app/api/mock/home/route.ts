import { NextResponse } from 'next/server';
import banner from '@/mocks/home/banner.json';
import about from '@/mocks/home/about.json';
import work from '@/mocks/home/work.json';
import features from '@/mocks/home/features.json';
import testimonials from '@/mocks/home/testimonials.json';
import cta from '@/mocks/home/cta.json';

export async function GET() {
  return NextResponse.json({
    data: {
      banner,
      about,
      work,
      features,
      testimonials,
      cta,
    },
  });
}
